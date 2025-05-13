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
 * Development test task for Evento.
 *
 * @package    local_evento
 * @copyright  2025 FHGR
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_evento\task;

defined('MOODLE_INTERNAL') || die();

/**
 * Development test task for testing Evento functionality.
 */
class dev_test extends \core\task\scheduled_task {
    /**
     * Get task name.
     *
     * @return string The name of the task
     */
    public function get_name() {
        return 'Evento development test';
    }

    /**
     * Execute the task.
     *
     * @return void
     */
    public function execute() {
        // Enable debug console
        \local_evento\dev\debug_console::enable(true);
        
        try {
            // Log start of test
            \local_evento\dev\debug_console::log("Starting Evento development test");
            
            // Get service instance
            $service = \local_evento\service::getInstance();
            $repo = $service->getRepository();
            
            // Test connection
            \local_evento\dev\debug_console::log("Testing connection to Evento API");
            $connected = $service->testConnection();
            \local_evento\dev\debug_console::log("Connection test result", ["success" => $connected]);
            
            if ($connected) {
                // Try to fetch some events
                \local_evento\dev\debug_console::log("Fetching recent events");
                $yesterday = new \DateTime('-1 day');
                $events = $repo->getEvents(
                    ['anlassVeranstalter' => 'FHGR'], 
                    ['lastChangeDate' => $yesterday, 'maxResults' => 5]
                );
                
                \local_evento\dev\debug_console::log("Recent events fetched", [
                    "count" => count($events),
                    "first_few" => array_slice($events, 0, 2)
                ]);
                
                // If we have events, get enrollments for the first one
                if (!empty($events)) {
                    $eventId = $events[0]->idAnlass;
                    \local_evento\dev\debug_console::log("Fetching enrollments for event $eventId");
                    $enrollments = $repo->getEnrollments($eventId);
                    
                    \local_evento\dev\debug_console::log("Enrollments fetched", [
                        "count" => count($enrollments),
                        "first_few" => array_slice($enrollments, 0, 2)
                    ]);
                    
                    // Get some users
                    \local_evento\dev\debug_console::log("Fetching users");
                    $users = $repo->getUsers(['personAktiv' => true], ['maxResults' => 5]);
                    
                    \local_evento\dev\debug_console::log("Users fetched", [
                        "count" => count($users),
                        "first_few" => array_slice($users, 0, 2)
                    ]);
                    
                    // Get organizational units
                    \local_evento\dev\debug_console::log("Fetching organizational units");
                    $orgUnits = $repo->getOrganizationalUnits(['isActiv' => true]);
                    
                    \local_evento\dev\debug_console::log("Organizational units fetched", [
                        "count" => count($orgUnits),
                        "first_few" => array_slice($orgUnits, 0, 2)
                    ]);
                }
            } else {
                \local_evento\dev\debug_console::warning("Connection to Evento API failed");
            }
        } catch (\Exception $e) {
            \local_evento\dev\debug_console::error($e->getMessage(), [
                "trace" => $e->getTraceAsString()
            ]);
        }
        
        // Output debug information
        \local_evento\dev\debug_console::dump();
    }
}