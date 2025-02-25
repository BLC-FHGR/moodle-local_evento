```php
<?php
defined('MOODLE_INTERNAL') || die();

/**
 * Comprehensive unit tests for local_evento_evento_service
 *
 * @package    local_evento
 * @category   test
 * @copyright  2024 FHGR Julien Rädler
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class local_evento_evento_service_testcase extends advanced_testcase {
    /** @var local_evento_evento_service */
    private $service;
    
    /** @var SoapClient */
    private $mockclient;
    
    /** @var stdClass */
    private $mockconfig;

    /**
     * Comprehensive mock data covering various scenarios
     */
    private const MOCK_DATA = [
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
        ]
    ];

    protected function setUp(): void {
        global $CFG;
        parent::setUp();
        $this->resetAfterTest();
        
        // Create mock configuration
        $this->mockconfig = new stdClass();
        $this->mockconfig->wslocation = 'https://test.example.com/soap';
        $this->mockconfig->wsuri = 'http://test.example.com/uri';
        $this->mockconfig->wstrace = true;
        $this->mockconfig->wsusername = 'testuser';
        $this->mockconfig->wspassword = 'testpass';
        $this->mockconfig->wswsdlfilename = 'evento_webservice_v1_1.wsdl';
        $this->mockconfig->adsidprefix = 'S-1-5-21-';
        $this->mockconfig->adshibbolethsuffix = '@fhgr.ch';
        
        // Create mock SOAP client
        $this->mockclient = $this->getMockBuilder(SoapClient::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['__call'])
            ->getMock();

        $this->mockclient->method('__call')
            ->will($this->returnCallback([$this, 'mockSoapCall']));
        
        $this->service = new local_evento_evento_service($this->mockclient, $this->mockconfig);
    }

    /**
     * Enhanced mock SOAP call handler
     */
    public function mockSoapCall($method, $params) {
        $response = new stdClass();
        
        switch ($method) {
            case 'listEventoAnlass':
                $eventNumber = $params[0]['theEventoAnlassFilter']['anlassNummer'] ?? null;
                if ($eventNumber && isset(self::MOCK_DATA['events'][$eventNumber])) {
                    $response->return = (object)self::MOCK_DATA['events'][$eventNumber];
                }
                break;

            case 'listEventoPersonenAnmeldung':
                $eventId = $params[0]['theEventoPersonenAnmeldungFilter']['idAnlass'] ?? null;
                if ($eventId && isset(self::MOCK_DATA['enrollments'][$eventId])) {
                    $response->return = array_map(function($enrollment) {
                        return (object)$enrollment;
                    }, self::MOCK_DATA['enrollments'][$eventId]);
                }
                break;

            case 'listEventoPerson':
                $personId = $params[0]['theEventoPersonFilter']['idPerson'] ?? null;
                if ($personId && isset(self::MOCK_DATA['persons'][$personId])) {
                    $response->return = (object)self::MOCK_DATA['persons'][$personId];
                }
                break;

            case 'listAdAccount':
                $accounts = [];
                
                // Handle different types of account queries
                if (isset($params[0]['theADAccount']['idPerson'])) {
                    $personId = $params[0]['theADAccount']['idPerson'];
                    if (isset(self::MOCK_DATA['accounts'][$personId])) {
                        $accounts = self::MOCK_DATA['accounts'][$personId];
                    }
                } else if (isset($params[0]['theADAccount']['isStudentAccount'])) {
                    // Return all student accounts
                    foreach (self::MOCK_DATA['accounts'] as $personAccounts) {
                        foreach ($personAccounts as $account) {
                            if ($account['isStudentAccount'] === '1') {
                                $accounts[] = $account;
                            }
                        }
                    }
                } else if (isset($params[0]['theADAccount']['isLecturerAccount'])) {
                    // Return all lecturer accounts
                    foreach (self::MOCK_DATA['accounts'] as $personAccounts) {
                        foreach ($personAccounts as $account) {
                            if ($account['isLecturerAccount'] === '1') {
                                $accounts[] = $account;
                            }
                        }
                    }
                } else if (isset($params[0]['theADAccount']['isEmployeeAccount'])) {
                    // Return all employee accounts
                    foreach (self::MOCK_DATA['accounts'] as $personAccounts) {
                        foreach ($personAccounts as $account) {
                            if ($account['isEmployeeAccount'] === '1') {
                                $accounts[] = $account;
                            }
                        }
                    }
                }
                
                if (!empty($accounts)) {
                    $response->return = array_map(function($account) {
                        return (object)$account;
                    }, $accounts);
                }
                break;

            case 'listEventoDeleted':
                $response->return = array_map(function($deleted) {
                    return (object)$deleted;
                }, self::MOCK_DATA['deleted']);
                break;

            case 'listEventoAnlassTyp':
                // Return success for connection test
                $response->return = [(object)[
                    'idAnlassTyp' => 1,
                    'anlassTypBez' => 'Test Type'
                ]];
                break;
        }

        return $response;
    }

    /**
     * Test initialization and connection
     */
    public function test_init_call() {
        $this->assertTrue($this->service->init_call(), 'Connection initialization should succeed');
    }

    /**
     * Test event retrieval
     */
    public function test_get_event_by_number() {
        // Test retrieving active event
        $event = $this->service->get_event_by_number('TEST.123.001');
        $this->assertNotNull($event);
        $this->assertEquals('Active Test Event', $event->anlassBezeichnung);
        
        // Test retrieving cancelled event
        $event = $this->service->get_event_by_number('TEST.125.001');
        $this->assertNotNull($event);
        $this->assertEquals(2, $event->idAnlassStatus);
        
        // Test non-existent event
        $event = $this->service->get_event_by_number('NONEXISTENT');
        $this->assertNull($event);
    }

    /**
     * Test enrollment retrieval
     */
    public function test_get_enrolments_by_eventid() {
        // Test active event enrollments
        $enrollments = $this->service->get_enrolments_by_eventid(123);
        $this->assertCount(3, $enrollments);
        
        // Verify different enrollment statuses
        $activeCount = 0;
        $withdrawnCount = 0;
        $waitingCount = 0;
        
        foreach ($enrollments as $enrollment) {
            switch ($enrollment->iDPAStatus) {
                case 1:
                    $activeCount++;
                    break;
                case 2:
                    $withdrawnCount++;
                    break;
                case 3:
                    $waitingCount++;
                    break;
            }
        }
        
        $this->assertEquals(1, $activeCount, 'Should have one active enrollment');
        $this->assertEquals(1, $withdrawnCount, 'Should have one withdrawn enrollment');
        $this->assertEquals(1, $waitingCount, 'Should have one waiting list enrollment');
    }

    /**
     * Test AD account retrieval by person ID (continued)
     */
    public function test_get_ad_accounts_by_evento_personid() {
        // Test active student account
        $accounts = $this->service->get_ad_accounts_by_evento_personid(456, true, true);
        $this->assertCount(1, $accounts);
        $this->assertEquals('doej', $accounts[0]->sAMAccountName);
        
        // Test inactive student account
        $accounts = $this->service->get_ad_accounts_by_evento_personid(457, true, true);
        $this->assertEmpty($accounts, 'Disabled accounts should not be returned when active filter is true');
        
        // Test student with multiple accounts
        $accounts = $this->service->get_ad_accounts_by_evento_personid(458, true, true);
        $this->assertCount(2, $accounts);
        
        // Test lecturer account
        $accounts = $this->service->get_ad_accounts_by_evento_personid(789, true, false);
        $this->assertCount(1, $accounts);
        $this->assertEquals('profp', $accounts[0]->sAMAccountName);
        $this->assertEquals('1', $accounts[0]->isLecturerAccount);
    }

    public function test_get_all_ad_accounts() {
        // Test retrieving all active accounts
        $accounts = $this->service->get_all_ad_accounts(true);
        
        // Count by account type
        $studentAccounts = array_filter($accounts, function($account) {
            return $account->isStudentAccount === '1' && $account->accountStatusDisabled === '0';
        });
        
        $lecturerAccounts = array_filter($accounts, function($account) {
            return $account->isLecturerAccount === '1' && $account->accountStatusDisabled === '0';
        });
        
        $employeeAccounts = array_filter($accounts, function($account) {
            return $account->isEmployeeAccount === '1' && $account->accountStatusDisabled === '0';
        });
        
        // Update assertions to match mock data structure
        $this->assertCount(3, $studentAccounts, 'Should have three active student accounts');
        $this->assertCount(1, $lecturerAccounts, 'Should have one active lecturer account');
        $this->assertCount(1, $employeeAccounts, 'Should have one active employee account');
    }

    /**
     * Test student AD account retrieval
     */
    public function test_get_student_ad_accounts() {
        // Test retrieving active student accounts
        $accounts = $this->service->get_student_ad_accounts(true);
        $this->assertCount(3, $accounts, 'Should have three active student accounts');
        
        // Verify all returned accounts are student accounts
        foreach ($accounts as $account) {
            $this->assertEquals('1', $account->isStudentAccount);
            $this->assertEquals('0', $account->accountStatusDisabled);
        }
        
        // Test retrieving all student accounts including inactive
        $accounts = $this->service->get_student_ad_accounts(false);
        $this->assertCount(4, $accounts, 'Should have four total student accounts including inactive');
    }

    /**
     * Test lecturer AD account retrieval
     */
    public function test_get_lecturer_ad_accounts() {
        // Test retrieving active lecturer accounts
        $accounts = $this->service->get_lecturer_ad_accounts(true);
        $this->assertCount(1, $accounts, 'Should have one active lecturer account');
        
        foreach ($accounts as $account) {
            $this->assertEquals('1', $account->isLecturerAccount);
            $this->assertEquals('0', $account->accountStatusDisabled);
        }
    }

    /**
     * Test employee AD account retrieval
     */
    public function test_get_employee_ad_accounts() {
        // Test retrieving active employee accounts
        $accounts = $this->service->get_employee_ad_accounts(true);
        $this->assertCount(1, $accounts, 'Should have one active employee account');
        
        foreach ($accounts as $account) {
            $this->assertEquals('1', $account->isEmployeeAccount);
            $this->assertEquals('0', $account->accountStatusDisabled);
        }
    }

    /**
     * Test person detail retrieval
     */
    public function test_get_person_by_id() {
        // Test retrieving active person
        $person = $this->service->get_person_by_id(456);
        $this->assertNotNull($person);
        $this->assertEquals('Doe', $person->personNachname);
        $this->assertEquals('John', $person->personVorname);
        $this->assertTrue($person->personAktiv);
        
        // Test retrieving inactive person
        $person = $this->service->get_person_by_id(457);
        $this->assertNotNull($person);
        $this->assertFalse($person->personAktiv);
        
        // Test retrieving non-existent person
        $person = $this->service->get_person_by_id(999);
        $this->assertNull($person);
    }

    /**
     * Test ID conversion methods
     */
    public function test_id_conversion() {
        $sid = 'S-1-5-21-2460181390-1097805571-3701207438-87544';
        $shibbolethId = '2460181390-1097805571-3701207438-87544@fhgr.ch';
        
        // Test SID to Shibboleth ID conversion
        $this->assertEquals(
            $shibbolethId,
            $this->service->sid_to_shibbolethid($sid)
        );
        
        // Test Shibboleth ID to SID conversion
        $this->assertEquals(
            $sid,
            $this->service->shibbolethid_to_sid($shibbolethId)
        );
    }

    // /**
    //  * Test error handling for connection failures
    //  */
    // public function test_connection_error_handling() {
    //     // Create failing mock client 
    //     $failingClient = $this->getMockBuilder(SoapClient::class)
    //         ->disableOriginalConstructor()
    //         ->getMock();
    
    //     // Configure mock to consistently throw the same error
    //     $failingClient->method('__call')
    //         ->willThrowException(new SoapFault('Client', 'Connection refused'));
        
    //     $service = new local_evento_evento_service($failingClient, $this->mockconfig);
    
    //     // First test init_call() which will retry but ultimately return false
    //     $this->assertFalse($service->init_call());
    
    //     // Now test an operation that should throw after retries
    //     $this->expectException(\moodle_exception::class);
    //     $this->expectExceptionMessage('eventoapierror');
        
    //     // This will retry MAX_RETRIES times then throw
    //     $service->get_event_by_number('TEST.123.001');
    // }

    /**
     * Test array conversion utility
     */
    public function test_to_array_conversion() {
        // Test with array input
        $array = ['test1', 'test2'];
        $this->assertEquals($array, local_evento_evento_service::to_array($array));
        
        // Test with single value
        $single = 'test';
        $this->assertEquals([$single], local_evento_evento_service::to_array($single));
        
        // Test with null
        $this->assertEquals([], local_evento_evento_service::to_array(null));
        
        // Test with object
        $obj = new stdClass();
        $obj->test = 'value';
        $this->assertEquals([$obj], local_evento_evento_service::to_array($obj));
    }
}