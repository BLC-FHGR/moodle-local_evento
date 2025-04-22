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
 * Repository for Evento API data access.
 *
 * @package    local_evento
 * @copyright  2025 Julien Rädler
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_evento\data;

use local_evento\api\client_interface;
use local_evento\cache\cache_manager;
use local_evento\api\response_processor;
use local_evento\service;

defined('MOODLE_INTERNAL') || die();

/**
 * Repository for accessing Evento API data.
 * 
 * This class provides methods to interact with the Evento API, handling
 * parameter formatting, caching, and response processing while correctly
 * structuring API requests according to the WSDL requirements.
 */
class repository implements repository_interface {
    /** @var client_interface The API client */
    private $apiClient;
    
    /** @var cache_manager The cache manager */
    private $cacheManager;
    
    /** @var response_processor The response processor */
    private $responseProcessor;
    
    /**
     * Constructor.
     *
     * @param client_interface $apiClient The API client
     * @param cache_manager $cacheManager The cache manager
     * @param response_processor $responseProcessor The response processor
     */
    public function __construct(
        client_interface $apiClient, 
        cache_manager $cacheManager,
        response_processor $responseProcessor
    ) {
        $this->apiClient = $apiClient;
        $this->cacheManager = $cacheManager;
        $this->responseProcessor = $responseProcessor;
    }
    
    /**
     * Format date values for API requests.
     * 
     * @param mixed $date \DateTime object or string date
     * @return string Formatted date string
     * @throws \InvalidArgumentException If date format is invalid
     */
    private function formatDate($date) {
        if ($date instanceof \DateTime) {
            return $date->format(service::DATETIME_FORMAT);
        } else if (is_string($date)) {
            // Attempt to parse string to DateTime and format
            try {
                return (new \DateTime($date))->format(service::DATETIME_FORMAT);
            } catch (\Exception $e) {
                throw new \InvalidArgumentException('Invalid date format: ' . $date);
            }
        }
        
        throw new \InvalidArgumentException('Date must be a DateTime object or string');
    }
    
    /**
     * Build limitation filter from options.
     * 
     * @param array $options The limitation options
     * @return array The formatted limitation filter
     */
    private function buildLimitationFilter(array $options) {
        $filter = [];
        
        if (isset($options['maxResults'])) {
            if (!is_int($options['maxResults']) || $options['maxResults'] < 1) {
                throw new \InvalidArgumentException('maxResults must be a positive integer');
            }
            $filter['theMaxResultsValue'] = $options['maxResults'];
        }
        
        if (isset($options['sortField'])) {
            $filter['theSortField'] = $options['sortField'];
        }
        
        if (isset($options['lastChangeDate'])) {
            $filter['theFromDate'] = $this->formatDate($options['lastChangeDate']);
        }
        
        if (isset($options['startDate'])) {
            $filter['theFromDate'] = $this->formatDate($options['startDate']);
        }
        
        if (isset($options['endDate'])) {
            $filter['theToDate'] = $this->formatDate($options['endDate']);
        }
        
        if (isset($options['fromKey'])) {
            $filter['theFromKey'] = $options['fromKey'];
        }
        
        if (isset($options['toKey'])) {
            $filter['theToKey'] = $options['toKey'];
        }
        
        return $filter;
    }
    
    /**
     * Get events from Evento based on filter criteria and limitation options.
     *
     * @param array $entityFilter The filter criteria for events
     * @param array $limitOptions Optional limitation options
     * @return array Processed event data
     * @throws \InvalidArgumentException If filter parameters are invalid
     */
    public function getEvents(array $entityFilter, array $limitOptions = []) {
        $params = [['theEventoAnlassFilter' => $entityFilter]];
        
        // Add limitation filter if options provided
        if (!empty($limitOptions)) {
            $params[0]['theLimitationFilter2'] = $this->buildLimitationFilter($limitOptions);
        }
        
        return $this->processCachedResponse(
            cache_manager::REGION_API_RESPONSES,
            $this->cacheManager->generateApiResponseKey('listEventoAnlass', $params),
            'listEventoAnlass',
            $params
        );
    }
    
    /**
     * Get enrollments for a specific event with optional limitation options.
     *
     * @param int $eventId The Evento event ID
     * @param array $limitOptions Optional limitation options
     * @return array Processed enrollment data
     * @throws \InvalidArgumentException If parameters are invalid
     */
    public function getEnrollments(int $eventId, array $limitOptions = []) {
        $entityFilter = ['idAnlass' => $eventId];
        $params = [['theEventoPersonenAnmeldungFilter' => $entityFilter]];
        
        // Add limitation filter if options provided
        if (!empty($limitOptions)) {
            $params[0]['theLimitationFilter2'] = $this->buildLimitationFilter($limitOptions);
        }
        
        return $this->processCachedResponse(
            cache_manager::REGION_API_RESPONSES,
            $this->cacheManager->generateApiResponseKey('listEventoPersonenAnmeldung', $params),
            'listEventoPersonenAnmeldung',
            $params
        );
    }
    
    /**
     * Get users based on specified criteria and limitation options.
     *
     * @param array $entityFilter The search criteria for users
     * @param array $limitOptions Optional limitation options
     * @return array Processed user data
     * @throws \InvalidArgumentException If filter parameters are invalid
     */
    public function getUsers(array $entityFilter, array $limitOptions = []) {
        $params = [['theEventoPersonFilter' => $entityFilter]];
        
        // Add limitation filter if options provided
        if (!empty($limitOptions)) {
            $params[0]['theLimitationFilter2'] = $this->buildLimitationFilter($limitOptions);
        }
        
        return $this->processCachedResponse(
            cache_manager::REGION_API_RESPONSES,
            $this->cacheManager->generateApiResponseKey('listEventoPerson', $params),
            'listEventoPerson',
            $params
        );
    }
    
    /**
     * Get organizational units from Evento with optional limitation options.
     *
     * @param array $entityFilter Optional filter criteria for organizational units
     * @param array $limitOptions Optional limitation options
     * @return array Processed organizational unit data
     * @throws \InvalidArgumentException If filter parameters are invalid
     */
    public function getOrganizationalUnits(array $entityFilter = [], array $limitOptions = []) {
        $params = [['theEventoOEFilter' => $entityFilter]];
        
        // Add limitation filter if options provided
        if (!empty($limitOptions)) {
            $params[0]['theLimitationFilter2'] = $this->buildLimitationFilter($limitOptions);
        }
        
        return $this->processCachedResponse(
            cache_manager::REGION_API_RESPONSES,
            $this->cacheManager->generateApiResponseKey('listEventoOE', $params),
            'listEventoOE',
            $params
        );
    }
    
    /**
     * Test connection to the Evento API by making a lightweight request.
     * 
     * @return bool True if connection is successful
     */
    public function testConnection(): bool {
        try {
            // Execute a lightweight API call to check connectivity
            // listEventoAnlassTyp is used because it returns a small amount of data
            $result = $this->apiClient->execute('listEventoAnlassTyp', [
                ['theLimitationFilter2' => ['theMaxResultsValue' => 1]]
            ]);
            
            // If we get a response with a 'return' property, the connection is working
            return isset($result->return);
        } catch (\Exception $e) {
            // Connection failed
            return false;
        }
    }
    
    /**
     * Process and cache API responses.
     * 
     * @param string $region The cache region
     * @param string $key The cache key
     * @param string $method The API method to call
     * @param array $params The API parameters
     * @return array The processed API response
     */
    private function processCachedResponse($region, $key, $method, $params) {
        // Try to get from cache first
        $cachedResult = $this->cacheManager->get($region, $key);
        
        if ($cachedResult !== false) {
            return $this->responseProcessor->process($cachedResult);
        }
        
        // Call the API if not cached
        $result = $this->apiClient->execute($method, $params);
        
        // Cache the raw response
        $this->cacheManager->set($region, $key, $result);
        
        // Process and return the result
        return $this->responseProcessor->process($result);
    }
}