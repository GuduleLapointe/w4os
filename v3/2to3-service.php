<?php
/**
 * Service class
 *
 * Defines the connection parameters and the methods to connect to the
 * OpenSimulator instances (grid services and simulators).
 *
 * An instance connection can include
 * - URI(robust and simulators): basic information fetching
 * - Database credentials(robust and simulators): queries and updates executed directly on the database
 * - Remote console(robust and simulators): remote access to the console
 * - REST API (simulators): some more access, mentioned for completeness, but not implemented
 *
 * Credential are stored in w4os-instances option, encrypted with the site key.
 *
 * @package w4os
 * @version 0.1
 * @since 2.9.1
 */

class W4OS3_Service {
	private $serviceURI;
	private $credentials;
	private $serviceType;

	private static $consoles; // make sure each console is initialized only once
	private static $dbs;      // make sure each db is initialized only once

	private $console;
	public $db;

	public function __construct() {
		$args = func_get_args();

		if ( is_array( $args ) && count( $args ) == 1 ) {
			// Single argument is the service URI
			$this->serviceURI  = $args[0];
			$this->credentials = W4OS3::get_credentials( $this->serviceURI );
			$this->init_console();
			$this->init_db();
		}
	}

	public function init() {
		$args = func_get_args();

		add_filter( 'w4os_settings', array( $this, 'register_w4os_settings' ), 10, 3 );

		$this->settings_transition();
	}

	/**
	 * Migrate legacy settings to the new format.
	 *
	 * @return void
	 */
	private function settings_transition() {
	}

	public function register_w4os_settings( $settings, $args = array(), $atts = array() ) {
		$login_uri        = get_option( 'w4os_login_uri', home_url() );
		$default_host     = parse_url( $login_uri, PHP_URL_HOST ) ?? 'yourgrid.org';
		$default_port     = parse_url( $login_uri, PHP_URL_PORT ) ?? 8002;
		$default_db_creds = array(
			'type' => 'mysql',
			'host' => get_option( 'w4os_db_host', 'localhost' ),
			'port' => get_option( 'w4os_db_port', '3306' ),
			'name' => get_option( 'w4os_db_database', 'robust' ),
			'user' => get_option( 'w4os_db_user', 'opensim' ),
			'pass' => get_option( 'w4os_db_pass' ),
		);

		$settings['w4os-settings']['tabs']['connections'] = array(
			'title'  => __( 'Connections', 'w4os' ),
			'fields' => array(
				'robust'   => array(
					'label'       => __( 'Robust', 'w4os' ),
					'type'        => 'instance_credentials',
					'description' => sprintf(
						__( 'Main ROBUST credentials set in %1$s, %2$s and %3$s.', 'w4os' ),
						'<code>[Const]</code>',
						'<code>[Network]</code>',
						'<code>[DatabaseService]</code>',
					),
					'default'     => array(
						'host' => $default_host,
						'port' => $default_port,
						'db'   => $default_db_creds,
					),
					'value'       => W4OS3::get_credentials( $login_uri ),
				),
				'assets'   => array(
					'label'       => __( 'Assets Service', 'w4os' ),
					'type'        => 'db_credentials',
					'description' => sprintf(
						__( 'Leave checked unless different credentials are set in %s.', 'w4os' ),
						'<code>[AssetService]</code>',
					),
					'default'     => array(
						'host' => 'assets.' . $default_host,
						'port' => $default_port,
						'db'   => array_merge( $default_db_creds, array( 'name' => 'assets' ) ),
					),
					'readonly'    => W4OS3::$console_enabled,
				),
				'profiles' => array(
					'label'       => __( 'User Profiles Service', 'w4os' ),
					'type'        => 'db_credentials',
					'description' => sprintf(
						__( 'Leave checked unless different credentials are set in %s.', 'w4os' ),
						'<code>[UserProfilesService]</code>',
					),
					'default'     => array(
						'host' => 'profiles.' . $default_host,
						'port' => '8002',
						'db'   => array_merge( $default_db_creds, array( 'name' => 'profiles' ) ),
					),
					'readonly'    => W4OS3::$console_enabled,
				),
			),
		);

		return $settings;
	}

	public function init_db() {
		if ( $this->db ) {
			return $this->db;
		}
		if ( isset( self::$dbs[ $this->serviceURI ] ) ) {
			$this->db = self::$dbs[ $this->serviceURI ];
			return $this->db;
		}

		$db_creds = $this->credentials['db'];
		if ( empty( $db_creds ) ) {
			return false;
		}
		if ( empty( $db_creds['enabled'] ) ) {
			return false;
		}

		$this->db = new W4OS_WPDB( $this->serviceURI );
		if ( is_wp_error( $this->db ) ) {
			error_log( 'simdb error ' . $this->db->get_error_message() );
			$this->db = false;
		} elseif ( $this->db ) {
			$tables = $this->db->get_results( 'show tables' );
			if ( ! $tables || count( $tables ) === 0 ) {
				$this->db = false;
			}
		}
		if ( $this->db ) {
			self::$dbs[ $this->serviceURI ] = $this->db;
		}
		return $this->db;
	}

	public function init_console() {
		if ( $this->console ) {
			return $this->console;
		}
		if ( isset( self::$consoles[ $this->serviceURI ] ) ) {
			$this->console = self::$consoles[ $this->serviceURI ];
			return $this->console;
		}

		$console_creds = $this->credentials['console'];
		if ( empty( $console_creds ) ) {
			return false;
		}
		if ( empty( $console_creds['enabled'] ) ) {
			return false;
		}

		$rest_args = array(
			'uri'         => $console_creds['host'] . ':' . $console_creds['port'],
			'ConsoleUser' => $console_creds['user'],
			'ConsolePass' => $console_creds['pass'],
		);

		$this->console = false;
		$rest          = new OpenSim_Rest( $rest_args );
		if ( isset( $rest->error ) && is_opensim_rest_error( $rest->error ) ) {
			error_log( __FUNCTION__ . ' ' . $rest->error->getMessage() );
			$response = $rest->error;
		} else {
			$response = $rest->sendCommand( 'show info' );
			if ( is_opensim_rest_error( $response ) ) {
				$response = new WP_Error( 'console_command_failed', $response->getMessage() );
			} else {
				$this->console = $rest;
			}
		}

		self::$consoles[ $this->serviceURI ] = $this->console;
		return $response;
	}

	/**
	 * Check if console is enabled.
	 */
	public function console_connected() {
		return ( $this->console && $this->console !== false );
	}

	/**
	 * Check if db is enabled.
	 */
	public function db_connected() {
		return ( $this->db->ready ?? false );
	}

	/**
	 * Send command to console and return result.
	 * If command is empty, return console status (true/false);
	 *
	 * @param string $command
	 * @return mixed WP_Error on error, boolean on status, or response array
	 */
	public function console( $command = null ) {
		if ( empty( $this->serviceURI ) || empty( $this->credentials ) ) {
			error_log( __FUNCTION__ . ' missing arguments to use console.' );
			return false;
		}

		// Initialize console, return false if failed
		if ( ! $this->init_console() ) {
			error_log( __FUNCTION__ . ' console initialization failed.' );
			return false;
		}
		if ( is_wp_error( $this->console ) ) {
			error_log( 'console error: ' . $this->console->get_error_message() );
			return false;
		}

		// Send command to console
		if ( $this->console && ! empty( $command ) ) {
			$response = $this->console->sendCommand( $command );
			if ( is_opensim_rest_error( $response ) ) {
				$error = new WP_Error( 'console_command_failed', $response->getMessage() );
				error_log( 'console error: ' . $error->get_error_message() );
				return $error;
			} else {
				return $response;
			}
		} else {
			return ( $this->console ) ? true : false;
		}
	}
}
