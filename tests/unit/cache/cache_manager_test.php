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
 * Unit tests for the Evento cache manager.
 *
 * @package    local_evento
 * @category   test
 * @copyright  2025 FHGR Julien Rädler
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_evento\tests\unit\cache;

defined('MOODLE_INTERNAL') || die();

use local_evento\cache\cache_manager;
use cache;
use PHPUnit\Framework\TestCase;

/**
 * Test case for the cache manager functionality.
 */
class cache_manager_test extends TestCase {
    /** @var cache_manager The cache manager under test */
    private $cacheManager;
    
    /** @var \PHPUnit\Framework\MockObject\MockObject[] Array of mock cache instances */
    private $mockCaches = [];
    
    /**
     * Set up before each test.
     */
    protected function setUp(): void {
        // Create the cache manager
        $this->cacheManager = new cache_manager();
        
        // Create mock caches for each region
        $regions = [
            cache_manager::REGION_API_RESPONSES,
            cache_manager::REGION_CATEGORY_STRUCTURE,
            cache_manager::REGION_FILTER_RESULTS,
            cache_manager::REGION_EVENT_MAPPING,
            cache_manager::REGION_USER_MAPPING
        ];
        
        foreach ($regions as $region) {
            $mockCache = $this->createMock(cache::class);
            $this->mockCaches[$region] = $mockCache;
        }
        
        // Inject mock caches into cache manager
        $reflection = new \ReflectionClass($this->cacheManager);
        $property = $reflection->getProperty('caches');
        $property->setAccessible(true);
        $property->setValue($this->cacheManager, $this->mockCaches);
    }
    
    /**
     * Test getting data from cache.
     */
    public function test_get() {
        $key = 'test_key';
        $expectedValue = 'test_value';
        $region = cache_manager::REGION_API_RESPONSES;
        
        // Set up the mock cache to return the expected value
        $this->mockCaches[$region]->expects($this->once())
            ->method('get')
            ->with($this->equalTo($key))
            ->willReturn($expectedValue);
        
        // Get the value from cache
        $result = $this->cacheManager->get($region, $key);
        
        // Verify the result
        $this->assertEquals($expectedValue, $result);
    }
    
    /**
     * Test setting data in cache.
     */
    public function test_set() {
        $key = 'test_key';
        $value = 'test_value';
        $region = cache_manager::REGION_API_RESPONSES;
        
        // Set up the mock cache to expect set to be called
        $this->mockCaches[$region]->expects($this->once())
            ->method('set')
            ->with(
                $this->equalTo($key),
                $this->equalTo($value)
            )
            ->willReturn(true);
        
        // Set the value in cache
        $result = $this->cacheManager->set($region, $key, $value);
        
        // Verify the result
        $this->assertTrue($result);
    }
    
    /**
     * Test invalidating a specific cache entry.
     */
    public function test_invalidate() {
        $key = 'test_key';
        $region = cache_manager::REGION_API_RESPONSES;
        
        // Set up the mock cache to expect delete to be called
        $this->mockCaches[$region]->expects($this->once())
            ->method('delete')
            ->with($this->equalTo($key))
            ->willReturn(true);
        
        // Invalidate the cache entry
        $result = $this->cacheManager->invalidate($region, $key);
        
        // Verify the result
        $this->assertTrue($result);
    }
    
    /**
     * Test invalidating an entire cache region.
     */
    public function test_invalidate_region() {
        $region = cache_manager::REGION_API_RESPONSES;
        
        // Set up the mock cache to expect purge to be called
        $this->mockCaches[$region]->expects($this->once())
            ->method('purge')
            ->willReturn(true);
        
        // Invalidate the entire region
        $result = $this->cacheManager->invalidateRegion($region);
        
        // Verify the result
        $this->assertTrue($result);
    }
    
    /**
     * Test generating a general cache key.
     */
    public function test_generate_key() {
        $prefix = 'test_prefix';
        $params = ['param1' => 'value1', 'param2' => 'value2'];
        
        // Generate the key
        $result = $this->cacheManager->generateKey($prefix, $params);
        
        // Verify the result format
        $this->assertStringStartsWith($prefix . '_', $result);
        
        // Generate another key with the same parameters
        $result2 = $this->cacheManager->generateKey($prefix, $params);
        
        // Keys with the same parameters should be identical
        $this->assertEquals($result, $result2);
        
        // Generate a key with different parameters
        $result3 = $this->cacheManager->generateKey($prefix, ['different' => 'params']);
        
        // Keys with different parameters should not be identical
        $this->assertNotEquals($result, $result3);
    }
    
    /**
     * Test generating an API response cache key.
     */
    public function test_generate_api_response_key() {
        $method = 'testMethod';
        $params = [['param1' => 'value1']];
        
        // Generate the key
        $result = $this->cacheManager->generateApiResponseKey($method, $params);
        
        // Verify the result format
        $this->assertStringStartsWith('api_' . $method . '_', $result);
    }
    
    /**
     * Test generating a category structure cache key.
     */
    public function test_generate_category_structure_key() {
        $categoryId = 123;
        
        // Generate the key
        $result = $this->cacheManager->generateCategoryStructureKey($categoryId);
        
        // Verify the result format
        $this->assertEquals('category_' . $categoryId, $result);
    }
    
    /**
     * Test generating a filter results cache key.
     */
    public function test_generate_filter_results_key() {
        $filterId = 'test_filter';
        $dataContext = ['context1' => 'value1', 'context2' => 'value2'];
        
        // Generate the key
        $result = $this->cacheManager->generateFilterResultsKey($filterId, $dataContext);
        
        // Verify the result format
        $this->assertStringStartsWith('filter_' . $filterId . '_', $result);
    }
    
    /**
     * Test generating an event mapping cache key.
     */
    public function test_generate_event_mapping_key() {
        $eventoId = 45685;
        
        // Generate the key
        $result = $this->cacheManager->generateEventMappingKey($eventoId);
        
        // Verify the result format
        $this->assertEquals('event_' . $eventoId, $result);
    }
    
    /**
     * Test invalid cache region throws exception.
     */
    public function test_invalid_region() {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid cache region');
        
        // Try to get from an invalid region
        $this->cacheManager->get('invalid_region', 'key');
    }
}