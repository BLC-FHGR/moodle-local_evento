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

namespace local_evento\parser;

use local_evento\dto\parsed_anlassnummer;
use local_evento\exception\parsing_exception;

/**
 * Class anlassnummer_parser
 * Centralized anlassnummer parsing - no more explode('.', $anlassnummer) scattered everywhere!
 *
 * @package    local_evento
 * @copyright  2025 YOUR NAME <your@email.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class anlassnummer_parser {
    
    public function parse(string $anlassnummer): parsed_anlassnummer {
        if (empty($anlassnummer)) {
            throw new parsing_exception("Empty anlassnummer provided");
        }

        $parts = explode('.', $anlassnummer);
        
        if (count($parts) !== 4) {
            throw new parsing_exception("Invalid anlassnummer format: {$anlassnummer}");
        }

        [$prefix, $middle_part, $semester_folder, $instance] = $parts;

        // Extract course code from first uppercase letter onwards
        $course_code = $this->extract_course_code($middle_part);

        return new parsed_anlassnummer(
            $anlassnummer,
            $course_code,        // "ENTMARK", "WT", "LABOR1"
            $semester_folder,    // "FS25_BS", "2025/26" 
            $prefix,             // "mod", "modh", "modk"
            $instance           // "001", "002"
        );
    }

    private function extract_course_code(string $middle_part): string {
        // Find first uppercase letter
        if (preg_match('/[A-Z]/', $middle_part, $matches, PREG_OFFSET_CAPTURE)) {
            $start_pos = $matches[0][1];
            return substr($middle_part, $start_pos);
        }
        
        // Fallback if no uppercase found
        return $middle_part;
    }
}