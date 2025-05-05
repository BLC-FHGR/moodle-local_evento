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
 * Authentication failure tests with VCR.
 *
 * @package    local_evento
 * @category   test
 * @copyright  2025 FHGR Julien Rädler
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use local_evento\api\client;
use local_evento\cache\cache_manager;
use local_evento\log\logger;
use VCR\VCR;

defined('MOODLE_INTERNAL') || die();

/**
 * Tests how the system handles authentication failures.
 * 
 * This test intentionally uses invalid credentials to test
 * error handling behavior. The cassette should be recorded
 * with deliberately incorrect credentials.
 */
class auth_failure_test extends advanced_testcase {

    /**
     * Test authentication failure handling.
     *
     * @vcr evento_auth_failure
     */
    public function test_auth_failure_handling(): void {
        global $CFG;
        
        $this->resetAfterTest();
        
        // Set invalid credentials for this test
        set_config('wslocation', 'https://ws.fh-htwchur.ch/eventowsblc/services/EventoWebservice.EventoWebserviceHttpSoap11Endpoint/', 'local_evento');
        set_config('wsuri', 'http://service.webservice.htwchur.ch', 'local_evento');
        set_config('wstrace', true, 'local_evento');
        set_config('wsusername', 'invalid_user', 'local_evento');
        set_config('wspassword', 'invalid_pass', 'local_evento');
        set_config('wswsdlfilename', 'evento_webservice_v1_2.wsdl', 'local_evento');
        
        // Create dependencies
        $cachemanager = new cache_manager();
        $logger = new logger('local_evento', new \null_progress_trace());
        
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
        
        $client = new client($wsdlfile, $options, $cachemanager, $logger);
        
        // Execute API call - this should fail with authentication error
        try {
            $result = $client->execute('listEventoAnlass', [
                ['theEventoAnlassFilter' => ['idAnlass' => 123]]
            ]);
            
            // If we get here, the authentication error wasn't thrown
            $this->fail('Expected an exception for authentication failure');
        } catch (\Exception $e) {
            // Verify we got the expected exception type/message
            $this->assertStringContainsString('Authentication', $e->getMessage());
        }
    }
    
    /**
     * Test circuit breaker activation on repeated auth failures.
     *
     * @vcr evento_circuit_breaker
     */
    public function test_circuit_breaker_activation(): void {
        global $CFG;
        
        $this->resetAfterTest();
        
        // Set invalid credentials for this test
        set_config('wslocation', 'https://ws.fh-htwchur.ch/eventowsblc/services/EventoWebservice.EventoWebserviceHttpSoap11Endpoint/', 'local_evento');
        set_config('wsuri', 'http://service.webservice.htwchur.ch', 'local_evento');
        set_config('wstrace', true, 'local_evento');
        set_config('wsusername', 'invalid_user', 'local_evento');
        set_config('wspassword', 'invalid_pass', 'local_evento');
        set_config('wswsdlfilename', 'evento_webservice_v1_2.wsdl', 'local_evento');
        
        // Create dependencies with custom circuit breaker that trips quickly (1 failure)
        $cachemanager = new cache_manager();
        $logger = new logger('local_evento', new \null_progress_trace());
        
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
        
        $client = new client($wsdlfile, $options, $cachemanager, $logger);
        
        // Configure a more sensitive circuit breaker (for testing)
        $reflection = new \ReflectionClass($client);
        $circuitBreaker = $reflection->getProperty('circuitbreaker');
        $circuitBreaker->setAccessible(true);
        $circuitBreaker->setValue($client, new \local_evento\api\circuit_breaker(1, 60));
        
        // First call should fail with auth error
        try {
            $client->execute('listEventoAnlass', [
                ['theEventoAnlassFilter' => ['idAnlass' => 123]]
            ]);
            $this->fail('Expected an exception for authentication failure');
        } catch (\Exception $e) {
            // Expected exception
        }
        
        // Second call should fail immediately due to circuit breaker
        $start = microtime(true);
        
        try {
            $client->execute('listEventoAnlass', [
                ['theEventoAnlassFilter' => ['idAnlass' => 123]]
            ]);
            $this->fail('Expected circuit breaker to prevent the call');
        } catch (\Exception $e) {
            // Verify circuit breaker message
            $this->assertStringContainsString('circuit breaker', $e->getMessage());
            
            // Verify call fails fast (no network delay)
            $duration = microtime(true) - $start;
            $this->assertLessThan(0.5, $duration, 'Circuit breaker should fail fast');
        }
    }
}