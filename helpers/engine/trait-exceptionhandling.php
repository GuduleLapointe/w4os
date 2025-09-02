<?php
/**
 * Exception Handling Trait
 * 
 * Provides consistent exception handling for engine classes.
 * Classes using this trait can return exceptions instead of throwing them,
 * allowing for graceful error handling.
 */

namespace OpenSim\Engine;

trait ExceptionHandling {
    /**
     * @var \Exception|null Exception object if an error occurred
     */
    protected $e = null;
    
    /**
     * Return true if object has an exception, false otherwise
     * 
     * @return bool True if exception exists
     */
    public function isException() {
        return $this->e instanceof \Exception;
    }

    /**
     * Return true if object has an error, false otherwise
     * Alias for isException() for backward compatibility
     * 
     * @return bool True if error exists
     */
    public function isError() {
        return $this->isException();
    }
    
    /**
     * Get exception message if object has an exception
     * 
     * @return string|null Exception message or null
     */
    public function getExceptionMessage() {
        return $this->e instanceof \Exception ? $this->e->getMessage() : null;
    }

    /**
     * Get error message (alias for getExceptionMessage for compatibility)
     * 
     * @return string|null Error message or null  
     */
    public function getError() {
        return $this->getExceptionMessage();
    }
    
    /**
     * Get the exception object
     * 
     * @return \Exception|null Exception object or null
     */
    public function getException() {
        return $this->e;
    }
    
    /**
     * Set an exception on this object
     * 
     * @param \Exception $exception Exception to set
     * @return void
     */
    protected function setException(\Exception $exception) {
        $this->e = $exception;
    }
    
    /**
     * Clear any existing exception
     * 
     * @return void
     */
    protected function clearException() {
        $this->e = null;
    }
    
    /**
     * Check if object is in a valid state (no exceptions)
     * 
     * @return bool True if valid, false if has exception
     */
    public function isValid() {
        return !$this->isException();
    }
}
