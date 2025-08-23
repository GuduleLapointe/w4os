<?php
/**
 * W4OS Database Engine
 * 
 * Core database functionality that can be used by both WordPress and helpers.
 * This will contain all the database connection and query logic.
 * 
 * This class extends PDO and provides additional methods for common operations.
 * 
 * Accepts mixed arguments:
 * - DSN string (e.g. 'mysql:host=localhost;dbname=mydb')
 * - Array with credentials:
 *   - 'user' => 'username'
 *   - 'pass' => 'password'
 *   - 'name' => 'database_name'
 *   - 'host' => 'localhost'
 *   - 'port' => 3306 (optional, defaults to 3306)
 * - Single string with service URI (e.g. 'http://localhost:8002')
 * 
 * @package magicoli/opensim-helpers
 * @since 3.0.0
 */

class OSPDO extends PDO {
    public $connected = false;
    public $db_name;
    
    public function __construct($dsn, $username = null, $password = null, $driver_options = null) {
        // TODO: move this UI-related code in the closest UI calling class/function
        if ( Engine_Settings::get( 'show_errors' ) ) {
            $this->show_errors();
		}
        
        // First handle the different attributes formatting
		$args = func_get_args();
		if ( count( $args ) == 1 && is_string( $args[0] ) ) {
			// If a single string is passed, assume it's service URI.
			$url_parts   = parse_url( $args[0] );
			$serviceURI  = $url_parts['host'] . ( empty( $url_parts['port'] ) ? '' : ':' . $url_parts['port'] );
			$credentials = W4OS3::get_credentials( $serviceURI );

			$db_enabled = $credentials['db']['enabled'] ?? false;
			if ( ! $db_enabled ) {
                $this->connected = false;
				return false;
			}

			$username    = $credentials['db']['user'];
			$password = $credentials['db']['pass'];
			$dbname     = $credentials['db']['name'] ?? $credentials['db']['database'] ?? null;
			$dbhost     = $credentials['db']['host'] . ( empty( $credentials['db']['port'] ) ? '' : ':' . $credentials['db']['port'] );
		} elseif ( is_array( $args[0] ) ) {
			// If args are passed as an array, extract them.
			$credentials = OpenSim::parse_args(
				$args[0],
				array(
					'user'     => null,
					'pass'     => null,
					'name' => null,
					'host'     => null,
					'port'     => null,
				)
			);
			$username     = $credentials['user'];
			$password  = $credentials['pass'];
			$dbname      = $credentials['name'] ?? $credentials['database'] ?? null;
			$dbhost      = $credentials['host'] . ( empty( $credentials['port'] ) ? '' : ':' . $credentials['port'] );
		} else {
            // split $dsn string formatted as 'mysql:host=$dbhost;dbname=$dbname' into $dbhost and $dbname
            $dsn_parts = explode(';', $dsn);
            $dbhost = str_replace('mysql:host=', '', $dsn_parts[0]);
            $dbname = str_replace('dbname=', '', $dsn_parts[1] ?? '');
            $username = $username ?? null;
            $password = $password ?? null;
            $credentials = array(
                'user' => $username,
                'pass' => $password,
                'name' => $dbname,
                'host' => $dbhost,
            );
        }

        if( empty($dbname)) {
            error_log("Database name is empty, aborting connection to " . print_r($dsn, true));
            return new Exception("Database name is empty, cannot connect to " . print_r($dsn, true));
        }

		// Fast connection check before attempting lengthy PDO connection
        // TODO: make connection check a global OpenSim method for any kind of service connection
		$url_parts = parse_url( $dbhost );
		$test_host = $url_parts['host'] ?? $url_parts['path'] ?? 'localhost';
		$test_port = $url_parts['port'] ?? 3306;
		// $socket    = @fsockopen( $test_host, $test_port, $errno, $errstr, 1 );
		// if ( ! $socket ) {
        $connection_test = service_available($test_host, $test_port);
        if(!service_available($test_host, $test_port))  {
			throw new Exception( "Failed to connect to the database server: " . print_r($connection_test, true) );
		}
        
        $dsn = 'mysql:host=' . $dbhost . ';dbname=' . $dbname;

        try {
            @parent::__construct($dsn, $username, $password, $driver_options);
            $this->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $this->connected = true;
            // DEBUG check if the db is really connected
            if ($this->connected) {
                try {
                    $this->query("SELECT 1");
                    $this->db_name = $dbname;
                } catch (PDOException $e) {
                    error_log("Database connection failed: " . $e->getMessage());
                    $this->connected = false;
                }
            }
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
            return $result;
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
    public function get_col($query, $params = array(), $column_offset = 0) {
        // TODO: if parent get_col() exists return its output and ignore this method

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

    /**
     * Convert .NET connection string to array format
     * 
     * @param string $connectionstring .NET connection string
     * @return array Array with keys: saveformat, type, host, port, name, user, pass
     */
	public static function connectionstring_to_array( $connectionstring, $provider = 'mysql' ) {
        if(is_array($connectionstring)) {
            // If already an array, just return it
            return $connectionstring;
        }
		$parts = explode( ';', $connectionstring );
		$creds = array();
		foreach ( $parts as $part ) {
			if (empty(trim($part))) continue; // Skip empty parts
			$pair              = explode( '=', $part, 2 ); // Limit to 2 parts in case value contains =
			if (count($pair) === 2) {
				$creds[ trim($pair[0]) ] = trim($pair[1]);
			}
		}
        if( preg_match( '/:[0-9]+$/', $creds['Data Source'] ?? '' ) ) {
            $host = explode( ':', $creds['Data Source'] );
            $creds['Data Source'] = $host[0];
            $creds['Port'] = empty( $host[1] ) || $host[1] == 3306 ? null : $host[1];
        }
        switch ( $provider ) {
            // TODO: test pgsql before enabling

            // case 'OpenSim.Data.PGSQL.dll':
            // case 'pgsql':
            // case 'postgres':
            // case 'postgresql':
            // case 'posql':
            //     $type = 'pgsql';
            //     // PostgreSQL specific handling if needed
            //     break;

            case 'OpenSim.Data.MySQL.dll':
            case 'mysql':
            default:
                $type = 'mysql';
        }

        $result = array(
            'type' => $type,
            'host' => $creds['Data Source'] ?? '',
            'port' => $creds['Port'] ?? null,
            'name' => $creds['Database'] ?? '',
            'user' => $creds['User ID'] ?? '',
            'pass' => $creds['Password'] ?? '',
            'saveformat' => 'connection_string',
            'ConnectionString' => $connectionstring, // Preserve original for reference
        );
		return $result;
	}

    // Version from Helpers, probably not better than the one above
	// public static function connectionstring_to_array( $connectionstring ) {
	// 	$parts = explode( ';', $connectionstring );
	// 	$creds = array();
	// 	foreach ( $parts as $part ) {
	// 		$pair              = explode( '=', $part );
	// 		$creds[ $pair[0] ] = $pair[1] ?? '';
	// 	}
    //     if( preg_match( '/:[0-9]+$/', $creds['Data Source'] ) ) {
    //         $host = explode( ':', $creds['Data Source'] );
    //         $creds['Data Source'] = $host[0];
    //         $creds['Port'] = empty( $host[1] || $host[1] == 3306 ) ? null : $creds['Port'];
    //     }
    //     $result = array(
    //         'host' => $creds['Data Source'],
    //         'port' => $creds['Port'] ?? null,
    //         'name' => $creds['Database'],
    //         'user' => $creds['User ID'],
    //         'pass' => $creds['Password'],
    //     );
	// 	return $result;
	// }

	/**
	 * Convert database credentials array back to .NET connection string format
	 * 
	 * @param array $creds Database credentials array
	 * @return string Connection string in .NET format
	 */
	public static function array_to_connectionstring( $creds ) {
		if ( empty($creds) || !is_array($creds) ) {
			return '';
		}

		// If we have the original string and nothing critical changed, use it
		if ( !empty($creds['original_string']) && 
			 !empty($creds['saveformat']) && 
			 $creds['saveformat'] === 'connection_string' ) {
			return $creds['original_string'];
		}

		$parts = array();
		
		// Build Data Source (host:port or just host)
		if ( !empty($creds['host']) ) {
			$data_source = $creds['host'];
			if ( !empty($creds['port']) && $creds['port'] != 3306 ) {
				$data_source .= ':' . $creds['port'];
			}
			$parts[] = 'Data Source=' . $data_source;
		}
		
		if ( !empty($creds['name']) ) {
			$parts[] = 'Database=' . $creds['name'];
		}
		
		if ( !empty($creds['user']) ) {
			$parts[] = 'User ID=' . $creds['user'];
		}
		
		if ( !empty($creds['pass']) ) {
            // TODO: escape password if needed
			$parts[] = 'Password=' . $creds['pass'];
		}
		
		// Add common OpenSim-specific options
		$parts[] = 'Old Guids=true';
		
		return implode(';', $parts) . ';';
	}
    
    public function is_connected() {
        return $this->connected;
    }

    public static function join_query_conditions($conditions, $glue = 'AND') {
        if (empty($conditions)) {
            return '';
        }
        
        $glue = strtoupper(trim($glue));
        if(! in_array($glue, array('AND', 'OR'))) {
            // Stop execution if glue is not AND or OR, query result 
            // would be unpredictable and could lead to SQL injection
            throw new Exception('Invalid glue for query conditions: ' . $glue);
        }

        if (is_array($conditions)) {
            $conditions = array_filter($conditions);
            if (empty($conditions)) {
                return '';
            }
            return implode(" $glue ", $conditions);
        }

        return $conditions;
    }

}

/**
 * OpenSim_Database class, alias of OSPDO
 */
class OpenSim_Database extends OSPDO {
    public function __construct($dsn, $username = null, $password = null, $driver_options = null) {
        parent::__construct($dsn, $username, $password, $driver_options);
    }
}
