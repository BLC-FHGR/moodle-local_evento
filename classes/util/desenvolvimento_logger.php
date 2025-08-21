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

namespace local_evento\util;

/**
 * Class desenvolvimento_logger
 * Development logger for immediate feedback during API development
 *
 * @package    local_evento
 * @copyright  2025 YOUR NAME <your@email.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class desenvolvimento_logger {
    
    private bool $enabled;
    
    public function __construct() {
        $this->enabled = get_config('local_evento', 'dev_logging_enabled') ?? false;
    }
    
    public function log_request(string $method, $data): void {
        if (!$this->enabled) return;
        
        $message = sprintf(
            "[EVENTO DEV] %s REQUEST: %s", 
            strtoupper($method), 
            json_encode($data, JSON_PRETTY_PRINT)
        );
        
        debugging($message, DEBUG_DEVELOPER);
        error_log($message);
    }
    
    public function log_response(string $method, $data): void {
        if (!$this->enabled) return;
        
        $message = sprintf(
            "[EVENTO DEV] %s RESPONSE: %s", 
            strtoupper($method), 
            json_encode($data, JSON_PRETTY_PRINT)
        );
        
        debugging($message, DEBUG_DEVELOPER);
        error_log($message);
    }
    
    public function log_error(string $method, \Throwable $e): void {
        $message = sprintf(
            "[EVENTO ERROR] %s: %s in %s:%d", 
            strtoupper($method), 
            $e->getMessage(),
            $e->getFile(),
            $e->getLine()
        );
        
        debugging($message, DEBUG_DEVELOPER);
        error_log($message);
    }
}
