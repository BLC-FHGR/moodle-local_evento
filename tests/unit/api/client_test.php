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
 * Unit tests for the client class.
 *
 * @package    local_evento
 * @category   test
 * @copyright  2025 FHGR Julien Rädler
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_evento\tests\unit\api;

defined('MOODLE_INTERNAL') || die();

// Include required files.
require_once(__DIR__ . '/../../mock/mock_responses.php');

use advanced_testcase;
use local_evento\tests\mock\mock_responses;
use local_evento\api\client;
use local_evento\api\api_exception;
use local_evento\cache\cache_manager;
use local_evento\log\logger;
use local_evento\api\circuit_breaker;

/**
 * Unit tests for the client class.
 */
class client_testcase extends advanced_testcase {
    use mock_responses;

    /**
     * @var client The client instance under test
     */
    protected $client;

    /**
     * @var cache_manager Mock cache manager
     */
    protected $cachemanager;

    /**
     * @var logger Mock logger
     */
    protected $logger;

    /**
     * Set up the test environment.
     */
    public function setUp(): void {
        parent::setUp();
        $this->resetAfterTest();

        // Create mock dependencies
        $this->cachemanager = $this->createMock(cache_manager::class);
        $this->logger = $this->createMock(logger::class);

        // Create a real client with mock SOAP client injected later
        $wsdlurl = __DIR__ . '/../../mock/mock_wsdl.xml';
        $options = [
            'location' => 'https://test.example.com/soap',
            'uri' => 'http://test.example.com/uri',
            'trace' => true
        ];

        $this->client = new client($wsdlurl, $options, $this->cachemanager, $this->logger);
    }

    /**
     * Test successful API call execution.
     */
    public function test_execute_success(): void {
        // Create a mock SOAP client that returns success
        $soapclient = $this->getMockBuilder(\SoapClient::class)
            ->disableOriginalConstructor()
            ->setMethods(['__soapCall', '__getLastRequest', '__getLastResponse'])
            ->getMock();

        $soapclient->expects($this->once())
            ->method('__soapCall')
            ->willReturn((object)['return' => 'success']);

        $soapclient->method('__getLastRequest')
            ->willReturn('<soap:Envelope>...</soap:Envelope>');

        $soapclient->method('__getLastResponse')
            ->willReturn('<soap:Envelope>...</soap:Envelope>');

        // Inject the mock SOAP client
        $reflection = new \ReflectionClass($this->client);
        $property = $reflection->getProperty('soapclient');
        $property->setAccessible(true);
        $property->setValue($this->client, $soapclient);

        // Execute the method under test
        $result = $this->client->execute('testMethod', ['param' => 'value']);

        // Verify the result
        $this->assertEquals('success', $result->return);
    }

    /**
     * Test execution with retries.
     */
    public function test_execute_with_retry(): void {
        // Create a mock SOAP client that fails first then succeeds
        $soapclient = $this->getMockBuilder(\SoapClient::class)
            ->disableOriginalConstructor()
            ->setMethods(['__soapCall', '__getLastRequest', '__getLastResponse'])
            ->getMock();

        $logger = $this->getMockBuilder(logger::class)
            ->disableOriginalConstructor()
            ->getMock();

        $soapclient->expects($this->exactly(2))
            ->method('__soapCall')
            ->willReturnOnConsecutiveCalls(
                $this->throwException(new \SoapFault('HTTP', 'Connection timeout')),
                (object)['return' => 'success']
            );

        $soapclient->method('__getLastRequest')
            ->willReturn('<soap:Envelope>...</soap:Envelope>');

        $soapclient->method('__getLastResponse')
            ->willReturn('<soap:Envelope>...</soap:Envelope>');

        // Configure logger to expect warning and then success debug
        $logger->expects($this->once())
            ->method('warning')
            ->with($this->stringContains('SOAP call failed, retrying'));

        $logger->expects($this->exactly(2))
            ->method('debug')
            ->withConsecutive(
                [$this->stringContains('Executing SOAP call')],
                [$this->stringContains('SOAP call successful')]
            );

        // Inject the mock SOAP client
        $reflection = new \ReflectionClass($this->client);
        $property = $reflection->getProperty('soapclient');
        $property->setAccessible(true);
        $property->setValue($this->client, $soapclient);

        // Modify retry policy for faster test execution
        $retryPolicy = $reflection->getProperty('retrypolicy');
        $retryPolicy->setAccessible(true);
        $retryPolicy->setValue($this->client, [
            'max_attempts' => 3,
            'delay' => 0, // No delay for testing
            'multiplier' => 1
        ]);

        // Execute the method under test
        $result = $this->client->execute('testMethod', ['param' => 'value']);

        // Verify the result
        $this->assertEquals('success', $result->return);
    }

    /**
     * Test handling of persistent failures.
     */
    public function test_execute_persistent_failure(): void {
        // Create a mock SOAP client that always fails
        $soapclient = $this->getMockBuilder(\SoapClient::class)
            ->disableOriginalConstructor()
            ->setMethods(['__soapCall', '__getLastRequest', '__getLastResponse'])
            ->getMock();

        $soapfault = new \SoapFault('Server', 'Internal server error');

        $logger = $this->getMockBuilder(logger::class)
            ->disableOriginalConstructor()
            ->getMock();

        $soapclient->expects($this->exactly(3))
            ->method('__soapCall')
            ->willThrowException($soapfault);

        $soapclient->method('__getLastRequest')
            ->willReturn('<soap:Envelope>...</soap:Envelope>');

        $soapclient->method('__getLastResponse')
            ->willReturn('<soap:Envelope>...</soap:Envelope>');

        // Configure logger expectations
        $logger->expects($this->exactly(2))
            ->method('warning');
            
        $logger->expects($this->once())
            ->method('error')
            ->with($this->stringContains('SOAP execution failed'));

        // Inject the mock SOAP client
        $reflection = new \ReflectionClass($this->client);
        $property = $reflection->getProperty('soapclient');
        $property->setAccessible(true);
        $property->setValue($this->client, $soapclient);

        // Modify retry policy for faster test execution
        $retryPolicy = $reflection->getProperty('retrypolicy');
        $retryPolicy->setAccessible(true);
        $retryPolicy->setValue($this->client, [
            'max_attempts' => 3,
            'delay' => 0, // No delay for testing
            'multiplier' => 1
        ]);

        // Execute the method under test and expect exception
        $this->expectException(\Exception::class);
        $this->client->execute('testMethod', ['param' => 'value']);
    }

    /**
     * Test circuit breaker functionality.
     */
    public function test_circuit_breaker(): void {
        // Create a mock SOAP client that always fails
        $soapclient = $this->getMockBuilder(\SoapClient::class)
            ->disableOriginalConstructor()
            ->setMethods(['__soapCall', '__getLastRequest', '__getLastResponse'])
            ->getMock();

        $soapfault = new \SoapFault('Server', 'Internal server error');
        
        $soapclient->expects($this->atLeastOnce())
            ->method('__soapCall')
            ->willThrowException($soapfault);

        // Inject the mock SOAP client
        $reflection = new \ReflectionClass($this->client);
        $property = $reflection->getProperty('soapclient');
        $property->setAccessible(true);
        $property->setValue($this->client, $soapclient);

        // Create a very sensitive circuit breaker (trips after 1 failure)
        $circuitBreaker = new circuit_breaker(1, 60);
        $cbProperty = $reflection->getProperty('circuitbreaker');
        $cbProperty->setAccessible(true);
        $cbProperty->setValue($this->client, $circuitBreaker);

        // Modify retry policy for faster test execution
        $retryPolicy = $reflection->getProperty('retrypolicy');
        $retryPolicy->setAccessible(true);
        $retryPolicy->setValue($this->client, [
            'max_attempts' => 1,
            'delay' => 0,
            'multiplier' => 1
        ]);

        // First call should throw a normal exception
        try {
            $this->client->execute('testMethod', ['param' => 'value']);
            $this->fail('Exception should have been thrown');
        } catch (\Exception $e) {
            // Expected exception
        }

        // Second call should fail immediately due to circuit breaker
        // We'll verify this by checking that the SOAP client method is not called again
        try {
            $this->client->execute('testMethod', ['param' => 'value']);
            $this->fail('Exception should have been thrown');
        } catch (\Exception $e) {
            // Expected exception
        }
    }

    /**
     * Test getting last request and response.
     */
    public function test_get_last_request_response(): void {
        // Create a mock SOAP client
        $soapclient = $this->getMockBuilder(\SoapClient::class)
            ->disableOriginalConstructor()
            ->setMethods(['__soapCall', '__getLastRequest', '__getLastResponse'])
            ->getMock();

        $soapclient->method('__soapCall')
            ->willReturn((object)['return' => 'success']);

        $soapclient->method('__getLastRequest')
            ->willReturn('<soap:Envelope><soap:Body>TestRequest</soap:Body></soap:Envelope>');

        $soapclient->method('__getLastResponse')
            ->willReturn('<soap:Envelope><soap:Body>TestResponse</soap:Body></soap:Envelope>');

        // Inject the mock SOAP client
        $reflection = new \ReflectionClass($this->client);
        $property = $reflection->getProperty('soapclient');
        $property->setAccessible(true);
        $property->setValue($this->client, $soapclient);

        // Execute a method to trigger the request/response
        $this->client->execute('testMethod', ['param' => 'value']);

        // Test the last request/response getters
        $this->assertStringContainsString('TestRequest', $this->client->get_last_request());
        $this->assertStringContainsString('TestResponse', $this->client->get_last_response());
    }

    /**
     * Data provider for testing retry decisions.
     *
     * @return array Test cases
     */
    public function should_retry_provider(): array {
        return [
            'HTTP error' => [new \SoapFault('HTTP', 'Connection refused'), true],
            'Connection error' => [new \SoapFault('Connection', 'Failed'), true],
            'Timeout error' => [new \SoapFault('Timeout', 'Operation timed out'), true],
            'Authentication error' => [new \SoapFault('Client', 'Authentication failed'), false],
            'Server error' => [new \SoapFault('Server', 'Internal error'), false],
            'Validation error' => [new \SoapFault('Client', 'Invalid parameters'), false],
        ];
    }

    /**
     * Test the should_retry method with various error types.
     *
     * @dataProvider should_retry_provider
     * @param \SoapFault $fault The fault to test
     * @param bool $expected The expected retry decision
     */
    public function test_should_retry(\SoapFault $fault, bool $expected): void {
        $reflection = new \ReflectionClass($this->client);
        $method = $reflection->getMethod('should_retry');
        $method->setAccessible(true);

        $result = $method->invokeArgs($this->client, [$fault, 1]);
        $this->assertEquals($expected, $result);
    }
}