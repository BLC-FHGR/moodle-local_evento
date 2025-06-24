<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Unit tests for the Evento API client.
 *
 * @package    local_evento
 * @category   test
 * @copyright  2025 FHGR Julien Rädler
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_evento\tests\unit\api;

defined('MOODLE_INTERNAL') || die();

use local_evento\api\client;
use local_evento\api\api_exception;
use local_evento\cache\cache_manager;
use local_evento\log\logger;
use local_evento\tests\mock\mock_soap_client;

/**
 * Test case for the API client functionality.
 */
class client_test extends \advanced_testcase {
    /** @var client The client under test */
    private $client;
    
    /** @var mock_soap_client The mock SOAP client */
    private $mockSoapClient;
    
    /** @var cache_manager|\PHPUnit\Framework\MockObject\MockObject The mock cache manager */
    private $mockCacheManager;
    
    /** @var logger|\PHPUnit\Framework\MockObject\MockObject The mock logger */
    private $mockLogger;
    
    /**
     * Set up before each test.
     */
    protected function setUp(): void {
        parent::setUp();
        
        global $CFG;
        
        // Create mock dependencies
        $this->mockCacheManager = $this->createMock(cache_manager::class);
        $this->mockLogger = $this->createMock(logger::class);
        
        // Get a temp file path for a mock WSDL
        $tempWsdl = $CFG->tempdir . '/mock_wsdl.xml';
        file_put_contents($tempWsdl, '<?xml version="1.0" encoding="UTF-8"?><definitions targetNamespace="http://example.org/evento"></definitions>');
        
        // Create the client with mocked dependencies
        $this->client = new client(
            $tempWsdl,
            [
                'location' => 'https://example.org/soap',
                'uri' => 'http://example.org/evento',
                'login' => 'testuser',
                'password' => 'testpass'
            ],
            $this->mockCacheManager,
            $this->mockLogger
        );
        
        // Create mock SOAP client
        $this->mockSoapClient = new mock_soap_client();
        
        // Inject mock SOAP client
        $this->client->set_soap_client($this->mockSoapClient);
        
        // Clean up temp file
        unlink($tempWsdl);
    }
    
    /**
     * Test executing a method with successful response.
     */
    public function test_execute_success(): void {
        // Set up the mock SOAP client to return a successful response
        $expectedResponse = (object)['return' => ['item1', 'item2']];
        $this->mockSoapClient->setCallHandler(function($method, $params) use ($expectedResponse) {
            if ($method === 'testMethod' && $params[0]['param1'] === 'value1') {
                return $expectedResponse;
            }
            return null;
        });
        
        // Set up expected logger calls
        $this->mockLogger->expects($this->exactly(2))
            ->method('debug')
            ->withConsecutive(
                [$this->stringContains('Executing SOAP call')],
                [$this->stringContains('SOAP call successful')]
            );
        
        // Execute the method
        $result = $this->client->execute('testMethod', [['param1' => 'value1']]);
        
        // Verify the result
        $this->assertEquals($expectedResponse, $result);
    }
    
    /**
     * Test executing a method with a retriable failure then success.
     */
    public function test_execute_retry_then_success(): void {
        // Set up the mock SOAP client to fail on first call, then succeed
        $attempt = 0;
        $expectedResponse = (object)['return' => ['item1', 'item2']];
        
        $this->mockSoapClient->setCallHandler(function($method, $params) use (&$attempt, $expectedResponse) {
            $attempt++;
            if ($attempt === 1) {
                // First attempt fails with a retriable error
                $fault = new \SoapFault('HTTP', 'Connection timed out');
                throw $fault;
            } else {
                // Second attempt succeeds
                return $expectedResponse;
            }
        });
        
        // Set up expected logger calls
        $this->mockLogger->expects($this->exactly(2))
            ->method('debug');
            
        $this->mockLogger->expects($this->once())
            ->method('warning')
            ->with($this->stringContains('SOAP call failed, retrying'));
        
        // Reduce delay to speed up test
        $reflection = new \ReflectionClass($this->client);
        $property = $reflection->getProperty('retrypolicy');
        $property->setAccessible(true);
        $property->setValue($this->client, [
            'max_attempts' => 3,
            'delay' => 1,
            'multiplier' => 1
        ]);
        
        // Execute the method
        $result = $this->client->execute('testMethod', [['param1' => 'value1']]);
        
        // Verify the result
        $this->assertEquals($expectedResponse, $result);
        $this->assertEquals(2, $attempt);
    }
    
    /**
     * Test executing a method with a non-retriable failure.
     */
    public function test_execute_non_retriable_failure(): void {
        // Set up the mock SOAP client to fail with a non-retriable error
        $this->mockSoapClient->setCallHandler(function($method, $params) {
            // Authentication error is not retriable
            $fault = new \SoapFault('Client', 'Authentication failed');
            throw $fault;
        });
        
        // Set up expected logger calls
        $this->mockLogger->expects($this->once())
            ->method('debug');
            
        $this->mockLogger->expects($this->once())
            ->method('error')
            ->with($this->stringContains('SOAP fault:'));
        
        // Execute the method and expect an exception
        $this->expectException(api_exception::class);
        $this->client->execute('testMethod', [['param1' => 'value1']]);
    }
    
    /**
     * Test executing a method with circuit breaker open.
     */
    public function test_execute_circuit_breaker_open(): void {
        // Force the circuit breaker to open state
        $reflection = new \ReflectionClass($this->client);
        $property = $reflection->getProperty('circuitbreaker');
        $property->setAccessible(true);
        $circuitBreaker = $property->getValue($this->client);
        $circuitBreaker->setState(\local_evento\api\circuit_breaker::STATE_OPEN);
        
        // Set up expected logger call
        $this->mockLogger->expects($this->once())
            ->method('error')
            ->with($this->stringContains('SOAP execution failed:'));
        
        // Execute the method and expect an exception
        $this->expectException(api_exception::class);
        $this->client->execute('testMethod', [['param1' => 'value1']]);
    }
    
    /**
     * Test getting the last request.
     */
    public function test_get_last_request(): void {
        $expectedRequest = '<soap:Envelope><soap:Body>Request</soap:Body></soap:Envelope>';
        $this->mockSoapClient->setLastRequest($expectedRequest);
        
        $result = $this->client->get_last_request();
        $this->assertEquals($expectedRequest, $result);
    }
    
    /**
     * Test getting the last response.
     */
    public function test_get_last_response(): void {
        $expectedResponse = '<soap:Envelope><soap:Body>Response</soap:Body></soap:Envelope>';
        $this->mockSoapClient->setLastResponse($expectedResponse);
        
        $result = $this->client->get_last_response();
        $this->assertEquals($expectedResponse, $result);
    }
    
    /**
     * Test getting the SOAP client options.
     */
    public function test_get_options(): void {
        $options = $this->client->get_options();
        
        $this->assertIsArray($options);
        $this->assertEquals('https://example.org/soap', $options['location']);
        $this->assertEquals('http://example.org/evento', $options['uri']);
        $this->assertEquals('testuser', $options['login']);
        $this->assertEquals('testpass', $options['password']);
    }
}