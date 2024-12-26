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

    public $db_enabled;
    public $console_enabled;
    private $console;

    public function __construct() {
        $args = func_get_args();

        if ( is_array( $args ) && count( $args ) == 1 ) {
            // Single argument is the service URI
            $this->serviceURI = $args[0];
            $this->credentials = W4OS3::get_credentials( $this->serviceURI );
            $this->db_enabled = $this->credentials['db']['enabled'] ?? false;
            if ( $this->credentials['console']['enabled'] ?? false ) {
                $this->init_console();
            }
        }
    }

    public function init() {
		add_filter( 'w4os_settings', array( $this, 'register_w4os_settings' ), 10, 3 );

        $this->settings_transition();
    }

    /**
     * Migrate legacy settings to the new format.
     * 
     * @return void
     */
    private function settings_transition() {
        $w4os_login_uri = get_option( 'w4os_login_uri', home_url() ) ?? 'localhost:8002';
        $parts = parse_url( $w4os_login_uri );
        $host = $parts['host'] ?? 'localhost';
        $port = $parts['port'] ?? 8002;
        $robust_uri = $host . ':' . $port;
        $db_creds = array(
            'type'     => 'mysql',
            'host'     => get_option( 'w4os_db_host', 'localhost' ),
            'port'     => get_option( 'w4os_db_port', '3306' ),
            'name'     => get_option( 'w4os_db_database', 'opensim' ),
            'user'     => get_option( 'w4os_db_user', 'opensim' ),
            'pass'     => get_option( 'w4os_db_pass' ),
        );
        $connections = array(
            'robust' => array(
                'host' => $host,
                'port' => $port,
                'uri' => $robust_uri,
                'db'  => array(
                    'type' => $db_creds['type'],
                    'host' => $db_creds['host'],
                    'port' => $db_creds['port'],
                    'name' => $db_creds['name'],
                    'user' => $db_creds['user'],
                    'pass' => $db_creds['pass'],
                ),
            ),
            'assets' => array(
                'use_defaults' => true,
            ),
            'profiles' => array(
                'use_defaults' => true,
            ),
        );
        $v3_settings = get_option( 'w4os-settings', array() );
        $v3_settings['connections'] = wp_parse_args( $connections, ($v3_settings['connections'] ?? array()) );
        // error_log( "v3_settings: " . print_r( $v3_settings, true ) );
    }

	public function register_w4os_settings( $settings, $args = array(), $atts = array() ) {
        $login_uri = get_option( 'w4os_login_uri', home_url() );
        $default_host = parse_url( $login_uri, PHP_URL_HOST ) ?? 'yourgrid.org';
        $default_port = parse_url( $login_uri, PHP_URL_PORT ) ?? 8002;
        $default_db_creds = array(
            'type' => 'mysql',
            'host' => get_option( 'w4os_db_host', 'localhost' ),
            'port' => get_option( 'w4os_db_port', '3306' ),
            'name' => get_option( 'w4os_db_database', 'robust' ),
            'user' => get_option( 'w4os_db_user', 'opensim' ),
            'pass' => get_option( 'w4os_db_pass' ),
        );
        $settings['w4os-settings']['tabs']['connections'] = array(
            'title'  => __( 'Connections', '≠w4os' ),
            'fields' => array(
                'robust' => array(
                    'label'       => __( 'Robust', 'w4os' ),
                    'type'        => 'instance_credentials',
                    'description' => sprintf(
                        __( 'Main ROBUST credentials set in %s, %s and %s.', 'w4os' ),
                        '<code>[Const]</code>',
                        '<code>[Network]</code>',
                        '<code>[DatabaseService]</code>',
                    ),
                    'default' => array(
                        'host' => $default_host,
                        'port' => $default_port,
                        'db'   => $default_db_creds,
                    ),
                ),
                'assets' => array(
                    'label'       => __( 'Assets Service', 'w4os' ),
                    'type'        => 'db_credentials',
                    'description' => sprintf(
                        __( 'Leave checked unless different credentials are set in %s.', 'w4os' ),
                        '<code>[AssetService]</code>',
                    ),
                    'default' => array(
                        'host' => 'assets.' . $default_host,
                        'port' => $default_port,
                        'db' => array_merge( $default_db_creds, array( 'name' => 'assets' ) )
                    ),
                ),
                'profiles' => array(
                    'label'       => __( 'User Profiles Service', 'w4os' ),
                    'type'        => 'db_credentials',
                    'description' => sprintf(
                        __( 'Leave checked unless different credentials are set in %s.', 'w4os' ),
                        '<code>[UserProfilesService]</code>',
                    ),
                    'default' => array(
                        'host' => 'profiles.' . $default_host,
                        'port' => '8002',
                        'db' => array_merge( $default_db_creds, array( 'name' => 'profiles' ) )
                    ),
                ),
                        ),
        );
		return $settings;
    }

    public function init_console() {
        if($this->console) {
            return $this->console;
        }

        $console_creds = $this->credentials['console'];
        if ( empty( $console_creds ) ) {
            return false;
        }
        $rest_args = array(
			'uri' 	   => $console_creds['host'] . ':' . $console_creds['port'],
			'ConsoleUser' => $console_creds['user'],
			'ConsolePass' => $console_creds['pass'],
		);

		$rest = new OpenSim_Rest( $rest_args );
		if ( isset( $rest->error ) && is_opensim_rest_error( $rest->error ) ) {
            error_log( __FUNCTION__ . ' ' . $rest->error->getMessage() );
            $this->console = false;
			return $rest->error;
		} else {
			$response = $rest->sendCommand( 'show info' );
			if ( is_opensim_rest_error( $response ) ) {
                $error = new WP_Error( 'console_command_failed', $response->getMessage() );
				$this->console = false;
				return $error;
			} else {
				$this->console = $rest;
				return $response;
			}
		}
    }

    public function console( $command = null ) {
        if ( empty( $this->serviceURI ) || empty( $this->credentials ) ) {
            error_log( __FUNCTION__ . ' missing arguments to use console.' );
            return false;
        }

        if ( ! $this->init_console() ) {
            error_log( __FUNCTION__ . ' console initialization failed.' );
            return false;
        }
        if ( is_wp_error( $this->console ) ) {
            error_log( "console error: " . $this->console->get_error_message() );
            return false;
        }
        if ( $this->console && ! empty( $command ) ) {
			$response = $this->console->sendCommand( $command );
			if ( is_opensim_rest_error( $response ) ) {
				$error = new WP_Error( 'console_command_failed', $response->getMessage() );
                error_log( "console error: " . $error->get_error_message() );
                return $error;
			} else {
				return $response;
			}
		}
	}
}
