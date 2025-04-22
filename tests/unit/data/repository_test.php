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
 * Unit tests for the repository class.
 *
 * @package    local_evento
 * @category   test
 * @copyright  2025 FHGR Julien Rädler
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_evento\tests\unit\data;

defined('MOODLE_INTERNAL') || die();

// Include required files.
require_once(__DIR__ . '/../../mock/mock_responses.php');

use advanced_testcase;
use local_evento\tests\mock\mock_responses;
use local_evento\data\repository;
use local_evento\api\client_interface;
use local_evento\cache\cache_manager;
use local_evento\api\response_processor;
use local_evento\service;

/**
 * Unit tests for the repository class.
 */
class repository_test extends advanced_testcase {
    use mock_responses;

    /**
     * @var repository The repository instance under test
     */
    protected $repository;

    /**
     * @var client_interface&\PHPUnit\Framework\MockObject\MockObject
     */
    private $client;

    /**
     * @var cache_manager&\PHPUnit\Framework\MockObject\MockObject
     */
    private $cacheManager;
    
    /**
     * @var response_processor&\PHPUnit\Framework\MockObject\MockObject
     */
    private $responseProcessor;

    /**
     * Set up the test environment.
     */
    public function setUp(): void {
        parent::setUp();
        $this->resetAfterTest();

        // Create mock dependencies
        $this->client = $this->createMock(client_interface::class);
        $this->cacheManager = $this->createMock(cache_manager::class);
        $this->responseProcessor = $this->createMock(response_processor::class);
        
        // Configure the response processor to return the exact input by default
        $this->responseProcessor->method('process')
            ->will($this->returnArgument(0));
            
        // Configure cache manager
        $this->cacheManager->method('generateApiResponseKey')
            ->willReturn('test_key');
            
        $this->cacheManager->method('get')
            ->willReturn(false); // Cache miss by default
            
        // Create the repository
        $this->repository = new repository(
            $this->client, 
            $this->cacheManager,
            $this->responseProcessor
        );
    }

    /**
     * Test getting events with entity filter.
     */
    public function test_get_events_with_entity_filter(): void {
        // Set up expected parameters and responses
        $entityFilter = ['anlassBezeichnung' => 'Test Event'];
        $expectedParams = [['theEventoAnlassFilter' => $entityFilter]];
        
        $expectedResult = (object)[
            'return' => [(object)[
                'idAnlass' => 123,
                'anlassBezeichnung' => 'Test Event'
            ]]
        ];
        
        // Configure client behavior
        $this->client->expects($this->once())
            ->method('execute')
            ->with(
                $this->equalTo('listEventoAnlass'),
                $this->equalTo($expectedParams)
            )
            ->willReturn($expectedResult);
            
        // Execute the method under test
        $result = $this->repository->getEvents($entityFilter);
        
        // Verify the result
        $this->assertEquals($expectedResult, $result);
    }
    
    /**
     * Test getting events with entity filter and limit options.
     */
    public function test_get_events_with_limitation_filter(): void {
        // Set up expected parameters and responses
        $entityFilter = ['anlassBezeichnung' => 'Test Event'];
        $limitOptions = ['maxResults' => 10, 'lastChangeDate' => '2024-01-01T00:00:00.000+01:00'];
        
        $expectedParams = [[
            'theEventoAnlassFilter' => $entityFilter,
            'theLimitationFilter2' => [
                'theMaxResultsValue' => 10,
                'theFromDate' => '2024-01-01T00:00:00.000+01:00'
            ]
        ]];
        
        $expectedResult = (object)[
            'return' => [(object)[
                'idAnlass' => 123,
                'anlassBezeichnung' => 'Test Event'
            ]]
        ];
        
        // Configure client behavior
        $this->client->expects($this->once())
            ->method('execute')
            ->with(
                $this->equalTo('listEventoAnlass'),
                $this->equalTo($expectedParams)
            )
            ->willReturn($expectedResult);
            
        // Execute the method under test
        $result = $this->repository->getEvents($entityFilter, $limitOptions);
        
        // Verify the result
        $this->assertEquals($expectedResult, $result);
    }
    
    /**
     * Test getting events with DateTime object for date limitation.
     */
    public function test_get_events_with_datetime_object(): void {
        // Set up expected parameters and responses
        $entityFilter = ['anlassBezeichnung' => 'Test Event'];
        $date = new \DateTime('2024-01-01');
        $limitOptions = ['lastChangeDate' => $date];
        
        // Format the date as expected in the formatted request
        $formattedDate = $date->format(service::DATETIME_FORMAT);
        
        $expectedParams = [[
            'theEventoAnlassFilter' => $entityFilter,
            'theLimitationFilter2' => [
                'theFromDate' => $formattedDate
            ]
        ]];
        
        $expectedResult = (object)[
            'return' => [(object)[
                'idAnlass' => 123,
                'anlassBezeichnung' => 'Test Event'
            ]]
        ];
        
        // Configure client behavior
        $this->client->expects($this->once())
            ->method('execute')
            ->with(
                $this->equalTo('listEventoAnlass'),
                $this->callback(function($params) use ($formattedDate) {
                    // Custom callback to verify the date was formatted correctly
                    return isset($params[0]['theLimitationFilter2']['theFromDate']) && 
                           $params[0]['theLimitationFilter2']['theFromDate'] === $formattedDate;
                })
            )
            ->willReturn($expectedResult);
            
        // Execute the method under test
        $result = $this->repository->getEvents($entityFilter, $limitOptions);
        
        // Verify the result
        $this->assertEquals($expectedResult, $result);
    }
    
    /**
     * Test getting enrollments for a specific event.
     */
    public function test_get_enrollments(): void {
        // Set up expected parameters and responses
        $eventId = 123;
        
        $expectedParams = [[
            'theEventoPersonenAnmeldungFilter' => ['idAnlass' => $eventId]
        ]];
        
        $expectedResult = (object)[
            'return' => [(object)[
                'idAnlass' => 123,
                'idPerson' => 456,
                'iDPAStatus' => 1
            ]]
        ];
        
        // Configure client behavior
        $this->client->expects($this->once())
            ->method('execute')
            ->with(
                $this->equalTo('listEventoPersonenAnmeldung'),
                $this->equalTo($expectedParams)
            )
            ->willReturn($expectedResult);
            
        // Execute the method under test
        $result = $this->repository->getEnrollments($eventId);
        
        // Verify the result
        $this->assertEquals($expectedResult, $result);
    }
    
    /**
     * Test getting enrollments with limitation filter.
     */
    public function test_get_enrollments_with_limitation(): void {
        // Set up expected parameters and responses
        $eventId = 123;
        $limitOptions = ['maxResults' => 5];
        
        $expectedParams = [[
            'theEventoPersonenAnmeldungFilter' => ['idAnlass' => $eventId],
            'theLimitationFilter2' => [
                'theMaxResultsValue' => 5
            ]
        ]];
        
        $expectedResult = (object)[
            'return' => [(object)[
                'idAnlass' => 123,
                'idPerson' => 456,
                'iDPAStatus' => 1
            ]]
        ];
        
        // Configure client behavior
        $this->client->expects($this->once())
            ->method('execute')
            ->with(
                $this->equalTo('listEventoPersonenAnmeldung'),
                $this->equalTo($expectedParams)
            )
            ->willReturn($expectedResult);
            
        // Execute the method under test
        $result = $this->repository->getEnrollments($eventId, $limitOptions);
        
        // Verify the result
        $this->assertEquals($expectedResult, $result);
    }
    
    /**
     * Test getting users with entity filter.
     */
    public function test_get_users(): void {
        // Set up expected parameters and responses
        $entityFilter = ['personNachname' => 'Doe'];
        
        $expectedParams = [[
            'theEventoPersonFilter' => $entityFilter
        ]];
        
        $expectedResult = (object)[
            'return' => [(object)[
                'idPerson' => 456,
                'personNachname' => 'Doe',
                'personVorname' => 'John'
            ]]
        ];
        
        // Configure client behavior
        $this->client->expects($this->once())
            ->method('execute')
            ->with(
                $this->equalTo('listEventoPerson'),
                $this->equalTo($expectedParams)
            )
            ->willReturn($expectedResult);
            
        // Execute the method under test
        $result = $this->repository->getUsers($entityFilter);
        
        // Verify the result
        $this->assertEquals($expectedResult, $result);
    }
    
    /**
     * Test getting organizational units.
     */
    public function test_get_organizational_units(): void {
        // Set up expected parameters and responses
        $expectedParams = [[
            'theEventoOEFilter' => []
        ]];
        
        $expectedResult = (object)[
            'return' => [(object)[
                'IDBenutzer' => 'DEPT1',
                'OE' => 'Department 1'
            ]]
        ];
        
        // Configure client behavior
        $this->client->expects($this->once())
            ->method('execute')
            ->with(
                $this->equalTo('listEventoOE'),
                $this->equalTo($expectedParams)
            )
            ->willReturn($expectedResult);
            
        // Execute the method under test
        $result = $this->repository->getOrganizationalUnits();
        
        // Verify the result
        $this->assertEquals($expectedResult, $result);
    }
    
    /**
     * Test getting organizational units with filter.
     */
    public function test_get_organizational_units_with_filter(): void {
        // Set up expected parameters and responses
        $entityFilter = ['isActiv' => true];
        
        $expectedParams = [[
            'theEventoOEFilter' => $entityFilter
        ]];
        
        $expectedResult = (object)[
            'return' => [(object)[
                'IDBenutzer' => 'DEPT1',
                'OE' => 'Department 1',
                'isActiv' => true
            ]]
        ];
        
        // Configure client behavior
        $this->client->expects($this->once())
            ->method('execute')
            ->with(
                $this->equalTo('listEventoOE'),
                $this->equalTo($expectedParams)
            )
            ->willReturn($expectedResult);
            
        // Execute the method under test
        $result = $this->repository->getOrganizationalUnits($entityFilter);
        
        // Verify the result
        $this->assertEquals($expectedResult, $result);
    }
    
    /**
     * Test error handling with invalid date.
     */
    public function test_invalid_date_format(): void {
        // Set up parameters with invalid date
        $entityFilter = ['anlassBezeichnung' => 'Test Event'];
        $limitOptions = ['lastChangeDate' => 'not-a-date'];
        
        // Execute the method under test and expect exception
        $this->expectException(\InvalidArgumentException::class);
        $this->repository->getEvents($entityFilter, $limitOptions);
    }
    
    /**
     * Test error handling with invalid maxResults.
     */
    public function test_invalid_max_results(): void {
        // Set up parameters with invalid maxResults
        $entityFilter = ['anlassBezeichnung' => 'Test Event'];
        $limitOptions = ['maxResults' => -5]; // Negative number
        
        // Execute the method under test and expect exception
        $this->expectException(\InvalidArgumentException::class);
        $this->repository->getEvents($entityFilter, $limitOptions);
    }
    
    /**
     * Test result caching behavior.
     */
    public function test_cache_hit(): void {
        // Set up a cached result
        $cachedResult = (object)[
            'return' => [(object)[
                'idAnlass' => 123,
                'anlassBezeichnung' => 'Cached Event'
            ]]
        ];
        
        // Configure cache manager to return a hit
        $this->cacheManager = $this->createMock(cache_manager::class);
        $this->cacheManager->method('generateApiResponseKey')
            ->willReturn('cache_key');
            
        $this->cacheManager->method('get')
            ->willReturn($cachedResult);
            
        // Recreate repository with updated cache manager
        $this->repository = new repository(
            $this->client, 
            $this->cacheManager,
            $this->responseProcessor
        );
        
        // The client should not be called
        $this->client->expects($this->never())
            ->method('execute');
            
        // Execute the method under test
        $result = $this->repository->getEvents(['anlassBezeichnung' => 'Test Event']);
        
        // Verify the result is from cache
        $this->assertEquals($cachedResult, $result);
    }
    
    /**
     * Test connection test functionality.
     */
    public function test_test_connection_success(): void {
        // Configure client to return successful response
        $successResponse = (object)['return' => [(object)['idAnlassTyp' => 1]]];
        
        $this->client->expects($this->once())
            ->method('execute')
            ->with(
                $this->equalTo('listEventoAnlassTyp'),
                $this->anything()
            )
            ->willReturn($successResponse);
            
        // Execute the method under test
        $result = $this->repository->testConnection();
        
        // Verify the result
        $this->assertTrue($result);
    }
    
    /**
     * Test connection test failure.
     */
    public function test_test_connection_failure(): void {
        // Configure client to throw exception
        $this->client->expects($this->once())
            ->method('execute')
            ->willThrowException(new \Exception('Connection failed'));
            
        // Execute the method under test
        $result = $this->repository->testConnection();
        
        // Verify the result
        $this->assertFalse($result);
    }
}