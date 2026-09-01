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
 * Evento cli tool to print the status list of the webservice.
 *
 * Use it to find out what a numeric status id means. Keep in mind that the operation
 * listEventoStatus serves the status values of the events. It is not proven that the
 * element idStatus of an EventoModulBeschreibung uses the very same value domain,
 * that is exactly what this script helps to check.
 *
 * Notes:
 *   - it is required to use the web server account when executing PHP CLI scripts
 *   - you need to change the "www-data" to match the apache user account
 *   - use "su" if "sudo" not available
 *
 * Usage help:
 * $ sudo -u www-data /usr/bin/php local/evento/cli/list_status.php --help
 *
 * @package    local_evento
 * @copyright  2026 FH Graubuenden
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

define('CLI_SCRIPT', true);

require(__DIR__.'/../../../config.php');
require_once("$CFG->libdir/clilib.php");

// Now get cli options.
list($options, $unrecognized) = cli_get_params(
    array(
        'idanlasstyp' => false,
        'idstatus' => false,
        'wslocation' => false,
        'wsdl' => false,
        'help' => false,
    ),
    array(
        't' => 'idanlasstyp',
        's' => 'idstatus',
        'h' => 'help',
    )
);

if ($unrecognized) {
    $unrecognized = implode("\n  ", $unrecognized);
    cli_error(get_string('cliunknowoption', 'admin', $unrecognized));
}

if ($options['help']) {
    $help = "Print the evento status list, to resolve a numeric status id into its name.

Options:
-t, --idanlasstyp=ID  Restrict the list to this event type. Use 3 for a module event (MODULANLASS).
                      Default is no restriction.
-s, --idstatus=ID     Highlight this status id in the output, for example the idStatus you saw in a
                      module description.
    --wslocation=URL  Override the configured service endpoint for this call only.
    --wsdl=FILENAME   Override the configured wsdl file name for this call only. The file has to be
                      located in local/evento/wsdl.
-h, --help            Print out this help.

Example:
\$ sudo -u www-data /usr/bin/php local/evento/cli/list_status.php --idstatus=2 \\
    --wslocation=https://ws.fh-htwchur.ch/eventowstestmb/services/EventoWebservice \\
    --wsdl=evento_webservice_v1_2.wsdl
";
    cli_writeln($help);
    exit(0);
}

// Build the client, honouring the overrides.
$overrides = array();
if (!empty($options['wslocation'])) {
    $overrides['wslocation'] = (string)$options['wslocation'];
}
if (!empty($options['wsdl'])) {
    $overrides['wswsdlfilename'] = (string)$options['wsdl'];
}

$config = get_config('local_evento');
$idanlasstyp = ($options['idanlasstyp'] === false) ? null : (int)$options['idanlasstyp'];
$highlight = ($options['idstatus'] === false) ? null : (int)$options['idstatus'];

cli_writeln('== Connection ==');
cli_writeln(sprintf('  %-16s %s', 'wsdl file:', $overrides['wswsdlfilename'] ?? $config->wswsdlfilename));
cli_writeln(sprintf('  %-16s %s', 'location:', $overrides['wslocation'] ?? $config->wslocation));
cli_writeln(sprintf('  %-16s %s', 'idAnlassTyp:', is_null($idanlasstyp) ? '(all)' : $idanlasstyp));
cli_writeln('');

try {
    $client = local_evento_evento_service::create_soap_client($overrides, $config);
    $service = new local_evento_evento_service($client);
    $statuslist = $service->get_status_list($idanlasstyp);
} catch (local_evento_service_exception $ex) {
    cli_writeln('== Service call FAILED ==');
    cli_writeln(sprintf('  %-16s %s', 'operation:', $ex->operation));
    cli_writeln(sprintf('  %-16s %s', 'faultcode:', is_null($ex->faultcode) ? '(none, not a SoapFault)' : $ex->faultcode));
    cli_writeln(sprintf('  %-16s %s', 'faultstring:', (string)$ex->faultstring));
    exit(1);
}

cli_writeln('== Status list ==');
if (empty($statuslist)) {
    cli_writeln('  The service returned no status values.');
    exit(0);
}

$found = false;
cli_writeln(sprintf('  %-10s %s', 'idStatus', 'statusName'));
cli_writeln('  ' . str_repeat('-', 60));
foreach ($statuslist as $status) {
    $idstatus = $status->idStatus ?? null;
    $marker = '  ';
    if (!is_null($highlight) && (string)$idstatus === (string)$highlight) {
        $marker = '=>';
        $found = true;
    }
    cli_writeln(sprintf('%s%-10s %s', $marker, var_export($idstatus, true), (string)($status->statusName ?? '')));
}
cli_writeln('  ' . str_repeat('-', 60));
cli_writeln('  ' . count($statuslist) . ' status values.');

if (!is_null($highlight)) {
    cli_writeln('');
    if ($found) {
        cli_writeln("  The status id {$highlight} is part of this list, see the marked row.");
    } else {
        cli_writeln("  The status id {$highlight} is NOT part of this list.");
        cli_writeln('  If that id came from a module description, then the description uses its own');
        cli_writeln('  status domain and the meaning has to be clarified with the service owner.');
    }
}

exit(0);
