<?php
if ( ! defined( 'W4OS_PLUGIN' ) ) {
	die;
}

class W4OS_WPDB extends WPDB {
	public function __construct( $dbuser, $dbpassword = null, $dbname = null, $dbhost = null, $dbport = null ) {
		if ( WP_DEBUG && WP_DEBUG_DISPLAY ) {
			$this->show_errors();
		}

		// If args are passed as an array, extract them.
		if ( is_array( $dbuser ) ) {
			$credentials = WP_parse_args( $dbuser, array(
				'user'     => null,
				'pass'     => null,
				'database' => null,
				'host'     => null,
				'port'     => null,
			) );
			$dbuser      = $credentials['user'];
			$dbpassword  = $credentials['pass'];
			$dbname      = $credentials['database'];
			$dbhost      = $credentials['host'] . ( empty( $credentials['port'] ) ? '' : ':' . $credentials['port'] );
		}

		// Use the `mysqli` extension if it exists unless `WP_USE_EXT_MYSQL` is defined as true.
		if ( function_exists( 'mysqli_connect' ) ) {
			$this->use_mysqli = true;

			if ( defined( 'WP_USE_EXT_MYSQL' ) ) {
				$this->use_mysqli = ! WP_USE_EXT_MYSQL;
			}
		}

		$this->dbuser     = $dbuser;
		$this->dbpassword = $dbpassword;
		$this->dbname     = $dbname;
		$this->dbhost     = $dbhost . ( empty( $dbport ) ? '' : ':' . $dbport );

		// wp-config.php creation will manually connect when ready.

		$this->db_connect();
	}

}
