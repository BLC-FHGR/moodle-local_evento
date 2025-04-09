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
 * Cache manager for Evento plugin.
 *
 * @package    local_evento
 * @copyright  2025 Julien Rädler
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_evento\cache;

defined('MOODLE_INTERNAL') || die();

use cache;

class evento_cache_manager implements cache_manager_interface {
    // Cache regions
    const REGION_API_RESPONSES = 'api_responses';
    const REGION_CATEGORY_STRUCTURE = 'category_structure';
    const REGION_FILTER_RESULTS = 'filter_results';
    const REGION_EVENT_MAPPING = 'event_mapping';
    const REGION_USER_MAPPING = 'user_mapping';
    
    // TTL values in seconds
    const TTL_API_RESPONSES = 1800;    // 30 minutes
    const TTL_CATEGORY_STRUCTURE = 3600;    // 1 hour
    const TTL_FILTER_RESULTS = 900;    // 15 minutes
    const TTL_EVENT_MAPPING = 86400;   // 1 day
    const TTL_USER_MAPPING = 86400;    // 1 day
    
    private $caches = [];
    
    public function __construct() {
        // Initialize the cache stores for each region
        $this->initializeCaches();
    }
    
    public function get($region, $key) {
        $this->validateRegion($region);
        return $this->caches[$region]->get($key);
    }
    
    public function set($region, $key, $value, $ttl = null) {
        $this->validateRegion($region);
        return $this->caches[$region]->set($key, $value);
    }
    
    public function invalidate($region, $key) {
        $this->validateRegion($region);
        return $this->caches[$region]->delete($key);
    }
    
    public function invalidateRegion($region) {
        $this->validateRegion($region);
        return $this->caches[$region]->purge();
    }
    
    public function generateKey($prefix, $params) {
        return $prefix . '_' . md5(serialize($params));
    }
    
    // Specific key generators for different types of data
    public function generateApiResponseKey($method, $params) {
        return $this->generateKey('api_' . $method, $params);
    }
    
    public function generateCategoryStructureKey($categoryId) {
        return 'category_' . $categoryId;
    }
    
    public function generateFilterResultsKey($filterId, $dataContext) {
        return $this->generateKey('filter_' . $filterId, $dataContext);
    }
    
    public function generateEventMappingKey($eventoId) {
        return 'event_' . $eventoId;
    }
    
    private function validateRegion($region) {
        if (!isset($this->caches[$region])) {
            throw new \InvalidArgumentException("Invalid cache region: {$region}");
        }
    }
    
    private function initializeCaches() {
        $this->caches[self::REGION_API_RESPONSES] = cache::make('local_evento', self::REGION_API_RESPONSES);
        $this->caches[self::REGION_CATEGORY_STRUCTURE] = cache::make('local_evento', self::REGION_CATEGORY_STRUCTURE);
        $this->caches[self::REGION_FILTER_RESULTS] = cache::make('local_evento', self::REGION_FILTER_RESULTS);
        $this->caches[self::REGION_EVENT_MAPPING] = cache::make('local_evento', self::REGION_EVENT_MAPPING);
        $this->caches[self::REGION_USER_MAPPING] = cache::make('local_evento', self::REGION_USER_MAPPING);
    }
}