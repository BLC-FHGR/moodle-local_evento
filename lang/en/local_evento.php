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
 * English language pack for Evento
 *
 * @package    local_evento
 * @category   string
 * @copyright  2025 Julien Rädler <julien.raedler@fhgr.ch>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$string['pluginname'] = 'Evento Integration V2';
$string['pluginname_desc'] = 'Clean service layer for Evento SOAP API integration';

// SOAP Settings
$string['soap_settings'] = 'SOAP API Configuration';
$string['soap_settings_desc'] = 'Configure connection to Evento SOAP webservice';
$string['soap_location'] = 'SOAP Location';
$string['soap_location_desc'] = 'URL of the Evento SOAP webservice endpoint';
$string['soap_wsdl'] = 'WSDL Filename';
$string['soap_wsdl_desc'] = 'WSDL file for the Evento webservice (in plugin wsdl/ directory)';
$string['soap_username'] = 'Username';
$string['soap_username_desc'] = 'Username for SOAP authentication';
$string['soap_password'] = 'Password';
$string['soap_password_desc'] = 'Password for SOAP authentication';
$string['soap_timeout'] = 'Timeout (seconds)';
$string['soap_timeout_desc'] = 'SOAP request timeout in seconds';

// Development Settings
$string['dev_settings'] = 'Development Settings';
$string['dev_settings_desc'] = 'Settings for development and debugging';
$string['dev_logging'] = 'Enable Development Logging';
$string['dev_logging_desc'] = 'Log all API requests/responses for development (disable in production!)';

// Dev Console
$string['dev_console'] = 'Development Console';
$string['open_dev_console'] = 'Open Development Console';
$string['test_anlassnummer'] = 'Test Anlassnummer';
$string['test_service'] = 'Test Service';