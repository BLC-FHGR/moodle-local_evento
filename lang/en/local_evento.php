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
 * Strings for component 'local_evento', language 'en'
 *
 * @package    local_evento
 * @copyright  2017 HTW Chur Roger Barras
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

$string['ad_sidprefix'] = 'Prefix of the AD sid';
$string['ad_sidprefix_desc'] = 'Prefix of the AD sid which is not part of the shibboleth ID';
$string['ad_shibbolethsuffix'] = 'Suffix of the shibboleth ID';
$string['error_servicecall'] = 'The evento webservice call "{$a->operation}" failed. Faultcode: {$a->faultcode}. Message: {$a->faultstring}';
$string['pluginname'] = 'Evento Integration';
$string['pluginname_desc'] = 'This plugin provides the access to the evento SOAP webservice';
$string['ws_location'] = 'Location';
$string['ws_uri'] = 'URI';
$string['ws_wsdlfilename'] = 'WSDL filename';
$string['ws_wsdlfilename_desc'] = 'Name of the WSDL file inside the plugin folder "wsdl". Version 1.2 adds the operation getEventoModulBeschreibung and the field anlass_IDAnlassModul, while anlass_IDAnlassStudiengang, EventoAdresse.adr_URL and EventoPerson.person_MWSTNr were removed. Set this back to an older file to roll back. The service address of the WSDL is always overridden by the setting "Location".';
$string['ws_username'] = 'Username';
$string['ws_password'] = 'Password';
$string['ws_trace'] = 'Soap tracing';
$string['ws_trace_desc'] = 'Soap tracing for debugging. Enter 1 to enable and 0 to disable.';
