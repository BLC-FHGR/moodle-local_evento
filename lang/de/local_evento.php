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
 * Strings for component 'local_evento', language 'de'
 *
 * @package    local_evento
 * @copyright  2017 HTW Chur Roger Barras
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

$string['ad_sidprefix'] = 'Prefix der AD sid';
$string['ad_sidprefix_desc'] = 'Prefix der AD sid welche nicht Teil der Shibboleth-ID ist';
$string['ad_shibbolethsuffix'] = 'Suffix der Shibboleth-ID';
$string['pluginname'] = 'Evento Integration';
$string['pluginname_desc'] = 'Diese Plugin stellt den Webservicezugriff auf evento bereit';
$string['ws_location'] = 'Location';
$string['ws_uri'] = 'URI';
$string['ws_wsdlfilename'] = 'WSDL Dateiname';
$string['ws_wsdlfilename_desc'] = 'Name der WSDL-Datei im Pluginordner "wsdl". Version 1.2 ergaenzt die Operation getEventoModulBeschreibung und das Feld anlass_IDAnlassModul, dafuer sind anlass_IDAnlassStudiengang, EventoAdresse.adr_URL und EventoPerson.person_MWSTNr entfallen. Fuer ein Rollback hier wieder eine aeltere Datei eintragen. Die Serviceadresse aus der WSDL wird immer durch die Einstellung "Location" ueberschrieben.';
$string['ws_username'] = 'Benutzername';
$string['ws_password'] = 'Passwort';
$string['ws_trace'] = 'Soap tracing';
$string['ws_trace_desc'] = 'Aktiviere Soap tracing zum Debuggen. Enter 1 zum aktivieren und 0 zum deaktivieren.';
