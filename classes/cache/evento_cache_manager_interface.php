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
 * Interface for Evento cache manager.
 *
 * @package    local_evento
 * @copyright  2025 Julien Rädler
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_evento\cache;

defined('MOODLE_INTERNAL') || die();

/**
 * Interface cache_manager_interface
 * 
 * Defines the contract for Evento caching operations.
 * This interface provides methods to manage caching for the Evento integration,
 * allowing efficient data storage and retrieval for SOAP responses, course mappings,
 * user data, and other frequently accessed information.
 */
interface cache_manager_interface {
    /**
     * Retrieve cached data from the specified region.
     *
     * @param string $region The cache region identifier
     * @param string $key The unique key for the cached data
     * @return mixed The cached data or false if not found
     */
    public function get($region, $key);
    
    /**
     * Store data in the specified cache region.
     *
     * @param string $region The cache region identifier
     * @param string $key The unique key for storing the data
     * @param mixed $value The data to cache
     * @param int|null $ttl Optional time-to-live in seconds (null for default)
     * @return bool Success or failure
     */
    public function set($region, $key, $value, $ttl = null);
    
    /**
     * Remove a specific item from the cache.
     *
     * @param string $region The cache region identifier
     * @param string $key The unique key for the cached data
     * @return bool Success or failure
     */
    public function invalidate($region, $key);
    
    /**
     * Clear all cached data in the specified region.
     *
     * @param string $region The cache region identifier
     * @return bool Success or failure
     */
    public function invalidateRegion($region);
    
    /**
     * Generate a standardized cache key.
     *
     * @param string $prefix The key prefix
     * @param mixed $params Parameters to include in the key
     * @return string The generated cache key
     */
    public function generateKey($prefix, $params);
    
    /**
     * Generate a cache key for API responses.
     *
     * @param string $method The API method name
     * @param array $params The API call parameters
     * @return string The generated cache key
     */
    public function generateApiResponseKey($method, $params);
    
    /**
     * Generate a cache key for category structure data.
     *
     * @param int $categoryId The category ID
     * @return string The generated cache key
     */
    public function generateCategoryStructureKey($categoryId);
    
    /**
     * Generate a cache key for filtered results.
     *
     * @param string $filterId The filter identifier
     * @param array $dataContext The data context for the filter
     * @return string The generated cache key
     */
    public function generateFilterResultsKey($filterId, $dataContext);
    
    /**
     * Generate a cache key for event mappings.
     *
     * @param int $eventoId The Evento event ID
     * @return string The generated cache key
     */
    public function generateEventMappingKey($eventoId);
}