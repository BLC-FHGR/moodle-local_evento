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

namespace local_evento\api\filter;

defined('MOODLE_INTERNAL') || die();

/**
 * A filter that ensures arrays are returned from SOAP responses.
 */
class array_response_filter implements filter_interface {
    public function apply($response) {
        // Log the incoming response for debugging purposes.
        debugging('Filter input: ' . json_encode($response), DEBUG_DEVELOPER);
        
        // Standard processing
        if (is_object($response) && isset($response->return)) {
            $data = $response->return;
            
            if (is_array($data)) {
                $result = $data;
            } else if ($data !== null) {
                $result = [$data];
            } else {
                $result = [];
            }
            
            // Log the filtered output when debugging is enabled.
            debugging('Filter output: ' . json_encode($result), DEBUG_DEVELOPER);
            return $result;
        }
        
        // Handle other cases
        if (is_array($response)) {
            return $response;
        }
        
        return [];
    }
}