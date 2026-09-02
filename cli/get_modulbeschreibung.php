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
 * Evento cli tool to inspect the operation getEventoModulBeschreibung.
 *
 * The script prints the raw response including the php data types so that the
 * contract of the webservice can be verified against the test system.
 *
 * Notes:
 *   - it is required to use the web server account when executing PHP CLI scripts
 *   - you need to change the "www-data" to match the apache user account
 *   - use "su" if "sudo" not available
 *
 * Usage help:
 * $ sudo -u www-data /usr/bin/php local/evento/cli/get_modulbeschreibung.php --help
 *
 * @package    local_evento
 * @copyright  2026 FH Graubuenden
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

define('CLI_SCRIPT', true);

require(__DIR__.'/../../../config.php');
require_once("$CFG->libdir/clilib.php");

/**
 * Prints the last soap request and response of a client.
 *
 * @param SoapClient|null $client the traced soap client
 * @return void
 */
function local_evento_cli_print_trace($client) {
    cli_writeln('== Raw soap trace ==');
    if (!($client instanceof SoapClient)) {
        cli_writeln('  No client available.');
        return;
    }
    $request = $client->__getLastRequest();
    $response = $client->__getLastResponse();
    if (is_null($request) && is_null($response)) {
        cli_writeln('  Empty. Tracing is off or no call has been made.');
        return;
    }
    cli_writeln('-- request --');
    cli_writeln((string)$request);
    cli_writeln('-- response --');
    cli_writeln((string)$response);
}

// Now get cli options.
list($options, $unrecognized) = cli_get_params(
    array(
        'anlassnummer' => false,
        'wslocation' => false,
        'wsdl' => false,
        'raw' => false,
        'compare' => false,
        'help' => false,
    ),
    array(
        'a' => 'anlassnummer',
        'r' => 'raw',
        'c' => 'compare',
        'h' => 'help',
    )
);

if ($unrecognized) {
    $unrecognized = implode("\n  ", $unrecognized);
    cli_error(get_string('cliunknowoption', 'admin', $unrecognized));
}

if ($options['help'] || empty($options['anlassnummer'])) {
    $help = "Read a module description from the evento webservice and print it including the data types.

Options:
-a, --anlassnummer=NUMBER  Required. The evento event-number, e.g. mod.boek-LEAD2.HS26_BS.001
    --wslocation=URL       Override the configured service endpoint for this call only. Use this to
                           query the test system without changing the setting local_evento/wslocation,
                           which is shared with enrol_evento and local_eventocoursecreation.
    --wsdl=FILENAME        Override the configured wsdl file name for this call only. The file has to
                           be located in local/evento/wsdl.
-r, --raw                  Additionally print the raw soap request and response xml. Enables tracing
                           for this call even if the setting local_evento/wstrace is off.
-c, --compare              Additionally read the event itself and compare the ids, to check whether the
                           description belongs to the main event (the module) or to the course run.
-h, --help                 Print out this help.

Example:
\$ sudo -u www-data /usr/bin/php local/evento/cli/get_modulbeschreibung.php \\
    --anlassnummer=mod.boek-LEAD2.HS26_BS.001 \\
    --wslocation=https://ws.fh-htwchur.ch/eventowstestmb/services/EventoWebservice \\
    --wsdl=evento_webservice_v1_2.wsdl --raw --compare
";
    if (!$options['help']) {
        cli_writeln('Missing required option --anlassnummer.');
        cli_writeln('');
        cli_writeln($help);
        exit(1);
    }
    cli_writeln($help);
    exit(0);
}

$anlassnummer = trim((string)$options['anlassnummer']);

// Build the client, honouring the overrides.
$overrides = array();
if (!empty($options['wslocation'])) {
    $overrides['wslocation'] = (string)$options['wslocation'];
}
if (!empty($options['wsdl'])) {
    $overrides['wswsdlfilename'] = (string)$options['wsdl'];
}
if (!empty($options['raw'])) {
    $overrides['wstrace'] = 1;
}

$config = get_config('local_evento');

cli_writeln('== Connection ==');
cli_writeln(sprintf('  %-16s %s', 'wsdl file:',
    $overrides['wswsdlfilename'] ?? $config->wswsdlfilename));
cli_writeln(sprintf('  %-16s %s', 'location:',
    $overrides['wslocation'] ?? $config->wslocation));
cli_writeln(sprintf('  %-16s %s', 'anlassNummer:', $anlassnummer));
cli_writeln('');

try {
    $client = local_evento_evento_service::create_soap_client($overrides, $config);
    $service = new local_evento_evento_service($client);
} catch (local_evento_service_exception $ex) {
    cli_error('Could not create the soap client: ' . $ex->faultstring);
}

// Fetch the module description.
try {
    $modulbeschreibung = $service->get_modulbeschreibung_by_number($anlassnummer);
} catch (local_evento_service_exception $ex) {
    cli_writeln('== Service call FAILED ==');
    cli_writeln(sprintf('  %-16s %s', 'operation:', $ex->operation));
    cli_writeln(sprintf('  %-16s %s', 'faultcode:', is_null($ex->faultcode) ? '(none, not a SoapFault)' : $ex->faultcode));
    cli_writeln(sprintf('  %-16s %s', 'faultstring:', (string)$ex->faultstring));
    cli_writeln(sprintf('  %-16s %s', 'debuginfo:', (string)$ex->debuginfo));
    if (!empty($options['raw'])) {
        local_evento_cli_print_trace($client);
    }
    exit(1);
}

cli_writeln('== Raw response ==');
if (is_null($modulbeschreibung)) {
    cli_writeln('  null');
    cli_writeln('  The call succeeded but the response carried no "return" element.');
    cli_writeln('  The service knows no module description for this event number.');
} else {
    cli_writeln('  php type: ' . get_class($modulbeschreibung));
    // Every element of EventoModulBeschreibung is optional and nillable, so report
    // the absent ones explicitly instead of silently printing nothing.
    $expected = array('idAnlass', 'idMB', 'idStatus', 'mbGueltigAb', 'mbText', 'mbVersion');
    $present = array_keys(get_object_vars($modulbeschreibung));
    foreach (array_unique(array_merge($expected, $present)) as $property) {
        if (!property_exists($modulbeschreibung, $property)) {
            cli_writeln(sprintf('  %-14s %-10s %s', $property, '(absent)', 'element not sent by the service'));
            continue;
        }
        $value = $modulbeschreibung->$property;
        $printable = is_scalar($value) ? (string)$value : var_export($value, true);
        if (strlen($printable) > 300) {
            $printable = substr($printable, 0, 300) . '... [' . strlen($printable) . ' bytes total]';
        }
        // Indent the wrapped lines of a multi line value below the value column.
        $printable = str_replace("\n", "\n" . str_repeat(' ', 27), $printable);
        cli_writeln(sprintf('  %-14s %-10s %s', $property, gettype($value), $printable));
    }
    foreach (array_diff($present, $expected) as $unexpected) {
        cli_writeln('  NOTE: the service sent the undocumented element "' . $unexpected . '".');
    }
}
cli_writeln('');

// Show what the caller would work with.
$normalized = local_evento_evento_service::normalize_modulbeschreibung($modulbeschreibung);
cli_writeln('== Normalized values ==');
if (is_null($normalized)) {
    cli_writeln('  null');
} else {
    foreach (get_object_vars($normalized) as $property => $value) {
        $printable = is_null($value) ? 'null' : (is_scalar($value) ? (string)$value : var_export($value, true));
        if (strlen($printable) > 300) {
            $printable = substr($printable, 0, 300) . '... [' . strlen($printable) . ' bytes total]';
        }
        cli_writeln(sprintf('  %-18s %-10s %s', $property, gettype($value), str_replace("\n", ' ', $printable)));
    }
    if (!is_null($normalized->mbgueltigab)) {
        $now = time();
        $delta = $normalized->mbgueltigab - $now;
        $readable = userdate($normalized->mbgueltigab, '', 99, false, false);
        cli_writeln('');
        cli_writeln(sprintf('  %-18s %s', 'mbGueltigAb is:', $readable));
        if ($delta > 0) {
            cli_writeln(sprintf('  %-18s IN THE FUTURE, in %s. The service answers for future dated descriptions.',
                'verdict:', format_time($delta)));
        } else {
            cli_writeln(sprintf('  %-18s in the past, %s ago.', 'verdict:', format_time(abs($delta))));
        }
    }
}
cli_writeln('');

// Cross check the assumption that the description belongs to the main event.
if (!empty($options['compare'])) {
    cli_writeln('== Cross check against listEventoAnlass ==');
    try {
        $events = local_evento_evento_service::to_array($service->get_event_by_number($anlassnummer));
    } catch (Throwable $ex) {
        $events = array();
        cli_writeln('  Could not read the event: ' . $ex->getMessage());
    }
    if (empty($events)) {
        cli_writeln('  No event found for this number.');
    } else {
        foreach ($events as $event) {
            $eventidanlass = $event->idAnlass ?? null;
            $idanlassmodul = $event->anlass_IDAnlassModul ?? null;
            cli_writeln(sprintf('  %-22s %s', 'anlassNummer:', (string)($event->anlassNummer ?? '')));
            cli_writeln(sprintf('  %-22s %s', 'idAnlass:', var_export($eventidanlass, true)));
            cli_writeln(sprintf('  %-22s %s', 'anlass_IDAnlassModul:', var_export($idanlassmodul, true)));
            cli_writeln(sprintf('  %-22s %s', 'idAnlassStatus:', var_export($event->idAnlassStatus ?? null, true)));
            if (!is_null($normalized) && !is_null($normalized->idanlass)) {
                if ((string)$normalized->idanlass === (string)$eventidanlass) {
                    cli_writeln('  verdict: the description carries the idAnlass of the event itself.');
                } else if (!is_null($idanlassmodul) && (string)$normalized->idanlass === (string)$idanlassmodul) {
                    cli_writeln('  verdict: the description carries anlass_IDAnlassModul, so it belongs to the main event.');
                } else {
                    cli_writeln('  verdict: the idAnlass of the description matches neither the event nor its module.');
                    cli_writeln('           Ask the service owner what it refers to.');
                }
            }
            cli_writeln('');
        }
    }
}

if (!empty($options['raw'])) {
    local_evento_cli_print_trace($client);
}

exit(0);
