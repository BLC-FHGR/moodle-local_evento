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

use VCR\VCR;
use local_evento\api\client;
use local_evento\cache\cache_manager;
use local_evento\log\logger;

/**
 * Integration test for client with VCR recording.
 *
 * @package    local_evento
 * @category   test
 * @copyright  2025 FHGR Julien Rädler
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class evento_client_test extends \advanced_testcase {

    /**
     * Test the client can list events with VCR.
     * 
     * This test uses VCR to record/replay SOAP interactions.
     * Run with VCR_MODE=record to create or refresh the cassette.
     *
     * @vcr evento_list_anlass
     */
    public function test_list_events(): void {
        global $CFG;
        
        $this->resetAfterTest();
        
        // Mock config for the test
        set_config('wslocation', 'https://ws.fh-htwchur.ch/eventowsblc/services/EventoWebservice.EventoWebserviceHttpSoap11Endpoint/', 'local_evento');
        set_config('wsuri', 'http://service.webservice.htwchur.ch', 'local_evento');
        set_config('wstrace', true, 'local_evento');
        set_config('wsusername', 'testuser', 'local_evento'); // Use environment variables in real tests
        set_config('wspassword', 'testpass', 'local_evento'); // Use environment variables in real tests
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
        
        // Execute API method with a known event ID
        $result = $client->execute('listEventoAnlass', [
            ['theEventoAnlassFilter' => ['idAnlass' => 123]]
        ]);
        
        // Assert the response structure (will be recorded in the cassette)
        $this->assertIsObject($result);
        $this->assertObjectHasAttribute('return', $result);
    }
    
    /**
     * Test API returns empty results correctly.
     *
     * @vcr evento_list_anlass_empty
     */
    public function test_empty_response(): void {
        global $CFG;
        
        $this->resetAfterTest();
        
        // Mock config for the test (same as above)
        set_config('wslocation', 'https://ws.fh-htwchur.ch/eventowsblc/services/EventoWebservice.EventoWebserviceHttpSoap11Endpoint/', 'local_evento');
        set_config('wsuri', 'http://service.webservice.htwchur.ch', 'local_evento');
        set_config('wstrace', true, 'local_evento');
        set_config('wsusername', 'testuser', 'local_evento');
        set_config('wspassword', 'testpass', 'local_evento');
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
        
        // Execute API method with a nonexistent event ID
        $result = $client->execute('listEventoAnlass', [
            ['theEventoAnlassFilter' => ['idAnlass' => 999999]]
        ]);
        
        // Assert empty result structure
        $this->assertIsObject($result);
        if (isset($result->return)) {
            $this->assertEmpty($result->return);
        } else {
            $this->assertObjectNotHasAttribute('return', $result);
        }
    }
}