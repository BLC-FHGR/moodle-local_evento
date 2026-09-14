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

/**
 * Tests for the exception of a failed webservice call.
 *
 * The point of interest is the one fault which is no failure at all: evento reports a
 * record it knows nothing about with a fault, using the faultcode of a real server
 * problem, so the message is all a caller has to tell the two apart.
 *
 * @package    local_evento
 * @copyright  2026 FH Graubuenden
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @covers     \local_evento_service_exception
 */
final class service_exception_test extends \advanced_testcase {

    /** The fault evento sends for a module it has no description for. */
    const NOTFOUND_FAULT = 'EventoHibernateManager -> getEventoModulBeschreibung -> '
        . 'org.apache.axis2.dataretrieval.DataRetrievalException: Keine Modulbeschreibung gefunden';

    /**
     * The fault of a missing record is recognized on the exception itself.
     */
    public function test_means_notfound(): void {
        $ex = new \local_evento_service_exception('getEventoModulBeschreibung', 'soapenv:Server',
            self::NOTFOUND_FAULT);

        $this->assertTrue($ex->means_notfound());
    }

    /**
     * The faultcode says nothing, evento uses the one of a server problem for it.
     */
    public function test_means_notfound_ignores_the_faultcode(): void {
        $ex = new \local_evento_service_exception('getEventoModulBeschreibung', 'HTTP',
            self::NOTFOUND_FAULT);

        $this->assertTrue($ex->means_notfound());
    }

    /**
     * A real problem of the webservice stays a failure.
     */
    public function test_means_notfound_keeps_real_failures_apart(): void {
        $ex = new \local_evento_service_exception('getEventoModulBeschreibung', 'HTTP',
            'Could not connect to host');

        $this->assertFalse($ex->means_notfound());
    }

    /**
     * A failure which carried no message at all is no answer either.
     */
    public function test_means_notfound_without_a_faultstring(): void {
        $ex = new \local_evento_service_exception('getEventoModulBeschreibung');

        $this->assertFalse($ex->means_notfound());
    }

    /**
     * Both marks work on their own, the wording as well as the exception class.
     *
     * @dataProvider notfound_messages_provider
     * @param string $message the fault message
     * @param bool $expected whether the message means that evento knows no such record
     */
    public function test_message_means_notfound(string $message, bool $expected): void {
        $this->assertSame($expected, \local_evento_service_exception::message_means_notfound($message));
    }

    /**
     * Data provider for {@see self::test_message_means_notfound()}.
     *
     * @return array of message and expectation
     */
    public static function notfound_messages_provider(): array {
        return array(
            'the whole fault' => array(self::NOTFOUND_FAULT, true),
            'the wording alone' => array('Keine Modulbeschreibung gefunden', true),
            'the wording in lower case' => array('keine modulbeschreibung gefunden', true),
            'the exception class alone' => array('DataRetrievalException: nothing', true),
            'an unreachable service' => array('Could not connect to host', false),
            'a broken endpoint' => array('Unable to parse URL', false),
            'an unknown operation' => array('The endpoint reference (EPR) for the Operation not found is '
                . 'http://example.org/EventoWebservice', false),
            'no message' => array('', false),
        );
    }

    /**
     * A message of null is no answer, it is simply nothing to go by.
     */
    public function test_message_means_notfound_null(): void {
        $this->assertFalse(\local_evento_service_exception::message_means_notfound(null));
    }
}
