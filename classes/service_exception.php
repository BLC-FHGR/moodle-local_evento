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
 * SOAP fault. It does NOT mean "the requested object does not exist"; the service
 * methods return null in that case.
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
