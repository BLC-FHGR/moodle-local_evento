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
 * Interface for Evento API client.
 *
 * @package    local_evento
 * @copyright  2025 Julien Rädler
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_evento\api;

defined('MOODLE_INTERNAL') || die();

/**
 * Interface for Evento API client.
 * 
 * This interface defines the contract for the SOAP client wrapper,
 * making it easier to provide alternative implementations for testing.
 */
interface client_interface {
    /**
     * Execute an API method with the given parameters.
     *
     * @param string $method The API method to call
     * @param array $params The parameters for the method
     * @return mixed The API response
     * @throws \Exception If execution fails
     */
    public function execute($method, $params);
    
    /**
     * Get the last request sent to the API.
     *
     * @return string The last request
     */
    public function get_last_request();
    
    /**
     * Get the last response received from the API.
     *
     * @return string The last response
     */
    public function get_last_response();
    
    /**
     * Get the SOAP client options.
     *
     * @return array The SOAP client options
     */
    public function get_options();
}