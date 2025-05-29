<?php
/**
 * OpenSim_Exception class
 * 
 * This class extends the Exception class to force logging of all exceptions.
 * 
 * @package magicoli/opensim-helpers
 */

class OpenSim_Exception extends Exception {
    // Properties defined by parent class, for reference:
    // protected string $message = "";
    // private string $string = "";
    // protected int $code;
    // protected string $file = "";
    // protected int $line;
    // private array $trace = [];
    // private ?Throwable $previous = null;

    public function __construct( $message, $code = 0, Exception $previous = null ) {
        parent::__construct( $message, $code, $previous );
        error_log( $this->__toString() );
    }

    // Disabled custom string representation of the exception, it is worst than the default one.
    // public function __toString() {
    //     $prefix = '';
    //     // return __CLASS__ . ": [{$this->code}]: {$this->message}\n";
    //     $message = strip_tags( $this->message );
    //     $trace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 2);
    //     if( ! empty( $trace[1] ) ) {
    //         $class = $trace[1]['class'] ?? '';
    //         $function = $trace[1]['function'] ?? '';
    //     }
    //     if( ! empty( trim ( $class . $function ) ) ) {
    //         $prefix .= '(' . ( empty( $class ) ? '' : $class . '::' ) . $function . ') ';
    //     }
    //     return $prefix . $message;
    // }
}

/**
 * This is temporary, it's misleading to replace Errors with Exceptions,but it's a way
 * to make sure I can replace all new Error() calls with new OpenSim_Exception() calls.
 * 
 * I pledge to check soon, but I spend too much time finetuning the Error catchers, so I
 * want to keep a way to switch back fast if needed.
 * 
 * TODO:
 * - Test again every use case where OpenSim_Error is used, make sure interrupts happen as expected.
 * - Replace all OpenSim_Error calls with OpenSim_Exception.
 * - Remove this class.
 */
class OpenSim_Error extends OpenSim_Exception {

    public function __construct( $message, $code = 0, Exception $previous = null ) {
        parent::__construct( $message, $code, $previous );
        // error_log( $this->__toString() );
    }
}
