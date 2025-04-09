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
 * Evento service for SOAP API communication.
 *
 * @package    local_evento
 * @copyright  2024 FHGR Julien Rädler
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * DateTime format of the Evento XML dateTime types.
 */
define('LOCAL_EVENTO_DATETIME_FORMAT', "Y-m-d\TH:i:s.uP");

/**
 * Class definition for the Evento webservice optimized for batch processing
 * during nightly synchronization jobs.
 *
 * This service handles all communication with the Evento SOAP API, providing
 * methods to retrieve events, enrollments, users, and other data.
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
     * Initialize the service keeping reference to the soap-client.
     *
     * @param SoapClient|null $client Optional SOAP client for testing
     * @param object|null $config Optional config for testing
     * @param progress_trace|null $trace Optional trace for logging
     */
    public function __construct(?SoapClient $client = null, ?object $config = null, ?progress_trace $trace = null) {
        global $CFG;
        
        $this->config = $config ?? get_config('local_evento');
        $this->trace = $trace ?? new null_progress_trace();
        
        if (!$client) {
            $this->initializeSoapClient();
        } else {
            $this->client = $client;
        }
    }

    /**
     * Initialize the SOAP client with configuration settings.
     *
     * @return void
     * @throws moodle_exception If connection fails
     */
    private function initializeSoapClient(): void {
        global $CFG;
        
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
            $this->handleSoapError($fault, self::ERROR_MESSAGES['SOAP_CONNECT']);
        }
    }

    /**
     * Set trace instance.
     * 
     * @param progress_trace $trace Trace instance for logging
     * @return void
     */
    public function setTrace(progress_trace $trace): void {
        $this->trace = $trace;
    }

    /**
     * Execute a SOAP request with retry logic and detailed logging.
     *
     * @param string $method SOAP method to call
     * @param array $params Parameters for the SOAP call
     * @param string $context Additional context for error messages
     * @return mixed Response from SOAP call
     * @throws moodle_exception on persistent failure
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
                $this->logSyncOperation($method, $duration, $attempts, $context);
                
                return $response;
            } catch (SoapFault $fault) {
                // On final attempt, throw error. Otherwise, retry
                if ($attempts >= self::MAX_RETRIES) {
                    $this->handleSoapError($fault, self::ERROR_MESSAGES['SOAP_REQUEST'], $context);
                }
                // Exponential backoff with jitter to prevent thundering herd
                $delay = pow(2, $attempts - 1) + (rand(0, 1000) / 1000);
                $this->logMessage("Evento API retry {$attempts} for {$method} after {$delay}s delay");
                sleep((int)$delay);
            }
        } while ($attempts < self::MAX_RETRIES);
    }

    /**
     * Handle SOAP errors with detailed logging.
     *
     * @param SoapFault $fault The SOAP fault
     * @param string $messageTemplate Message template for the error
     * @param string $context Additional context for error messages
     * @throws moodle_exception Always throws after logging
     */
    private function handleSoapError(SoapFault $fault, string $messageTemplate, string $context = ''): void {
        $errordetails = [
            'code' => $fault->faultcode,
            'string' => $fault->faultstring,
            'detail' => property_exists($fault, 'detail') ? $fault->detail : '',
            'context' => $context,
            'last_request' => $this->client->__getLastRequest(),
            'last_response' => $this->client->__getLastResponse()
        ];
        
        // Log detailed error for administrators
        $this->logMessage('Evento SOAP Error: ' . json_encode($errordetails, JSON_PRETTY_PRINT));
        
        // Throw exception with user-friendly message
        $message = sprintf($messageTemplate, $fault->faultstring);
        throw new moodle_exception('eventoapierror', 'local_evento', '', $message);
    }

    /**
     * Log synchronization operation details.
     *
     * @param string $operation Operation name
     * @param float $duration Operation duration in seconds
     * @param int $attempts Number of attempts made
     * @param string $context Additional context information
     * @return void
     */
    private function logSyncOperation(string $operation, float $duration, int $attempts, string $context = ''): void {
        $message = sprintf(
            'Evento sync operation: %s, Context: %s, Duration: %.4f seconds, Attempts: %d',
            $operation,
            $context,
            $duration,
            $attempts
        );
        $this->logMessage($message);
    }
    
    /**
     * Log a message using the appropriate trace mechanism.
     *
     * @param string $message The message to log
     * @param int $level The trace level (default: 1)
     * @return void
     */
    private function logMessage(string $message, int $level = 1): void {
        if ($this->trace) {
            $this->trace->output($message, $level);
        } else {
            mtrace($message);
        }
    }

    /**
     * Get event by ID with improved error handling.
     * 
     * @param int $eventid The Evento event ID
     * @return stdClass|null Event object or null if not found/error
     */
    public function getEventById(int $eventid): ?stdClass {
        try {
            $request = [
                'theEventoAnlassFilter' => ['idAnlass' => $eventid],
                'theLimitationFilter2' => ['theMaxResultsValue' => 10]
            ];
            
            $result = $this->executeSoapRequest(
                'listEventoAnlass',
                $request,
                "Fetching event ID {$eventid}"
            );
            
            if (!property_exists($result, "return")) {
                $this->logMessage("No event found for ID {$eventid}");
                return null;
            }
            
            return is_array($result->return) ? reset($result->return) : $result->return;
            
        } catch (Exception $e) {
            $this->logMessage("Error fetching event {$eventid}: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Process large datasets in batches.
     *
     * @param array $items Items to process
     * @param callable $processor Function to process each batch
     * @param string $context Context for logging
     * @return array Processing results
     */
    private function processInBatches(array $items, callable $processor, string $context = ''): array {
        $results = [];
        $batches = array_chunk($items, self::BATCH_SIZE);
        
        foreach ($batches as $index => $batch) {
            $this->logMessage(sprintf(
                'Processing batch %d/%d for %s (%d items)',
                $index + 1,
                count($batches),
                $context,
                count($batch)
            ));
            
            try {
                $results = array_merge($results, $processor($batch));
            } catch (Exception $e) {
                $this->logMessage(sprintf('Error processing batch %d: %s', $index + 1, $e->getMessage()));
                // Continue with next batch instead of failing completely
                continue;
            }
        }
        
        return $results;
    }

    /**
     * Get all enrollments for an event with batched processing.
     * 
     * @param int $eventid The event ID
     * @return array Array of enrollment objects
     */
    public function getEnrolmentsByEventId(int $eventid): array {
        try {
            $request = [
                'theEventoPersonenAnmeldungFilter' => ['idAnlass' => $eventid],
                'theLimitationFilter2' => ['theMaxResultsValue' => self::BATCH_SIZE]
            ];
            
            $result = $this->executeSoapRequest(
                'listEventoPersonenAnmeldung',
                $request,
                "Getting enrollments for event {$eventid}"
            );
            
            return property_exists($result, "return") ? $this->toArray($result->return) : [];
        } catch (Exception $e) {
            $this->logMessage("Error fetching enrollments for event {$eventid}: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Optimized batch retrieval of AD accounts.
     * 
     * @param bool|null $isactive Only return active accounts if true
     * @return array Array of AD account objects
     */
    public function getAllAdAccounts(?bool $isactive = null): array {
        $this->logMessage('Starting batch retrieval of AD accounts...');
        
        $accounts = [];
        $seen = []; // Track accounts we've already added by objectSid
        $types = ['employee', 'lecturer', 'student'];
        
        foreach ($types as $type) {
            $this->logMessage("Retrieving {$type} accounts...");
            $method = "get{$type}AdAccounts";
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
        
        $this->logMessage(sprintf('Retrieved %d total accounts', count($accounts)));
        return $accounts;
    }
    
    /**
     * Get active student AD accounts with optimized batch processing.
     * 
     * @param bool|null $isactive Only return active accounts if true
     * @return array Array of student AD account objects
     */
    public function getStudentAdAccounts(?bool $isactive = null): array {
        return $this->getAccountsByType('student', $isactive);
    }

    /**
     * Get lecturer AD accounts with optimized batch processing.
     * 
     * @param bool|null $isactive Only return active accounts if true
     * @return array Array of lecturer AD account objects
     */
    public function getLecturerAdAccounts(?bool $isactive = null): array {
        return $this->getAccountsByType('lecturer', $isactive);
    }

    /**
     * Get employee AD accounts with optimized batch processing.
     * 
     * @param bool|null $isactive Only return active accounts if true
     * @return array Array of employee AD account objects
     */
    public function getEmployeeAdAccounts(?bool $isactive = null): array {
        return $this->getAccountsByType('employee', $isactive);
    }

    /**
     * Generic method to get accounts by type.
     * 
     * @param string $type Account type (student, lecturer, employee)
     * @param bool|null $isactive Only return active accounts if true
     * @return array Array of AD account objects
     */
    private function getAccountsByType(string $type, ?bool $isactive = null): array {
        $context = "{$type} accounts" . ($isactive ? " (active only)" : "");
        $this->logMessage("Retrieving {$context}...");
        
        try {
            $request = [
                'theADAccount' => ["is{$type}Account" => 1],
                'theEventoLimitatinFilter1' => ['theMaxResultsValue' => 30000]
            ];
            
            $result = $this->executeSoapRequest('listAdAccount', $request, "Fetching {$context}");
            
            if (!property_exists($result, "return")) {
                $this->logMessage("No {$type} accounts found");
                return [];
            }

            $accounts = $this->toArray($result->return);
            
            if ($isactive) {
                $accounts = array_filter($accounts, function($account) {
                    return $account->accountStatusDisabled == '0';
                });
            }
            
            $this->logMessage(sprintf("Retrieved %d {$type} accounts", count($accounts)));
            return $accounts;
        } catch (Exception $e) {
            $this->logMessage("Error fetching {$type} accounts: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Get person details with improved error handling.
     * 
     * @param int $personid The Evento person ID
     * @return stdClass|null Person object or null if not found/error
     */
    public function getPersonById(int $personid): ?stdClass {
        try {
            $request = [
                'theEventoPersonFilter' => ['idPerson' => $personid],
                'theLimitationFilter2' => ['theMaxResultsValue' => 10]
            ];
            
            $result = $this->executeSoapRequest(
                'listEventoPerson',
                $request,
                "Fetching person {$personid}"
            );
            
            if (!property_exists($result, "return")) {
                $this->logMessage("No person found for ID {$personid}");
                return null;
            }
            
            return is_array($result->return) ? reset($result->return) : $result->return;
            
        } catch (Exception $e) {
            $this->logMessage("Error fetching person {$personid}: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Get AD accounts by Evento person ID with improved error handling.
     * 
     * @param int $personid The Evento person ID
     * @param bool|null $isactive Filter by active status
     * @param bool|null $isstudent Filter by student status
     * @return array Array of AD account objects
     */
    public function getAdAccountsByEventoPersonId(int $personid, ?bool $isactive = null, ?bool $isstudent = null): array {
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
            
            $result = $this->executeSoapRequest(
                'listAdAccount',
                $request,
                "Fetching {$context}"
            );
            
            if (!property_exists($result, "return")) {
                return [];
            }
            
            $accounts = $this->toArray($result->return);
            
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
            $this->logMessage("Error fetching {$context}: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Initialize connection with improved error handling.
     * 
     * @return bool Success status
     */
    public function initCall(): bool {
        $this->logMessage("Initializing Evento connection...");
        
        try {
            $request = [
                'theLimitationFilter2' => ['theMaxResultsValue' => 10]
            ];
            
            $result = $this->executeSoapRequest(
                'listEventoAnlassTyp',
                $request,
                "Connection test"
            );
            
            $success = property_exists($result, "return");
            $this->logMessage($success ? "Connection initialized successfully" : "Connection test failed");
            return $success;
            
        } catch (Exception $e) {
            $this->logMessage("Connection initialization failed: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Get event by number with improved error handling.
     * 
     * @param string $number The Evento event number
     * @return stdClass|null Event object or null if not found/error
     */
    public function getEventByNumber(string $number): ?stdClass {
        try {
            $request = [
                'theEventoAnlassFilter' => ['anlassNummer' => $number],
                'theLimitationFilter2' => ['theMaxResultsValue' => 10]
            ];
            
            $result = $this->executeSoapRequest(
                'listEventoAnlass',
                $request,
                "Fetching event {$number}"
            );
            
            if (!property_exists($result, "return")) {
                $this->logMessage("No event found for number {$number}");
                return null;
            }
            
            return is_array($result->return) ? reset($result->return) : $result->return;
            
        } catch (Exception $e) {
            $this->logMessage("Error fetching event {$number}: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Convert Active Directory SID to Shibboleth ID.
     * 
     * @param string $sid The Active Directory SID
     * @return string The Shibboleth ID
     */
    public function sidToShibbolethId(string $sid): string {
        return trim(str_replace($this->config->adsidprefix, "", $sid) . $this->config->adshibbolethsuffix);
    }

    /**
     * Convert Shibboleth ID to Active Directory SID.
     * 
     * @param string $shibbolethid The Shibboleth ID
     * @return string The Active Directory SID
     */
    public function shibbolethIdToSid(string $shibbolethid): string {
        return trim($this->config->adsidprefix . str_replace($this->config->adshibbolethsuffix, "", $shibbolethid));
    }

    /**
     * Utility method to ensure consistent array handling.
     * 
     * @param mixed $value Value to convert to array
     * @return array The resulting array
     */
    public function toArray($value): array {
        if (is_array($value)) {
            return $value;
        }
        if (is_null($value)) {
            return [];
        }
        return [$value];
    }

    /**
     * Get all active Veranstalter from Evento using the OE endpoint.
     * Used during course creation to determine category structure.
     * 
     * @return array Array of EventoOE objects
     * @throws moodle_exception on API error
     */
    public function getActiveVeranstalter(): array {
        $this->logMessage("Retrieving Veranstalter from OE endpoint...");
        
        try {
            $request = [
                'theEventoOEFilter' => new stdClass(),  // Empty filter to get all entries
                'theLimitationFilter2' => [
                    'theFromDate' => (new DateTime('2018-01-01'))->format(LOCAL_EVENTO_DATETIME_FORMAT),
                    'theToDate' => (new DateTime('2100-01-01'))->format(LOCAL_EVENTO_DATETIME_FORMAT)
                ]
            ];
            
            $result = $this->executeSoapRequest(
                'listEventoOE',
                $request,
                "Fetching Veranstalter"
            );
            
            if (!property_exists($result, "return")) {
                $this->logMessage("No Veranstalter found");
                return [];
            }
            
            $oeList = $this->toArray($result->return);
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
            $this->logMessage(sprintf("Retrieved %d Veranstalter", count($result)));
            return $result;
            
        } catch (Exception $e) {
            $this->logMessage("Error retrieving Veranstalter: " . $e->getMessage());
            throw new moodle_exception('eventoapierror', 'local_evento', '', 
                sprintf(self::ERROR_MESSAGES['SOAP_REQUEST'], 'Error retrieving Veranstalter'));
        }
    }

    /**
     * Get all events for a specific Veranstalter/OE.
     * 
     * @param string $veranstalter The Veranstalter identifier
     * @param DateTime|null $fromDate Optional start date filter
     * @param DateTime|null $toDate Optional end date filter
     * @return array Array of EventoAnlass objects
     * @throws moodle_exception on API error
     */
    public function getEventsByVeranstalter(
        string $veranstalter, 
        ?DateTime $fromDate = null, 
        ?DateTime $toDate = null
    ): array {
        $this->logMessage("Retrieving events for Veranstalter: {$veranstalter}");
        
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
            
            $result = $this->executeSoapRequest(
                'listEventoAnlass',
                $request,
                "Fetching events for Veranstalter {$veranstalter}"
            );
            
            if (!property_exists($result, "return")) {
                $this->logMessage("No events found for Veranstalter {$veranstalter}");
                return [];
            }
            
            $events = $this->toArray($result->return);
            $this->logMessage(sprintf("Retrieved %d events for Veranstalter %s", count($events), $veranstalter));
            
            return $events;
            
        } catch (Exception $e) {
            $this->logMessage("Error retrieving events for Veranstalter {$veranstalter}: " . $e->getMessage());
            throw new moodle_exception('eventoapierror', 'local_evento', '', 
                sprintf(self::ERROR_MESSAGES['SOAP_REQUEST'], 'Error retrieving events by Veranstalter'));
        }
    }

    /**
     * Get events for a specific Veranstalter filtered by current and next year.
     * Performs filtering on client side since API doesn't support direct date field filtering.
     * 
     * @param string $veranstalter The Veranstalter identifier
     * @return array Array of EventoAnlass objects
     */
    public function getEventsByVeranstalterYears(string $veranstalter): array {
        $currentYear = (int)date('Y');
        $nextYear = $currentYear + 1;
        
        $this->logMessage("Fetching events for years {$currentYear} and {$nextYear}");
        
        try {
            $allEvents = $this->getEventsByVeranstalter($veranstalter);
            
            // Filter events by year
            $filteredEvents = array_filter($allEvents, function($event) use ($currentYear, $nextYear) {
                if (empty($event->anlassDatumVon)) {
                    $this->logMessage("Event {$event->anlassNummer} has no start date", 2);
                    return false;
                }
                
                try {
                    $eventDate = new DateTime($event->anlassDatumVon);
                    $eventYear = (int)$eventDate->format('Y');
                    $include = $eventYear === $currentYear || $eventYear === $nextYear;
                    
                    $this->logMessage("Event {$event->anlassNummer}: Date: " . $eventDate->format('Y-m-d') . 
                                      ", Include: " . ($include ? 'yes' : 'no'), 2);
                    
                    return $include;
                } catch (Exception $e) {
                    $this->logMessage("Error parsing date for event {$event->anlassNummer}", 2);
                    return false;
                }
            });
            
            return array_values($filteredEvents);
            
        } catch (Exception $e) {
            $this->logMessage("Error getting events: " . $e->getMessage());
            return [];
        }
    }
}