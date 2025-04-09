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
 * Interface for Evento data repository.
 *
 * @package    local_evento
 * @copyright  2025 Julien Rädler
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_evento\data;

defined('MOODLE_INTERNAL') || die();

/**
 * Interface evento_repository_interface
 * 
 * Defines the contract for Evento data repository operations.
 * This interface provides methods to access Evento data from the API
 * with intelligent caching to optimize performance of nightly synchronization jobs.
 */
interface evento_repository_interface {
    /**
     * Get events from Evento based on filter criteria.
     * 
     * Returns events with intelligent caching to reduce API calls.
     *
     * @param array $filter The filter criteria for events
     * @return array Processed event data
     */
    public function getEvents($filter);
    
    /**
     * Get enrollments for a specific event.
     * 
     * Returns all student enrollments for the given event ID.
     *
     * @param int $eventId The Evento event ID
     * @return array Processed enrollment data
     */
    public function getEnrollments($eventId);
    
    /**
     * Get users based on specified criteria.
     * 
     * Returns user data matching the provided criteria.
     *
     * @param array $criteria The search criteria for users
     * @return array Processed user data
     */
    public function getUsers($criteria);
    
    /**
     * Get organizational units from Evento.
     * 
     * Returns all organizational units (Veranstalter) for category mapping.
     *
     * @return array Processed organizational unit data
     */
    public function getOrganizationalUnits();
}