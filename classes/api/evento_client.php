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
 * Robust SOAP client for Evento API.
 *
 * @package    local_evento
 * @copyright  2025 Julien Rädler
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_evento\api;

defined('MOODLE_INTERNAL') || die();

use local_evento\cache\evento_cache_manager;
use local_evento\log\evento_logger;

/**
 * Robust SOAP client with circuit breaker and retry functionality.
 */
class evento_client {
    /** @var \SoapClient The underlying SOAP client */
    private $soapclient;
    
    /** @var circuit_breaker The circuit breaker for fault tolerance */
    private $circuitbreaker;
    
    /** @var evento_cache_manager The cache manager */
    private $cachemanager;
    
    /** @var evento_logger The logger instance */
    private $logger;
    
    /** @var array The retry policy configuration */
    private $retrypolicy;

    /**
     * Constructor.
     *
     * @param string $wsdlurl The URL to the WSDL file
     * @param array $options SOAP client options
     * @param evento_cache_manager $cachemanager The cache manager
     * @param evento_logger $logger The logger instance
     */
    public function __construct($wsdlurl, $options, evento_cache_manager $cachemanager, evento_logger $logger) {
        $this->soapclient = new \SoapClient($wsdlurl, $options);
        $this->circuitbreaker = new circuit_breaker(5, 300); // 5 failures, 5 minute reset
        $this->cachemanager = $cachemanager;
        $this->logger = $logger;
        $this->retrypolicy = [
            'maxattempts' => 3,
            'delay' => 2,
            'multiplier' => 2
        ];
    }
    
    public function execute($method, $params) {
        try {
            return $this->circuitbreaker->execute(function() use ($method, $params) {
                return $this->execute_with_retry($method, $params);
            });
        } catch (\Exception $e) {
            $this->logger->error('SOAP execution failed: ' . $e->getMessage(), [
                'method' => $method,
                'circuit_state' => $this->circuitbreaker->getState()
            ]);
            throw $e;
        }
    }
    
    private function execute_with_retry($method, $params, $attempt = 1) {
        try {
            $this->logger->debug('Executing SOAP call', [
                'method' => $method,
                'attempt' => $attempt
            ]);
            
            $result = $this->soapclient->__soapCall($method, $params);
            
            $this->logger->debug('SOAP call successful', [
                'method' => $method
            ]);
            
            return $result;
        } catch (\SoapFault $fault) {
            if ($attempt < $this->retrypolicy['maxAttempts'] && $this->should_retry($fault, $attempt)) {
                $delay = $this->retrypolicy['delay'] * pow($this->retrypolicy['multiplier'], $attempt - 1);
                
                $this->logger->warning('SOAP call failed, retrying', [
                    'method' => $method,
                    'attempt' => $attempt,
                    'delay' => $delay,
                    'fault_code' => $fault->faultcode
                ]);
                
                sleep($delay);
                return $this->execute_with_retry($method, $params, $attempt + 1);
            }
            
            throw $this->handle_soap_fault($fault, $method);
        }
    }
    
    private function should_retry(\SoapFault $fault, $attempt) {
        // Determine if this error is retryable based on fault code
        // Network errors, timeouts are retryable
        // Authentication errors, invalid requests are not
        $retryableCodes = ['HTTP', 'Connection', 'Timeout'];
        
        foreach ($retryableCodes as $code) {
            if (strpos($fault->faultcode, $code) !== false) {
                return true;
            }
        }
        
        return false;
    }
    
    private function handle_soap_fault(\SoapFault $fault, $method) {
        // Convert SOAP faults to more meaningful exceptions
        // Log detailed information about the fault
        $this->logger->error('SOAP fault: ' . $fault->faultcode, [
            'method' => $method,
            'message' => $fault->getMessage(),
            'detail' => isset($fault->detail) ? json_encode($fault->detail) : null
        ]);
        
        return new evento_api_exception(
            'API call failed: ' . $fault->getMessage(),
            0,
            $fault
        );
    }
    
    public function get_last_request() {
        return $this->soapclient->__get_last_request();
    }
    
    public function get_last_response() {
        return $this->soapclient->__get_last_response();
    }
}

/**
 * Exception thrown by evento client.
 */
class evento_api_exception extends \Exception {

}