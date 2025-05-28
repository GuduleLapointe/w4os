<?php
/**
 * W4OS Database Engine
 * 
 * Core database functionality that can be used by both WordPress and helpers.
 * This will contain all the database connection and query logic.
 */

class OSPDO extends PDO {
    public $connected = false;
    
    public function __construct($dsn, $username = null, $password = null, $driver_options = null) {
        // First handle the different attributes formatting
		if ( WP_DEBUG && WP_DEBUG_DISPLAY ) {
			$this->show_errors();
		}

		$args = func_get_args();
		if ( count( $args ) == 1 && is_string( $args[0] ) ) {
			// If a single string is passed, assume it's service URI.
			$url_parts   = parse_url( $args[0] );
			$serviceURI  = $url_parts['host'] . ( empty( $url_parts['port'] ) ? '' : ':' . $url_parts['port'] );
			$credentials = W4OS2to3::get_credentials( $serviceURI );

			$db_enabled = $credentials['db']['enabled'] ?? false;
			if ( ! $db_enabled ) {
				return false;
			}

			$username    = $credentials['db']['user'];
			$password = $credentials['db']['pass'];
			$dbname     = $credentials['db']['name'];
			$dbhost     = $credentials['db']['host'] . ( empty( $credentials['db']['port'] ) ? '' : ':' . $credentials['db']['port'] );
		} elseif ( is_array( $args[0] ) ) {
			// If args are passed as an array, extract them.
			$credentials = OpenSim::parse_args(
				$args[0],
				array(
					'user'     => null,
					'pass'     => null,
					'database' => null,
					'host'     => null,
					'port'     => null,
				)
			);
			$username     = $credentials['user'];
			$password  = $credentials['pass'];
			$dbname      = $credentials['database'];
			$dbhost      = $credentials['host'] . ( empty( $credentials['port'] ) ? '' : ':' . $credentials['port'] );
		}

		// Use the `mysqli` extension if it exists unless `WP_USE_EXT_MYSQL` is defined as true.
		if ( function_exists( 'mysqli_connect' ) ) {
			$this->use_mysqli = true;

			// Set mysqli connection timeout to 5 seconds
			ini_set( 'mysqli.connect_timeout', 1 );

			if ( defined( 'WP_USE_EXT_MYSQL' ) ) {
				$this->use_mysqli = ! WP_USE_EXT_MYSQL;
			}
		}

		// Actual db_connect() attempt can take forever is remote connection is not allowed.
		// So, we should first make a quick test to verify the remote port is accessible
		// before attempting to connect to the database.

		$url_parts = parse_url( $dbhost );
		$test_host = $url_parts['host'];
		$test_port = $url_parts['port'] ?? 3306;
		$socket    = @fsockopen( $test_host, $test_port, $errno, $errstr, 1 );
		if ( ! $socket ) {
			$error = "Failed to connect to the database server: $errstr";
			error_log( $error );
			// If the port is not accessible, we should not attempt to connect to the database.
			return new WP_Error( 'db_connect_error', $error );
		}

        $dsn = 'mysql:host=' . $dbhost . ';dbname=' . $dbname;

        try {
            @parent::__construct($dsn, $username, $password, $driver_options);
            $this->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $this->connected = true;
        } catch (PDOException $e) {
            error_log("Could not connect to database $dsn as $username");
            $this->connected = false;
        }
    }

    /**
     * Prepare SQL query, execute with params and log error if any
     *
     * @param  string $query
     * @param  array  $params     substitute markers passed to execute()
     * @param  array  $options    options passed to prepare()
     * @return PDOstatement if success, false on error
     */
    public function prepareAndExecute($query, $params = null, $options = array()) {
        $trace = debug_backtrace()[0];
        $trace = $trace['file'] . ':' . $trace['line'];

        $statement = $this->prepare($query, $options);
        $result = $statement->execute($params);

        if ($result) {
            return $statement;
        }

        error_log('Error ' . $statement->errorCode() . ' ' . $statement->errorInfo()[2] . ' ' . $trace);
        return false;
    }

    public function insert($table, $values) {
        $markers = [];
        foreach ($values as $field => $value) {
            $markers[] = ':' . $field;
        }
        $markers = implode(',', $markers);
        $fields = implode(',', array_keys($values));
        $sql = "INSERT INTO $table ($fields) VALUES ($markers)";
        $statement = $this->prepare($sql);
        return $statement->execute($values);
    }

    /**
     * Get single value using PDO standard
     */
    public function get_var($query, $params = array()) {
        $statement = $this->prepareAndExecute($query, $params);
        if ($statement) {
            $result = $statement->fetchColumn();
            $statement->closeCursor();
            return $result;
        }
        return false;
    }

    /**
     * Get single column as array using PDO standard
     */
    public function get_column($query, $params = array(), $column_offset = 0) {
        // Bypass if get_col() exists, not validated yet, keep for further testing
        // if ( method_exists( $this, 'get_col' ) ) {
        //     return $this->get_col( $query, $params, $column_offset );
        // }

        $statement = $this->prepareAndExecute($query, $params);
        if ($statement) {
            $results = array();
            while ($row = $statement->fetchColumn($column_offset)) {
                $results[] = $row;
            }
            $statement->closeCursor();
            return $results;
        }
        return array();
    }

    /**
     * Get single row using PDO standard
     */
    public function get_row($query, $params = array(), $output = PDO::FETCH_OBJ) {
        $statement = $this->prepareAndExecute($query, $params);
        if ($statement) {
            $result = $statement->fetch($output);
            $statement->closeCursor();
            return $result;
        }
        return null;
    }

    /**
     * Get multiple rows using PDO standard
     */
    public function get_results($query, $params = array(), $output = PDO::FETCH_OBJ) {
        $statement = $this->prepareAndExecute($query, $params);
        if ($statement) {
            $results = $statement->fetchAll($output);
            $statement->closeCursor();
            return $results;
        }
        return array();
    }

    /**
     * Check if table exists
     */
    public function table_exists($table) {
        if (!$this->connected) {
            return false;
        }
        
        try {
            $result = $this->query("SELECT 1 FROM $table LIMIT 1");
            return $result !== false;
        } catch (Exception $e) {
            return false;
        }
    }

    /**
     * Check if multiple tables exist
     */
    public function tables_exist($tables) {
        if (is_string($tables)) {
            $tables = array($tables);
        }
        
        foreach ($tables as $table) {
            if (!$this->table_exists($table)) {
                return false;
            }
        }
        return true;
    }
}
