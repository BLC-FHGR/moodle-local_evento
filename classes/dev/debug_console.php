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
 * Debug console for Evento development.
 *
 * @package    local_evento
 * @copyright  2025 FHGR
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_evento\dev;

defined('MOODLE_INTERNAL') || die();

/**
 * Debug console for Evento development.
 * 
 * This class provides a simple console output mechanism for tracking
 * API calls, responses, and other debug information during development.
 */
class debug_console {
    /** @var array Buffer for log entries */
    private static $buffer = [];
    
    /** @var bool Whether debug output is enabled */
    private static $enabled = false;
    
    /** @var float|null Start time for elapsed time tracking */
    private static $start_time = null;
    
    /** @var bool Whether to output colorized text (if terminal supports it) */
    private static $colorize = true;
    
    /** @var string Output file path if file output is enabled */
    private static $output_file = null;

    /**
     * Enable or disable debug output.
     *
     * @param bool $enabled Whether to enable debug output
     * @return void
     */
    public static function enable($enabled = true) {
        self::$enabled = $enabled;
        if ($enabled && self::$start_time === null) {
            self::$start_time = microtime(true);
        }
    }
    
    /**
     * Set whether to colorize terminal output.
     *
     * @param bool $colorize Whether to colorize output
     * @return void
     */
    public static function set_colorize($colorize = true) {
        self::$colorize = $colorize;
    }
    
    /**
     * Set output file for logging.
     *
     * @param string|null $filepath Path to output file or null to disable file output
     * @return void
     */
    public static function set_output_file($filepath) {
        self::$output_file = $filepath;
    }

    /**
     * Log a message with optional data.
     *
     * @param string $message The message to log
     * @param mixed $data Optional data to log
     * @param string $level Log level (info, warning, error)
     * @return void
     */
    public static function log($message, $data = null, $level = 'info') {
        if (!self::$enabled) return;
        
        $time = microtime(true);
        $elapsed = self::$start_time ? ($time - self::$start_time) : 0;
        
        $entry = [
            'time' => date('H:i:s'),
            'elapsed' => round($elapsed, 3),
            'message' => $message,
            'data' => $data,
            'level' => $level
        ];
        
        self::$buffer[] = $entry;
    }

    /**
     * Log a SOAP request.
     *
     * @param string $method The SOAP method name
     * @param array $params The parameters for the SOAP call
     * @return void
     */
    public static function soap_request($method, $params) {
        if (!self::$enabled) return;
        self::log("SOAP REQUEST: $method", $params, 'soap_request');
    }

    /**
     * Log a SOAP response.
     *
     * @param string $method The SOAP method name
     * @param mixed $response The response from the SOAP call
     * @return void
     */
    public static function soap_response($method, $response) {
        if (!self::$enabled) return;
        self::log("SOAP RESPONSE: $method", $response, 'soap_response');
    }

    /**
     * Log an error message.
     *
     * @param string $message The error message
     * @param mixed $data Optional error details
     * @return void
     */
    public static function error($message, $data = null) {
        if (!self::$enabled) return;
        self::log("ERROR: $message", $data, 'error');
    }

    /**
     * Log a warning message.
     *
     * @param string $message The warning message
     * @param mixed $data Optional warning details
     * @return void
     */
    public static function warning($message, $data = null) {
        if (!self::$enabled) return;
        self::log("WARNING: $message", $data, 'warning');
    }

    /**
     * Dump the log buffer to output.
     *
     * @param bool $clear Whether to clear the buffer after dumping
     * @return void
     */
    public static function dump($clear = true) {
        if (!self::$enabled || empty(self::$buffer)) return;
        
        $output = "\n=== EVENTO DEBUG OUTPUT ===\n";
        foreach (self::$buffer as $entry) {
            // Format time and elapsed info
            $timeInfo = "[{$entry['time']} | +{$entry['elapsed']}s]";
            
            // Colorize based on level if enabled
            $message = $entry['message'];
            if (self::$colorize && php_sapi_name() === 'cli') {
                $timeInfo = self::colorize($timeInfo, 'dark_gray');
                
                switch ($entry['level']) {
                    case 'error':
                        $message = self::colorize($message, 'red');
                        break;
                    case 'warning':
                        $message = self::colorize($message, 'yellow');
                        break;
                    case 'soap_request':
                        $message = self::colorize($message, 'cyan');
                        break;
                    case 'soap_response':
                        $message = self::colorize($message, 'green');
                        break;
                }
            }
            
            $output .= "$timeInfo $message\n";
            
            if ($entry['data'] !== null) {
                $data = self::format_data($entry['data']);
                // Indent data for better readability
                $data = preg_replace('/^/m', '    ', $data);
                $output .= "$data\n";
            }
        }
        $output .= "=== END DEBUG OUTPUT ===\n\n";
        
        // Output to console
        echo $output;
        
        // Output to file if configured
        if (self::$output_file) {
            file_put_contents(self::$output_file, strip_tags($output), FILE_APPEND);
        }
        
        // Clear buffer if requested
        if ($clear) {
            self::$buffer = [];
        }
    }

    /**
     * Format data for output.
     *
     * @param mixed $data The data to format
     * @return string Formatted data
     */
    private static function format_data($data) {
        if (is_string($data)) {
            return $data;
        }
        
        if (is_object($data) && method_exists($data, '__toString')) {
            return (string)$data;
        }
        
        // Convert SimpleXMLElement to string if needed
        if ($data instanceof \SimpleXMLElement) {
            $dom = dom_import_simplexml($data);
            return $dom->ownerDocument->saveXML($dom->ownerDocument->documentElement);
        }
        
        // JSON format with indentation for objects/arrays
        return json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    }
    
    /**
     * Colorize text for terminal output.
     *
     * @param string $text The text to colorize
     * @param string $color The color name
     * @return string Colorized text
     */
    private static function colorize($text, $color) {
        $colors = [
            'black' => "\033[0;30m",
            'dark_gray' => "\033[1;30m",
            'red' => "\033[0;31m",
            'light_red' => "\033[1;31m",
            'green' => "\033[0;32m",
            'light_green' => "\033[1;32m",
            'brown' => "\033[0;33m",
            'yellow' => "\033[1;33m",
            'blue' => "\033[0;34m",
            'light_blue' => "\033[1;34m",
            'magenta' => "\033[0;35m",
            'light_magenta' => "\033[1;35m",
            'cyan' => "\033[0;36m",
            'light_cyan' => "\033[1;36m",
            'light_gray' => "\033[0;37m",
            'white' => "\033[1;37m",
            'reset' => "\033[0m",
        ];
        
        if (!isset($colors[$color])) {
            return $text;
        }
        
        return $colors[$color] . $text . $colors['reset'];
    }
}