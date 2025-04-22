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
 * Logger implementation for the Evento system.
 */
class logger implements logger_interface {
    /** @var \progress_trace The trace instance for real-time output */
    private $trace;
    
    /** @var string The component name for logging */
    private $component;
    
    /** @var array Mapping of log levels to Moodle debug levels */
    const LOG_LEVEL_MAP = [
        'debug' => DEBUG_DEVELOPER,
        'info' => DEBUG_NORMAL,
        'warning' => DEBUG_NORMAL,
        'error' => DEBUG_MINIMAL
    ];

    /**
     * Constructor.
     *
     * @param string $component The component name for logging
     * @param \progress_trace|null $trace Optional trace instance
     */
    public function __construct(string $component = 'local_evento', ?\progress_trace $trace = null) {
        $this->component = $component;
        $this->trace = $trace ?? new \null_progress_trace();
    }

    /**
     * Log a debug message.
     *
     * @param string $message The message to log
     * @param array $context Additional context data
     * @return void
     */
    public function debug(string $message, array $context = []): void {
        $this->log('debug', $message, $context);
    }

    /**
     * Log an info message.
     *
     * @param string $message The message to log
     * @param array $context Additional context data
     * @return void
     */
    public function info(string $message, array $context = []): void {
        $this->log('info', $message, $context);
    }

    /**
     * Log a warning message.
     *
     * @param string $message The message to log
     * @param array $context Additional context data
     * @return void
     */
    public function warning(string $message, array $context = []): void {
        $this->log('warning', $message, $context);
    }

    /**
     * Log an error message.
     *
     * @param string $message The message to log
     * @param array $context Additional context data
     * @return void
     */
    public function error(string $message, array $context = []): void {
        $this->log('error', $message, $context);
    }

    /**
     * Set a trace handler for real-time output.
     *
     * @param \progress_trace $trace The trace handler
     * @return void
     */
    public function setTrace(\progress_trace $trace): void {
        $this->trace = $trace;
    }

    /**
     * Get the trace instance.
     *
     * @return \progress_trace The trace instance
     */
    public function getTrace(): \progress_trace {
        return $this->trace;
    }

    /**
     * Internal logging method.
     *
     * @param string $level The log level
     * @param string $message The message to log
     * @param array $context Additional context data
     * @return void
     */
    private function log(string $level, string $message, array $context = []): void {
        global $CFG;
        
        // Format message with context
        $formattedMessage = $this->formatMessage($message, $context);
        
        // Output to trace if appropriate
        if ($level === 'debug') {
            $this->trace->output($formattedMessage, 3); // High detail level
        } else if ($level === 'info') {
            $this->trace->output($formattedMessage, 1); // Normal detail level
        } else {
            // Warnings and errors always output
            $this->trace->output($formattedMessage, 0);
        }
        
        // Map log levels to Moodle debug levels
        $debuglevel = self::LOG_LEVEL_MAP[$level] ?? DEBUG_NORMAL;
        
        // Only log if the current debug level allows it
        if (isset($CFG->debug) && ($CFG->debug >= $debuglevel)) {
            // Use Moodle's debugging function
            // This will display messages in the browser if $CFG->debugdisplay is enabled
            // and will write to the PHP error log if configured
            $dbgLevel = match($level) {
                'debug' => DEBUG_DEVELOPER,
                'info' => DEBUG_ALL,
                'warning' => DEBUG_NORMAL,
                'error' => DEBUG_MINIMAL,
                default => DEBUG_NORMAL
            };
            
            // Add component prefix to the message
            $prefixedMessage = "[{$this->component}] {$formattedMessage}";
            
            // Use Moodle's debugging function
            debugging($prefixedMessage, $dbgLevel);
            
            // For error and warning, also write to PHP error log to ensure visibility
            if ($level === 'error' || $level === 'warning') {
                error_log($prefixedMessage);
            }
        }
    }

    /**
     * Format a message with context data.
     *
     * @param string $message The message template
     * @param array $context The context data
     * @return string The formatted message
     */
    private function formatMessage(string $message, array $context = []): string {
        if (empty($context)) {
            return $message;
        }
        
        // Add context as JSON if present
        return $message . ' ' . json_encode($context, JSON_UNESCAPED_UNICODE);
    }
}