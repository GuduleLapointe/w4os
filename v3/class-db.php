<?php
if ( ! defined( 'W4OS_PLUGIN' ) ) {
	die;
}

if ( W4OS_ENABLE_V3 ) {
	class W4OS_WPDB extends WPDB {
		public function __construct( $dbuser, $dbpassword = null, $dbname = null, $dbhost = null, $dbport = null ) {
			if ( WP_DEBUG && WP_DEBUG_DISPLAY ) {
				$this->show_errors();
			}

			$args = func_get_args();
			if ( count( $args ) == 1 && is_string( $args[0] ) ) {
				// If a single string is passed, assume it's service URI.
				$url_parts   = parse_url( $args[0] );
				$serviceURI  = $url_parts['host'] . ( empty( $url_parts['port'] ) ? '' : ':' . $url_parts['port'] );
				$credentials = W4OS3::get_credentials( $serviceURI );

				$db_enabled = $credentials['db']['enabled'] ?? false;
				if ( ! $db_enabled ) {
					return false;
				}

				$dbuser     = $credentials['db']['user'];
				$dbpassword = $credentials['db']['pass'];
				$dbname     = $credentials['db']['name'];
				$dbhost     = $credentials['db']['host'] . ( empty( $credentials['db']['port'] ) ? '' : ':' . $credentials['db']['port'] );
			} elseif ( is_array( $args[0] ) ) {
				// If args are passed as an array, extract them.
				$credentials = WP_parse_args(
					$args[0],
					array(
						'user'     => null,
						'pass'     => null,
						'database' => null,
						'host'     => null,
						'port'     => null,
					)
				);
				$dbuser      = $credentials['user'];
				$dbpassword  = $credentials['pass'];
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

			$this->dbuser     = $dbuser;
			$this->dbpassword = $dbpassword;
			$this->dbname     = $dbname;
			$this->dbhost     = $dbhost;

			$this->db_connect();
			if ( ! $this->ready ) {
				error_log( sprintf( __CLASS__ . ' Error connecting to db %s at %s: %s', $this->dbname, $this->dbhost, $this->last_error ) );
			}
		}
	}
}
