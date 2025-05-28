<?php
/**
 * Tranisition initialisation class for v2 to v3.
 *
 * This class loads the classes and functions needed to test v3 features
 * while keeping v2 features available.
 *
 * It will replace both v1/init.php and v2/loader.php when all
 * new v3 features are validated, and all remaining v2 or legacy features
 * are ported to v3.
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

/**
 * Main plugin class.
 *
 * This class loads the classes and functions needed to test v3 features
 */
class W4OS2to3 {
	public static $robust_db;
	public static $assets_db;
	public static $profile_db;
	private $console               = null;
	public static $console_enabled = null;
	public static $db_enabled      = null;
	public static $ini             = null;
	private static $key;
	private static $avatar = null;
	private static $user   = null;

	// public function __construct() {
	// Safety only, this class should not be instantiated.
	// self::init();
	// }

	public function init() {
		// Transitional. Add settings fields to the legacy settings page.
		add_action( 'rwmb_meta_boxes', array( $this, 'register_legacy_settings_fields' ), 20 );
		if ( ! W4OS_ENABLE_V3 ) {
			return;
		}

		$this->set_key(); // Make sure to call first, so key is available for other functions.

		$robust_creds          = self::get_credentials( 'robust' );
		self::$console_enabled = $robust_creds['console']['enabled'] ?? false;

		self::constants();
		self::includes();

		$this->get_ini_config();

		add_action( 'init', array( $this, 'viewer_session_auth' ) );

		// Allow 'w4os/avatar-menu' block within 'core/navigation'
        // add_filter( 'allowed_block_types_all', array( $this, 'w4os_allowed_navigation_blocks' ), 10, 2 );
	}

	public static function in_world_call() {
		$viewer = preg_match( '/SecondLife|OpenSim/', $_SERVER['HTTP_USER_AGENT'] );
		return ( $viewer ) ? true : false;
	}

	/**
	 * Detect if the current page is called by a viewer (e.g. like FireStorm or Singularity, or any other SecondLife compatible viewer).
	 */
	public function viewer_session_auth() {
		if ( self::$avatar !== null ) {
			return ( self::$avatar ) ? true : false;
		}

		$session = null;
		if ( ! self::$robust_db ) {
			$session = false;
		} elseif ( ! isset( $_GET['session_id'] ) ) {
			$session = false;
		} elseif ( isset( $_SERVER['HTTP_USER_AGENT'] ) ) {
			$viewer = preg_match( '/SecondLife|OpenSim/', $_SERVER['HTTP_USER_AGENT'] );
			if ( ! self::in_world_call() ) {
				$session = false;
			} else {
				$parts      = explode( '?', $_GET['session_id'] );
				$session_id = $parts[0];
				if ( ! OpenSim::is_uuid( $session_id, false ) ) {
					$session = false;
				} else {
					$sql    = sprintf(
						"SELECT UserID FROM Presence WHERE SessionID = '%s'",
						esc_sql( $session_id )
					);
					$result = self::$robust_db->get_results( $sql );

					$avatar_uuid = ( $result ) ? $result[0]->UserID : false;
					if ( ! $avatar_uuid ) {
						$session = false;
					} else {
						$session      = true;
						$avatar       = new W4OS3_Avatar( $avatar_uuid );
						$user         = ( $avatar->Email ) ? get_user_by( 'email', $avatar->Email ) : false;
						self::$avatar = ( $avatar ) ? $avatar : false;
						self::$user   = ( $user ) ? $user : false;
					}
				}
			}
		}

		return $session;
	}

	/**
	 * Get viewer session avatar.
	 */
	public static function session_avatar() {
		return self::$avatar;
	}

	/**
	 * Get config from console and convert to an array of sections and key-value pairs.
	 */
	public function get_ini_config( $instance = 'robust' ) {
		if ( self::$ini ) {
			return self::$ini;
		}
		$ini = array();
		if ( ! $this->get_console_config( $instance ) ) {
			// Console is not configured, ignore it.
			return $ini;
		}

		$response = $this->console( $instance, 'config get' );
		if ( $response === false ) {
			error_log( __FUNCTION__ . ' response false' );
			return $ini;
		}
		if ( $response ) {
			// $ini = implode( "\n", $response );
			$config = self::normalize_ini( $response );
			$ini    = parse_ini_string( $config, true );
			if ( ! $ini ) {
				$error = 'Failed to parse config';
				// error_log( __FUNCTION__ . ' ' . $error );
				return new WP_Error( 'config_parse_error', 'Failed to parse config' );
			}
		} elseif ( is_wp_error( $response ) ) {
			$error = new WP_Error( 'console_command_failed', $response->getMessage() );
			error_log( __FUNCTION__ . ' Error ' . print_r( $error, true ) );
			return $error;
		} else {
			$error = 'Unknown error ' . print_r( $response, true );
			error_log( __FUNCTION__ . ' Error ' . $error );
			return $error;
		}

		self::$ini = $ini;
		return $ini;
	}

	/**
	 * Normalize an INI string. Make sure each value is encosed in quotes.
	 */
	public static function normalize_ini( $ini ) {
		if ( ! $ini || is_wp_error( $ini ) ) {
			return false;
		}
		if ( is_array( $ini ) ) {
			$lines = $ini;
		} elseif ( is_string( $ini ) ) {
			$lines = explode( "\n", $ini );
		} else {
			return false;
		}

		$ini = '';
		foreach ( $lines as $line ) {
			$line = trim( $line );
			if ( empty( $line ) ) {
				continue;
			}
			$parts = explode( '=', $line );
			if ( count( $parts ) < 2 ) {
				$ini .= "$line\n";
				continue;
			}
			// use first part as key, the rest as value
			$key   = array_shift( $parts );
			$value = implode( '=', $parts );
			if ( preg_match( '/^"/', $value ) ) {
				$ini .= "$key = $value\n";
			} else {
				$ini .= "$key = \"$value\"\n";
			}
		}
		return $ini;
	}

	public function console( $instance = 'robust', $command = null ) {
		if ( ! is_array( $instance ) && ! $this->get_console_config( $instance ) ) {
			return false;
		}

		$console = $this->console_connect( $instance );
		if ( $console === false ) {
			return false;
		}

		if ( ! $this->console || is_wp_error( $this->console ) ) {
			return $this->console;
		}

		if ( $this->console && ! empty( $command ) ) {
			$response = $this->console->sendCommand( $command );
			if ( is_opensim_rest_error( $response ) ) {
				$error = new WP_Error( 'console_command_failed', $response->getMessage() );
			} else {
				return $response;
			}
		}
	}

	private function get_console_config( $instance = 'robust' ) {
		if ( is_array( $instance ) ) {
			$console_prefs = $instance;
		} else {
			$connections   = self::get_option( 'w4os-settings:connections', array() );
			$console_prefs = $connections[ $instance ]['console'] ?? false;
		}

		if ( ! $console_prefs ) {
			return false;
		}
		if ( empty( $console_prefs['host'] ) || empty( $console_prefs['port'] ) || empty( $console_prefs['user'] ) || empty( $console_prefs['pass'] ) ) {
			return false;
		}

		$config = array_filter(
			array(
				'uri'         => $console_prefs['host'] . ':' . $console_prefs['port'],
				'ConsoleUser' => $console_prefs['user'],
				'ConsolePass' => $console_prefs['pass'],
			)
		);

		return $config;
	}

	public function console_connect( $instance = 'robust' ) {
		if ( $this->console !== null ) {
			return $this->console;
		}

		$rest_args = $this->get_console_config( $instance );
		if ( empty( $rest_args ) ) {
			error_log( "Console not set for $instance, that's OK" );
			return;
		}

		$rest = new OpenSim_Rest( $rest_args );
		if ( isset( $rest->error ) && is_opensim_rest_error( $rest->error ) ) {
			$error         = new WP_Error( 'console_connection_failed', $rest->error->getMessage() );
			$this->console = $error;
			return $error;
		} else {
			$response = $rest->sendCommand( 'show info' );
			if ( is_opensim_rest_error( $response ) ) {
				$error         = new WP_Error( 'console_command_failed', $response->getMessage() );
				$this->console = $error;
				return $error;
			} else {
				$this->console = $rest;
				return $response;
			}
		}
	}

	function db( $db = 'robust' ) {
		if ( $db == 'robust' ) {
			return $this->robust_db;
		}
		// return $this->robust_db;
	}

	public static function constants() {
		define( 'W4OS_PLUGIN_DIR_URL', plugin_dir_url( __DIR__ ) );
		// define( 'W4OS_PLUGIN', basename( W4OS_PLUGIN_DIR ) . '/w4os.php' );
		define( 'W4OS_INCLUDES_DIR', plugin_dir_path( __FILE__ ) );
		define( 'W4OS_TEMPLATES_DIR', W4OS_INCLUDES_DIR . 'templates/' );
		define( 'W4OS_PATTERN_NAME', '[A-Za-z][A-Za-z0-9]* [A-Za-z][A-Za-z0-9]*' ); // Moved to v3 init class
		// define( 'W4OS_NULL_KEY', '00000000-0000-0000-0000-000000000000' );

		define(
			'W4OS_DB_ROBUST',
			array(
				'user'     => get_option( 'w4os_db_user', 'opensim' ),
				'pass'     => get_option( 'w4os_db_pass', 'opensim' ),
				'database' => get_option( 'w4os_db_database', 'opensim' ),
				'host'     => get_option( 'w4os_db_host', 'localhost' ),
				'port'     => get_option( 'w4os_db_port', '3306' ),
			// 'type'     => 'mysql',
			)
		);
	}

	public static function includes() {
		// Transition classes will be loaded here.

		// First we include all the files
		require_once W4OS_INCLUDES_DIR . '2to3-settings.php';
		require_once W4OS_INCLUDES_DIR . '2to3-service.php';

		require_once W4OS_INCLUDES_DIR . 'helpers/2to3-helper-list.php';
		require_once W4OS_INCLUDES_DIR . 'helpers/2to3-helper-models.php';
		require_once W4OS_INCLUDES_DIR . 'helpers/2to3-helper-userless-auth.php';
		require_once W4OS_INCLUDES_DIR . 'helpers/2to3-helper-usermenu.php';
		require_once W4OS_PLUGIN_DIR . 'v2/admin-helpers/class-opensim-rest.php';

		// require_once W4OS_INCLUDES_DIR . 'class-flux.php';

		// Once all files are loaded, we start the classes.
		$Settings = new W4OS3_Settings();
		$Settings->init();

		self::$robust_db  = new OSPDO( W4OS_DB_ROBUST );
		self::$assets_db  = self::$robust_db;
		self::$profile_db = self::$robust_db;
	}

	public static function account_url() {
		$account_slug = get_option( 'w4os_account_url', 'account' );
		// Check if page exists
		$page = get_page_by_path( $account_slug );
		$account_url = ( $page ) ? get_permalink( $page->ID ) : false;
		if ( empty( $account_url ) ) {
			// Return default wordpress user profile page
			return get_edit_user_link();
		}
		return $account_url;
	}

	function register_legacy_settings_fields( $meta_boxes ) {
		$prefix = 'w4os_';

		$meta_boxes[] = array(
			'title'          => __( 'Beta Features', 'w4os' ),
			'id'             => 'beta_features',
			'settings_pages' => array( 'w4os_settings' ),
			'class'          => 'w4os-settings',
			'fields'         => array(
				array(
					'name'       => __( 'Enable v3 Beta Features', 'w4os' ),
					'id'         => 'w4os-enable-v3-beta',
					'type'       => 'switch',
					'desc'       => __( 'Warning: this could break your website and/or your OpenSimulator services.', 'w4os' ),
					'std'        => get_option( 'w4os-enable-v3-beta', false ),
					'style'      => 'rounded',
					'save_field' => true,
				),
			),
		);

		return $meta_boxes;
	}


	// Replicate core add_submenu_page to simplify other classes code.
	public static function add_submenu_page( $parent_slug, $page_title, $menu_title, $capability, $menu_slug, $callback = '', $position = null ) {
		$parent_slug = 'w4os';
		$prefix      = $parent_slug . '-';
		if ( ! preg_match( '/^' . $prefix . '/', $menu_slug ) ) {
			$menu_slug = $prefix . $menu_slug;
		}
		add_submenu_page(
			$parent_slug,
			$page_title,
			$menu_title,
			$capability,
			$menu_slug,
			$callback,
			$position,
		);
	}


	static function get_option( $option, $default = false ) {
		if ( is_array( $option ) && isset( $option[1] ) ) {
			$option_group = $option[0];
			$option       = $option[1];
		} else {
			$option_group = 'w4os-settings';
		}

		if ( preg_match( '/:/', $option ) ) {
			$option_group = strstr( $option, ':', true );
			$option       = trim( strstr( $option, ':' ), ':' );
		}

		$options = get_option( $option_group );
		if ( $options && isset( $options[ $option ] ) ) {
			return $options[ $option ];
		}

		// Fallback to v2 settings untill v3 settings are all implemented.
		if ( $option_group == 'w4os-settings' ) {
			$options = get_option( 'w4os_settings' );
			if ( $options && isset( $options[ $option ] ) ) {
				return $options[ $option ];
			}
		}

		return $default;
	}

	static function update_option( $option, $value, $autoload = null ) {
		if ( is_array( $option ) && isset( $option[1] ) ) {
			$option_group = $option[0];
			$option       = $option[1];
		} elseif ( preg_match( '/:/', $option ) ) {
			$option_group = strstr( $option, ':', true );
			$option       = trim( strstr( $option, ':' ), ':' );
		} else {
			$option_group = null;
		}
		$options            = get_option( $option_group );
		$options[ $option ] = $value;
		$result             = update_option( $option_group, $options, $autoload );

		return $result;
	}

	static function is_new_post( $args = null ) {
		global $pagenow;
		// make sure we are on the backend
		if ( ! is_admin() ) {
			return false;
		}
		return in_array( $pagenow, array( 'post-new.php' ) );
		// return in_array( $pagenow, array( 'post.php', 'post-new.php' ) );
	}

	/**
	 * Date function that accepts multiple arguments.
	 *
	 * This function is a wrapper for wp_date that accepts multiple arguments.
	 * For backward compatibility, the function accepts both
	 * w4os_date( $timestamp, $format, $timezone ) and
	 * w4os_date( $format, $timestamp, $timezone ) formats.
	 *
	 * @param mixed  $timestamp     Optional. Unix timestamp or date string to convert. Default current time.
	 * @param string $format        Optional. Format to use for date. Default to website date format.
	 * @param string $timezone      Optional. Timezone to use for date. Default to website timezone.
	 *
	 * @return string The date in the specified format.
	 */
	public static function date( $timestamp = null, $format = null, $timezone = null ) {
		$args = func_get_args();
		if ( empty( $args ) ) {
			$timestamp = time();
			$format    = get_option( 'date_format' );
		} elseif ( is_numeric( $args[0] ) ) {
			$timestamp = $args[0];
			$format    = $args[1] ?? get_option( 'date_format' );
		} else {
			$format    = $args[0] ?? get_option( 'date_format' );
			$timestamp = $args[1] ?? time();
		}

		$timezone = $args[2] ?? null;
		if ( empty( $timestamp ) ) {
			return;
		}
		if ( empty( $format ) ) {
			$format = get_option( 'date_format' );
		}
		return wp_date( $format, $timestamp, $timezone );
	}


	static function format_date( $timestamp, $format = 'MEDIUM', $timetype_str = 'NONE' ) {
		switch( $format ) {
			case 'MEDIUM':
				$format = get_option( 'date_format' );
				$date = date_i18n( $format, $timestamp );
				break;

			case 'LONG':
			case 'DATE_TIME':
				$date = sprintf (
					__( '%s at %s', 'w4os' ),
					date_i18n( get_option( 'date_format' ), $timestamp ),
					date_i18n( get_option( 'time_format' ), $timestamp )
				);
				break;

			default:
				$format = get_option( 'date_format' );
				$date = date_i18n( $format, $timestamp );
		}

		return $date;
		// return date_i18n( $format, $timestamp );
	}

	/**
	 * Rewrite of wp_enqueue_scripts to allow minimal syntax in scripts.
	 */
	public static function enqueue_script( $handle, $src, $deps = array(), $ver = false, $in_footer = false ) {
		$handle = preg_match( '/^w4os-/', $handle ) ? $handle : 'w4os-' . $handle;
		$ver    = empty( $ver ) ? W4OS_VERSION : $ver;
		$src    = preg_match( '/^http/', $src ) ? $src : W4OS_PLUGIN_DIR_URL . $src;
		$hook   = is_admin() ? 'admin_enqueue_scripts' : 'wp_enqueue_scripts';
		if ( function_exists( 'wp_enqueue_script' ) ) {
			wp_enqueue_script( $handle, $src, $deps, $ver, $in_footer );
			return;
		}
		add_action(
			$hook,
			function () use ( $handle, $src, $deps, $ver, $in_footer ) {
				wp_enqueue_script( $handle, $src, $deps, $ver, $in_footer );
			}
		);
	}

	/**
	 * Rewrite of wp_enqueue_styles to allow minimal syntax in styles.
	 */
	public static function enqueue_style( $handle, $src, $deps = array(), $ver = false, $media = 'all' ) {
		$handle = preg_match( '/^w4os-/', $handle ) ? $handle : 'w4os-' . $handle;
		$ver    = empty( $ver ) ? W4OS_VERSION : $ver;
		$src    = preg_match( '/^http/', $src ) ? $src : W4OS_PLUGIN_DIR_URL . $src;
		$hook   = is_admin() ? 'admin_enqueue_scripts' : 'wp_enqueue_scripts';
		if ( function_exists( 'wp_enqueue_style' ) ) {
			wp_enqueue_style( $handle, $src, $deps, $ver, $media );
			return;
		}
		add_action(
			$hook,
			function () use ( $handle, $src, $deps, $ver, $media ) {
				wp_enqueue_style( $handle, $src, $deps, $ver, $media );
			}
		);
	}


	public static function connectionstring_to_array( $connectionstring ) {
		$parts = explode( ';', $connectionstring );
		$creds = array();
		foreach ( $parts as $part ) {
			$pair              = explode( '=', $part );
			$creds[ $pair[0] ] = $pair[1] ?? '';
		}
		return $creds;
	}

	// is_true moved in engine/class-opensim.php
	// is_empty moved in engine/class-opensim.php
	// is_uuid moved in engine/class-opensim.php

	public static function update_credentials( $serverURI, $credentials ) {

		$console_enabled = self::validate_console_creds( $credentials['console'] );

		$credentials['console']['enabled'] = ( $console_enabled ) ? true : false;
		if ( $console_enabled ) {
			$credentials['console']['enabled'] = true;
			$session                           = new W4OS3();
			$command                           = 'config get DatabaseService ConnectionString';
			$result                            = $session->console( $credentials['console'], 'config get DatabaseService ConnectionString' );
			if ( $result && is_array( $result ) ) {
				$result = array_shift( $result );
				$result = explode( ' : ', $result );
				$result = array_pop( $result );
				// $result = preg_replace( '/.*Data Source=', 'host=', $result );
				$data = self::connectionstring_to_array( $result );
				$db   = array_filter(
					array(
						'host' => $data['Data Source'],
						'port' => $data['Port'] ?? 3306,
						'name' => $data['Database'],
						'user' => $data['User ID'],
						'pass' => $data['Password'],
					)
				);
				if ( $db['host'] == 'localhost' && $credentials['host'] != $_SERVER['SERVER_ADDR'] ) {
					$db['host'] = $credentials['host'];
				}
				$credentials['db'] = $db;
			}
		}

		$credentials['db']['enabled'] = self::validate_db_credentials( $credentials['db'] );

		error_log( __FUNCTION__ . ' ' . $serverURI . ' ' . print_r( $credentials, true ) );

		$options               = self::decrypt( get_option( 'w4os-credentials' ) );
		$options               = get_option( 'w4os-credentials' );
		$options[ $serverURI ] = self::encrypt( $credentials );
		update_option( 'w4os-credentials', $options );
	}

	public static function get_credentials( $instance ) {
		$key = self::$key;

		if ( $instance == 'robust' ) {
			$instance = get_option( 'w4os_login_uri' );
		}

		if ( empty( $key ) ) {
			error_log( __FUNCTION__ . ' called before key is set, abort' );
			return false;
		}

		if ( is_string( $instance ) ) {
			$instance = OpenSim::sanitize_login_uri( $instance );
			$parts              = parse_url( $instance );
			$serverURI          = $parts['host'] . ':' . $parts['port'];
			$cred_options       = get_option( 'w4os-credentials' ); // Shoud somewhere else, but it's where it's currently.
			$server_credentials = self::decrypt( $cred_options[ $serverURI ] ?? array() );
		} else {
			$server_credentials = array();
		}

		$credentials = wp_parse_args(
			$server_credentials,
			array(
				'host'        => null,
				'port'        => null,
				'use_default' => false,
				'console'     => array(
					'host' => null,
					'port' => null,
					'user' => null,
					'pass' => null,
				),
				'db'          => array(
					'host' => null,
					'port' => null,
					'name' => null,
					'user' => null,
					'pass' => null,
				),
			)
		);

		// Use localhost for database connection if the server is on the same host.
		if ( isset( $_SERVER['SERVER_ADDR'] ) && $_SERVER['SERVER_ADDR'] == gethostbyname( $credentials['db']['host'] ) ) {
			$credentials['db']['host'] = 'localhost';
		}

		return $credentials;
	}

	/**
	 * Calculate a unique site key. Uuse to encrypt and decrypt sensitive data like connection credentials.
	 *
	 * - unique and persistent (i.e. the same key is generated every time).
	 * - not stored in the database, generated on the fly.
	 * - depends on W4OS_LOGIN_URI and an additional secret key specific to the plugin.
	 *
	 * @return string The site key
	 */
	private function set_key() {
		$login_uri = get_option( 'w4os_login_uri', home_url() );
		$grid_info = self::grid_info();
		$combine   = array( $login_uri, $grid_info['gridnick'], $grid_info['platform'] );
		$key       = md5( sanitize_title( implode( ' ', $combine ) ) );
		self::$key = $key;
	}

	/**
	 * Encrypt data with self::$key, in such a way that it can be decrypted later with the same key
	 */
	public static function encrypt( $data ) {
		return OpenSim::encrypt( $data, self::$key );
	}

	/**
	 * Decrypt data with self::$key, encrypted with self::encrypt
	 */
	public static function decrypt( $data ) {
		return OpenSim::decrypt( $data, self::$key );
	}

	public static function grid_info( $gateway_uri = null, $force = false ) {
		$local_uri       = 'http://localhost:8002';
		if( empty( $gateway_uri ) ) {
			$gateway_uri = get_option( 'w4os_login_uri', $local_uri );
			$transient_key = 'w4os_grid_info';
		} else {
			$transient_key = 'w4os_grid_info_' . sanitize_key( $gateway_uri );
		}
		$grid_info     = get_transient( $transient_key );
		if ( $grid_info && ! $force ) {
			return $grid_info;
		}

		// $check_login_uri = ( get_option( 'w4os_login_uri' ) ) ? 'http://' . get_option( 'w4os_login_uri' ) : $local_uri;
		// $check_login_uri = preg_replace( '+http://http+', 'http', $check_login_uri );
		$check_login_uri = 'http://' . preg_replace( '+.*://+', '', $gateway_uri );

		$xml = self::fast_xml( $check_login_uri . '/get_grid_info' );

		if ( ! $xml ) {
			return false;
		}

		if ( $check_login_uri == $local_uri ) {
			w4os_admin_notice( __( 'A local Robust server has been found. Please check Login URI and Grid Name configuration.', 'w4os' ), 'success' );
		}

		$grid_info = (array) $xml;
		if ( get_option( 'w4os_provide_search', false ) ) {
			$grid_info['SearchURL'] = get_option( 'w4os_search_url' ) . '?gk=http://' . get_option( 'w4os_login_uri' );
		}

		if ( 'provide' === get_option( 'w4os_profile_page' ) && empty( $grid_info['profile'] ) && defined( 'W4OS_PROFILE_URL' ) ) {
			$grid_info['profile'] = W4OS_PROFILE_URL;
		}
		if ( ! empty( $grid_info['login'] ) ) {
			update_option( 'w4os_login_uri', preg_replace( '+/*$+', '', preg_replace( '+https*://+', '', $grid_info['login'] ) ) );
		}
		if ( ! empty( $grid_info['gridname'] ) ) {
			update_option( 'w4os_grid_name', $grid_info['gridname'] );
		}
		// if ( isset( $grid_info['OfflineMessageURL'] ) ) {
		// update_option( 'w4os_offline_helper_uri', $grid_info['OfflineMessageURL'] );
		// }

		if ( isset( $urls ) && is_array( $urls ) ) {
			w4os_get_urls_statuses( $urls, get_option( 'w4os_check_urls_now' ) );
		}

		update_option( 'w4os_grid_info', json_encode( $grid_info ) );
		set_transient( $transient_key, $grid_info, 60 * 60 * 24 );

		return $grid_info;
	}

	/**
	 * Fast XML function.
	 *
	 * Not sure what makes it fast, but it's used in several places.
	 */
	public static function fast_xml( $url ) {
		// Exit silently if required php modules are missing
		if ( ! function_exists( 'curl_init' ) ) {
			return null;
		}
		if ( ! function_exists( 'simplexml_load_string' ) ) {
			return null;
		}

		$ch = curl_init();
		curl_setopt( $ch, CURLOPT_URL, $url );
		curl_setopt( $ch, CURLOPT_RETURNTRANSFER, 1 );
		$html = curl_exec( $ch );
		curl_close( $ch );
		$xml = simplexml_load_string( $html );
		return $xml;
	}

	/**
	 * Validate arbitrary console credentials.
	 *
	 * Used to validate console credentials before saving them.
	 */
	public static function validate_console_creds( $console_creds ) {
		if ( ! $console_creds ) {
			return false;
		}
		$rest_args = array(
			'uri'         => $console_creds['host'] . ':' . $console_creds['port'],
			'ConsoleUser' => $console_creds['user'],
			'ConsolePass' => $console_creds['pass'],
		);
		if ( empty( $rest_args['uri'] ) || empty( $rest_args['ConsoleUser'] ) || empty( $rest_args['ConsolePass'] ) ) {
			return false;
		}
		$rest = new OpenSim_Rest( $rest_args );
		if ( isset( $rest->error ) && is_opensim_rest_error( $rest->error ) ) {
			return $rest->error->getMessage();
		} else {
			$response = $rest->sendCommand( 'show info' );
			if ( is_opensim_rest_error( $response ) ) {
				return $response->getMessage();
			} else {
				$console_creds['enabled'] = true;
				return $console_creds;
			}
		}
	}

	public static function validate_db_credentials( $db_creds ) {
		if ( empty( $db_creds['host'] ) || empty( $db_creds['user'] ) || empty( $db_creds['pass'] ) || empty( $db_creds['name'] ) ) {
			error_log( __FUNCTION__ . ' missing arguments ' . print_r( $db_creds, true ) );
			return null;
		}
		@$db_conn = new mysqli( $db_creds['host'], $db_creds['user'], $db_creds['pass'], $db_creds['name'], $db_creds['port'] );
		if ( $db_conn && ! $db_conn->connect_error ) {
			$db_conn->close();
			return true;
		} else {
			return false;
		}
	}

	public static function img( $img_uuid, $atts = array() ) {
		if ( OpenSim::empty( $img_uuid ) ) {
			return;
		}
		if ( ! OpenSim::is_uuid( $img_uuid ) ) {
			return;
		}
		$asset_url = w4os_get_asset_url( $img_uuid );
		if ( empty( $asset_url ) ) {
			return;
		}
		$class      = $atts['class'] ?? '';
		$class      = is_array( $class ) ? implode( ' ', $class ) : $class;
		$width      = ( isset( $atts['width'] ) ) ? 'width="' . $atts['width'] . '"' : '';
		$height     = ( isset( $atts['height'] ) ) ? 'height="' . $atts['height'] . '"' : '';
		$attributes = trim( $width . ' ' . $height );
		$alt        = $atts['alt'] ?? '';
		return sprintf(
			'<img src="%s" class="%s" alt="%s" %s>',
			$asset_url,
			$class,
			$alt,
			$attributes,
		);
	}

	public static function modal( $id, $url = null, $content = null ) {
		$footer_buttons = array(
			( empty( $url ) ) ? '' : sprintf(
				'<a href="%s" rel="noopener noreferrer" class="button">%s</a>',
				$url,
				__( 'Open page', 'w4os' ),
			),
		);
		$footer_buttons = array_filter( $footer_buttons );
		$footer         = ( empty( $footer_buttons ) ) ? '' : sprintf(
			'<div class="modal-footer clear">%s</div>',
			implode( '', $footer_buttons ),
		);
		if ( ! empty( $content ) ) {
			return sprintf(
				'<dialog id="modal-%s" class="w4os-modal">
					<button type="button" class="modal-close" onclick="closeModal()">×</button>
					%s
					%s
				</dialog>',
				$id,
				$content,
				$footer,
			);
		} elseif ( ! empty( $url ) ) {
			// add modal=true and embed=true to url arguments
			$url = add_query_arg( array( 'modal' => 'true' ), $url );
			// Use display $url content as modal content
			return sprintf(
				'<dialog id="modal-%s" class="w4os-modal">
					<button type="button" onclick="closeModal()" style="float:right;">×</button>
					<iframe src="%s"></iframe>
					%s
				</dialog>',
				$id,
				$url,
				$footer,
			);
		}
	}

	// /**
    //  * Allow 'w4os/avatar-menu' within 'core/navigation' blocks.
    //  *
    //  * @param array|string $allowed_blocks Array of allowed block types or '*' for all.
    //  * @param array        $block The current block being processed.
    //  * @return array|string Modified array of allowed block types.
    //  */
    // public function w4os_allowed_navigation_blocks( $allowed_blocks, $block ) {
    //     if ( isset( $block['blockName'] ) && $block['blockName'] === 'core/navigation' ) {
    //         if ( is_array( $allowed_blocks ) ) {
    //             // Add your custom block to the allowed blocks array
    //             $allowed_blocks[] = 'w4os/avatar-menu';
    //         }
    //     }
    //     return $allowed_blocks;
    // }
}

$w4os3 = new W4OS2to3();
$w4os3->init();
