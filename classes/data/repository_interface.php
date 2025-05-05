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
interface repository_interface {
    /**
     * Get events from Evento based on filter criteria and limitation options.
     * 
     * Returns events with intelligent caching to reduce API calls.
     * 
     * Example usage:
     * ```php
     * // Get all events for a specific organizer modified after a certain date
     * $repo->getEvents(
     *     ['anlassVeranstalter' => 'FHGR'],
     *     ['lastChangeDate' => new \DateTime('2024-01-01'), 'maxResults' => 100]
     * );
     * ```
     *
     * @param array $entityFilter The filter criteria for events (e.g., anlassVeranstalter, anlassBezeichnung)
     * @param array $limitOptions Optional limitation options including:
     *                           - lastChangeDate: \DateTime|string Last modification date filter
     *                           - startDate: \DateTime|string Date range start
     *                           - endDate: \DateTime|string Date range end
     *                           - maxResults: int Maximum number of results to return
     *                           - sortField: string Field to sort by
     * @return array Processed event data
     * @throws \InvalidArgumentException If filter parameters are invalid
     */
    public function getEvents(array $entityFilter, array $limitOptions = []);
    
    /**
     * Get enrollments for a specific event with optional limitation options.
     * 
     * Returns student enrollments for the given event ID with filtering capabilities.
     * 
     * Example usage:
     * ```php
     * // Get all enrollments for event 12345 with status changes after Jan 1, 2024
     * $repo->getEnrollments(
     *     12345,
     *     ['lastChangeDate' => new \DateTime('2024-01-01')]
     * );
     * ```
     *
     * @param int $eventId The Evento event ID
     * @param array $limitOptions Optional limitation options including:
     *                           - lastChangeDate: \DateTime|string Last modification date filter
     *                           - maxResults: int Maximum number of results to return
     * @return array Processed enrollment data
     * @throws \InvalidArgumentException If parameters are invalid
     */
    public function getEnrollments(int $eventId, array $limitOptions = []);
    
    /**
     * Get users based on specified criteria and limitation options.
     * 
     * Returns user data matching the provided criteria with filtering capabilities.
     * 
     * Example usage:
     * ```php
     * // Get users with last name "Smith" modified after Jan 1, 2024
     * $repo->getUsers(
     *     ['personNachname' => 'Smith'],
     *     ['lastChangeDate' => new \DateTime('2024-01-01')]
     * );
     * ```
     *
     * @param array $entityFilter The search criteria for users (e.g., personNachname, personVorname)
     * @param array $limitOptions Optional limitation options including:
     *                           - lastChangeDate: \DateTime|string Last modification date filter
     *                           - maxResults: int Maximum number of results to return
     * @return array Processed user data
     * @throws \InvalidArgumentException If filter parameters are invalid
     */
    public function getUsers(array $entityFilter, array $limitOptions = []);
    
    /**
     * Get organizational units from Evento with optional limitation options.
     * 
     * Returns organizational units (Veranstalter) for category mapping with filtering capabilities.
     * 
     * Example usage:
     * ```php
     * // Get active organizational units modified after Jan 1, 2024
     * $repo->getOrganizationalUnits(
     *     ['isActiv' => true],
     *     ['lastChangeDate' => new \DateTime('2024-01-01')]
     * );
     * ```
     *
     * @param array $entityFilter Optional filter criteria for organizational units
     * @param array $limitOptions Optional limitation options including:
     *                           - lastChangeDate: \DateTime|string Last modification date filter
     *                           - maxResults: int Maximum number of results to return
     * @return array Processed organizational unit data
     * @throws \InvalidArgumentException If filter parameters are invalid
     */
    public function getOrganizationalUnits(array $entityFilter = [], array $limitOptions = []);
}