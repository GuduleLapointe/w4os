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

class W4OS3_Service extends OpenSim_Service {

	public function __construct() {
		$args = func_get_args();

		if (is_array($args) && count($args) == 1) {
			// Single argument is the service URI
			$serviceURI = $args[0];
			$credentials = W4OS3::get_credentials($serviceURI);
			// Call parent constructor with URI and credentials
			parent::__construct($serviceURI, $credentials);
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

	/**
	 * Override parent error checking for WordPress
	 */
	protected function is_error($obj) {
		return is_wp_error($obj);
	}

	/**
	 * Override parent error message extraction for WordPress
	 */
	protected function get_error_message($error) {
		if (is_wp_error($error)) {
			return $error->get_error_message();
		}
		return parent::get_error_message($error);
	}

	/**
	 * Override parent error creation for WordPress
	 */
	protected function create_error($code, $message) {
		return new WP_Error($code, $message);
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
}
