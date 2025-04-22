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
 * Interface for response filters.
 */
interface filter_interface {
    /**
     * Apply this filter to a response.
     *
     * @param mixed $response The response to filter
     * @return mixed The filtered response
     */
    public function apply($response);
}