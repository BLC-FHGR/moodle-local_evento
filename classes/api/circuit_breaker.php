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
 * Circuit breaker implementation for handling API failures.
 *
 * @package    local_evento
 * @copyright  2025 FHGR Julien Rädler
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_evento\api;

defined('MOODLE_INTERNAL') || die();

/**
 * Circuit breaker for API resilience.
 * 
 * This class implements the circuit breaker pattern to prevent cascading failures
 * when the Evento API is experiencing issues. It keeps track of failures and
 * temporarily prevents calls if too many failures occur in a short time period.
 */
class circuit_breaker {
    /** @var int The current state of the circuit breaker */
    private int $state;

    /** @var int The number of consecutive failures */
    private int $failurecount;

    /** @var int The threshold of failures before opening the circuit */
    private int $failurethreshold;

    /** @var int The time in seconds to wait before attempting to close the circuit again */
    private int $resettimeout;

    /** @var int Timestamp of the last failure */
    private int $lastfailuretime;

    /** @var int State constants: circuit is closed (normal operation) */
    const STATE_CLOSED = 0;

    /** @var int State constants: circuit is open (preventing calls) */
    const STATE_OPEN = 1;

    /** @var int State constants: circuit is half-open (testing if system has recovered) */
    const STATE_HALF_OPEN = 2;

    /**
     * Constructor.
     *
     * @param int $failurethreshold Number of failures before opening the circuit
     * @param int $resettimeout Time in seconds to wait before testing the circuit again
     */
    public function __construct(int $failurethreshold = 5, int $resettimeout = 300) {
        $this->state = self::STATE_CLOSED;
        $this->failurecount = 0;
        $this->failurethreshold = $failurethreshold;
        $this->resettimeout = $resettimeout;
        $this->lastfailuretime = 0;
    }

    /**
     * Execute a function with circuit breaker protection.
     *
     * @param callable $operation The operation to execute
     * @return mixed The result of the operation
     * @throws \Exception If the circuit is open or the operation fails
     */
    public function execute(callable $operation) {
        if ($this->state === self::STATE_OPEN) {
            // Check if it's time to try again
            if ((time() - $this->lastfailuretime) > $this->resettimeout) {
                $this->state = self::STATE_HALF_OPEN;
            } else {
                throw new circuit_breaker_exception(
                    'Circuit breaker is open - too many failures',
                    circuit_breaker_exception::CIRCUIT_OPEN
                );
            }
        }

        try {
            $result = $operation();

            // If we're in half-open state and the call succeeded, reset the circuit
            if ($this->state === self::STATE_HALF_OPEN) {
                $this->reset();
            }

            return $result;
        } catch (\Exception $e) {
            $this->recordFailure();

            // Re-throw the original exception
            throw $e;
        }
    }

    /**
     * Record a failure and potentially open the circuit.
     *
     * @return void
     */
    private function recordFailure(): void {
        $this->failurecount++;
        $this->lastfailuretime = time();

        if ($this->failurecount >= $this->failurethreshold) {
            $this->state = self::STATE_OPEN;
        }
    }

    /**
     * Reset the circuit breaker to closed state.
     *
     * @return void
     */
    private function reset(): void {
        $this->state = self::STATE_CLOSED;
        $this->failurecount = 0;
    }

    /**
     * Get the current state of the circuit breaker.
     *
     * @return int One of the STATE_* constants
     */
    public function getState(): int {
        return $this->state;
    }

    /**
     * Get the current failure count.
     *
     * @return int The number of consecutive failures
     */
    public function getFailureCount(): int {
        return $this->failurecount;
    }

    /**
     * Force the circuit to a specific state (primarily for testing).
     *
     * @param int $state The state to set
     * @return void
     */
    public function setState(int $state): void {
        if (in_array($state, [self::STATE_CLOSED, self::STATE_OPEN, self::STATE_HALF_OPEN])) {
            $this->state = $state;
        }
    }

    /**
     * Check if the circuit is currently open.
     *
     * @return bool True if the circuit is open
     */
    public function isOpen(): bool {
        return $this->state === self::STATE_OPEN;
    }

    /**
     * Check if the circuit is currently closed.
     *
     * @return bool True if the circuit is closed
     */
    public function isClosed(): bool {
        return $this->state === self::STATE_CLOSED;
    }

    /**
     * Check if the circuit is currently half-open.
     *
     * @return bool True if the circuit is half-open
     */
    public function isHalfOpen(): bool {
        return $this->state === self::STATE_HALF_OPEN;
    }
}

/**
 * Exception thrown by the circuit breaker.
 */
class circuit_breaker_exception extends \Exception {
    /** @var int Error code for when the circuit is open */
    const CIRCUIT_OPEN = 1000;
}