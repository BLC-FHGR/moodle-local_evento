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
 * Integration tests for the Evento API repository.
 *
 * @package    local_evento
 * @category   test
 * @copyright  2025 FHGR Julien Rädler
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_evento\tests\integration;

defined('MOODLE_INTERNAL') || die();

use local_evento\api\client;
use local_evento\api\response_processor;
use local_evento\cache\cache_manager;
use local_evento\data\repository;
use local_evento\log\logger;
use local_evento\tests\mock\mock_factory;
use local_evento\tests\mock\mock_soap_client;
use PHPUnit\Framework\TestCase;

/**
 * Test case for integration between repository and API client.
 */
class repository_test extends TestCase {
    /** @var repository The repository under test */
    private $repository;
    
    /** @var client The client with mock soap client */
    private $client;
    
    /** @var mock_soap_client The mock SOAP client */
    private $mockSoapClient;
    
    /** @var \PHPUnit\Framework\MockObject\MockObject The mock cache manager */
    private $mockCacheManager;
    
    /** @var response_processor The response processor */
    private $responseProcessor;
    
    /**
     * Set up before each test.
     */
    protected function setUp(): void {
        global $CFG;
        
        // Create mock logger
        $mockLogger = $this->createMock(logger::class);
        
        // Create real response processor (no need to mock)
        $this->responseProcessor = new response_processor();
        
        // Create mock cache manager
        $this->mockCacheManager = $this->getMockBuilder(cache_manager::class)
            ->disableOriginalConstructor()
            ->getMock();
        
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
            $mockLogger
        );
        
        // Create mock SOAP client
        $this->mockSoapClient = new mock_soap_client();
        
        // Inject mock SOAP client
        $this->client->set_soap_client($this->mockSoapClient);
        
        // Create the repository
        $this->repository = new repository(
            $this->client,
            $this->mockCacheManager,
            $this->responseProcessor
        );
        
        // Clean up temp file
        unlink($tempWsdl);
    }
    
    /**
     * Test getting events with cache miss.
     */
    public function test_get_events_cache_miss() {
        // Create mock events
        $mockEvents = mock_factory::create_evento_anlass_collection();
        
        // Configure cache manager to return cache miss
        $this->mockCacheManager->expects($this->once())
            ->method('generateApiResponseKey')
            ->with(
                $this->equalTo('listEventoAnlass'),
                $this->callback(function($params) {
                    return isset($params[0]['theEventoAnlassFilter']) &&
                           $params[0]['theEventoAnlassFilter']['anlassVeranstalter'] === 'ba_arc';
                })
            )
            ->willReturn('api_listEventoAnlass_abc123');
        
        $this->mockCacheManager->expects($this->once())
            ->method('get')
            ->with(
                $this->equalTo(cache_manager::REGION_API_RESPONSES),
                $this->equalTo('api_listEventoAnlass_abc123')
            )
            ->willReturn(false); // Cache miss
        
        // Configure cache manager to set the result in cache
        $this->mockCacheManager->expects($this->once())
            ->method('set')
            ->with(
                $this->equalTo(cache_manager::REGION_API_RESPONSES),
                $this->equalTo('api_listEventoAnlass_abc123'),
                $this->equalTo($mockEvents)
            )
            ->willReturn(true);
        
        // Configure SOAP client to return the mock events
        $this->mockSoapClient->setCallHandler(function($method, $params) use ($mockEvents) {
            if ($method === 'listEventoAnlass') {
                return $mockEvents;
            }
            return null;
        });
        
        // Call the method to get events
        $result = $this->repository->getEvents(['anlassVeranstalter' => 'ba_arc']);
        
        // Verify the result
        $this->assertIsArray($result);
        $this->assertCount(3, $result);
        $this->assertEquals(45685, $result[0]->idAnlass);
        $this->assertEquals('Fachvorträge', $result[0]->anlassBezeichnung);
        $this->assertEquals(45686, $result[1]->idAnlass);
        $this->assertEquals('Entwurf I', $result[1]->anlassBezeichnung);
    }
    
    /**
     * Test getting events with cache hit.
     */
    public function test_get_events_cache_hit() {
        // Create mock events
        $mockEvents = mock_factory::create_evento_anlass_collection();
        
        // Configure cache manager to return cache hit
        $this->mockCacheManager->expects($this->once())
            ->method('generateApiResponseKey')
            ->willReturn('api_listEventoAnlass_abc123');
        
        $this->mockCacheManager->expects($this->once())
            ->method('get')
            ->willReturn($mockEvents); // Cache hit
        
        // Cache set should not be called
        $this->mockCacheManager->expects($this->never())
            ->method('set');
        
        // SOAP client should not be called
        $this->mockSoapClient->setCallHandler(function($method, $params) {
            $this->fail('SOAP client should not be called when cache hit');
            return null;
        });
        
        // Call the method to get events
        $result = $this->repository->getEvents(['anlassVeranstalter' => 'ba_arc']);
        
        // Verify the result
        $this->assertIsArray($result);
        $this->assertCount(3, $result);
        $this->assertEquals(45685, $result[0]->idAnlass);
        $this->assertEquals('Fachvorträge', $result[0]->anlassBezeichnung);
    }
    
    /**
     * Test getting enrollments for an event.
     */
    public function test_get_enrollments() {
        // Create mock enrollments
        $mockEnrollments = mock_factory::create_evento_personen_anmeldung_collection();
        
        // Configure cache manager
        $this->mockCacheManager->expects($this->once())
            ->method('generateApiResponseKey')
            ->willReturn('api_listEventoPersonenAnmeldung_abc123');
        
        $this->mockCacheManager->expects($this->once())
            ->method('get')
            ->willReturn(false); // Cache miss
        
        $this->mockCacheManager->expects($this->once())
            ->method('set')
            ->willReturn(true);
        
        // Configure SOAP client to return the mock enrollments
        $this->mockSoapClient->setCallHandler(function($method, $params) use ($mockEnrollments) {
            if ($method === 'listEventoPersonenAnmeldung') {
                return $mockEnrollments;
            }
            return null;
        });
        
        // Call the method to get enrollments
        $result = $this->repository->getEnrollments(45685);
        
        // Verify the result
        $this->assertIsArray($result);
        $this->assertCount(3, $result);
        $this->assertEquals(633320, $result[0]->iDAnmeldung);
        $this->assertEquals(161261, $result[0]->idPerson);
    }
    
    /**
     * Test getting users based on criteria.
     */
    public function test_get_users() {
        // Create mock persons
        $mockPersons = mock_factory::create_evento_person_collection();
        
        // Configure cache manager
        $this->mockCacheManager->expects($this->once())
            ->method('generateApiResponseKey')
            ->willReturn('api_listEventoPerson_abc123');
        
        $this->mockCacheManager->expects($this->once())
            ->method('get')
            ->willReturn(false); // Cache miss
        
        $this->mockCacheManager->expects($this->once())
            ->method('set')
            ->willReturn(true);
        
        // Configure SOAP client to return the mock persons
        $this->mockSoapClient->setCallHandler(function($method, $params) use ($mockPersons) {
            if ($method === 'listEventoPerson') {
                return $mockPersons;
            }
            return null;
        });
        
        // Call the method to get users
        $result = $this->repository->getUsers([
            'personNachname' => 'Walser',
            'personVorname' => 'Daniel'
        ]);
        
        // Verify the result
        $this->assertIsArray($result);
        $this->assertCount(3, $result);
        $this->assertEquals(2360, $result[0]->idPerson);
        $this->assertEquals('Daniel', $result[0]->personVorname);
        $this->assertEquals('Walser', $result[0]->personNachname);
    }
    
    /**
     * Test getting organizational units.
     */
    public function test_get_organizational_units() {
        // Create mock OEs
        $mockOEs = mock_factory::create_evento_oe_collection();
        
        // Configure cache manager
        $this->mockCacheManager->expects($this->once())
            ->method('generateApiResponseKey')
            ->willReturn('api_listEventoOE_abc123');
        
        $this->mockCacheManager->expects($this->once())
            ->method('get')
            ->willReturn(false); // Cache miss
        
        $this->mockCacheManager->expects($this->once())
            ->method('set')
            ->willReturn(true);
        
        // Configure SOAP client to return the mock OEs
        $this->mockSoapClient->setCallHandler(function($method, $params) use ($mockOEs) {
            if ($method === 'listEventoOE') {
                return $mockOEs;
            }
            return null;
        });
        
        // Call the method to get organizational units
        $result = $this->repository->getOrganizationalUnits(['isActiv' => true]);
        
        // Verify the result
        $this->assertIsArray($result);
        $this->assertCount(3, $result);
        $this->assertEquals('ba_arc', $result[0]->IDBenutzer);
        $this->assertEquals('Bachelor Architektur', $result[0]->OE);
    }
    
    /**
     * Test that invalid date format throws exception.
     */
    public function test_invalid_date_format() {
        // Call the method with invalid date format
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid date format:');
        
        $this->repository->getEvents(
            ['anlassVeranstalter' => 'ba_arc'],
            ['lastChangeDate' => 'not-a-date']
        );
    }
    
    /**
     * Test that invalid maxResults throws exception.
     */
    public function test_invalid_max_results() {
        // Call the method with invalid maxResults
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('maxResults must be a positive integer');
        
        $this->repository->getEvents(
            ['anlassVeranstalter' => 'ba_arc'],
            ['maxResults' => -1]
        );
    }
    
    /**
     * Test testing the connection.
     */
    public function test_connection_success() {
        // Configure SOAP client to return success
        $this->mockSoapClient->setCallHandler(function($method, $params) {
            if ($method === 'listEventoAnlassTyp') {
                return (object)[
                    'return' => [
                        (object)['idAnlassTyp' => 1, 'anlassTypBez' => 'Test Type']
                    ]
                ];
            }
            return null;
        });
        
        // Test the connection
        $result = $this->repository->testConnection();
        
        // Connection should be successful
        $this->assertTrue($result);
    }
    
    /**
     * Test connection failure.
     */
    public function test_connection_failure() {
        // Configure SOAP client to throw an exception
        $this->mockSoapClient->setCallHandler(function($method, $params) {
            throw new \Exception('Connection failed');
        });
        
        // Test the connection
        $result = $this->repository->testConnection();
        
        // Connection should fail
        $this->assertFalse($result);
    }
    
    /**
     * Test date formatting.
     */
    public function test_date_formatting() {
        // Configure cache manager and SOAP client for a successful call
        $this->mockCacheManager->method('generateApiResponseKey')->willReturn('api_key');
        $this->mockCacheManager->method('get')->willReturn(false);
        $this->mockCacheManager->method('set')->willReturn(true);
        
        // Set up the mock SOAP client to capture the formatted date
        $capturedParams = null;
        $this->mockSoapClient->setCallHandler(function($method, $params) use (&$capturedParams) {
            $capturedParams = $params;
            return mock_factory::create_evento_anlass_collection();
        });
        
        // Use DateTime object for date
        $date = new \DateTime('2025-01-15');
        $this->repository->getEvents(
            ['anlassVeranstalter' => 'ba_arc'],
            ['lastChangeDate' => $date]
        );
        
        // Verify the date was formatted correctly
        $this->assertIsArray($capturedParams);
        $this->assertArrayHasKey(0, $capturedParams);
        $this->assertArrayHasKey('theLimitationFilter2', $capturedParams[0]);
        $this->assertArrayHasKey('theFromDate', $capturedParams[0]['theLimitationFilter2']);
        $this->assertMatchesRegularExpression(
            '/2025-01-15T\d{2}:\d{2}:\d{2}\.\d{6}\+\d{2}:\d{2}/',
            $capturedParams[0]['theLimitationFilter2']['theFromDate']
        );
    }
}