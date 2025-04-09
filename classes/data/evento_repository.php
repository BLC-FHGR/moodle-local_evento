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
 * Cache repository for Evento plugin.
 *
 * @package    local_evento
 * @copyright  2025 Julien Rädler
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_evento\data;

use local_evento\api\evento_client_interface;
use local_evento\cache\evento_cache_manager;
use local_evento\api\evento_response_processor;

defined('MOODLE_INTERNAL') || die();

class evento_repository {
    private $apiClient;
    private $cacheManager;
    private $responseProcessor;
    
    public function __construct(
        evento_client_interface $apiClient, 
        evento_cache_manager $cacheManager,
        evento_response_processor $responseProcessor
    ) {
        $this->apiClient = $apiClient;
        $this->cacheManager = $cacheManager;
        $this->responseProcessor = $responseProcessor;
    }
    
    public function getEvents($filter) {
        return $this->processCachedResponse(
            evento_cache_manager::REGION_API_RESPONSES,
            $this->cacheManager->generateApiResponseKey('listEventoAnlass', $filter),
            'listEventoAnlass',
            [$filter]
        );
    }
    
    public function getEnrollments($eventId) {
        $filter = ['idAnlass' => $eventId];
        
        return $this->processCachedResponse(
            evento_cache_manager::REGION_API_RESPONSES,
            $this->cacheManager->generateApiResponseKey('listEventoPersonenAnmeldung', $filter),
            'listEventoPersonenAnmeldung',
            [$filter]
        );
    }
    
    public function getUsers($criteria) {
        return $this->processCachedResponse(
            evento_cache_manager::REGION_API_RESPONSES,
            $this->cacheManager->generateApiResponseKey('listEventoPerson', $criteria),
            'listEventoPerson',
            [$criteria]
        );
    }
    
    public function getOrganizationalUnits() {
        return $this->processCachedResponse(
            evento_cache_manager::REGION_API_RESPONSES,
            $this->cacheManager->generateApiResponseKey('listEventoOE', []),
            'listEventoOE',
            [[]]
        );
    }
    
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