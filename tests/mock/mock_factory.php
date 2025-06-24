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
 * Mock factory for creating test objects for the Evento API client.
 *
 * This factory provides methods to create realistic mock objects for all
 * entity types in the Evento API. Each method creates a base object with
 * realistic default values that can be customized for specific test cases.
 *
 * @package    local_evento
 * @category   test
 * @copyright  2025 FHGR Julien Rädler
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_evento\tests\mock;

defined('MOODLE_INTERNAL') || die();

/**
 * Factory class for creating mock objects for Evento API tests.
 *
 * Usage examples:
 * 
 * // Create a default event
 * $event = EventoMockFactory::create_evento_anlass();
 * 
 * // Create an event with custom properties
 * $event = EventoMockFactory::create_evento_anlass([
 *     'idAnlass' => 12345,
 *     'anlassBezeichnung' => 'My Custom Event'
 * ]);
 * 
 * // Create a collection of events
 * $response = EventoMockFactory::create_evento_anlass_collection([
 *     ['idAnlass' => 1001, 'anlassBezeichnung' => 'Event 1'],
 *     ['idAnlass' => 1002, 'anlassBezeichnung' => 'Event 2']
 * ]);
 */
class mock_factory {
    /**
     * Create a base EventoAnlass (event) object with default values.
     * 
     * This represents a course or module in the Evento system.
     *
     * @param array $customFields Fields to override defaults
     * @return \stdClass EventoAnlass object
     */
    public static function create_evento_anlass(array $customFields = []): \stdClass {
        // Base object with realistic default values
        $defaults = [
            'idAnlass' => 45685,
            'erfassung' => '2025-01-30T16:18:22.000+01:00',
            'erfassungVon' => 'zimmerka',
            'aenderung' => '2025-02-28T08:57:09.000+01:00',
            'aenderungVon' => 'blischkm',
            'anlassBezeichnung' => 'Fachvorträge',
            'anlassDatumVon' => '2025-02-17T00:00:00.000+01:00',
            'anlassDatumBis' => '2025-09-14T00:00:00.000+02:00',
            'anlassNummer' => 'mod.arch-FAVO.FS25_BS.001',
            'idAnlassKategorie' => 1,
            'idAnlassTyp' => 3,
            'idAnlassStatus' => 10230,
            'idAnlassNiveau' => 60,
            'anlassVeranstalter' => 'ba_arc',
            'anlassLeitungIdPerson' => 2360,
            'anlassVorlage' => false,
            'anlass_ECTS' => 2.0,
            'anlass_IDAnlassModul' => 38209,
            'anlass_Modulart' => 114,
            'anlass_NurFuerGruppenbildung' => false,
            // Related objects with their defaults
            'anlassKategorie' => self::create_evento_anlass_kategorie(),
            'anlassStatus' => self::create_evento_status([
                'idStatus' => 10230, 
                'statusName' => 'a.Aktiv'
            ]),
            'anlassTyp' => self::create_evento_anlass_typ(),
            'anlass_Veranstalter' => self::create_evento_benutzer(),
            'array_EventoAnlassLeitung' => self::create_evento_anlass_leitung()
        ];
        
        // Merge custom fields with defaults
        return (object)array_replace_recursive($defaults, $customFields);
    }
    
    /**
     * Create a base EventoStatus object.
     * 
     * This represents a status in the Evento system (e.g. active, inactive).
     *
     * @param array $customFields Fields to override defaults
     * @return \stdClass EventoStatus object
     */
    public static function create_evento_status(array $customFields = []): \stdClass {
        $defaults = [
            'idStatus' => 10230,
            'statusName' => 'a.Aktiv',
            'aenderung' => '2011-08-09T14:11:31.257+02:00',
            'aenderungVon' => 'clx\\feis',
            'erfassungVon' => 'auto'
        ];
        
        return (object)array_replace_recursive($defaults, $customFields);
    }
    
    /**
     * Create a base EventoAnlassKategorie object.
     * 
     * This represents a category for events (e.g. lecture, seminar).
     *
     * @param array $customFields Fields to override defaults
     * @return \stdClass EventoAnlassKategorie object
     */
    public static function create_evento_anlass_kategorie(array $customFields = []): \stdClass {
        $defaults = [
            'idAnlassKategorie' => 1,
            'anlassKategorieBez' => 'Lehrveranstaltung',
            'anlassKategorieAktiv' => true,
            'erfassung' => '1999-03-09T00:00:00.000+01:00',
            'erfassungVon' => 'rho',
            'aenderung' => '2003-08-14T16:25:27.000+02:00',
            'aenderungVon' => 'aha'
        ];
        
        return (object)array_replace_recursive($defaults, $customFields);
    }
    
    /**
     * Create a base EventoAnlassTyp object.
     * 
     * This represents a type for events (e.g. module event, study program).
     *
     * @param array $customFields Fields to override defaults
     * @return \stdClass EventoAnlassTyp object
     */
    public static function create_evento_anlass_typ(array $customFields = []): \stdClass {
        $defaults = [
            'idAnlassTyp' => 3,
            'anlassTypBez' => 'Modulanlass',
            'anlassTypAktiv' => true,
            'erfassung' => '2002-08-22T00:00:00.000+02:00',
            'erfassungVon' => 'smo',
            'aenderung' => '2005-06-15T15:04:00.000+02:00',
            'aenderungVon' => 'balzano'
        ];
        
        return (object)array_replace_recursive($defaults, $customFields);
    }
    
    /**
     * Create a base EventoBenutzer object.
     * 
     * This represents a user (organization) in the Evento system.
     *
     * @param array $customFields Fields to override defaults
     * @return \stdClass EventoBenutzer object
     */
    public static function create_evento_benutzer(array $customFields = []): \stdClass {
        $defaults = [
            'idBenutzer' => 'ba_arc',
            'benutzerName' => 'BA Architektur',
            'benutzerAktiv' => true,
            'benutzerArt' => 'O',
            'benutzerIdPerson' => 142283,
            'benutzerIstVeranstalter' => true,
            'erfassung' => '2017-03-14T10:17:40.000+01:00',
            'erfassungVon' => 'gygaxnin',
            'aenderung' => '2023-06-26T16:42:42.210+02:00',
            'aenderungVon' => 'EFHG4364'
        ];
        
        return (object)array_replace_recursive($defaults, $customFields);
    }
    
    /**
     * Create a base EventoAnlassLeitung object.
     * 
     * This represents the management/leadership of an event.
     *
     * @param array $customFields Fields to override defaults
     * @return \stdClass EventoAnlassLeitung object
     */
    public static function create_evento_anlass_leitung(array $customFields = []): \stdClass {
        $defaults = [
            'idAnlassLtg' => 100413,
            'anlassLtgIdAnlass' => 45685,
            'anlassLtgIdPerson' => 2360,
            'anlassLtgIdAnlassLtgRolle' => 2,
            'erfassung' => '2025-01-30T16:19:40.000+01:00',
            'erfassungVon' => 'zimmerka',
            'aenderung' => '2025-01-30T16:19:40.000+01:00',
            'aenderungVon' => 'zimmerka',
            'anlassLeitungRolle' => self::create_evento_anlass_leitung_rolle(),
            'anlassLtgPerson' => self::create_evento_person([
                'idPerson' => 2360,
                'personVorname' => 'Daniel',
                'personNachname' => 'Walser'
            ])
        ];
        
        return (object)array_replace_recursive($defaults, $customFields);
    }
    
    /**
     * Create a base EventoAnlassLeitungRolle object.
     * 
     * This represents a role for event management (e.g. main leader).
     *
     * @param array $customFields Fields to override defaults
     * @return \stdClass EventoAnlassLeitungRolle object
     */
    public static function create_evento_anlass_leitung_rolle(array $customFields = []): \stdClass {
        $defaults = [
            'idAnlassLtgRolle' => 2,
            'anlassLtgRolleBezeichnung' => 'Hauptleitung',
            'anlassLtgRolleBezeichnungKrz' => 'Hauptleitung',
            'anlassLtgRolleBezeichnungSort' => 100,
            'anlassLtgRolleAktiv' => true,
            'erfassung' => '2004-06-29T14:16:00.000+02:00',
            'erfassungVon' => 'balzano',
            'aenderung' => '2024-12-12T15:33:49.873+01:00',
            'aenderungVon' => 'casuttku'
        ];
        
        return (object)array_replace_recursive($defaults, $customFields);
    }
    
    /**
     * Create a base EventoPerson object.
     * 
     * This represents a person in the Evento system.
     *
     * @param array $customFields Fields to override defaults
     * @return \stdClass EventoPerson object
     */
    public static function create_evento_person(array $customFields = []): \stdClass {
        $defaults = [
            'idPerson' => 2360,
            'personVorname' => 'Daniel',
            'personNachname' => 'Walser',
            'personTitel' => 'Prof.',
            'person_AkadTitel' => 'Dipl. Arch. ETH',
            'personeMail' => 'daniel.walser@fhgr.ch',
            'personTelefon1' => '+41 44  251 40 97',
            'personTelefon2' => '+41 81 286 24 64',
            'person_Mobile' => '+41 76 316 40 97',
            'personAktiv' => true,
            'personSex' => 'M',
            'personAnrede' => 'Herr',
            'personBriefanrede' => 'Sehr geehrter Herr Walser',
            'personAdresse1' => 'Limmatstrasse 256',
            'personPlz' => '8005',
            'personOrt' => 'Zürich',
            'personLand' => 'CH',
            'idPersonStatus' => 30040,
            'personenStatus' => self::create_evento_status([
                'idStatus' => 30040, 
                'statusName' => 'ps.Aktiv'
            ]),
            'array_adressen' => [],
            'array_personenGruppierung' => [],
            'array_personenanmeldungen' => []
        ];
        
        return (object)array_replace_recursive($defaults, $customFields);
    }
    
    /**
     * Create a base EventoPersonenAnmeldung object.
     * 
     * This represents a registration/enrollment in the Evento system.
     *
     * @param array $customFields Fields to override defaults
     * @return \stdClass EventoPersonenAnmeldung object
     */
    public static function create_evento_personen_anmeldung(array $customFields = []): \stdClass {
        $defaults = [
            'iDAnmeldung' => 633320,
            'aenderung' => '2023-08-29T09:58:21.013+02:00',
            'aenderungVon' => 'looserco',
            'erfassung' => '2023-01-30T08:29:40.000+01:00',
            'erfassungVon' => 'glarnerl',
            'idAnlass' => 38661,
            'iDPAStatus' => 20270,
            'idPerson' => 161261,
            'personenAnmeldungStatus' => self::create_evento_status([
                'idStatus' => 20270, 
                'statusName' => 'aA.Erfolgreich teilgenommen'
            ])
        ];
        
        return (object)array_replace_recursive($defaults, $customFields);
    }
    
    /**
     * Create a base ADAccount object.
     * 
     * This represents an Active Directory account in the Evento system.
     *
     * @param array $customFields Fields to override defaults
     * @return \stdClass ADAccount object
     */
    public static function create_ad_account(array $customFields = []): \stdClass {
        $defaults = [
            'accountStatusDisabled' => 0,
            'changed' => '2025-02-09T19:19:05.000+01:00',
            'created' => '2017-08-31T09:16:44.000+02:00',
            'description' => null,
            'hasSeveralAccounts' => null,
            'idPerson' => 142665,
            'isEmployeeAccount' => 0,
            'isLecturerAccount' => 0,
            'isStudentAccount' => 1,
            'objectSid' => 'S-1-5-21-2460181390-1097805571-3701207438-39403',
            'sAMAccountName' => 'riedmafloria'
        ];
        
        return (object)array_replace_recursive($defaults, $customFields);
    }
    
    /**
     * Create a base EventoOE object.
     * 
     * This represents an organizational unit in the Evento system.
     *
     * @param array $customFields Fields to override defaults
     * @return \stdClass EventoOE object
     */
    public static function create_evento_oe(array $customFields = []): \stdClass {
        $defaults = [
            'IDBenutzer' => 'ba_arc',
            'OE' => 'Bachelor Architektur',
            'aenderung' => '2023-11-15T14:32:21.000+01:00',
            'aenderungVon' => 'casuttku',
            'benutzerName' => 'BA Architektur',
            'benutzerVorname' => null,
            'erfassung' => '2017-03-14T10:17:40.000+01:00',
            'erfassungVon' => 'gygaxnin',
            'isActiv' => true
        ];
        
        return (object)array_replace_recursive($defaults, $customFields);
    }
    
    /**
     * Create a response with a collection of EventoAnlass objects.
     *
     * @param array $customEvents Array of customized event data
     * @return \stdClass Response with multiple events
     */
    public static function create_evento_anlass_collection(array $customEvents = []): \stdClass {
        $response = (object)['return' => []];
        
        // If no custom events provided, create three default events with different IDs
        if (empty($customEvents)) {
            $response->return = [
                self::create_evento_anlass(['idAnlass' => 45685, 'anlassBezeichnung' => 'Fachvorträge']),
                self::create_evento_anlass(['idAnlass' => 45686, 'anlassBezeichnung' => 'Entwurf I']),
                self::create_evento_anlass(['idAnlass' => 45687, 'anlassBezeichnung' => 'Konstruktion'])
            ];
        } else {
            // Create events from custom data
            foreach ($customEvents as $eventData) {
                $response->return[] = self::create_evento_anlass($eventData);
            }
        }
        
        return $response;
    }
    
    /**
     * Create a response with a collection of EventoPersonenAnmeldung objects.
     *
     * @param array $customEnrollments Array of customized enrollment data
     * @return \stdClass Response with multiple enrollments
     */
    public static function create_evento_personen_anmeldung_collection(array $customEnrollments = []): \stdClass {
        $response = (object)['return' => []];
        
        // If no custom enrollments provided, create default enrollments
        if (empty($customEnrollments)) {
            $response->return = [
                self::create_evento_personen_anmeldung(['iDAnmeldung' => 633320, 'idPerson' => 161261]),
                self::create_evento_personen_anmeldung(['iDAnmeldung' => 633399, 'idPerson' => 160702]),
                self::create_evento_personen_anmeldung([
                    'iDAnmeldung' => 633401, 
                    'idPerson' => 160166,
                    'iDPAStatus' => 20275,
                    'personenAnmeldungStatus' => self::create_evento_status([
                        'idStatus' => 20275, 
                        'statusName' => 'aA.Nicht erfolgreich teilgenommen'
                    ])
                ])
            ];
        } else {
            // Create enrollments from custom data
            foreach ($customEnrollments as $enrollmentData) {
                $response->return[] = self::create_evento_personen_anmeldung($enrollmentData);
            }
        }
        
        return $response;
    }
    
    /**
     * Create a response with a collection of EventoPerson objects.
     *
     * @param array $customPersons Array of customized person data
     * @return \stdClass Response with multiple persons
     */
    public static function create_evento_person_collection(array $customPersons = []): \stdClass {
        $response = (object)['return' => []];
        
        // If no custom persons provided, create default persons
        if (empty($customPersons)) {
            $response->return = [
                self::create_evento_person(['idPerson' => 2360, 'personVorname' => 'Daniel', 'personNachname' => 'Walser']),
                self::create_evento_person(['idPerson' => 2358, 'personVorname' => 'Christian', 'personNachname' => 'Wagner']),
                self::create_evento_person(['idPerson' => 115088, 'personVorname' => 'Peter', 'personNachname' => 'Kühne'])
            ];
        } else {
            // Create persons from custom data
            foreach ($customPersons as $personData) {
                $response->return[] = self::create_evento_person($personData);
            }
        }
        
        return $response;
    }
    
    /**
     * Create a response with a collection of ADAccount objects.
     *
     * @param array $customAccounts Array of customized account data
     * @return \stdClass Response with multiple accounts
     */
    public static function create_ad_account_collection(array $customAccounts = []): \stdClass {
        $response = (object)['return' => []];
        
        // If no custom accounts provided, create default accounts
        if (empty($customAccounts)) {
            $response->return = [
                self::create_ad_account(['sAMAccountName' => 'riedmafloria', 'idPerson' => 142665, 'isStudentAccount' => 1]),
                self::create_ad_account(['sAMAccountName' => 'danilovaiana', 'idPerson' => 141837, 'isStudentAccount' => 1]),
                self::create_ad_account([
                    'sAMAccountName' => 'walserdaniel', 
                    'idPerson' => 2360,
                    'isStudentAccount' => 0,
                    'isLecturerAccount' => 1
                ])
            ];
        } else {
            // Create accounts from custom data
            foreach ($customAccounts as $accountData) {
                $response->return[] = self::create_ad_account($accountData);
            }
        }
        
        return $response;
    }
    
    /**
     * Create a response with a collection of EventoOE objects.
     *
     * @param array $customOEs Array of customized OE data
     * @return \stdClass Response with multiple OEs
     */
    public static function create_evento_oe_collection(array $customOEs = []): \stdClass {
        $response = (object)['return' => []];
        
        // If no custom OEs provided, create default OEs
        if (empty($customOEs)) {
            $response->return = [
                self::create_evento_oe(['IDBenutzer' => 'ba_arc', 'OE' => 'Bachelor Architektur']),
                self::create_evento_oe(['IDBenutzer' => 'ma_arc', 'OE' => 'Master Architektur']),
                self::create_evento_oe(['IDBenutzer' => 'ba_multimedia', 'OE' => 'Bachelor Multimedia Production'])
            ];
        } else {
            // Create OEs from custom data
            foreach ($customOEs as $oeData) {
                $response->return[] = self::create_evento_oe($oeData);
            }
        }
        
        return $response;
    }
    
    /**
     * Create a session timeout response.
     *
     * @return \stdClass Session timeout response
     */
    public static function create_session_timeout_response(): \stdClass {
        return (object)[
            'faultcode' => 'Client',
            'faultstring' => 'Session Expired',
            'detail' => (object)[
                'message' => 'Your session has expired. Please log in again.'
            ]
        ];
    }
    
    /**
     * Create an error response.
     *
     * @param string $code Error code
     * @param string $message Error message
     * @param string $detail Detailed error message
     * @return \stdClass Error response
     */
    public static function create_error_response(
        string $code = 'Server', 
        string $message = 'Internal Server Error',
        string $detail = 'An unexpected error occurred'
    ): \stdClass {
        return (object)[
            'faultcode' => $code,
            'faultstring' => $message,
            'detail' => (object)[
                'message' => $detail
            ]
        ];
    }
    
    /**
     * Create a SOAP fault.
     *
     * @param string $code Fault code
     * @param string $message Fault message
     * @return \SoapFault Soap fault object
     */
    public static function create_soap_fault(string $code = 'Server', string $message = 'Internal error'): \SoapFault {
        return new \SoapFault($code, $message);
    }
    
    /**
     * Create an empty response.
     *
     * @return \stdClass Empty response
     */
    public static function create_empty_response(): \stdClass {
        return (object)['return' => []];
    }
}