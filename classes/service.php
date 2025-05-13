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
 * Main service class for the Evento system.
 *
 * @package    local_evento
 * @copyright  2025 FHGR Julien Rädler
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_evento;

defined('MOODLE_INTERNAL') || die();

use core_calendar\local\event\entities\event;
use local_evento\api\client;
use local_evento\api\response_processor;
use local_evento\cache\cache_manager;
use local_evento\data\repository;
use local_evento\log\logger;
use local_evento\api\filter\array_response_filter;

/**
 * Main service class for Evento integration.
 * 
 * This class serves as the primary entry point for interacting with the Evento
 * system. It provides access to the repository, client, and other components
 * while managing their lifecycles and dependencies.
 */
class service {
    /** @var string DateTime format of the Evento XML dateTime types */
    const DATETIME_FORMAT = "Y-m-d\TH:i:s.uP";
    
    /** @var repository The repository instance */
    private $repository;
    
    /** @var client The API client instance */
    private $client;
    
    /** @var cache_manager The cache manager instance */
    private $cachemanager;
    
    /** @var logger The logger instance */
    private $logger;
    
    /** @var object The plugin configuration */
    private $config;
    
    /** @var service Singleton instance */
    private static $instance;

    /** @var response_processor The response processor */
    private $responseprocessor;

    /**
     * Constructor.
     *
     * @param \progress_trace|null $trace Optional trace handler for logging
     * @param client|null $client Optional API client for testing
     * @param repository|null $repository Optional repository for testing
     */
    private function __construct(?\progress_trace $trace = null, ?client $client = null, ?repository $repository = null) {
        // Load configuration
        $this->config = get_config('local_evento');
        
        // Create logger with trace if provided
        $this->logger = new logger('local_evento', $trace ?? new \null_progress_trace());
        
        // Create cache manager
        $this->cachemanager = new cache_manager();

        // Create response processor
        $this->responseprocessor = new response_processor();
        $this->responseprocessor->addFilter(new array_response_filter());
        
        // Create or use provided client
        if ($client) {
            $this->client = $client;
        } else {
            $this->initializeClient();
        }
        
        // Create or use provided repository
        $this->repository = $repository ?? new repository($this->client, $this->cachemanager, $this->responseprocessor);
    }

    /**
     * Get or create the singleton instance.
     *
     * @param \progress_trace|null $trace Optional trace handler for logging
     * @return service The service instance
     */
    public static function getInstance(?\progress_trace $trace = null): service {
        if (is_null(self::$instance)) {
            self::$instance = new self($trace);
        } else if ($trace) {
            // Update trace if provided
            self::$instance->setTrace($trace);
        }
        
        return self::$instance;
    }

    /**
     * Initialize the API client.
     *
     * @return void
     */
    private function initializeClient(): void {
        global $CFG;
        
        $wsdlfile = $CFG->dirroot . '/local/evento/wsdl/' . $this->config->wswsdlfilename;
        
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
        
        $this->client = new client($wsdlfile, $options, $this->cachemanager, $this->logger);
    }

    /**
     * Set a new trace handler.
     *
     * @param \progress_trace $trace The trace handler
     * @return void
     */
    public function setTrace(\progress_trace $trace): void {
        $this->logger->setTrace($trace);
    }

    /**
     * Get the repository instance.
     *
     * @return repository The repository
     */
    public function getRepository(): repository {
        return $this->repository;
    }

    /**
     * Get the API client instance.
     *
     * @return client The API client
     */
    public function getClient(): client {
        return $this->client;
    }

    /**
     * Get the cache manager instance.
     *
     * @return cache_manager The cache manager
     */
    public function getCacheManager(): cache_manager {
        return $this->cachemanager;
    }

    /**
     * Get the logger instance.
     *
     * @return logger The logger
     */
    public function getLogger(): logger {
        return $this->logger;
    }
    
    /**
     * Get the response processor instance.
     *
     * @return response_processor The response processor
     */
    public function getResponseProcessor(): response_processor {
        return $this->responseprocessor;
    }

    /**
     * Test connection to the Evento API.
     *
     * @return bool True if connection successful
     */
    public function testConnection(): bool {
        try {
            return $this->repository->testConnection();
        } catch (\Exception $e) {
            $this->logger->error('Connection test failed: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Convert an Active Directory SID to a Shibboleth ID.
     *
     * @param string $sid The Active Directory SID
     * @return string The Shibboleth ID
     */
    public function sidToShibbolethId(string $sid): string {
        return trim(str_replace($this->config->adsidprefix, '', $sid) . $this->config->adshibbolethsuffix);
    }

    /**
     * Convert a Shibboleth ID to an Active Directory SID.
     *
     * @param string $shibbolethid The Shibboleth ID
     * @return string The Active Directory SID
     */
    public function shibbolethIdToSid(string $shibbolethid): string {
        return trim($this->config->adsidprefix . str_replace($this->config->adshibbolethsuffix, '', $shibbolethid));
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
     * Create an instance for testing.
     *
     * @param client $client Mock API client
     * @param repository $repository Mock repository
     * @param \progress_trace|null $trace Optional trace handler
     * @return service Service instance with mock dependencies
     */
    public static function createForTesting(client $client, repository $repository, ?\progress_trace $trace = null): service {
        return new self($trace, $client, $repository);
    }

    /**
     * Reset the singleton instance (primarily for testing).
     *
     * @return void
     */
    public static function resetInstance(): void {
        self::$instance = null;
    }
}