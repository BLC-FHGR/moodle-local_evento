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

namespace local_evento;

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->dirroot . '/local/evento/tests/fixtures/test_soap_client.php');

/**
 * Tests for the evento webservice encapsulation.
 *
 * The soap client is injected as a test double, so none of these tests talks to evento.
 *
 * @package    local_evento
 * @copyright  2026 FH Graubuenden
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @covers     \local_evento_evento_service
 */
final class evento_service_test extends \advanced_testcase {

    /** @var test_soap_client the double injected into the service last created. */
    protected $client;

    /**
     * Builds a service with a soap client double answering a single operation.
     *
     * @param string $operation the operation the double answers
     * @param mixed $returnvalue the value the double returns, a Throwable is thrown instead
     * @return \local_evento_evento_service the service under test
     */
    protected function create_service(string $operation, $returnvalue) {
        $this->set_plugin_config();
        $this->client = new test_soap_client([$operation => $returnvalue]);

        return new \local_evento_evento_service($this->client);
    }

    /**
     * Stores a minimal plugin configuration.
     *
     * On a fresh test database none of the settings has been saved, so the tests that
     * touch the configuration have to provide it themselves.
     *
     * @return void
     */
    protected function set_plugin_config(): void {
        set_config('wslocation', 'https://ws.example.invalid/services/EventoWebservice', 'local_evento');
        set_config('wswsdlfilename', 'evento_webservice_v1_2.wsdl', 'local_evento');
        set_config('wsuri', '', 'local_evento');
        set_config('wsusername', 'tester', 'local_evento');
        set_config('wspassword', 'secret', 'local_evento');
        set_config('wstrace', 0, 'local_evento');
    }

    /**
     * A regular answer is handed over untouched.
     */
    public function test_get_modulbeschreibung_by_number_regular_response(): void {
        $this->resetAfterTest(true);

        $expected = (object)[
            'idAnlass' => 12345,
            'idMB' => 678,
            'idStatus' => 2,
            'mbGueltigAb' => '2026-09-15T00:00:00.000+02:00',
            'mbText' => '<p>Modulbeschreibung</p>',
            'mbVersion' => 1.1,
        ];
        $service = $this->create_service('getEventoModulBeschreibung', (object)['return' => $expected]);

        $result = $service->get_modulbeschreibung_by_number('mod.boek-LEAD2.HS26_BS.001');

        $this->assertSame($expected, $result);
        $this->assertSame(1, $this->client->count_calls('getEventoModulBeschreibung'));
    }

    /**
     * The event number is passed on trimmed and as the only request element.
     */
    public function test_get_modulbeschreibung_by_number_builds_the_request(): void {
        $this->resetAfterTest(true);

        $service = $this->create_service('getEventoModulBeschreibung', (object)['return' => new \stdClass()]);

        $service->get_modulbeschreibung_by_number('  mod.boek-LEAD2.HS26_BS.001  ');

        $call = $this->client->get_call();
        $this->assertSame('getEventoModulBeschreibung', $call['operation']);
        // No limitation filter, and the number arrives trimmed.
        $this->assertSame(['anlassNummer' => 'mod.boek-LEAD2.HS26_BS.001'], $call['request']);
    }

    /**
     * A response without a "return" element means "no description", not an error.
     */
    public function test_get_modulbeschreibung_by_number_empty_response(): void {
        $this->resetAfterTest(true);

        $service = $this->create_service('getEventoModulBeschreibung', new \stdClass());

        $this->assertNull($service->get_modulbeschreibung_by_number('mod.boek-LEAD2.HS26_BS.001'));
    }

    /**
     * A nulled response is tolerated as well.
     */
    public function test_get_modulbeschreibung_by_number_null_response(): void {
        $this->resetAfterTest(true);

        $service = $this->create_service('getEventoModulBeschreibung', null);

        $this->assertNull($service->get_modulbeschreibung_by_number('mod.boek-LEAD2.HS26_BS.001'));
    }

    /**
     * A SoapFault becomes a service exception which carries the faultcode.
     */
    public function test_get_modulbeschreibung_by_number_soapfault(): void {
        $this->resetAfterTest(true);

        $fault = new \SoapFault('soap:Server', 'Anlass nicht gefunden');
        $service = $this->create_service('getEventoModulBeschreibung', $fault);

        try {
            $service->get_modulbeschreibung_by_number('mod.boek-LEAD2.HS26_BS.001');
            $this->fail('A local_evento_service_exception was expected.');
        } catch (\local_evento_service_exception $ex) {
            $this->assertSame('soap:Server', $ex->faultcode);
            $this->assertSame('Anlass nicht gefunden', $ex->faultstring);
            $this->assertSame('getEventoModulBeschreibung', $ex->operation);
            $this->assertSame($fault, $ex->previousexception);
        }
        $this->assertDebuggingCalled();
    }

    /**
     * Any other throwable becomes a service exception without a faultcode.
     */
    public function test_get_modulbeschreibung_by_number_throwable(): void {
        $this->resetAfterTest(true);

        $service = $this->create_service('getEventoModulBeschreibung', new \RuntimeException('connection refused'));

        try {
            $service->get_modulbeschreibung_by_number('mod.boek-LEAD2.HS26_BS.001');
            $this->fail('A local_evento_service_exception was expected.');
        } catch (\local_evento_service_exception $ex) {
            $this->assertNull($ex->faultcode);
            $this->assertSame('connection refused', $ex->faultstring);
        }
        $this->assertDebuggingCalled();
    }

    /**
     * An empty event number returns null without calling the webservice.
     *
     * @dataProvider empty_number_provider
     * @param string $number the event number to test
     */
    public function test_get_modulbeschreibung_by_number_empty_number(string $number): void {
        $this->resetAfterTest(true);

        $service = $this->create_service('getEventoModulBeschreibung', new \stdClass());

        $this->assertNull($service->get_modulbeschreibung_by_number($number));
        // The point of this test: no soap call at all.
        $this->assertSame(0, $this->client->count_calls());
    }

    /**
     * Data provider for the empty event numbers.
     *
     * @return array of event numbers that must not reach the webservice
     */
    public static function empty_number_provider(): array {
        return [
            'empty string' => [''],
            'spaces' => ['   '],
            'tab and newline' => ["\t\n"],
        ];
    }

    /**
     * The status list is always returned as an array.
     */
    public function test_get_status_list(): void {
        $this->resetAfterTest(true);

        $status = (object)['idStatus' => 2, 'statusName' => 'freigegeben'];
        $service = $this->create_service('listEventoStatus', (object)['return' => [$status]]);
        $this->assertSame([$status], $service->get_status_list());

        // A single object is wrapped, an empty response becomes an empty array.
        $service = $this->create_service('listEventoStatus', (object)['return' => $status]);
        $this->assertSame([$status], $service->get_status_list());

        $service = $this->create_service('listEventoStatus', new \stdClass());
        $this->assertSame([], $service->get_status_list());
    }

    /**
     * A full module description is normalized into comparable values.
     */
    public function test_normalize_modulbeschreibung(): void {
        $raw = (object)[
            'idAnlass' => 12345,
            'idMB' => 678,
            'idStatus' => 2,
            'mbGueltigAb' => '2026-09-15T00:00:00.000+02:00',
            'mbText' => '<p>Modulbeschreibung</p>',
            'mbVersion' => 1.1,
        ];

        $normalized = \local_evento_evento_service::normalize_modulbeschreibung($raw);

        $this->assertSame(12345, $normalized->idanlass);
        $this->assertSame(678, $normalized->idmb);
        $this->assertSame(2, $normalized->idstatus);
        $this->assertSame('<p>Modulbeschreibung</p>', $normalized->mbtext);
        $this->assertSame(1.1, $normalized->mbversion);
        $this->assertSame(1100, $normalized->mbversionscaled);
        $this->assertSame('1.100', $normalized->mbversionstring);
        $this->assertSame('2026-09-15T00:00:00.000+02:00', $normalized->mbgueltigabraw);
        $this->assertSame(strtotime('2026-09-15T00:00:00+02:00'), $normalized->mbgueltigab);
    }

    /**
     * Every element is optional and nillable, so all of them may be missing.
     */
    public function test_normalize_modulbeschreibung_empty(): void {
        $this->assertNull(\local_evento_evento_service::normalize_modulbeschreibung(null));

        $normalized = \local_evento_evento_service::normalize_modulbeschreibung(new \stdClass());

        foreach (get_object_vars($normalized) as $property => $value) {
            $this->assertNull($value, "Property {$property} should be null.");
        }
    }

    /**
     * The scaled version allows an exact comparison where a float comparison is unsafe.
     */
    public function test_normalize_modulbeschreibung_version_scaling(): void {
        $scaled = function ($version) {
            $normalized = \local_evento_evento_service::normalize_modulbeschreibung((object)['mbVersion' => $version]);
            return $normalized->mbversionscaled;
        };

        $this->assertSame(2000, $scaled(2.0));
        $this->assertSame(1100, $scaled(1.1));
        $this->assertSame(10250, $scaled(10.25));
        // A float built by arithmetic is not exactly 0.3, the scaled value still is.
        $this->assertSame(300, $scaled(0.1 + 0.2));
        // The value survives a roundtrip through a string, as it would through the database.
        $this->assertSame($scaled(1.1), $scaled((float)(string)1.1));
    }

    /**
     * The dateTime conversion copes with the variants the service may send.
     */
    public function test_evento_datetime_to_timestamp(): void {
        $this->assertSame(strtotime('2026-09-15T00:00:00+02:00'),
            \local_evento_evento_service::evento_datetime_to_timestamp('2026-09-15T00:00:00.000+02:00'));
        // Without the fractional seconds, which LOCAL_EVENTO_DATETIME_FORMAT would require.
        $this->assertSame(strtotime('2026-09-15T00:00:00+02:00'),
            \local_evento_evento_service::evento_datetime_to_timestamp('2026-09-15T00:00:00+02:00'));
        $this->assertNull(\local_evento_evento_service::evento_datetime_to_timestamp(null));
        $this->assertNull(\local_evento_evento_service::evento_datetime_to_timestamp(''));
        $this->assertNull(\local_evento_evento_service::evento_datetime_to_timestamp('   '));

        // An unparsable value is logged and swallowed, it must not break a sync.
        $this->assertNull(\local_evento_evento_service::evento_datetime_to_timestamp('not a date'));
        $this->assertDebuggingCalled();
    }

    /**
     * The wsdl override must not be able to escape the plugin folder.
     */
    public function test_create_soap_client_rejects_a_missing_wsdl(): void {
        $this->resetAfterTest(true);
        $this->set_plugin_config();

        $this->expectException(\local_evento_service_exception::class);
        \local_evento_evento_service::create_soap_client(['wswsdlfilename' => '../../../config.php']);
    }

    /**
     * The shipped wsdl files are loadable and carry the expected operations.
     *
     * @dataProvider wsdl_provider
     * @param string $filename the wsdl file inside the wsdl folder
     * @param bool $hasmodulbeschreibung whether the file is expected to know getEventoModulBeschreibung
     */
    public function test_shipped_wsdl_files(string $filename, bool $hasmodulbeschreibung): void {
        $this->resetAfterTest(true);
        $this->set_plugin_config();

        $client = \local_evento_evento_service::create_soap_client(['wswsdlfilename' => $filename]);
        $functions = $client->__getFunctions();

        $this->assertNotEmpty(preg_grep('/listEventoAnlass\(/', $functions));
        $this->assertSame($hasmodulbeschreibung,
            !empty(preg_grep('/getEventoModulBeschreibung\(/', $functions)));
    }

    /**
     * Data provider for the shipped wsdl files.
     *
     * @return array of file name and expectation
     */
    public static function wsdl_provider(): array {
        return [
            'current' => ['evento_webservice_v1_2.wsdl', true],
            'rollback target' => ['evento_webservice_v1_1.wsdl', false],
        ];
    }
}
