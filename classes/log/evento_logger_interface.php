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

/**
 * Logger implementation for the Evento system.
 *
 * @package    local_evento
 * @copyright  2025 FHGR Julien Rädler
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_evento\log;

defined('MOODLE_INTERNAL') || die();

/**
 * Interface for evento loggers.
 */
interface logger_interface {
    /**
     * Log a debug message.
     *
     * @param string $message The message to log
     * @param array $context Additional context data
     * @return void
     */
    public function debug(string $message, array $context = []): void;
    
    /**
     * Log an info message.
     *
     * @param string $message The message to log
     * @param array $context Additional context data
     * @return void
     */
    public function info(string $message, array $context = []): void;
    
    /**
     * Log a warning message.
     *
     * @param string $message The message to log
     * @param array $context Additional context data
     * @return void
     */
    public function warning(string $message, array $context = []): void;
    
    /**
     * Log an error message.
     *
     * @param string $message The message to log
     * @param array $context Additional context data
     * @return void
     */
    public function error(string $message, array $context = []): void;
    
    /**
     * Set a trace handler for real-time output.
     *
     * @param \progress_trace $trace The trace handler
     * @return void
     */
    public function setTrace(\progress_trace $trace): void;
}
