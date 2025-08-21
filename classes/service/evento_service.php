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

namespace local_evento\service;

use local_evento\dto\course_info;
use local_evento\dto\enrollment_collection;

/**
 * Interface evento_service
 * Clean Anti-Corruption Layer for Evento SOAP API
 *
 * @package    local_evento
 * @copyright  2025 Julien Rädler <julien.raedler@fhgr.ch>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
interface evento_service {
    public function get_course_info(string $anlassnummer): course_info;
    public function get_enrollments(string $anlassnummer): enrollment_collection;
    public function is_service_available(): bool;
}
