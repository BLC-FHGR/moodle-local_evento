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
 * Class enrollment_info
 *
 * @package    local_evento
 * @copyright  2025 YOUR NAME <your@email.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class enrollment_info {
    
    private int $person_id;
    private string $status;
    private string $role; // 'student' or 'teacher'
    private ?\DateTime $enrollment_date;

    public function __construct(
        int $person_id,
        string $status,
        string $role = 'student',
        ?\DateTime $enrollment_date = null
    ) {
        $this->person_id = $person_id;
        $this->status = $status;
        $this->role = $role;
        $this->enrollment_date = $enrollment_date;
    }

    public function get_person_id(): int { return $this->person_id; }
    public function get_status(): string { return $this->status; }
    public function get_role(): string { return $this->role; }
    public function get_enrollment_date(): ?\DateTime { return $this->enrollment_date; }
    
    public function is_student(): bool { return $this->role === 'student'; }
    public function is_teacher(): bool { return $this->role === 'teacher'; }
}
