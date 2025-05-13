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
 * CLI script to run Evento development tests.
 *
 * @package    local_evento
 * @copyright  2025 FHGR
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

define('CLI_SCRIPT', true);

require(__DIR__ . '/../../../config.php');
require_once($CFG->libdir . '/clilib.php');

// Get CLI options
list($options, $unrecognized) = cli_get_params([
    'help' => false,
    'output' => '',
    'colorize' => true,
], [
    'h' => 'help',
    'o' => 'output',
    'c' => 'colorize'
]);

if ($options['help']) {
    $help = "Evento development test CLI script.

Options:
-h, --help              Print this help.
-o, --output=PATH       Write output to file.
-c, --colorize=BOOL     Colorize output (true/false).

Example:
\$ php cli/dev_test.php --output=/tmp/evento_debug.log
";
    echo $help;
    exit(0);
}

// Configure debug console
\local_evento\dev\debug_console::enable(true);
\local_evento\dev\debug_console::set_colorize($options['colorize']);

if (!empty($options['output'])) {
    \local_evento\dev\debug_console::set_output_file($options['output']);
    cli_writeln("Output will be saved to: " . $options['output']);
}

// Create and execute task
cli_writeln("Running Evento development test...");
$task = new \local_evento\task\dev_test();
$task->execute();
cli_writeln("Done!");