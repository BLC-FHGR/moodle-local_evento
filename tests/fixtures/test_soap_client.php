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

namespace local_evento;

/**
 * Test double for the soap client used by local_evento_evento_service.
 *
 * The service calls the operations of the webservice as plain methods, so the double
 * catches them with __call() and records them. This is deliberately hand written
 * instead of a PHPUnit mock, because mocking undefined methods is deprecated.
 *
 * @package    local_evento
 * @copyright  2026 FH Graubuenden
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class test_soap_client {

    /** @var array the recorded calls, each entry has the keys "operation" and "request" */
    public $calls = array();

    /** @var array the configured answers, keyed by operation name */
    protected $responses = array();

    /**
     * Constructor.
     *
     * @param array $responses answers keyed by operation name. A Throwable is thrown
     *                         instead of being returned.
     */
    public function __construct(array $responses = array()) {
        $this->responses = $responses;
    }

    /**
     * Answers a webservice operation.
     *
     * @param string $name the operation name
     * @param array $arguments the arguments, the first one being the request
     * @return mixed the configured answer
     * @throws \Throwable the configured throwable, or a RuntimeException for an unconfigured operation
     */
    public function __call($name, $arguments) {
        $this->calls[] = array(
            'operation' => $name,
            'request' => $arguments[0] ?? null,
        );

        if (!array_key_exists($name, $this->responses)) {
            throw new \RuntimeException("The test double has no answer configured for the operation '{$name}'.");
        }

        $response = $this->responses[$name];
        if ($response instanceof \Throwable) {
            throw $response;
        }

        return $response;
    }

    /**
     * Returns the number of recorded calls.
     *
     * @param string|null $operation count only the calls of this operation, null for all
     * @return int the number of calls
     */
    public function count_calls(?string $operation = null): int {
        if (is_null($operation)) {
            return count($this->calls);
        }

        return count(array_filter($this->calls, function($call) use ($operation) {
            return $call['operation'] === $operation;
        }));
    }

    /**
     * Returns a recorded call.
     *
     * @param int $index the zero based index of the call
     * @return array|null the call with the keys "operation" and "request", null if there is none
     */
    public function get_call(int $index = 0): ?array {
        return $this->calls[$index] ?? null;
    }
}
