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

use local_evento\cache\cache_manager;
use local_evento\log\logger;

/**
 * Robust SOAP client with circuit breaker and retry functionality.
 */
class client implements client_interface {
    /** @var \SoapClient The underlying SOAP client */
    private \SoapClient $soapclient;
    
    /** @var circuit_breaker The circuit breaker for fault tolerance */
    private circuit_breaker $circuitbreaker;
    
    /** @var cache_manager The cache manager */
    private cache_manager $cachemanager;
    
    /** @var logger The logger instance */
    private logger $logger;
    
    /** @var array The retry policy configuration */
    private array $retrypolicy;

    /** @var array The SOAP client options */
    private array $options = [];

    /**
     * Constructor.
     *
     * @param string $wsdlurl The URL to the WSDL file
     * @param array $options SOAP client options
     * @param cache_manager $cachemanager The cache manager
     * @param logger $logger The logger instance
     */
    public function __construct($wsdlurl, $options, cache_manager $cachemanager, logger $logger) {
        try {
            // Extract namespace from WSDL if available
            if (file_exists($wsdlurl)) {
                $wsdlContent = file_get_contents($wsdlurl);
                if (preg_match('/targetNamespace="([^"]+)"/', $wsdlContent, $matches)) {
                    $options['uri'] = $matches[1];
                    $logger->debug('Setting URI from WSDL file', [
                        'extracted_uri' => $options['uri'],
                        'wsdl_path' => $wsdlurl
                    ]);
                }
            }
            
            $logger->debug('SOAP client options', [
                'wsdlurl' => $wsdlurl,
                'options' => $options
            ]);
            
            // Store options for later use (important for mocking)
            $this->options = $options;
            
            $this->soapclient = new \SoapClient($wsdlurl, $options);
            $this->circuitbreaker = new circuit_breaker(5, 300);
            $this->cachemanager = $cachemanager;
            $this->logger = $logger;
            $this->retrypolicy = [
                'max_attempts' => 3,
                'delay' => 2,
                'multiplier' => 2
            ];
        } catch (\Exception $e) {
            $logger->error('Failed to initialize SOAP client', [
                'error' => $e->getMessage(),
                'wsdlurl' => $wsdlurl
            ]);
            throw $e;
        }
    }
    
    /**
     * Set a custom SOAP client (for testing).
     *
     * @param \SoapClient $client The SOAP client to use
     */
    public function set_soap_client($client) {
        $this->soapclient = $client;
    }
    
    /**
     * Get the SOAP client options.
     *
     * @return array The SOAP client options
     */
    public function get_options() {
        return $this->options;
    }
    
    /**
     * Execute an API method.
     *
     * @param string $method The API method to call
     * @param array $params The parameters for the method
     * @return mixed The API response
     * @throws \Exception If execution fails
     */
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
            if ($attempt < $this->retrypolicy['max_attempts'] && $this->should_retry($fault, $attempt)) {
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
        
        return new api_exception(
            'API call failed: ' . $fault->getMessage(),
            0,
            $fault
        );
    }
    
    /**
     * Get the last request sent to the API.
     *
     * @return string The last request
     */
    public function get_last_request() {
        if (method_exists($this->soapclient, '__getLastRequest')) {
            return $this->soapclient->__getLastRequest();
        }
        return '';
    }

    /**
     * Get the last response received from the API.
     *
     * @return string The last response
     */
    public function get_last_response() {
        if (method_exists($this->soapclient, '__getLastResponse')) {
            return $this->soapclient->__getLastResponse();
        }
        return '';
    }
}

/**
 * Exception thrown by evento client.
 */
class api_exception extends \Exception {

}