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

namespace local_evento\service;

use local_evento\dto\course_info;
use local_evento\dto\enrollment_collection;
use local_evento\dto\enrollment_info;
use local_evento\parser\anlassnummer_parser;
use local_evento\exception\evento_service_exception;
use local_evento\soap\soap_client_factory; 

/**
 * Class soap_evento_service
 * SOAP Implementation of EventoService
 *
 * @package    local_evento
 * @copyright  2025 YOUR NAME <your@email.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class soap_evento_service implements evento_service {
    
    private ?\SoapClient $soap_client = null;
    private anlassnummer_parser $parser;
    private \local_evento\util\desenvolvimento_logger $logger;

    public function __construct() {
        $this->parser = new anlassnummer_parser();
        $this->logger = new \local_evento\util\desenvolvimento_logger();
    }

    public function get_course_info(string $anlassnummer): course_info {
        try {
            $this->logger->log_request('get_course_info', $anlassnummer);
            
            $parsed = $this->parser->parse($anlassnummer);
            $raw_data = $this->fetch_raw_course_data($anlassnummer);
            
            if (empty($raw_data)) {
                throw new evento_service_exception("No course data found for: {$anlassnummer}");
            }

            $course_info = $this->map_to_course_info($raw_data, $parsed);
            
            $this->logger->log_response('get_course_info', $course_info);
            return $course_info;
            
        } catch (\SoapFault $e) {
            $this->logger->log_error('get_course_info', $e);
            throw new evento_service_exception("Failed to fetch course info: " . $e->getMessage(), $e);
        }
    }

    public function get_enrollments(string $anlassnummer): enrollment_collection {
        try {
            $this->logger->log_request('get_enrollments', $anlassnummer);
            
            $raw_enrollments = $this->fetch_raw_enrollment_data($anlassnummer);
            $enrollments = $this->map_to_enrollment_collection($anlassnummer, $raw_enrollments);
            
            $this->logger->log_response('get_enrollments', [
                'anlassnummer' => $anlassnummer,
                'count' => $enrollments->get_total_count()
            ]);
            
            return $enrollments;
            
        } catch (\SoapFault $e) {
            $this->logger->log_error('get_enrollments', $e);
            throw new evento_service_exception("Failed to fetch enrollments: " . $e->getMessage(), $e);
        }
    }

    public function is_service_available(): bool {
        try {
            $client = $this->get_soap_client();
            
            // Simple test call - list event types with minimal result limit
            $request = [
                'theLimitationFilter2' => [
                    'theMaxResultsValue' => 1
                ]
            ];
            
            $result = $client->listEventoAnlassTyp($request);
            return property_exists($result, 'return');
            
        } catch (\Exception $e) {
            $this->logger->log_error('is_service_available', $e);
            return false;
        }
    }

    /**
     * Get or create SOAP client
     */
    private function get_soap_client(): \SoapClient {
        if ($this->soap_client === null) {
            $this->soap_client = soap_client_factory::create();
        }
        return $this->soap_client;
    }

    /**
     * Fetch raw course data from Evento SOAP API
     */
    private function fetch_raw_course_data(string $anlassnummer): ?object {
        $client = $this->get_soap_client();
        
        $request = [
            'theEventoAnlassFilter' => [
                'anlassNummer' => $anlassnummer
            ],
            'theLimitationFilter2' => [
                'theMaxResultsValue' => 10
            ]
        ];
        
        $result = $client->listEventoAnlass($request);
        
        if (!property_exists($result, 'return') || empty($result->return)) {
            return null;
        }
        
        // Handle both single object and array responses
        $events = is_array($result->return) ? $result->return : [$result->return];
        
        // Find exact match
        foreach ($events as $event) {
            if ($event->anlassNummer === $anlassnummer) {
                return $event;
            }
        }
        
        return null;
    }

    /**
     * Fetch raw enrollment data from Evento SOAP API
     */
    private function fetch_raw_enrollment_data(string $anlassnummer): array {
        // First get the course to get the event ID
        $course_data = $this->fetch_raw_course_data($anlassnummer);
        if (!$course_data || !property_exists($course_data, 'idAnlass')) {
            return [];
        }

        $client = $this->get_soap_client();
        
        $request = [
            'theEventoPersonenAnmeldungFilter' => [
                'idAnlass' => $course_data->idAnlass
            ],
            'theLimitationFilter2' => [
                'theMaxResultsValue' => 1000
            ]
        ];
        
        $result = $client->listEventoPersonenAnmeldung($request);
        
        if (!property_exists($result, 'return') || empty($result->return)) {
            return [];
        }
        
        return is_array($result->return) ? $result->return : [$result->return];
    }

    /**
     * Map raw SOAP data to CourseInfo DTO
     */
    private function map_to_course_info(object $raw_data, \local_evento\dto\parsed_anlassnummer $parsed): course_info {
        $start_date = null;
        $end_date = null;
        
        if (!empty($raw_data->anlassDatumVon)) {
            $start_date = new \DateTime($raw_data->anlassDatumVon);
        }
        
        if (!empty($raw_data->anlassDatumBis)) {
            $end_date = new \DateTime($raw_data->anlassDatumBis);
        }
        
        return new course_info(
            $parsed,  // Pass the whole parsed object instead of individual components
            $raw_data->anlassBezeichnung ?? '',
            $start_date,
            $end_date,
            $raw_data->idAnlassStatus ?? ''
        );
    }

    /**
     * Map raw enrollment data to EnrollmentCollection
     */
    private function map_to_enrollment_collection(string $anlassnummer, array $raw_enrollments): enrollment_collection {
        $enrollments = [];
        
        foreach ($raw_enrollments as $raw_enrollment) {
            $enrollment_date = null;
            if (!empty($raw_enrollment->erfassung)) {
                $enrollment_date = new \DateTime($raw_enrollment->erfassung);
            }
            
            $enrollments[] = new enrollment_info(
                $raw_enrollment->idPerson ?? 0,
                $raw_enrollment->iDPAStatus ?? '',
                'student', // Default to student, teachers handled separately
                $enrollment_date
            );
        }
        
        return new enrollment_collection($anlassnummer, $enrollments);
    }
}
