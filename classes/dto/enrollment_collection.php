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
 * Class enrollment_collection
 *
 * @package    local_evento
 * @copyright  2025 YOUR NAME <your@email.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class enrollment_collection {
    
    /** @var enrollment_info[] */
    private array $enrollments;
    private string $anlassnummer;
    private int $total_count;

    /**
     * @param enrollment_info[] $enrollments
     */
    public function __construct(string $anlassnummer, array $enrollments = []) {
        $this->anlassnummer = $anlassnummer;
        $this->enrollments = $enrollments;
        $this->total_count = count($enrollments);
    }

    public function get_anlassnummer(): string {
        return $this->anlassnummer;
    }

    /**
     * @return enrollment_info[]
     */
    public function get_enrollments(): array {
        return $this->enrollments;
    }

    public function get_total_count(): int {
        return $this->total_count;
    }

    /**
     * Filter enrollments by status
     */
    public function filter_by_status(array $valid_statuses): enrollment_collection {
        $filtered = array_filter($this->enrollments, function($enrollment) use ($valid_statuses) {
            return in_array($enrollment->get_status(), $valid_statuses);
        });
        
        return new enrollment_collection($this->anlassnummer, $filtered);
    }

    /**
     * Get unique person IDs
     */
    public function get_person_ids(): array {
        return array_unique(array_map(function($enrollment) {
            return $enrollment->get_person_id();
        }, $this->enrollments));
    }
}
