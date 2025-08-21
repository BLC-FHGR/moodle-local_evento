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
 * Class course_info
 * Clean DTO for course information - no more string parsing chaos
 *
 * @package    local_evento
 * @copyright  2025 YOUR NAME <your@email.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class course_info {
    
    private parsed_anlassnummer $parsed;
    private string $title;
    private string $study_program;  // From Veranstalter, not anlassnummer!
    private ?\DateTime $start_date;
    private ?\DateTime $end_date;
    private string $status;

    public function __construct(
        parsed_anlassnummer $parsed,
        string $title,
        string $study_program,  // From event Veranstalter
        ?\DateTime $start_date = null,
        ?\DateTime $end_date = null,
        string $status = ''
    ) {
        $this->parsed = $parsed;
        $this->title = $title;
        $this->study_program = $study_program;
        $this->start_date = $start_date;
        $this->end_date = $end_date;
        $this->status = $status;
    }

    // Parsed anlassnummer data
    public function get_anlassnummer(): string { return $this->parsed->get_original(); }
    public function get_course_code(): string { return $this->parsed->get_course_code(); }
    public function get_semester_folder(): string { return $this->parsed->get_semester_folder(); }
    public function get_instance(): string { return $this->parsed->get_instance(); }
    public function get_category_identifier(): string { return $this->parsed->get_category_identifier(); }
    
    // Event data
    public function get_title(): string { return $this->title; }
    public function get_study_program(): string { return $this->study_program; } // From Veranstalter!
    public function get_start_date(): ?\DateTime { return $this->start_date; }
    public function get_end_date(): ?\DateTime { return $this->end_date; }
    public function get_status(): string { return $this->status; }
}