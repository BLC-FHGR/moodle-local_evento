<?php
defined('MOODLE_INTERNAL') || die();
/**
 * DateTime format of the evento xml dateTime types
 */
define('LOCAL_EVENTO_DATETIME_FORMAT', "Y-m-d\TH:i:s.uP");

/**
 * Class definition for the evento webservice optimized for batch processing
 * during nightly synchronization jobs
 *
 * @package    local_evento
 * @copyright  2024 FHGR Julien Rädler
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class local_evento_evento_service {
    /** @var object Plugin configuration */
    private $config;
    
    /** @var SoapClient SOAP client instance */
    private $client;

    /** @var progress_trace Trace for debugging */
    private $trace;
    
    /** @var int Maximum retries for SOAP requests */
    private const MAX_RETRIES = 3;
    
    /** @var int Batch size for large data retrievals */
    private const BATCH_SIZE = 1000;
    
    /** @var array Mapping of error codes to detailed messages */
    private const ERROR_MESSAGES = [
        'SOAP_CONNECT' => 'Unable to connect to Evento service: %s',
        'SOAP_AUTH' => 'Authentication failed with Evento service: %s',
        'SOAP_REQUEST' => 'Error processing Evento service request: %s',
        'INVALID_RESPONSE' => 'Invalid response received from Evento service: %s'
    ];

    /**
     * Initialize the service keeping reference to the soap-client
     *
     * @param SoapClient|null $client Optional SOAP client for testing
     * @param object|null $config Optional config for testing
     */
    public function __construct($client = null, $config = null, $trace = null) {
        global $CFG;
        
        $this->config = $config ?? get_config('local_evento');
        $this->trace = $trace ?? new null_progress_trace();
        
        if (!$client) {
            $options = [
                'location' => $this->config->wslocation,
                'uri' => $this->config->wsuri,
                'trace' => $this->config->wstrace,
                'login' => $this->config->wsusername,
                'password' => $this->config->wspassword,
                'connection_timeout' => 30,
                'features' => SOAP_SINGLE_ELEMENT_ARRAYS,
                'exceptions' => true
            ];
            
            $wsdl = $CFG->dirroot . "/local/evento/wsdl/" . $this->config->wswsdlfilename;
            
            try {
                $this->client = new SoapClient($wsdl, $options);
            } catch (SoapFault $fault) {
                $this->handle_soap_error($fault, self::ERROR_MESSAGES['SOAP_CONNECT']);
            }
        } else {
            $this->client = $client;
        }
    }

    /**
     * Set trace instance
     * 
     * @param progress_trace $trace Trace instance
     */
    public function set_trace($trace) {
        $this->trace = $trace;
    }

    /**
     * Execute a SOAP request with retry logic and detailed logging
     *
     * @param string $method SOAP method to call
     * @param array $params Parameters for the SOAP call
     * @param string $context Additional context for error messages
     * @return mixed Response from SOAP call
     * @throws moodle_exception
     */
    public function execute_soap_request($method, array $params, $context = '') {
        $attempts = 0;
        $starttime = microtime(true);
        
        do {
            try {
                $attempts++;
                $response = $this->client->$method($params);
                
                // Log performance metrics for monitoring
                $duration = microtime(true) - $starttime;
                $this->log_sync_operation($method, $duration, $attempts, $context);
                
                return $response;
            } catch (SoapFault $fault) {
                // On final attempt, throw error. Otherwise, retry
                if ($attempts >= self::MAX_RETRIES) {
                    $this->handle_soap_error($fault, self::ERROR_MESSAGES['SOAP_REQUEST'], $context);
                }
                // Exponential backoff with jitter to prevent thundering herd
                $delay = pow(2, $attempts - 1) + (rand(0, 1000) / 1000);
                mtrace("Evento API retry {$attempts} for {$method} after {$delay}s delay");
                sleep((int)$delay);
            }
        } while ($attempts < self::MAX_RETRIES);
    }

    /**
     * Handle SOAP errors with detailed logging
     *
     * @param SoapFault $fault
     * @param string $messageTemplate
     * @param string $context
     * @throws moodle_exception
     */
    private function handle_soap_error(SoapFault $fault, $messageTemplate, $context = '') {
        $errordetails = [
            'code' => $fault->faultcode,
            'string' => $fault->faultstring,
            'detail' => property_exists($fault, 'detail') ? $fault->detail : '',
            'context' => $context,
            'last_request' => $this->client->__getLastRequest(),
            'last_response' => $this->client->__getLastResponse()
        ];
        
        // Log detailed error for administrators
        mtrace('Evento SOAP Error: ' . json_encode($errordetails, JSON_PRETTY_PRINT));
        
        // Throw exception with user-friendly message
        $message = sprintf($messageTemplate, $fault->faultstring);
        throw new moodle_exception('eventoapierror', 'local_evento', '', $message);
    }

    /**
     * Log synchronization operation details
     *
     * @param string $operation
     * @param float $duration
     * @param int $attempts
     * @param string $context
     */
    private function log_sync_operation($operation, $duration, $attempts, $context = '') {
        $message = sprintf(
            'Evento sync operation: %s, Context: %s, Duration: %.4f seconds, Attempts: %d',
            $operation,
            $context,
            $duration,
            $attempts
        );
        mtrace($message);
    }

    /**
     * Get event by ID with improved error handling
     * 
     * @param int $eventid The evento event ID
     * @return stdClass|null Event object
     */
    public function get_event_by_id($eventid) {
        try {
            $request = [
                'theEventoAnlassFilter' => ['idAnlass' => $eventid],
                'theLimitationFilter2' => ['theMaxResultsValue' => 10]
            ];
            
            $result = $this->execute_soap_request(
                'listEventoAnlass',
                $request,
                "Fetching event ID {$eventid}"
            );
            
            if (!property_exists($result, "return")) {
                mtrace("No event found for ID {$eventid}");
                return null;
            }
            
            return is_array($result->return) ? reset($result->return) : $result->return;
            
        } catch (Exception $e) {
            mtrace("Error fetching event {$eventid}: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Process large datasets in batches
     *
     * @param array $items Items to process
     * @param callable $processor Function to process each batch
     * @param string $context Context for logging
     * @return array Processing results
     */
    private function process_in_batches(array $items, callable $processor, $context = '') {
        $results = [];
        $batches = array_chunk($items, self::BATCH_SIZE);
        
        foreach ($batches as $index => $batch) {
            mtrace(sprintf(
                'Processing batch %d/%d for %s (%d items)',
                $index + 1,
                count($batches),
                $context,
                count($batch)
            ));
            
            try {
                $results = array_merge($results, $processor($batch));
            } catch (Exception $e) {
                mtrace(sprintf('Error processing batch %d: %s', $index + 1, $e->getMessage()));
                // Continue with next batch instead of failing completely
                continue;
            }
        }
        
        return $results;
    }

    /**
     * Get all enrollments for an event with batched processing
     * 
     * @param string $eventid
     * @return array
     */
    public function get_enrolments_by_eventid($eventid) {
        $request = [
            'theEventoPersonenAnmeldungFilter' => ['idAnlass' => $eventid],
            'theLimitationFilter2' => ['theMaxResultsValue' => self::BATCH_SIZE]
        ];
        
        $result = $this->execute_soap_request(
            'listEventoPersonenAnmeldung',
            $request,
            "Getting enrollments for event {$eventid}"
        );
        
        return property_exists($result, "return") ? self::to_array($result->return) : [];
    }

    /**
     * Optimized batch retrieval of AD accounts
     * 
     * @param bool $isactive
     * @return array
     */
    public function get_all_ad_accounts($isactive = null) {
        mtrace('Starting batch retrieval of AD accounts...');
        
        $accounts = [];
        $seen = []; // Track accounts we've already added by objectSid
        $types = ['employee', 'lecturer', 'student'];
        
        foreach ($types as $type) {
            mtrace("Retrieving {$type} accounts...");
            $method = "get_{$type}_ad_accounts";
            $typeAccounts = $this->$method($isactive);
            
            if ($typeAccounts) {
                // Only add accounts we haven't seen before
                foreach ($typeAccounts as $account) {
                    if (!isset($seen[$account->objectSid])) {
                        $accounts[] = $account;
                        $seen[$account->objectSid] = true;
                    }
                }
            }
        }
        
        mtrace(sprintf('Retrieved %d total accounts', count($accounts)));
        return $accounts;
    }
    
    /**
     * Get active student AD accounts with optimized batch processing
     * 
     * @param bool $isactive Only return active accounts if true
     * @return array
     */
    public function get_student_ad_accounts($isactive = null) {
        $context = "student accounts" . ($isactive ? " (active only)" : "");
        mtrace("Retrieving {$context}...");
        
        $request = [
            'theADAccount' => ['isStudentAccount' => 1],
            'theEventoLimitatinFilter1' => ['theMaxResultsValue' => 30000]
        ];
        
        $result = $this->execute_soap_request('listAdAccount', $request, "Fetching {$context}");
        
        if (!property_exists($result, "return")) {
            mtrace("No student accounts found");
            return [];
        }

        $accounts = self::to_array($result->return);
        
        if ($isactive) {
            $accounts = array_filter($accounts, function($account) {
                return $account->accountStatusDisabled == '0';
            });
        }
        
        mtrace(sprintf("Retrieved %d student accounts", count($accounts)));
        return $accounts;
    }

    /**
     * Get lecturer AD accounts with optimized batch processing
     * 
     * @param bool $isactive Only return active accounts if true
     * @return array
     */
    public function get_lecturer_ad_accounts($isactive = null) {
        $context = "lecturer accounts" . ($isactive ? " (active only)" : "");
        mtrace("Retrieving {$context}...");
        
        $request = [
            'theADAccount' => ['isLecturerAccount' => 1],
            'theEventoLimitatinFilter1' => ['theMaxResultsValue' => 30000]
        ];
        
        $result = $this->execute_soap_request('listAdAccount', $request, "Fetching {$context}");
        
        if (!property_exists($result, "return")) {
            mtrace("No lecturer accounts found");
            return [];
        }

        $accounts = self::to_array($result->return);
        
        if ($isactive) {
            $accounts = array_filter($accounts, function($account) {
                return $account->accountStatusDisabled == '0';
            });
        }
        
        mtrace(sprintf("Retrieved %d lecturer accounts", count($accounts)));
        return $accounts;
    }

    /**
     * Get employee AD accounts with optimized batch processing
     * 
     * @param bool $isactive Only return active accounts if true
     * @return array
     */
    public function get_employee_ad_accounts($isactive = null) {
        $context = "employee accounts" . ($isactive ? " (active only)" : "");
        mtrace("Retrieving {$context}...");
        
        $request = [
            'theADAccount' => ['isEmployeeAccount' => 1],
            'theEventoLimitatinFilter1' => ['theMaxResultsValue' => 30000]
        ];
        
        $result = $this->execute_soap_request('listAdAccount', $request, "Fetching {$context}");
        
        if (!property_exists($result, "return")) {
            mtrace("No employee accounts found");
            return [];
        }

        $accounts = self::to_array($result->return);
        
        if ($isactive) {
            $accounts = array_filter($accounts, function($account) {
                return $account->accountStatusDisabled == '0';
            });
        }
        
        mtrace(sprintf("Retrieved %d employee accounts", count($accounts)));
        return $accounts;
    }

    /**
     * Get person details with improved error handling
     * 
     * @param string $personid The evento person ID
     * @return stdClass|null Person object
     */
    public function get_person_by_id($personid) {
        try {
            $request = [
                'theEventoPersonFilter' => ['idPerson' => $personid],
                'theLimitationFilter2' => ['theMaxResultsValue' => 10]
            ];
            
            $result = $this->execute_soap_request(
                'listEventoPerson',
                $request,
                "Fetching person {$personid}"
            );
            
            return property_exists($result, "return") ? $result->return : null;
            
        } catch (Exception $e) {
            mtrace("Error fetching person {$personid}: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Get AD accounts by evento person ID with improved error handling
     * 
     * @param string $personid The evento person ID
     * @param bool|null $isactive Filter by active status
     * @param bool|null $isstudent Filter by student status
     * @return array AD account objects
     */
    public function get_ad_accounts_by_evento_personid($personid, $isactive = null, $isstudent = null) {
        $context = sprintf(
            "person %s accounts%s%s",
            $personid,
            $isactive ? " (active only)" : "",
            $isstudent ? " (students only)" : ""
        );
        
        try {
            $request = [
                'theADAccount' => ['idPerson' => $personid],
                'theEventoLimitatinFilter1' => ['theMaxResultsValue' => 10]
            ];
            
            $result = $this->execute_soap_request(
                'listAdAccount',
                $request,
                "Fetching {$context}"
            );
            
            if (!property_exists($result, "return")) {
                return [];
            }
            
            $accounts = self::to_array($result->return);
            
            // Apply filters
            if ($isactive || $isstudent) {
                $accounts = array_filter($accounts, function($account) use ($isactive, $isstudent) {
                    $matchesActive = !$isactive || $account->accountStatusDisabled == '0';
                    $matchesStudent = !$isstudent || $account->isStudentAccount == '1';
                    return $matchesActive && $matchesStudent;
                });
            }
            
            return $accounts;
            
        } catch (Exception $e) {
            mtrace("Error fetching {$context}: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Initialize connection with improved error handling
     * 
     * @return bool Success status
     */
    public function init_call() {
        mtrace("Initializing Evento connection...");
        
        try {
            $request = [
                'theLimitationFilter2' => ['theMaxResultsValue' => 10]
            ];
            
            $result = $this->execute_soap_request(
                'listEventoAnlassTyp',
                $request,
                "Connection test"
            );
            
            $success = property_exists($result, "return");
            mtrace($success ? "Connection initialized successfully" : "Connection test failed");
            return $success;
            
        } catch (Exception $e) {
            mtrace("Connection initialization failed: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Get event by number with improved error handling
     * 
     * @param string $number The evento event number
     * @return stdClass|null Event object
     */
    public function get_event_by_number($number) {
        try {
            $request = [
                'theEventoAnlassFilter' => ['anlassNummer' => $number],
                'theLimitationFilter2' => ['theMaxResultsValue' => 10]
            ];
            
            $result = $this->execute_soap_request(
                'listEventoAnlass',
                $request,
                "Fetching event {$number}"
            );
            
            if (!property_exists($result, "return")) {
                mtrace("No event found for number {$number}");
                return null;
            }
            
            return $result->return;
            
        } catch (Exception $e) {
            mtrace("Error fetching event {$number}: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Handle ID conversions between systems
     */
    public function sid_to_shibbolethid($sid) {
        return trim(str_replace($this->config->adsidprefix, "", $sid) . $this->config->adshibbolethsuffix);
    }

    public function shibbolethid_to_sid($shibbolethid) {
        return trim($this->config->adsidprefix . str_replace($this->config->adshibbolethsuffix, "", $shibbolethid));
    }

    /**
     * Utility method to ensure consistent array handling
     * 
     * @param mixed $value
     * @return array
     */
    public static function to_array($value) {
        if (is_array($value)) {
            return $value;
        }
        if (is_null($value)) {
            return [];
        }
        return [$value];
    }

    /**
     * Get all active Veranstalter from Evento using the OE endpoint
     * Used during course creation to determine category structure
     * 
     * @return array Array of EventoOE objects
     */
    public function get_active_veranstalter() {
        mtrace("Retrieving Veranstalter from OE endpoint...");
        
        try {
            $request = [
                'theEventoOEFilter' => new stdClass(),  // Empty filter to get all entries
                'theLimitationFilter2' => [
                    'theFromDate' => (new DateTime('2018-01-01'))->format(LOCAL_EVENTO_DATETIME_FORMAT),
                    'theToDate' => (new DateTime('2100-01-01'))->format(LOCAL_EVENTO_DATETIME_FORMAT)
                ]
            ];
            
            $result = $this->execute_soap_request(
                'listEventoOE',
                $request,
                "Fetching Veranstalter"
            );
            
            if (!property_exists($result, "return")) {
                mtrace("No Veranstalter found");
                return [];
            }
            
            $oeList = self::to_array($result->return);
            $veranstalter = [];
            
            foreach ($oeList as $oe) {
                if (empty($oe->IDBenutzer)) {
                    continue;
                }
                
                $veranstalter[$oe->IDBenutzer] = (object)[
                    'IDBenutzer' => $oe->IDBenutzer,
                    'OE' => $oe->OE,
                    'aenderung' => $oe->aenderung,
                    'aenderungVon' => $oe->aenderungVon,
                    'benutzerName' => $oe->benutzerName,
                    'benutzerVorname' => $oe->benutzerVorname,
                    'erfassung' => $oe->erfassung,
                    'erfassungVon' => $oe->erfassungVon,
                    'isActiv' => $oe->isActiv
                ];
            }
            
            // Sort by name for consistent order
            uasort($veranstalter, function($a, $b) {
                return strcasecmp($a->benutzerName, $b->benutzerName);
            });
            
            $result = array_values($veranstalter);
            mtrace(sprintf("Retrieved %d Veranstalter", count($result)));
            return $result;
            
        } catch (Exception $e) {
            mtrace("Error retrieving Veranstalter: " . $e->getMessage());
            throw new moodle_exception('eventoapierror', 'local_evento', '', 
                sprintf(self::ERROR_MESSAGES['SOAP_REQUEST'], 'Error retrieving Veranstalter'));
        }
    }

    /**
     * Get all events for a specific Veranstalter/OE
     * 
     * @param string $veranstalter The Veranstalter identifier
     * @param DateTime|null $fromDate Optional start date filter
     * @param DateTime|null $toDate Optional end date filter
     * @return array Array of EventoAnlass objects
     */
    public function get_events_by_veranstalter($veranstalter, DateTime $fromDate = null, DateTime $toDate = null) {
        mtrace("Retrieving events for Veranstalter: {$veranstalter}");
        
        try {
            // Set default date range if not provided
            $fromDate = $fromDate ?? new DateTime('-1 year');
            $toDate = $toDate ?? new DateTime('+2 years');
            
            $request = [
                'theEventoAnlassFilter' => [
                    'anlassVeranstalter' => $veranstalter
                ],
                'theLimitationFilter2' => [
                    'theFromDate' => $fromDate->format(LOCAL_EVENTO_DATETIME_FORMAT),
                    'theToDate' => $toDate->format(LOCAL_EVENTO_DATETIME_FORMAT),
                    'theMaxResultsValue' => self::BATCH_SIZE
                ]
            ];
            
            $result = $this->execute_soap_request(
                'listEventoAnlass',
                $request,
                "Fetching events for Veranstalter {$veranstalter}"
            );
            
            if (!property_exists($result, "return")) {
                mtrace("No events found for Veranstalter {$veranstalter}");
                return [];
            }
            
            $events = self::to_array($result->return);
            mtrace(sprintf("Retrieved %d events for Veranstalter %s", count($events), $veranstalter));
            
            return $events;
            
        } catch (Exception $e) {
            mtrace("Error retrieving events for Veranstalter {$veranstalter}: " . $e->getMessage());
            throw new moodle_exception('eventoapierror', 'local_evento', '', 
                sprintf(self::ERROR_MESSAGES['SOAP_REQUEST'], 'Error retrieving events by Veranstalter'));
        }
    }

    /**
     * Get events for a specific Veranstalter filtered by current and next year
     * Performs filtering on client side since API doesn't support direct date field filtering
     * 
     * @param string $veranstalter The Veranstalter identifier
     * @return array Array of EventoAnlass objects
     */
    public function get_events_by_veranstalter_years(string $veranstalter): array {
        $currentYear = (int)date('Y');
        $nextYear = $currentYear + 1;
        
        $this->trace->output("Fetching events for years {$currentYear} and {$nextYear}");
        
        try {
            $allEvents = $this->get_events_by_veranstalter($veranstalter);
            
            // Debug event filtering
            $filteredEvents = array_filter($allEvents, function($event) use ($currentYear, $nextYear) {
                if (empty($event->anlassDatumVon)) {
                    $this->trace->output("Event {$event->anlassNummer} has no start date");
                    return false;
                }
                
                try {
                    $eventDate = new DateTime($event->anlassDatumVon);
                    $eventYear = (int)$eventDate->format('Y');
                    $include = $eventYear === $currentYear || $eventYear === $nextYear;
                    
                    $this->trace->output("Event {$event->anlassNummer}:");
                    $this->trace->output("- Date: " . $eventDate->format('Y-m-d'));
                    $this->trace->output("- Include: " . ($include ? 'yes' : 'no'));
                    
                    return $include;
                } catch (Exception $e) {
                    $this->trace->output("Error parsing date for event {$event->anlassNummer}");
                    return false;
                }
            });
            
            return array_values($filteredEvents);
            
        } catch (Exception $e) {
            $this->trace->output("Error getting events: " . $e->getMessage());
            return [];
        }
    }

}