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
 * Unit tests for the service class.
 *
 * @package    local_evento
 * @category   test
 * @copyright  2025 FHGR Julien Rädler
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_evento\tests\unit;

defined('MOODLE_INTERNAL') || die();

// Include required files.
require_once(__DIR__ . '/../mock/mock_responses.php');

use advanced_testcase;
use local_evento\tests\mock\mock_responses;
use local_evento\api\client;
use local_evento\api\response_processor;
use local_evento\cache\cache_manager;
use local_evento\service;
use local_evento\data\repository;
use local_evento\log\logger;

/**
 * Unit tests for the service class.
 */
class service_testcase extends advanced_testcase {
    use mock_responses;

    /**
     * @var service The service instance under test
     */
    protected $service;

    /**
     * @var client&\PHPUnit\Framework\MockObject\MockObject
     */
    private $client;

    /**
     * @var repository&\PHPUnit\Framework\MockObject\MockObject
     */
    protected $repository;

    /**
     * @var \progress_trace Mock trace instance
     */
    protected $trace;

    /**
     * Set up the test environment.
     */
    public function setUp(): void {
        parent::setUp();
        $this->resetAfterTest();

        // Create mock dependencies
        $this->client = $this->createMock(client::class);
        $this->repository = $this->createMock(repository::class);
        $this->trace = $this->createMock(\progress_trace::class);

        // Make sure the singleton is reset
        service::resetInstance();

        // Override config settings for testing
        set_config('wslocation', 'https://test.example.com/soap', 'local_evento');
        set_config('wsuri', 'http://test.example.com/uri', 'local_evento');
        set_config('wstrace', '1', 'local_evento');
        set_config('wsusername', 'testuser', 'local_evento');
        set_config('wspassword', 'testpass', 'local_evento');
        set_config('wswsdlfilename', 'webservice_v1_1.wsdl', 'local_evento');
        set_config('adsidprefix', 'S-1-5-21-', 'local_evento');
        set_config('adshibbolethsuffix', '@fhgr.ch', 'local_evento');
    }

    /**
     * Test singleton instance creation and retrieval.
     */
    public function test_get_instance(): void {
        // First call should create the instance
        $service1 = service::getInstance();
        $this->assertInstanceOf(service::class, $service1);

        // Second call should return the same instance
        $service2 = service::getInstance();
        $this->assertSame($service1, $service2);

        // Call with trace should update the trace
        $trace = new \text_progress_trace();
        $service3 = service::getInstance($trace);
        $this->assertSame($service1, $service3);
        
        // Check that the trace got updated by testing logger trace
        $logger = $service3->getLogger();
        $reflection = new \ReflectionClass($logger);
        $property = $reflection->getProperty('trace');
        $property->setAccessible(true);
        $currentTrace = $property->getValue($logger);
        $this->assertSame($trace, $currentTrace);
    }

    /**
     * Test creation for testing with mock dependencies.
     */
    public function test_create_for_testing(): void {
        // Create service with mocks
        $service = service::createForTesting($this->client, $this->repository, $this->trace);
        
        // Verify the service has our mock objects
        $this->assertSame($this->client, $service->getClient());
        $this->assertSame($this->repository, $service->getRepository());
        
        // Check logger has our trace
        $logger = $service->getLogger();
        $reflection = new \ReflectionClass($logger);
        $property = $reflection->getProperty('trace');
        $property->setAccessible(true);
        $currentTrace = $property->getValue($logger);
        $this->assertSame($this->trace, $currentTrace);
    }

    /**
     * Test connection testing functionality.
     */
    public function test_test_connection(): void {
        // Setup mock repository
        $this->repository->expects($this->once())
            ->method('testConnection')
            ->willReturn(true);
            
        // Create service with mocks
        $service = service::createForTesting($this->client, $this->repository, $this->trace);
        
        // Test connection
        $result = $service->testConnection();
        $this->assertTrue($result);
    }

    /**
     * Test connection testing with error handling.
     */
    public function test_test_connection_error(): void {
        // Setup mock repository to throw an exception
        $this->repository->expects($this->once())
            ->method('testConnection')
            ->willThrowException(new \Exception('Connection failed'));
            
        // Create service with mocks
        $service = service::createForTesting($this->client, $this->repository, $this->trace);
        
        // Test connection should catch the exception and return false
        $result = $service->testConnection();
        $this->assertFalse($result);
    }

    /**
     * Test SID to Shibboleth ID conversion.
     */
    public function test_sid_to_shibboleth_id(): void {
        // Create service with mocks
        $service = service::createForTesting($this->client, $this->repository, $this->trace);
        
        // Test conversion
        $sid = 'S-1-5-21-2460181390-1097805571-3701207438-87544';
        $expected = '2460181390-1097805571-3701207438-87544@fhgr.ch';
        
        $result = $service->sidToShibbolethId($sid);
        $this->assertEquals($expected, $result);
    }

    /**
     * Test Shibboleth ID to SID conversion.
     */
    public function test_shibboleth_id_to_sid(): void {
        // Create service with mocks
        $service = service::createForTesting($this->client, $this->repository, $this->trace);
        
        // Test conversion
        $shibbolethId = '2460181390-1097805571-3701207438-87544@fhgr.ch';
        $expected = 'S-1-5-21-2460181390-1097805571-3701207438-87544';
        
        $result = $service->shibbolethIdToSid($shibbolethId);
        $this->assertEquals($expected, $result);
    }

    /**
     * Test to array conversion utility.
     */
    public function test_to_array(): void {
        // Create service with mocks
        $service = service::createForTesting($this->client, $this->repository, $this->trace);
        
        // Test with array input
        $array = ['test1', 'test2'];
        $this->assertEquals($array, $service->toArray($array));
        
        // Test with single value
        $single = 'test';
        $this->assertEquals([$single], $service->toArray($single));
        
        // Test with null
        $this->assertEquals([], $service->toArray(null));
        
        // Test with object
        $obj = new \stdClass();
        $obj->test = 'value';
        $this->assertEquals([$obj], $service->toArray($obj));
    }

    /**
     * Test getter methods.
     */
    public function test_getters(): void {
        // Create service with mocks
        $service = service::createForTesting($this->client, $this->repository, $this->trace);
        
        // Test getters
        $this->assertSame($this->client, $service->getClient());
        $this->assertSame($this->repository, $service->getRepository());
        $this->assertInstanceOf(logger::class, $service->getLogger());
        $this->assertInstanceOf(cache_manager::class, $service->getCacheManager());
        $this->assertInstanceOf(response_processor::class, $service->getResponseProcessor());
    }

    /**
     * Test set trace method.
     */
    public function test_set_trace(): void {
        // Create service with mocks
        $service = service::createForTesting($this->client, $this->repository, $this->trace);
        
        // Create a new trace
        $newTrace = new \text_progress_trace();
        
        // Set the new trace
        $service->setTrace($newTrace);
        
        // Check that the logger trace was updated
        $logger = $service->getLogger();
        $reflection = new \ReflectionClass($logger);
        $property = $reflection->getProperty('trace');
        $property->setAccessible(true);
        $currentTrace = $property->getValue($logger);
        $this->assertSame($newTrace, $currentTrace);
    }
}