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
    // Plugin configuration.
    private $config;
    private $client;

    /**
     * Initialize the service keeping reference to the soap-client
     *
     * @param SoapClient $client
     */
    public function __construct($client = null) {
        global $CFG;

        $this->config = get_config('local_evento');

        $options = array(
            'location' => $this->config->wslocation,
            'uri' => $this->config->wsuri,
            'trace' => $this->config->wstrace,
            'login' => $this->config->wsusername,
            'password' => $this->config->wspassword
            // 'soap_version' => SOAP_1_2
        );
        $wsdl = $CFG->dirroot . "/local/evento/wsdl/" . $this->config->wswsdlfilename;

        if (!isset($client)) {
            $this->client = new SoapClient($wsdl, $options);
        } else {
            $this->client = $client;
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