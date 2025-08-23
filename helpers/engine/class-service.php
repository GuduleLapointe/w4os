<?php
/**
 * OpenSimulator Service Engine
 *
 * Framework-agnostic service connection management for OpenSimulator instances.
 * Handles connections to grid services and simulators including:
 * - URI connections for basic information fetching
 * - Database connections for direct queries and updates
 * - Remote console connections for console access
 *
 * @package OpenSim Engine
 * @version 0.1
 */

class OpenSim_Service {
    protected $serviceURI;
    protected $credentials;
    protected $serviceType;

    protected static $consoles = array(); // Ensure each console is initialized only once
    protected static $dbs = array();      // Ensure each db is initialized only once

    protected $console;
    public $db;

    public function __construct($serviceURI = null, $credentials = null) {
        if ($serviceURI) {
            $this->serviceURI = $serviceURI;
            $this->credentials = $credentials;
            $this->init_console();
            $this->init_db();
        }
    }

    /**
     * Initialize database connection
     */
    public function init_db() {
        if ($this->db) {
            return $this->db;
        }
        if (isset(self::$dbs[$this->serviceURI])) {
            $this->db = self::$dbs[$this->serviceURI];
            return $this->db;
        }

        $db_creds = $this->credentials['db'] ?? null;
        if (empty($db_creds) || empty($db_creds['enabled'])) {
            return false;
        }

        $this->db = new OSPDO($this->serviceURI);
        if ($this->is_error($this->db)) {
            error_log('Database connection error: ' . $this->get_error_message($this->db));
            $this->db = false;
        } elseif ($this->db) {
            $tables = $this->db->get_results('SHOW TABLES');
            if (!$tables || count($tables) === 0) {
                $this->db = false;
            }
        }
        
        if ($this->db) {
            self::$dbs[$this->serviceURI] = $this->db;
        }
        return $this->db;
    }

    /**
     * Initialize console connection
     */
    public function init_console() {
        if ($this->console) {
            return $this->console;
        }
        if (isset(self::$consoles[$this->serviceURI])) {
            $this->console = self::$consoles[$this->serviceURI];
            return $this->console;
        }

        $console_creds = $this->credentials['console'] ?? null;
        if (empty($console_creds) || empty($console_creds['enabled'])) {
            return false;
        }

        $rest_args = array(
            'uri'         => $console_creds['host'] . ':' . $console_creds['port'],
            'ConsoleUser' => $console_creds['user'],
            'ConsolePass' => $console_creds['pass'],
        );

        $this->console = false;
        $rest = new OpenSim_Rest($rest_args);
        
        if ($this->is_rest_error($rest->error ?? null)) {
            error_log(__METHOD__ . ' ' . $this->get_error_message($rest->error));
            $response = $rest->error;
        } else {
            $response = $rest->sendCommand('show info');
            if ($this->is_rest_error($response)) {
                $response = $this->create_error('console_command_failed', $this->get_error_message($response));
            } else {
                $this->console = $rest;
            }
        }

        self::$consoles[$this->serviceURI] = $this->console;
        return $response;
    }

    /**
     * Check if console is connected
     */
    public function console_connected() {
        return ($this->console && $this->console !== false);
    }

    /**
     * Check if database is connected
     */
    public function db_connected() {
        return ($this->db->ready ?? false);
    }

    /**
     * Send command to console and return result
     * If command is empty, return console status (true/false)
     *
     * @param string $command
     * @return mixed Error on failure, boolean on status, or response array
     */
    public function console($command = null) {
        if (empty($this->serviceURI) || empty($this->credentials)) {
            error_log(__METHOD__ . ' missing arguments to use console.');
            return false;
        }

        // Initialize console, return false if failed
        if (!$this->init_console()) {
            error_log(__METHOD__ . ' console initialization failed.');
            return false;
        }
        if ($this->is_error($this->console)) {
            error_log('console error: ' . $this->get_error_message($this->console));
            return false;
        }

        // Send command to console
        if ($this->console && !empty($command)) {
            $response = $this->console->sendCommand($command);
            if ($this->is_rest_error($response)) {
                $error = $this->create_error('console_command_failed', $this->get_error_message($response));
                error_log('console error: ' . $this->get_error_message($error));
                return $error;
            } else {
                return $response;
            }
        } else {
            return ($this->console) ? true : false;
        }
    }

    /**
     * Framework-agnostic error checking
     * Override in framework-specific implementations
     */
    protected function is_error($obj) {
        return false;
    }

    /**
     * Framework-agnostic REST error checking
     * Override in framework-specific implementations
     */
    protected function is_rest_error($obj) {
        return function_exists('is_opensim_rest_error') ? is_opensim_rest_error($obj) : false;
    }

    /**
     * Framework-agnostic error message extraction
     * Override in framework-specific implementations
     */
    protected function get_error_message($error) {
        if (is_object($error) && method_exists($error, 'getMessage')) {
            return $error->getMessage();
        }
        return 'Unknown error';
    }

    /**
     * Framework-agnostic error creation
     * Override in framework-specific implementations
     */
    protected function create_error($code, $message) {
        return array('error' => $code, 'message' => $message);
    }

    /**
     * Get service URI
     */
    public function get_service_uri() {
        return $this->serviceURI;
    }
}
