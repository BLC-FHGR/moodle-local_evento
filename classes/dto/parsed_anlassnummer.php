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

namespace local_evento\dto;

/**
 * Class parsed_anlassnummer
 *
 * @package    local_evento
 * @copyright  2025 YOUR NAME <your@email.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class parsed_anlassnummer {
    
    private string $original;
    private string $course_code;
    private string $semester_folder;
    private string $prefix;
    private string $instance;

    public function __construct(
        string $original,
        string $course_code,
        string $semester_folder, 
        string $prefix,
        string $instance
    ) {
        $this->original = $original;
        $this->course_code = $course_code;
        $this->semester_folder = $semester_folder;
        $this->prefix = $prefix;
        $this->instance = $instance;
    }

    public function get_original(): string { return $this->original; }
    public function get_course_code(): string { return $this->course_code; }
    public function get_semester_folder(): string { return $this->semester_folder; }
    public function get_prefix(): string { return $this->prefix; }
    public function get_instance(): string { return $this->instance; }
    
    /**
     * Get category identifier (prefix + course_code)
     */
    public function get_category_identifier(): string {
        return $this->prefix . '.' . $this->course_code;
    }
}
