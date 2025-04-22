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
 * Mock SOAP client for testing.
 *
 * @package    local_evento
 * @category   test
 * @copyright  2025 FHGR Julien Rädler
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_evento\tests\mock;

defined('MOODLE_INTERNAL') || die();

/**
 * Mock SOAP client for testing that doesn't require a real SOAP connection.
 * 
 * This class emulates the behavior of PHP's SoapClient without making any
 * actual network connections. It can be used to test SOAP-dependent code.
 */
class mock_soap_client {
    /** @var array The SOAP client options */
    private $options;
    
    /** @var string The last request XML */
    private $lastRequest = '<soap:Envelope xmlns:soap="http://www.w3.org/2003/05/soap-envelope"><soap:Body>Mock Request</soap:Body></soap:Envelope>';
    
    /** @var string The last response XML */
    private $lastResponse = '<soap:Envelope xmlns:soap="http://www.w3.org/2003/05/soap-envelope"><soap:Body>Mock Response</soap:Body></soap:Envelope>';
    
    /** @var callable The callback for SOAP calls */
    private $soapCallCallback;
    
    /**
     * Constructor.
     *
     * @param string|null $wsdl Path to the WSDL file (ignored in mock)
     * @param array $options SOAP client options
     */
    public function __construct($wsdl = null, $options = []) {
        $this->options = $options;
    }
    
    /**
     * Set the callback function to handle SOAP calls.
     *
     * @param callable $callback The callback function
     * @return self
     */
    public function setCallHandler(callable $callback) {
        $this->soapCallCallback = $callback;
        return $this;
    }
    
    /**
     * Set the last request XML.
     *
     * @param string $request The XML request
     * @return self
     */
    public function setLastRequest($request) {
        $this->lastRequest = $request;
        return $this;
    }
    
    /**
     * Set the last response XML.
     *
     * @param string $response The XML response
     * @return self
     */
    public function setLastResponse($response) {
        $this->lastResponse = $response;
        return $this;
    }
    
    /**
     * Magic method to handle SOAP method calls.
     *
     * @param string $method The SOAP method called
     * @param array $args The arguments passed to the method
     * @return mixed The response
     */
    public function __call($method, $args) {
        if ($method === '__soapCall') {
            return $this->handleSoapCall($args[0], $args[1]);
        }
        
        // For any other methods, return a default response
        return null;
    }
    
    /**
     * Handle SOAP calls.
     *
     * @param string $method The SOAP method name
     * @param array $params The parameters passed to the method
     * @return mixed The response from the callback function or a default response
     */
    private function handleSoapCall($method, $params) {
        if (isset($this->soapCallCallback) && is_callable($this->soapCallCallback)) {
            return call_user_func($this->soapCallCallback, $method, $params);
        }
        
        // Default response if no callback is set
        return (object)['return' => []];
    }
    
    /**
     * Get the last request sent to the API.
     *
     * @return string The last request
     */
    public function __getLastRequest() {
        return $this->lastRequest;
    }
    
    /**
     * Get the last response received from the API.
     *
     * @return string The last response
     */
    public function __getLastResponse() {
        return $this->lastResponse;
    }
}