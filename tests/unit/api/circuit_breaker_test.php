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
 * Unit tests for the Evento API circuit breaker.
 *
 * @package    local_evento
 * @category   test
 * @copyright  2025 FHGR Julien Rädler
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_evento\tests\unit\api;

defined('MOODLE_INTERNAL') || die();

use local_evento\api\circuit_breaker;
use local_evento\api\circuit_breaker_exception;
use PHPUnit\Framework\TestCase;

/**
 * Test case for the circuit breaker functionality.
 */
class circuit_breaker_test extends TestCase {
    /**
     * Test successful execution.
     */
    public function test_successful_execution() {
        // Create a circuit breaker with default settings
        $circuitBreaker = new circuit_breaker();
        
        // Define a test operation that will succeed
        $operation = function() {
            return 'success';
        };
        
        // Execute the operation through the circuit breaker
        $result = $circuitBreaker->execute($operation);
        
        // Assert that the operation was executed successfully
        $this->assertEquals('success', $result);
        
        // Assert that the circuit is still closed
        $this->assertTrue($circuitBreaker->isClosed());
        
        // Assert that the failure count is zero
        $this->assertEquals(0, $circuitBreaker->getFailureCount());
    }
    
    /**
     * Test that the circuit opens after multiple failures.
     */
    public function test_circuit_opens_after_failures() {
        // Create a circuit breaker with low threshold for testing
        $failureThreshold = 3;
        $circuitBreaker = new circuit_breaker($failureThreshold, 60);
        
        // Define a test operation that will always fail
        $operation = function() {
            throw new \Exception('Test failure');
        };
        
        // Execute the operation multiple times, expecting failures
        for ($i = 0; $i < $failureThreshold; $i++) {
            try {
                $circuitBreaker->execute($operation);
                $this->fail('Expected exception was not thrown');
            } catch (\Exception $e) {
                // Expected exception, do nothing
            }
        }
        
        // Assert that the circuit is now open
        $this->assertTrue($circuitBreaker->isOpen());
        
        // Assert that the failure count matches the threshold
        $this->assertEquals($failureThreshold, $circuitBreaker->getFailureCount());
        
        // Try one more time, should throw circuit_breaker_exception
        try {
            $circuitBreaker->execute($operation);
            $this->fail('Expected circuit_breaker_exception was not thrown');
        } catch (circuit_breaker_exception $e) {
            $this->assertEquals(circuit_breaker_exception::CIRCUIT_OPEN, $e->getCode());
        }
    }
    
    /**
     * Test half-open state after timeout.
     */
    public function test_half_open_state_after_timeout() {
        // Create a circuit breaker with short timeout
        $circuitBreaker = new circuit_breaker(2, 1); // 1 second timeout
        
        // Force the circuit to open state
        $circuitBreaker->setState(circuit_breaker::STATE_OPEN);
        
        // Set last failure time to now
        $reflection = new \ReflectionClass($circuitBreaker);
        $property = $reflection->getProperty('lastfailuretime');
        $property->setAccessible(true);
        $property->setValue($circuitBreaker, time());
        
        // Verify circuit is open
        $this->assertTrue($circuitBreaker->isOpen());
        
        // Define a test operation
        $operation = function() {
            return 'success';
        };
        
        // Try to execute, should throw exception
        try {
            $circuitBreaker->execute($operation);
            $this->fail('Expected circuit_breaker_exception was not thrown');
        } catch (circuit_breaker_exception $e) {
            $this->assertEquals(circuit_breaker_exception::CIRCUIT_OPEN, $e->getCode());
        }
        
        // Wait for timeout
        sleep(2);
        
        // Now execution should be allowed, circuit should be half-open
        $result = $circuitBreaker->execute($operation);
        $this->assertEquals('success', $result);
        
        // Circuit should be closed after successful execution in half-open state
        $this->assertTrue($circuitBreaker->isClosed());
        $this->assertEquals(0, $circuitBreaker->getFailureCount());
    }
    
    /**
     * Test that the circuit stays open if execution fails in half-open state.
     */
    public function test_circuit_stays_open_on_half_open_failure() {
        // Create a circuit breaker
        $circuitBreaker = new circuit_breaker(2, 60);
        
        // Force the circuit to half-open state
        $circuitBreaker->setState(circuit_breaker::STATE_HALF_OPEN);
        
        // Verify circuit is half-open
        $this->assertTrue($circuitBreaker->isHalfOpen());
        
        // Define a test operation that will fail
        $operation = function() {
            throw new \Exception('Test failure');
        };
        
        // Try to execute, should throw the original exception
        try {
            $circuitBreaker->execute($operation);
            $this->fail('Expected exception was not thrown');
        } catch (\Exception $e) {
            $this->assertEquals('Test failure', $e->getMessage());
        }
        
        // Circuit should be open after failed execution in half-open state
        $this->assertTrue($circuitBreaker->isOpen());
    }
    
    /**
     * Test manual state changes.
     */
    public function test_manual_state_changes() {
        // Create a circuit breaker
        $circuitBreaker = new circuit_breaker();
        
        // Default state should be closed
        $this->assertTrue($circuitBreaker->isClosed());
        
        // Set to open state
        $circuitBreaker->setState(circuit_breaker::STATE_OPEN);
        $this->assertTrue($circuitBreaker->isOpen());
        
        // Set to half-open state
        $circuitBreaker->setState(circuit_breaker::STATE_HALF_OPEN);
        $this->assertTrue($circuitBreaker->isHalfOpen());
        
        // Set back to closed state
        $circuitBreaker->setState(circuit_breaker::STATE_CLOSED);
        $this->assertTrue($circuitBreaker->isClosed());
    }
    
    /**
     * Test invalid state doesn't change the current state.
     */
    public function test_invalid_state_value() {
        // Create a circuit breaker
        $circuitBreaker = new circuit_breaker();
        
        // Default state should be closed
        $this->assertTrue($circuitBreaker->isClosed());
        
        // Try to set an invalid state
        $invalidState = 999;
        $circuitBreaker->setState($invalidState);
        
        // State should still be closed
        $this->assertTrue($circuitBreaker->isClosed());
    }
}