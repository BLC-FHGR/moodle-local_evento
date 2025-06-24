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
 * Unit tests for the Evento service.
 *
 * @package    local_evento
 * @category   test
 * @copyright  2025 FHGR Julien Rädler
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_evento\tests\unit;

defined('MOODLE_INTERNAL') || die();

use local_evento\api\client;
use local_evento\cache\cache_manager;
use local_evento\data\repository;
use local_evento\log\logger;
use local_evento\service;
use PHPUnit\Framework\TestCase;

/**
 * Test case for the service functionality.
 */
class service_test extends TestCase {
    /** @var \PHPUnit\Framework\MockObject\MockObject Mock client */
    private $mockClient;
    
    /** @var \PHPUnit\Framework\MockObject\MockObject Mock repository */
    private $mockRepository;
    
    /** @var \PHPUnit\Framework\MockObject\MockObject Mock trace */
    private $mockTrace;
    
    /** @var service The service instance */
    private $service;
    
    /**
     * Set up before each test.
     */
    protected function setUp(): void {
        // Reset the singleton instance
        service::resetInstance();
        
        // Create mock dependencies
        $this->mockClient = $this->createMock(client::class);
        $this->mockRepository = $this->createMock(repository::class);
        $this->mockTrace = $this->createMock(\progress_trace::class);
        
        // Create the service with mock dependencies
        $this->service = service::createForTesting(
            $this->mockClient,
            $this->mockRepository,
            $this->mockTrace
        );
    }
    
    /**
     * Test singleton getInstance method.
     */
    public function test_get_instance() {
        // Reset the singleton instance
        service::resetInstance();
        
        // Get the instance
        $instance1 = service::getInstance();
        
        // Verify it's a service instance
        $this->assertInstanceOf(service::class, $instance1);
        
        // Get the instance again, should be the same object
        $instance2 = service::getInstance();
        
        // Verify it's the same instance
        $this->assertSame($instance1, $instance2);
        
        // Get the instance with a trace
        $mockTrace = $this->createMock(\progress_trace::class);
        $instance3 = service::getInstance($mockTrace);
        
        // Verify it's still the same instance
        $this->assertSame($instance1, $instance3);
    }
    
    /**
     * Test getting the repository.
     */
    public function test_get_repository() {
        $repository = $this->service->getRepository();
        $this->assertSame($this->mockRepository, $repository);
    }
    
    /**
     * Test getting the client.
     */
    public function test_get_client() {
        $client = $this->service->getClient();
        $this->assertSame($this->mockClient, $client);
    }
    
    /**
     * Test getting the cache manager.
     */
    public function test_get_cache_manager() {
        $cacheManager = $this->service->getCacheManager();
        $this->assertInstanceOf(cache_manager::class, $cacheManager);
    }
    
    /**
     * Test getting the logger.
     */
    public function test_get_logger() {
        $logger = $this->service->getLogger();
        $this->assertInstanceOf(logger::class, $logger);
    }
    
    /**
     * Test testing the connection.
     */
    public function test_test_connection_success() {
        // Set up the mock repository to return success
        $this->mockRepository->expects($this->once())
            ->method('testConnection')
            ->willReturn(true);
        
        // Test the connection
        $result = $this->service->testConnection();
        
        // Verify the result
        $this->assertTrue($result);
    }
    
    /**
     * Test connection failure.
     */
    public function test_test_connection_failure() {
        // Set up the mock repository to throw an exception
        $this->mockRepository->expects($this->once())
            ->method('testConnection')
            ->willThrowException(new \Exception('Connection failed'));
        
        // Test the connection
        $result = $this->service->testConnection();
        
        // Verify the result
        $this->assertFalse($result);
    }
    
    /**
     * Test converting SID to Shibboleth ID.
     */
    public function test_sid_to_shibboleth_id() {
        // Set mock config via reflection
        $reflection = new \ReflectionClass($this->service);
        $property = $reflection->getProperty('config');
        $property->setAccessible(true);
        $property->setValue($this->service, (object)[
            'adsidprefix' => 'S-1-5-21-',
            'adshibbolethsuffix' => '@fhgr.ch'
        ]);
        
        // Convert SID to Shibboleth ID
        $result = $this->service->sidToShibbolethId('S-1-5-21-12345');
        
        // Verify the result
        $this->assertEquals('12345@fhgr.ch', $result);
    }
    
    /**
     * Test converting Shibboleth ID to SID.
     */
    public function test_shibboleth_id_to_sid() {
        // Set mock config via reflection
        $reflection = new \ReflectionClass($this->service);
        $property = $reflection->getProperty('config');
        $property->setAccessible(true);
        $property->setValue($this->service, (object)[
            'adsidprefix' => 'S-1-5-21-',
            'adshibbolethsuffix' => '@fhgr.ch'
        ]);
        
        // Convert Shibboleth ID to SID
        $result = $this->service->shibbolethIdToSid('12345@fhgr.ch');
        
        // Verify the result
        $this->assertEquals('S-1-5-21-12345', $result);
    }
    
    /**
     * Test converting values to arrays.
     */
    public function test_to_array() {
        // Test with an array
        $result = $this->service->toArray(['item1', 'item2']);
        $this->assertEquals(['item1', 'item2'], $result);
        
        // Test with a single value
        $result = $this->service->toArray('single');
        $this->assertEquals(['single'], $result);
        
        // Test with null
        $result = $this->service->toArray(null);
        $this->assertEquals([], $result);
    }
    
    /**
     * Test setting a new trace.
     */
    public function test_set_trace() {
        // Create a new mock trace
        $newMockTrace = $this->createMock(\progress_trace::class);
        
        // Get the logger to verify it receives the new trace
        $logger = $this->service->getLogger();
        
        // Use reflection to replace the logger with a mock we can verify
        $mockLogger = $this->createMock(logger::class);
        $mockLogger->expects($this->once())
            ->method('setTrace')
            ->with($this->identicalTo($newMockTrace));
        
        $reflection = new \ReflectionClass($this->service);
        $property = $reflection->getProperty('logger');
        $property->setAccessible(true);
        $property->setValue($this->service, $mockLogger);
        
        // Set the new trace
        $this->service->setTrace($newMockTrace);
    }
}