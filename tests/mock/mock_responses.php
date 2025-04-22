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
 * Mock data for Evento API testing.
 *
 * @package    local_evento
 * @category   test
 * @copyright  2025 FHGR Julien Rädler
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_evento\tests\mock;

defined('MOODLE_INTERNAL') || die();

/**
 * Mock data trait to be used across all tests.
 */
trait mock_responses {
    /**
     * Comprehensive mock data covering various scenarios
     */
    protected static $MOCK_DATA = [
        'events' => [
            // Regular active event
            'TEST.123.001' => [
                'idAnlass' => 123,
                'anlassNummer' => 'TEST.123.001',
                'anlassBezeichnung' => 'Active Test Event',
                'anlassDatumVon' => '2024-02-01',
                'anlassDatumBis' => '2024-07-31',
                'idAnlassStatus' => 1,
                'array_EventoAnlassLeitung' => [
                    [
                        'idAnlassLtg' => 1,
                        'anlassLtgIdPerson' => 789,
                        'anlassLtgIdAnlassLtgRolle' => 1
                    ]
                ]
            ],
            // Event in the past
            'TEST.124.001' => [
                'idAnlass' => 124,
                'anlassNummer' => 'TEST.124.001',
                'anlassBezeichnung' => 'Past Event',
                'anlassDatumVon' => '2023-08-01',
                'anlassDatumBis' => '2024-01-31',
                'idAnlassStatus' => 1
            ],
            // Cancelled event
            'TEST.125.001' => [
                'idAnlass' => 125,
                'anlassNummer' => 'TEST.125.001',
                'anlassBezeichnung' => 'Cancelled Event',
                'anlassDatumVon' => '2024-02-01',
                'anlassDatumBis' => '2024-07-31',
                'idAnlassStatus' => 2
            ]
        ],
        'enrollments' => [
            // Active event enrollments
            123 => [
                // Active student enrollment
                [
                    'idAnlass' => 123,
                    'idPerson' => 456,
                    'iDPAStatus' => 1,
                    'iDAnmeldung' => 1001
                ],
                // Withdrawn student enrollment
                [
                    'idAnlass' => 123,
                    'idPerson' => 457,
                    'iDPAStatus' => 2,
                    'iDAnmeldung' => 1002
                ],
                // Waiting list student enrollment
                [
                    'idAnlass' => 123,
                    'idPerson' => 458,
                    'iDPAStatus' => 3,
                    'iDAnmeldung' => 1003
                ]
            ],
            // Past event enrollments
            124 => [
                [
                    'idAnlass' => 124,
                    'idPerson' => 456,
                    'iDPAStatus' => 1,
                    'iDAnmeldung' => 1004
                ]
            ]
        ],
        'persons' => [
            // Active student
            456 => [
                'idPerson' => 456,
                'personNachname' => 'Doe',
                'personVorname' => 'John',
                'personeMail' => 'john.doe@example.com',
                'personAktiv' => true
            ],
            // Inactive student
            457 => [
                'idPerson' => 457,
                'personNachname' => 'Smith',
                'personVorname' => 'Jane',
                'personeMail' => 'jane.smith@example.com',
                'personAktiv' => false
            ],
            // Student with multiple accounts
            458 => [
                'idPerson' => 458,
                'personNachname' => 'Multiple',
                'personVorname' => 'Max',
                'personeMail' => 'max.multiple@example.com',
                'personAktiv' => true
            ],
            // Teacher
            789 => [
                'idPerson' => 789,
                'personNachname' => 'Prof',
                'personVorname' => 'Peter',
                'personeMail' => 'peter.prof@example.com',
                'personAktiv' => true
            ]
        ],
        'accounts' => [
            // Student with single account
            456 => [
                [
                    'idPerson' => 456,
                    'accountStatusDisabled' => '0',
                    'isStudentAccount' => '1',
                    'isLecturerAccount' => '0',
                    'isEmployeeAccount' => '0',
                    'sAMAccountName' => 'doej',
                    'objectSid' => 'S-1-5-21-2460181390-1097805571-3701207438-87544'
                ]
            ],
            // Student with disabled account
            457 => [
                [
                    'idPerson' => 457,
                    'accountStatusDisabled' => '1',
                    'isStudentAccount' => '1',
                    'isLecturerAccount' => '0',
                    'isEmployeeAccount' => '0',
                    'sAMAccountName' => 'smithj',
                    'objectSid' => 'S-1-5-21-2460181390-1097805571-3701207438-87545'
                ]
            ],
            // Student with multiple accounts
            458 => [
                [
                    'idPerson' => 458,
                    'accountStatusDisabled' => '0',
                    'isStudentAccount' => '1',
                    'isLecturerAccount' => '0',
                    'isEmployeeAccount' => '0',
                    'sAMAccountName' => 'multiplem1',
                    'objectSid' => 'S-1-5-21-2460181390-1097805571-3701207438-87546'
                ],
                [
                    'idPerson' => 458,
                    'accountStatusDisabled' => '0',
                    'isStudentAccount' => '1',
                    'isLecturerAccount' => '0',
                    'isEmployeeAccount' => '0',
                    'sAMAccountName' => 'multiplem2',
                    'objectSid' => 'S-1-5-21-2460181390-1097805571-3701207438-87547'
                ]
            ],
            // Teacher account
            789 => [
                [
                    'idPerson' => 789,
                    'accountStatusDisabled' => '0',
                    'isStudentAccount' => '0',
                    'isLecturerAccount' => '1',
                    'isEmployeeAccount' => '1',
                    'sAMAccountName' => 'profp',
                    'objectSid' => 'S-1-5-21-2460181390-1097805571-3701207438-87548'
                ]
            ]
        ],
        'deleted' => [
            [
                'idDeleted' => 1,
                'idDeletedTyp' => 1,
                'deletedIntKey1' => 126,
                'deletedAt' => '2024-01-15 10:00:00',
                'deletedBy' => 'system'
            ]
        ],
        'anlasstyp' => [
            [
                'idAnlassTyp' => 1,
                'anlassTypBez' => 'Test Type'
            ],
            [
                'idAnlassTyp' => 2,
                'anlassTypBez' => 'Lecture'
            ],
            [
                'idAnlassTyp' => 3,
                'anlassTypBez' => 'Workshop'
            ]
        ],
        'organizational_units' => [
            [
                'IDBenutzer' => 'DEPT1',
                'OE' => 'Department 1',
                'benutzerName' => 'Department',
                'benutzerVorname' => 'One',
                'isActiv' => true
            ],
            [
                'IDBenutzer' => 'DEPT2',
                'OE' => 'Department 2',
                'benutzerName' => 'Department',
                'benutzerVorname' => 'Two',
                'isActiv' => true
            ]
        ]
    ];

    /**
     * Create a mock soap client that returns preconfigured responses.
     *
     * @return \SoapClient Mock SOAP client
     */
    protected function createMockSoapClient() {
        // Include the MockSoapClient class
        require_once(__DIR__ . '/../mock/mock_soap_client.php');
        
        $options = [
            'uri' => 'http://service.webservice.htwchur.ch'
        ];
        
        return new \local_evento\tests\mock\mock_soap_client(
            $options,
            [$this, 'mockSoapCall']
        );
    }

    /**
     * Mock SOAP call handler.
     *
     * @param string $method The SOAP method called
     * @param array $params The parameters passed to the SOAP call
     * @return \stdClass Response object mimicking SOAP response
     */
    public function mockSoapCall($method, $args) {
        // In PHP SOAP client, the first argument is the method name and the second argument
        // contains the parameters for the SOAP call in their native structure
        echo "DEBUG mockSoapCall method: $method\n";
        echo "DEBUG mockSoapCall args: " . json_encode($args) . "\n";
    
        $response = new \stdClass();
        
        // For simplicity, let's extract just the parameters we need
        switch ($method) {
            case 'listEventoAnlass':
                // From the WSDL: <xs:element name="listEventoAnlass">
                //                     <xs:complexType>
                //                         <xs:sequence>
                //                             <xs:element minOccurs="0" name="theEventoAnlassFilter" nillable="true" type="ax22:EventoAnlass"/>
                //                             <xs:element minOccurs="0" name="theLimitationFilter2" nillable="true" type="ax22:EventoLimitationFilter2"/>
                //                         </xs:sequence>
                //                     </xs:complexType>
                //                 </xs:element>
                
                if (isset($args[0]['theEventoAnlassFilter'])) {
                    $filter = $args[0]['theEventoAnlassFilter'];
                    
                    if (isset($filter['anlassNummer'])) {
                        echo "DEBUG Looking for anlassNummer: " . $filter['anlassNummer'] . "\n";
                        
                        if (isset(self::$MOCK_DATA['events'][$filter['anlassNummer']])) {
                            echo "DEBUG Found event in mock data\n";
                            $response->return = [(object)self::$MOCK_DATA['events'][$filter['anlassNummer']]];
                        } else {
                            echo "DEBUG Event not found in mock data\n";
                            $response->return = [];
                        }
                    } else {
                        // Return all events if no specific filter
                        echo "DEBUG No specific anlassNummer, returning all events\n";
                        $response->return = array_map(function($event) {
                            return (object)$event;
                        }, array_values(self::$MOCK_DATA['events']));
                    }
                } else {
                    // Return all events if no filter
                    echo "DEBUG No filter, returning all events\n";
                    $response->return = array_map(function($event) {
                        return (object)$event;
                    }, array_values(self::$MOCK_DATA['events']));
                }
                break;
                
            case 'listEventoPerson':
                // From the WSDL: <xs:element name="listEventoPerson">
                //                     <xs:complexType>
                //                         <xs:sequence>
                //                             <xs:element minOccurs="0" name="theEventoPersonFilter" nillable="true" type="ax22:EventoPerson"/>
                //                             <xs:element minOccurs="0" name="theLimitationFilter2" nillable="true" type="ax22:EventoLimitationFilter2"/>
                //                         </xs:sequence>
                //                     </xs:complexType>
                //                 </xs:element>
                
                if (isset($args[0]['theEventoPersonFilter'])) {
                    $filter = $args[0]['theEventoPersonFilter'];
                    
                    if (isset($filter['idPerson'])) {
                        echo "DEBUG Looking for idPerson: " . $filter['idPerson'] . "\n";
                        
                        if (isset(self::$MOCK_DATA['persons'][$filter['idPerson']])) {
                            echo "DEBUG Found person in mock data\n";
                            $response->return = [(object)self::$MOCK_DATA['persons'][$filter['idPerson']]];
                        } else {
                            echo "DEBUG Person not found in mock data\n";
                            $response->return = [];
                        }
                    } else {
                        // Return all persons if no specific filter
                        echo "DEBUG No specific idPerson, returning all persons\n";
                        $response->return = array_map(function($person) {
                            return (object)$person;
                        }, array_values(self::$MOCK_DATA['persons']));
                    }
                } else {
                    // Return all persons if no filter
                    echo "DEBUG No filter, returning all persons\n";
                    $response->return = array_map(function($person) {
                        return (object)$person;
                    }, array_values(self::$MOCK_DATA['persons']));
                }
                break;
                
            case 'listEventoPersonenAnmeldung':
                // From the WSDL: <xs:element name="listEventoPersonenAnmeldung">
                //                     <xs:complexType>
                //                         <xs:sequence>
                //                             <xs:element minOccurs="0" name="theEventoPersonenAnmeldungFilter" nillable="true" type="ax22:EventoPersonenAnmeldung"/>
                //                             <xs:element minOccurs="0" name="theLimitationFilter2" nillable="true" type="ax22:EventoLimitationFilter2"/>
                //                         </xs:sequence>
                //                     </xs:complexType>
                //                 </xs:element>
                
                if (isset($args[0]['theEventoPersonenAnmeldungFilter'])) {
                    $filter = $args[0]['theEventoPersonenAnmeldungFilter'];
                    
                    if (isset($filter['idAnlass'])) {
                        echo "DEBUG Looking for enrollments with idAnlass: " . $filter['idAnlass'] . "\n";
                        
                        if (isset(self::$MOCK_DATA['enrollments'][$filter['idAnlass']])) {
                            echo "DEBUG Found enrollments in mock data\n";
                            $response->return = array_map(function($enrollment) {
                                return (object)$enrollment;
                            }, self::$MOCK_DATA['enrollments'][$filter['idAnlass']]);
                        } else {
                            echo "DEBUG Enrollments not found in mock data\n";
                            $response->return = [];
                        }
                    } else {
                        // Return empty if no event ID
                        $response->return = [];
                    }
                } else {
                    // Return empty if no filter
                    $response->return = [];
                }
                break;
                
            case 'listAdAccount':
                // From the WSDL: <xs:element name="listAdAccount">
                //                     <xs:complexType>
                //                         <xs:sequence>
                //                             <xs:element minOccurs="0" name="theADAccount" nillable="true" type="ax24:ADAccount"/>
                //                             <xs:element minOccurs="0" name="theEventoLimitatinFilter1" nillable="true" type="ax22:EventoLimitationFilter1"/>
                //                         </xs:sequence>
                //                     </xs:complexType>
                //                 </xs:element>
                
                if (isset($args[0]['theADAccount'])) {
                    $filter = $args[0]['theADAccount'];
                    
                    if (isset($filter['idPerson'])) {
                        echo "DEBUG Looking for accounts with idPerson: " . $filter['idPerson'] . "\n";
                        
                        if (isset(self::$MOCK_DATA['accounts'][$filter['idPerson']])) {
                            echo "DEBUG Found accounts in mock data\n";
                            $response->return = array_map(function($account) {
                                return (object)$account;
                            }, self::$MOCK_DATA['accounts'][$filter['idPerson']]);
                        } else {
                            echo "DEBUG Accounts not found in mock data\n";
                            $response->return = [];
                        }
                    } else {
                        // Handle other filters like isStudentAccount, isLecturerAccount, etc.
                        $accounts = [];
                        
                        if (isset($filter['isStudentAccount']) && $filter['isStudentAccount'] == '1') {
                            // Collect all student accounts
                            foreach (self::$MOCK_DATA['accounts'] as $personAccounts) {
                                foreach ($personAccounts as $account) {
                                    if ($account['isStudentAccount'] === '1') {
                                        $accounts[] = $account;
                                    }
                                }
                            }
                        }
                        
                        // Similar handling for other account filters
                        
                        if (!empty($accounts)) {
                            $response->return = array_map(function($account) {
                                return (object)$account;
                            }, $accounts);
                        } else {
                            $response->return = [];
                        }
                    }
                } else {
                    // Return empty if no filter
                    $response->return = [];
                }
                break;
                
            case 'listEventoOE':
                // From the WSDL: <xs:element name="listEventoOE">
                //                     <xs:complexType>
                //                         <xs:sequence>
                //                             <xs:element minOccurs="0" name="theEventoOEFilter" nillable="true" type="ax22:EventoOE"/>
                //                             <xs:element minOccurs="0" name="theLimitationFilter2" nillable="true" type="ax22:EventoLimitationFilter2"/>
                //                         </xs:sequence>
                //                     </xs:complexType>
                //                 </xs:element>
                
                // Return all organizational units regardless of filter for simplicity
                echo "DEBUG Returning all organizational units\n";
                $response->return = array_map(function($unit) {
                    return (object)$unit;
                }, self::$MOCK_DATA['organizational_units']);
                break;
    
            case 'listAdAccount':
                $accounts = [];
                
                if (isset($params[0]['theADAccount']['idPerson'])) {
                    $personId = $params[0]['theADAccount']['idPerson'];
                    if (isset(self::$MOCK_DATA['accounts'][$personId])) {
                        $accounts = self::$MOCK_DATA['accounts'][$personId];
                    }
                } 
                // Additional account logic remains the same...
                
                if (!empty($accounts)) {
                    $response->return = array_map(function($account) {
                        return (object)$account;
                    }, $accounts);
                } else {
                    $response->return = [];
                }
                break;
    
            case 'listEventoDeleted':
                $response->return = array_map(function($deleted) {
                    return (object)$deleted;
                }, self::$MOCK_DATA['deleted']);
                break;
                
            default:
                // Default to empty array for any unhandled method
                $response->return = [];
        }
    
        echo "DEBUG mockSoapCall returning: " . json_encode($response) . "\n";
        return $response;
    }

    /**
     * Create standard mock configuration for tests.
     *
     * @return \stdClass Mock config object
     */
    protected function createMockConfig() {
        $config = new \stdClass();
        $config->wslocation = 'https://test.example.com/soap';
        $config->wsuri = 'http://test.example.com/uri';
        $config->wstrace = true;
        $config->wsusername = 'testuser';
        $config->wspassword = 'testpass';
        $config->wswsdlfilename = 'evento_webservice_v1_1.wsdl';
        $config->adsidprefix = 'S-1-5-21-';
        $config->adshibbolethsuffix = '@fhgr.ch';
        return $config;
    }
}