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

namespace local_evento\exception;

/**
 * Class evento_service_exception
 *
 * @package    local_evento
 * @copyright  2025 YOUR NAME <your@email.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class evento_service_exception extends \Exception {
    
    private ?\Throwable $soap_fault;

    public function __construct(string $message, ?\Throwable $previous = null) {
        parent::__construct($message, 0, $previous);
        $this->soap_fault = $previous;
    }

    public function get_soap_fault(): ?\Throwable {
        return $this->soap_fault;
    }
}
