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

defined('MOODLE_INTERNAL') || die();

/**
 * Exception thrown when a call to the evento webservice fails technically.
 *
 * This exception means "the call could not be completed", for example because the
 * service is unreachable, the credentials are wrong or the service answered with a
 * SOAP fault. Wherever evento leaves the choice, "the requested object does not
 * exist" is not one of its meanings, the service methods return null in that case.
 *
 * Evento does not leave the choice everywhere though. Some operations answer a
 * request for a record they know nothing about with a fault instead of with an empty
 * response, getEventoModulBeschreibung among them, and they use the same faultcode
 * they use for a real server problem. Only the message tells the two apart, so it has
 * to be read: {@see self::means_notfound()} does that, and every caller which would
 * otherwise take a missing record for a broken service has to ask it.
 *
 * The caller can inspect {@see self::$faultcode} to tell different service side
 * problems apart. The faultcode is null whenever the failure did not originate from
 * a SoapFault.
 *
 * This class lives in its own file on purpose. The Moodle class loader only maps the
 * class whose name matches the file name, so an exception declared inside
 * evento_service.php would not be autoloadable and a "catch" in another plugin could
 * silently fail to match.
 *
 * @package    local_evento
 * @copyright  2026 FH Graubuenden
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class local_evento_service_exception extends moodle_exception {

    /**
     * Marks in a fault message which mean that evento knows no such record.
     *
     * Both are looked for. "DataRetrievalException" is the axis2 class evento raises
     * when a lookup found nothing, it names no operation and survives a change of
     * wording. The German wording is what getEventoModulBeschreibung sends today and
     * is kept as the second mark, so a rename of the exception class does not turn
     * every missing record into a failure on its own either.
     */
    const NOTFOUND_MARKS = array('keine modulbeschreibung gefunden', 'dataretrievalexception');

    /** @var string|null The SOAP faultcode, null if the failure was not a SoapFault. */
    public $faultcode = null;

    /** @var string|null The SOAP faultstring respectively the message of the caught throwable. */
    public $faultstring = null;

    /** @var string The name of the webservice operation that failed. */
    public $operation = '';

    /** @var Throwable|null The originally caught throwable. */
    public $previousexception = null;

    /**
     * Constructor.
     *
     * @param string $operation name of the webservice operation, e.g. "getEventoModulBeschreibung"
     * @param string|null $faultcode the SOAP faultcode, null if not available
     * @param string|null $faultstring the SOAP faultstring or the throwable message
     * @param Throwable|null $previous the originally caught throwable
     */
    public function __construct($operation, $faultcode = null, $faultstring = null, ?Throwable $previous = null) {
        $this->operation = (string)$operation;
        $this->faultcode = is_null($faultcode) ? null : (string)$faultcode;
        $this->faultstring = is_null($faultstring) ? null : (string)$faultstring;
        $this->previousexception = $previous;

        $a = new stdClass();
        $a->operation = $this->operation;
        $a->faultcode = is_null($this->faultcode) ? '-' : $this->faultcode;
        $a->faultstring = is_null($this->faultstring) ? '-' : $this->faultstring;

        parent::__construct('error_servicecall', 'local_evento', '', $a, $this->build_debuginfo($previous));
    }

    /**
     * Tells whether this fault only says that evento knows no such record.
     *
     * A true answer means the call itself worked and has to be treated like an empty
     * response, not like a failure: the record is missing, the service is fine. It
     * must never mark the service as unavailable and it must never make a sync remove
     * content it wrote earlier.
     *
     * @return bool true if the fault is an answer and not a failure
     */
    public function means_notfound(): bool {
        // The faultstring names the real cause, getMessage() is the localised wrapper
        // around it, which carries the faultstring as well.
        return self::message_means_notfound($this->faultstring ?? $this->getMessage());
    }

    /**
     * Tells whether a fault message only says that evento knows no such record.
     *
     * Takes the message itself, for callers which no longer hold the exception, for
     * example one reading a message back from a log or from a stored error.
     *
     * @param string|null $message the fault message of the call
     * @return bool true if the message is an answer and not a failure
     */
    public static function message_means_notfound($message): bool {
        $message = core_text::strtolower((string)$message);
        foreach (self::NOTFOUND_MARKS as $mark) {
            if (strpos($message, $mark) !== false) {
                return true;
            }
        }

        return false;
    }

    /**
     * Builds the debug information from the originally caught throwable.
     *
     * @param Throwable|null $previous the originally caught throwable
     * @return string|null the debug information or null if there is none
     */
    protected function build_debuginfo(?Throwable $previous) {
        if (is_null($previous)) {
            return null;
        }
        return get_class($previous) . ' in ' . $previous->getFile() . ':' . $previous->getLine();
    }
}
