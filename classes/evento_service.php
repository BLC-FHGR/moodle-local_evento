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
 * DateTime format of the evento xml dateTime types
 */
define('LOCAL_EVENTO_DATETIME_FORMAT', "Y-m-d\TH:i:s.uP");

/**
 * Class definition for the evento webservice call
 *
 * @package    local_evento
 * @copyright  2017 HTW Chur Roger Barras
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

class local_evento_evento_service {

    /**
     * Factor used to turn the float "mbVersion" into a comparable integer.
     *
     * A float comparison is unreliable, especially after a roundtrip through the
     * database. The scaled integer allows an exact equality check and keeps the
     * natural ordering of the versions.
     */
    const MB_VERSION_SCALE = 1000;

    // Plugin configuration.
    private $config;
    private $client;

    /**
     * Initialize the service keeping reference to the soap-client
     *
     * @param SoapClient $client
     */
    public function __construct($client = null) {
        $this->config = get_config('local_evento');

        if (!isset($client)) {
            $this->client = self::create_soap_client(array(), $this->config);
        } else {
            $this->client = $client;
        }
    }

    /**
     * Creates a soap client from the plugin configuration.
     *
     * The overrides exist for the cli scripts. They allow a call against another
     * endpoint or another wsdl file without touching the plugin configuration, which
     * is shared with enrol_evento and local_eventocoursecreation.
     *
     * Note that the "location" option always wins over the service address inside the
     * wsdl file.
     *
     * @param array $overrides values overriding the configuration, allowed keys are
     *                         "wslocation", "wswsdlfilename" and "wstrace"
     * @param stdClass|null $config the plugin configuration, read from the database if null
     * @return SoapClient the soap client
     * @throws local_evento_service_exception if the wsdl file is missing or the client cannot be built
     */
    public static function create_soap_client(array $overrides = array(), $config = null) {
        global $CFG;

        if (is_null($config)) {
            $config = get_config('local_evento');
        }

        // On a fresh installation none of the settings has been saved yet, so do not
        // assume that the configuration object carries the properties.
        $wsdlfilename = $overrides['wswsdlfilename'] ?? ($config->wswsdlfilename ?? '');
        // Never let an override escape the wsdl folder of the plugin.
        $wsdlfilename = basename($wsdlfilename);
        $wsdl = $CFG->dirroot . "/local/evento/wsdl/" . $wsdlfilename;
        if ($wsdlfilename === '' || !is_readable($wsdl)) {
            debugging("Error, the evento wsdl file '{$wsdl}' does not exist or is not readable.");
            throw new local_evento_service_exception('create_soap_client', null,
                "The wsdl file '{$wsdlfilename}' does not exist or is not readable.");
        }

        $options = array(
            'location' => $overrides['wslocation'] ?? ($config->wslocation ?? ''),
            'uri' => $config->wsuri ?? '',
            'trace' => $overrides['wstrace'] ?? ($config->wstrace ?? 0),
            'login' => $config->wsusername ?? '',
            'password' => $config->wspassword ?? ''
            // 'soap_version' => SOAP_1_2
        );

        try {
            return new SoapClient($wsdl, $options);
        } catch (SoapFault $fault) {
            $faultcode = isset($fault->faultcode) ? (string)$fault->faultcode : null;
            debugging("Error, could not create the evento soap client from '{$wsdl}': " . $fault->getMessage());
            throw new local_evento_service_exception('create_soap_client', $faultcode, $fault->getMessage(), $fault);
        } catch (Throwable $ex) {
            debugging("Error, could not create the evento soap client from '{$wsdl}': {$ex->getMessage()}");
            throw new local_evento_service_exception('create_soap_client', null, $ex->getMessage(), $ex);
        }
    }

    /**
     * Doing a simple init Webservice call to open the connection
     * @return boolean true if the request was successfully
     */
    public function init_call() {
        try {
            $request['theLimitationFilter2']['theMaxResultsValue'] = 10;
            $result = $this->client->listEventoAnlassTyp($request);
            return property_exists($result, "return") ? true : null;
        } catch (SoapFault $fault) {
            debugging("Error, the init webservice call to evento failed: ". $fault->__toString());
            return false;
        } catch (Exception $ex) {
            debugging("Error, the init webservice call to evento failed: {$ex->getMessage()}");
            return false;
        } catch (Throwable $ex) {
            debugging("Error, the init webservice call to evento failed: {$ex->getMessage()}");
            return false;
        }
    }

    /**
     * Obtains an event by the id-number
     * @param string $number the evento event-number like "mod.bspEA2.HS16_BS.001"
     * @return stdClass event object "EventoAnlass" definied in the wsdl
     */
    public function get_event_by_number($number) {
        // Set request filter.
        $request['theEventoAnlassFilter']['anlassNummer'] = $number;
        // To limit the response size if something went wrong.
        $request['theLimitationFilter2']['theMaxResultsValue'] = 10000;
        $result = $this->client->listEventoAnlass($request);
        return property_exists($result, "return") ? $result->return : null;
    }

    /**
     * Obtains events by filters
     * @param local_evento_eventoanlassfilter $eventoanlassfilter the evento event-number like "mod.bspEA2.HS16_BS.001"
     * @param local_evento_limitationfilter2 $limitationfilter2 filter for response limitation
     * @return array|stdClass event object "EventoAnlass" definied in the wsdl
     */
    public function get_events_by_filter(local_evento_eventoanlassfilter $eventoanlassfilter, local_evento_limitationfilter2 $limitationfilter2) {
        // Set request filter.
        !empty($eventoanlassfilter->anlassnummer) ? $request['theEventoAnlassFilter']['anlassNummer'] = $eventoanlassfilter->anlassnummer : null;
        !empty($eventoanlassfilter->idanlasstyp) ? $request['theEventoAnlassFilter']['idAnlassTyp'] = $eventoanlassfilter->idanlasstyp : null;
        !empty($eventoanlassfilter->idAnlass) ? $request['theEventoAnlassFilter']['idAnlass'] = $eventoanlassfilter->idAnlass : null;
        !empty($eventoanlassfilter->idAnlassStatus) ? $request['theEventoAnlassFilter']['idAnlassStatus'] = $eventoanlassfilter->idAnlassStatus : null;
        // To limit the response size if something went wrong.
        !empty($limitationfilter2->themaxresultvalue) ? $request['theLimitationFilter2']['theMaxResultsValue'] = $limitationfilter2->themaxresultvalue : null;
        !empty($limitationfilter2->thefromdate) ? $request['theLimitationFilter2']['theFromDate'] = $limitationfilter2->thefromdate : null;
        !empty($limitationfilter2->thetodate) ? $request['theLimitationFilter2']['theToDate'] = $limitationfilter2->thetodate : null;
        // Sort order.
        !empty($limitationfilter2->sortfield) ? $request['theLimitationFilter2']['theSortField'] = $limitationfilter2->sortfield : null;
        $result = $this->client->listEventoAnlass($request);

        return property_exists($result,"return") ? $result->return : null;
    }

    /**
     * Obtains the module description of an event by the id-number.
     *
     * The webservice answers with at most one description, it selects the matching
     * record itself. According to the service owner the description returned belongs
     * to the main event (the module), not to the individual course run.
     *
     * The return value is the untouched object from the SOAP response. Use
     * {@see self::normalize_modulbeschreibung()} to obtain comparable values.
     *
     * @param string $anlassnummer the evento event-number like "mod.boek-LEAD2.HS26_BS.001"
     * @return stdClass|null module description object "EventoModulBeschreibung" defined in the wsdl,
     *                       null if the number is empty or the service knows no description for it
     * @throws local_evento_service_exception if the webservice call itself failed
     */
    public function get_modulbeschreibung_by_number(string $anlassnummer): ?stdClass {
        $anlassnummer = trim($anlassnummer);
        // An empty number would make the service return an arbitrary or empty result, so do not even ask.
        if ($anlassnummer === '') {
            return null;
        }

        // Set request filter. This operation has no limitation filter.
        $request['anlassNummer'] = $anlassnummer;
        $result = $this->call('getEventoModulBeschreibung', $request);

        return (is_object($result) && property_exists($result, "return")) ? $result->return : null;
    }

    /**
     * Obtains the list of the evento status values.
     *
     * Useful to resolve a numeric status id into its name.
     *
     * @param int|null $idanlasstyp restrict the list to this event type, see local_evento_idanlasstyp;
     *                              default null to get all status values
     * @return array of stdClass status object "EventoStatus" defined in the wsdl
     * @throws local_evento_service_exception if the webservice call itself failed
     */
    public function get_status_list($idanlasstyp = null): array {
        // Set request filter.
        if (!empty($idanlasstyp)) {
            $request['theEventoAnlassTypFilter']['idAnlassTyp'] = $idanlasstyp;
        }
        // To limit the response size if something went wrong.
        $request['theLimitationFilter2']['theMaxResultsValue'] = 1000;
        $result = $this->call('listEventoStatus', $request);

        return self::to_array((is_object($result) && property_exists($result, "return")) ? $result->return : null);
    }

    /**
     * Executes a webservice operation and turns any failure into a service exception.
     *
     * A missing result is not a failure and is left to the calling method, an empty
     * response simply means that the service knows no matching record.
     *
     * @param string $operation name of the webservice operation
     * @param array $request the request as expected by the operation
     * @return mixed the raw response of the soap client
     * @throws local_evento_service_exception if the webservice call failed
     */
    protected function call($operation, array $request) {
        try {
            return $this->client->$operation($request);
        } catch (SoapFault $fault) {
            $faultcode = isset($fault->faultcode) ? (string)$fault->faultcode : null;
            debugging("Error, the evento webservice call '{$operation}' failed with the faultcode '"
                . ($faultcode ?? '-') . "': " . $fault->getMessage());
            throw new local_evento_service_exception($operation, $faultcode, $fault->getMessage(), $fault);
        } catch (Throwable $ex) {
            debugging("Error, the evento webservice call '{$operation}' failed: {$ex->getMessage()}");
            throw new local_evento_service_exception($operation, null, $ex->getMessage(), $ex);
        }
    }

    /**
     * Converts a module description into comparable values.
     *
     * The properties of the returned object are lowercase to follow the Moodle
     * naming style and to keep them apart from the raw webservice properties.
     *
     * @param stdClass|null $modulbeschreibung module description object "EventoModulBeschreibung"
     * @return stdClass|null object with the normalized values or null if there is nothing to normalize
     */
    public static function normalize_modulbeschreibung($modulbeschreibung): ?stdClass {
        if (!is_object($modulbeschreibung)) {
            return null;
        }

        $normalized = new stdClass();
        $normalized->idanlass = self::to_int($modulbeschreibung->idAnlass ?? null);
        $normalized->idmb = self::to_int($modulbeschreibung->idMB ?? null);
        $normalized->idstatus = self::to_int($modulbeschreibung->idStatus ?? null);

        $mbtext = $modulbeschreibung->mbText ?? null;
        $normalized->mbtext = is_null($mbtext) ? null : (string)$mbtext;

        // Keep the raw value, the scaled integer is the one to store and to compare.
        $mbversion = $modulbeschreibung->mbVersion ?? null;
        if (is_numeric($mbversion)) {
            $normalized->mbversion = (float)$mbversion;
            $normalized->mbversionscaled = (int)round($normalized->mbversion * self::MB_VERSION_SCALE);
            $normalized->mbversionstring = sprintf('%.3F', $normalized->mbversion);
        } else {
            $normalized->mbversion = null;
            $normalized->mbversionscaled = null;
            $normalized->mbversionstring = null;
        }

        // The soap client hands over a xs:dateTime as a plain string, keep it for the logs.
        $mbgueltigab = $modulbeschreibung->mbGueltigAb ?? null;
        $normalized->mbgueltigabraw = is_null($mbgueltigab) ? null : (string)$mbgueltigab;
        $normalized->mbgueltigab = self::evento_datetime_to_timestamp($mbgueltigab);

        return $normalized;
    }

    /**
     * Converts an evento xml dateTime value into a unix timestamp.
     *
     * Do not use LOCAL_EVENTO_DATETIME_FORMAT with createFromFormat() here. That format
     * requires the fractional seconds, which the service does not necessarily send.
     *
     * @param string|null $value the xml dateTime value
     * @return int|null the unix timestamp or null if the value is empty or unparsable
     */
    public static function evento_datetime_to_timestamp($value): ?int {
        if (!is_string($value) || trim($value) === '') {
            return null;
        }
        try {
            $datetime = new DateTimeImmutable($value);
        } catch (Throwable $ex) {
            debugging("Error, could not parse the evento dateTime value '{$value}': {$ex->getMessage()}");
            return null;
        }

        return $datetime->getTimestamp();
    }

    /**
     * Converts a value into an integer, keeping null as null.
     *
     * @param mixed $value
     * @return int|null the integer value or null if the value is null or not numeric
     */
    protected static function to_int($value): ?int {
        return is_numeric($value) ? (int)$value : null;
    }

    /**
     * Obtains the enrolments of an event
     * @param string $eventid the evento eventid
     * @return array of stdClass event object "EventoPersonenAnmeldung" definied in the wsdl
     */
    public function get_enrolments_by_eventid($eventid) {
        // Set request filter.
        $request['theEventoPersonenAnmeldungFilter']['idAnlass'] = $eventid;
        // To limit the response size if something went wrong.
        $request['theLimitationFilter2']['theMaxResultsValue'] = 1000;
        $result = $this->client->listEventoPersonenAnmeldung($request);

        return property_exists($result,"return") ? $result->return : null;
    }

    /**
     * Obtains the person details
     * @param string $personid the evento eventid
     * @return stdClass person object "EventoPerson" definied in the wsdl
     */
    public function get_person_by_id($personid) {
        // Set request filter.
        $request['theEventoPersonFilter']['idPerson'] = $personid;
        // To limit the response size if something went wrong.
        $request['theLimitationFilter2']['theMaxResultsValue'] = 10;
        $result = $this->client->listEventoPerson($request);

        return property_exists($result,"return") ? $result->return : null;
    }

    /**
     * Obtains the Active Directory accountdetails
     *
     * @param string $personid the evento eventid
     * @param bool $isactive true to get only active accounts; default null.
     * @param bool $isstudent true if you like to get students; default null.
     * @return stdClass person object "EventoPerson" definied in the wsdl
     */
    public function get_ad_accounts_by_evento_personid($personid, $isactive = null, $isstudent=null) {
        // Set request filter.
        $request['theADAccount']['idPerson'] = $personid;
        // To limit the response size if something went wrong.
        $request['theEventoLimitatinFilter1']['theMaxResultsValue'] = 10;
        $result = $this->client->listAdAccount($request);
        // Filter result. 
        if (property_exists($result,"return") && is_array($result->return)) {
            if (!empty($isactive)) {
                $result->return = array_filter($result->return,
                                    function ($var) {
                                        return($var->accountStatusDisabled == '0');
                                    }
                );
            }
            if (!empty($isstudent)) {
                $result->return = array_filter($result->return,
                                    function ($var) {
                                        return ($var->isStudentAccount == '1');
                                    }
                );
            }
        }

        return property_exists($result,"return") ? $result->return : null;
    }

    /**
     * Obtains the Active Directory accountdetails of students
     *
     * @param bool $isactive true to get only active accounts; default null to get all.
     * @return array of stdClass person object "EventoPerson" definied in the wsdl
     */
    public function get_student_ad_accounts($isactive = null) {
        // Set request filter.
        $request['theADAccount']['isStudentAccount'] = 1;
        // To limit the response size if something went wrong.
        $request['theEventoLimitatinFilter1']['theMaxResultsValue'] = 30000;
        $result = $this->client->listAdAccount($request);
        // Filter result.
        if (property_exists($result,"return") && is_array($result->return)) {
            if (!empty($isactive)) {
                $result->return = array_filter($result->return,
                                    function ($var) {
                                        return($var->accountStatusDisabled == '0');
                                    }
                );
            }
        }

        return property_exists($result,"return") ? $result->return : null;
    }

    /**
     * Obtains the Active Directory accountdetails of lecturers
     *
     * @param bool $isactive true to get only active accounts; default null to get all.
     * @return array of stdClass person object "EventoPerson" definied in the wsdl
     */
    public function get_lecturer_ad_accounts($isactive = null) {
        // Set request filter.
        $request['theADAccount']['isLecturerAccount'] = 1;
        // To limit the response size if something went wrong.
        $request['theEventoLimitatinFilter1']['theMaxResultsValue'] = 30000;
        $result = $this->client->listAdAccount($request);
        // Filter result.
        if (property_exists($result,"return") && is_array($result->return)) {
            if (!empty($isactive)) {
                $result->return = array_filter($result->return,
                                    function ($var) {
                                        return($var->accountStatusDisabled == '0');
                                    }
                );
            }
        }

        return property_exists($result,"return") ? $result->return : null;
    }

    /**
     * Obtains the Active Directory accountdetails of employees
     *
     * @param bool $isactive true to get only active accounts; default null to get all.
     * @return array of stdClass person object "EventoPerson" definied in the wsdl
     */
    public function get_employee_ad_accounts($isactive = null) {
        // Set request filter.
        $request['theADAccount']['isEmployeeAccount'] = 1;
        // To limit the response size if something went wrong.
        $request['theEventoLimitatinFilter1']['theMaxResultsValue'] = 30000;
        $result = $this->client->listAdAccount($request);
        // Filter result.
        if (property_exists($result,"return") && is_array($result->return)) {
            if (!empty($isactive)) {
                $result->return = array_filter($result->return,
                                    function ($var) {
                                        return($var->accountStatusDisabled == '0');
                                    }
                );
            }
        }

        return property_exists($result,"return") ? $result->return : null;
    }

    /**
     * Obtains all the Active Directory accountdetails
     * of employees, lecturers, students
     *
     * @param bool $isactive true to get only active accounts; default null to get all.
     * @return array of stdClass person object "EventoPerson" definied in the wsdl
     */
    public function get_all_ad_accounts($isactive = null) {
        // Set request filter.
        $result = array();
        $employees = self::to_array($this->get_employee_ad_accounts($isactive));
        $lecturers = self::to_array($this->get_lecturer_ad_accounts($isactive));
        $students = self::to_array($this->get_student_ad_accounts($isactive));
        if (isset($employees) && isset($lecturers)) {
            $result = array_merge($employees, $lecturers);
        }
        if (isset($students)) {
            $result = array_merge($students, $result);
        }

        return $result;
    }

    /**
     * Converts an AD SID to a shibboleth Id
     *
     * @param string $sid sid of the user from the Active Directory
     * @return string shibboleth id
     */
    public function sid_to_shibbolethid($sid) {
        return trim(str_replace($this->config->adsidprefix, "", $sid) . $this->config->adshibbolethsuffix);
    }

    /**
     * Converts a shibboleth ID to an Active Directory SID
     *
     * @param string $sishibbolethid shibbolethid of the user
     * @return string sid from the Active Directory
     */
    public function shibbolethid_to_sid($shibbolethid) {
        return trim($this->config->adsidprefix . str_replace($this->config->adshibbolethsuffix, "", $shibbolethid));
    }

    /**
     * Create an array if the value is not already one.
     *
     * @param var $value
     * @return array of the $value
     */
    public static function to_array($value) {
        $returnarray = array();
        if (is_array($value)) {
            $returnarray = $value;
        } else if (!is_null($value)) {
            $returnarray[0] = $value;
        }
        return $returnarray;
    }

}

/**
 * Class definition for filtering the listEventoAnlass
 *
 * @package    local_evento
 * @copyright  2017 HTW Chur Roger Barras
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class local_evento_eventoanlassfilter {
    /** @var string */
    public $anlassnummer = null;
    /** @var int */
    public $idanlasstyp = null;
    /** @var int */
    public $idAnlass = null;
    /** @var int */
    public $idAnlassStatus = null;
}

/**
 * Class definition for limiting the response
 *
 * @package    local_evento
 * @copyright  2017 HTW Chur Roger Barras
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class local_evento_limitationfilter2 {
    /** @var string */
    public $thefromdate = null;
    /** @var string */
    public $thetodate = null;
    /** @var int */
    public $themaxresultvalue = null;
    /** @var string */
    public $sortfield = null;
}

/**
 * Enumeration of "idAnlassTyp"
 *
 * @package    local_evento
 * @copyright  2017 HTW Chur Roger Barras
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
abstract class local_evento_idanlasstyp {
    const MODULANLASS = 3;
}