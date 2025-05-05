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
 * Integration tests for the repository with VCR.
 *
 * @package    local_evento
 * @category   test
 * @copyright  2025 FHGR Julien Rädler
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use local_evento\data\repository;
use local_evento\api\client;
use local_evento\cache\cache_manager;
use local_evento\log\logger;
use local_evento\api\response_processor;
use local_evento\api\filter\array_response_filter;
use VCR\VCR;

defined('MOODLE_INTERNAL') || die();

/**
 * Integration tests for repository-level operations using VCR.
 *
 * These tests use PHP-VCR to record and replay API interactions,
 * allowing consistent offline testing while validating the full
 * request-response cycle.
 */
class api_integration_test extends advanced_testcase {

    /**
     * @var repository The repository under test
     */
    private $repository;
    
    /**
     * @var client The API client
     */
    private $client;
    
    /**
     * @var cache_manager The cache manager
     */
    private $cacheManager;
    
    /**
     * @var logger The logger
     */
    private $logger;
    
    /**
     * @var response_processor The response processor
     */
    private $responseProcessor;

    /**
     * Set up the test environment.
     */
    public function setUp(): void {
        global $CFG;
        
        parent::setUp();
        $this->resetAfterTest();
        
        // Mock config for the test
        set_config('wslocation', 'https://ws.fh-htwchur.ch/eventowsblc/services/EventoWebservice.EventoWebserviceHttpSoap11Endpoint/', 'local_evento');
        set_config('wsuri', 'http://service.webservice.htwchur.ch', 'local_evento');
        set_config('wstrace', true, 'local_evento');
        set_config('wsusername', 'testuser', 'local_evento'); // For real tests, use env vars
        set_config('wspassword', 'testpass', 'local_evento'); // For real tests, use env vars
        set_config('wswsdlfilename', 'evento_webservice_v1_2.wsdl', 'local_evento');
        
        // Create dependencies
        $this->cacheManager = new cache_manager();
        $this->logger = new logger('local_evento', new \null_progress_trace());
        $this->responseProcessor = new response_processor();
        
        // Add array response filter
        $this->responseProcessor->addFilter(new array_response_filter());
        
        // Create client
        $wsdlfile = $CFG->dirroot . '/local/evento/wsdl/evento_webservice_v1_2.wsdl';
        $options = [
            'location' => get_config('local_evento', 'wslocation'),
            'uri' => get_config('local_evento', 'wsuri'),
            'trace' => get_config('local_evento', 'wstrace'),
            'login' => get_config('local_evento', 'wsusername'),
            'password' => get_config('local_evento', 'wspassword'),
            'connection_timeout' => 30,
            'features' => SOAP_SINGLE_ELEMENT_ARRAYS,
            'exceptions' => true
        ];
        
        $this->client = new client($wsdlfile, $options, $this->cacheManager, $this->logger);
        
        // Create repository
        $this->repository = new repository(
            $this->client,
            $this->cacheManager,
            $this->responseProcessor
        );
    }
    
    /**
     * Test getting events with the repository.
     *
     * @vcr repository_get_events
     */
    public function test_get_events(): void {
        // Execute the repository method with known ID
        $result = $this->repository->getEvents(['idAnlass' => 123]);
        
        // Assert response structure
        $this->assertIsArray($result);
        if (!empty($result)) {
            $this->assertArrayHasKey(0, $result);
            $this->assertIsObject($result[0]);
            $this->assertObjectHasAttribute('idAnlass', $result[0]);
        }
    }
    
    /**
     * Test getting events with date limitation filter.
     *
     * @vcr repository_get_events_with_date
     */
    public function test_get_events_with_date_filter(): void {
        // Execute with date filter
        $result = $this->repository->getEvents(
            ['anlassVeranstalter' => 'FHGR'], // Filter by organizer
            ['lastChangeDate' => new \DateTime('-30 days')] // Only changes in last 30 days
        );
        
        // Assert response structure
        $this->assertIsArray($result);
    }
    
    /**
     * Test getting enrollments for a specific event.
     *
     * @vcr repository_get_enrollments
     */
    public function test_get_enrollments(): void {
        // Execute with known event ID
        $result = $this->repository->getEnrollments(123);
        
        // Assert response structure
        $this->assertIsArray($result);
        if (!empty($result)) {
            $this->assertArrayHasKey(0, $result);
            $this->assertIsObject($result[0]);
            $this->assertObjectHasAttribute('idAnlass', $result[0]);
            $this->assertObjectHasAttribute('idPerson', $result[0]);
        }
    }
    
    /**
     * Test getting users with entity filter.
     *
     * @vcr repository_get_users
     */
    public function test_get_users(): void {
        // Execute with filter
        $result = $this->repository->getUsers(['personNachname' => 'Doe']);
        
        // Assert response structure
        $this->assertIsArray($result);
    }
    
    /**
     * Test getting organizational units.
     *
     * @vcr repository_get_organizational_units
     */
    public function test_get_organizational_units(): void {
        // Execute method
        $result = $this->repository->getOrganizationalUnits();
        
        // Assert response structure
        $this->assertIsArray($result);
        if (!empty($result)) {
            $this->assertArrayHasKey(0, $result);
            $this->assertIsObject($result[0]);
            $this->assertObjectHasAttribute('OE', $result[0]);
        }
    }
}