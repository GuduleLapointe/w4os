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
class W4OS3 {
	public static $robust_db;
	public static $assets_db;
	public static $profile_db;
	private $console = null;
	private $ini = array();
	private static $key;

	// public function __construct() {
	// Safety only, this class should not be instantiated.
	// self::init();
	// }

	public function init() {
		// Transitional. Add settings fields to the legacy settings page.
		add_action( 'rwmb_meta_boxes', array( $this, 'register_legacy_settings_fields' ), 20 );
		define( 'W4OS_ENABLE_V3', get_option( 'w4os-enable-v3-beta', false ) );

		if ( ! W4OS_ENABLE_V3 ) {
			return;
		}

		$this->set_key(); // Make sure to call first, so key is available for other functions.
		
		self::constants();
		self::includes();

		// Connect to the robust database and make it available to all classes.
		// $this->robust_db = new W4OS_WPDB( W4OS_DB_ROBUST );
		// $this->assets_db = $this->robust_db;
		// $this->profile_db = $this->robust_db;

		// self::$robust_db = new W4OS_WPDB( W4OS_DB_ROBUST );
		// self::$assets_db = self::$robust_db;
		// self::$profile_db = self::$robust_db;
		
		$this->ini = $this->get_ini_config();
	}

	/**
	 * Get config from console and convert to an array of sections and key-value pairs.
	 */
	public function get_ini_config( $instance = 'robust' ) {
		$ini = array();
		if ( ! $this->get_console_config( $instance ) ) {
			return $ini;
		}

		$response = $this->console( $instance, 'config get' );
		if ( $response === false ) {
			return $ini;
		}
		if ( $response ) {
			// $ini = implode( "\n", $response );
			$config = self::normalize_ini( $response );
			$ini = parse_ini_string( $config, true );
			if ( $ini ) {
				return $ini;
			} else {
				return new WP_Error( 'config_parse_error', 'Failed to parse config' );
			}
		} else if ( is_wp_error( $response ) ) {
			$error = new WP_Error( 'console_command_failed', $response->getMessage() );
			error_log( 'Error ' . print_r( $error, true ) );
			return $error;
		} else {
			$error = 'Unknown error ' . print_r( $response, true );
			error_log( 'get_ini_config Error ' . $error );
			return $error;
		}
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
		} else if ( is_string( $ini ) ) {
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
			$key = array_shift( $parts );
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

		if( ! $this->console || is_wp_error( $this->console ) ) {
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
			$connections = W4OS3::get_option( 'w4os-settings:connections', array() );
			$console_prefs = $connections[$instance]['console'] ?? false;
		}

		if ( ! $console_prefs ) {
			return false;
		}
		if ( empty ( $console_prefs['host'] ) || empty( $console_prefs['port'] ) || empty( $console_prefs['user'] ) || empty( $console_prefs['pass'] ) ) {
			return false;
		}

		$config = array_filter( array(
			'uri' 	   => $console_prefs['host'] . ':' . $console_prefs['port'],
			'ConsoleUser' => $console_prefs['user'],
			'ConsolePass' => $console_prefs['pass'],
		) );

		return $config;
	}

	public function console_connect( $instance = 'robust' ) {
		if ( $this->console !== null ) {
			return $this->console;
		}

		$rest_args = $this->get_console_config( $instance );
		if ( empty( $rest_args ) ) {
			error_log("Console not set for $instance, that's OK");
			return;
		}

		$rest = new OpenSim_Rest( $rest_args );
		if ( isset( $rest->error ) && is_opensim_rest_error( $rest->error ) ) {
			$error = new WP_Error( 'console_connection_failed', $rest->error->getMessage() );
			$this->console = $error;
			return $error;
		} else {
			$response = $rest->sendCommand( 'show info' );
			if ( is_opensim_rest_error( $response ) ) {
				$error = new WP_Error( 'console_command_failed', $response->getMessage() );
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
		define( 'W4OS_PLUGIN_DIR', plugin_dir_path( __DIR__ ) );
		define( 'W4OS_PLUGIN_DIR_URL', plugin_dir_url( __DIR__ ) );
		define( 'W4OS_PLUGIN', basename( W4OS_PLUGIN_DIR ) . '/w4os.php' );
		define( 'W4OS_INCLUDES_DIR', plugin_dir_path( __FILE__ ) );
		define( 'W4OS_TEMPLATES_DIR', W4OS_INCLUDES_DIR . 'templates/' );
		define( 'W4OS_PATTERN_NAME', '[A-Za-z][A-Za-z0-9]* [A-Za-z][A-Za-z0-9]*' ); // Moved to v3 init class

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
		require_once W4OS_INCLUDES_DIR . 'class-db.php';
		require_once W4OS_INCLUDES_DIR . '2to3-service.php';
		
		require_once W4OS_INCLUDES_DIR . 'helpers/2to3-helper-list.php';
		require_once W4OS_INCLUDES_DIR . 'helpers/2to3-helper-models.php';
		require_once W4OS_PLUGIN_DIR . 'v2/admin-helpers/class-opensim-rest.php';
		
		// Load v3 features if enabled
		if ( W4OS_ENABLE_V3 ) {
			// Include v3 feature files
			require_once W4OS_INCLUDES_DIR . '2to3-avatar.php';
			// require_once W4OS_INCLUDES_DIR . '2to3-region.php';
		}

		// Once all files are loaded, we start the classes.
		$Settings = new W4OS3_Settings();
		$Settings->init();

		self::$robust_db = new W4OS_WPDB( W4OS_DB_ROBUST );
		self::$assets_db = self::$robust_db;
		self::$profile_db = self::$robust_db;
		
		if ( W4OS_ENABLE_V3 ) {
			$Instances = new W4OS3_Service();
			$Instances->init();
			$AvatarClass = new W4OS3_Avatar();
			$AvatarClass->init();
			$ModelClass = new W4OS3_Model();
			$ModelClass->init();
			// $RegionClass = new W4OS3_Region();
			// $RegionClass->init();
		}
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
			$option_main = $option[0];
			$option      = $option[1];
		} else {
			$options_main = 'w4os_settings';
		}
		$result = $default;
		if ( preg_match( '/:/', $option ) ) {
			$options_main = strstr( $option, ':', true );
			$option       = trim( strstr( $option, ':' ), ':' );
		}

		$options = get_option( $options_main );
		if ( $options && isset( $options[ $option ] ) ) {
			$result = $options[ $option ];
		}

		// } else {
		// $result = get_option($option, $default);
		// }
		return $result;
	}

	static function update_option( $option, $value, $autoload = null ) {
		if ( is_array( $option ) && isset( $option[1] ) ) {
			$option_main = $option[0];
			$option      = $option[1];
		} elseif ( preg_match( '/:/', $option ) ) {
			$options_main = strstr( $option, ':', true );
			$option       = trim( strstr( $option, ':' ), ':' );
		} else {
			$options_main = null;
		}
		$options            = get_option( $options_main );
		$options[ $option ] = $value;
		$result             = update_option( $options_main, $options, $autoload );

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

	/**
	 * Rewrite of wp_enqueue_scripts to allow minimal syntax in scripts.
	 */
	public static function enqueue_script( $handle, $src, $deps = array(), $ver = false, $in_footer = false ) {
		$handle = preg_match( '/^w4os-/', $handle ) ? $handle : 'w4os-' . $handle;
		$ver    = empty( $ver ) ? W4OS_VERSION : $ver;
		$src    = preg_match( '/^http/', $src ) ? $src : W4OS_PLUGIN_DIR_URL . $src;
		$hook   = is_admin() ? 'admin_enqueue_scripts' : 'wp_enqueue_scripts';
		if ( function_exists( 'wp_enqueue_script' ) ) {
			// error_log( 'Enqueueing script: ' . $handle . ' ' . $src );
			wp_enqueue_script( $handle, $src, $deps, $ver, $in_footer );
			return;
		}
		add_action(
			$hook,
			function () use ( $handle, $src, $deps, $ver, $in_footer ) {
				// error_log( 'Hook Enqueueing script: ' . $handle . ' ' . $src );
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

	public static function is_true( $value ) {
		if ( is_bool( $value ) ) {
			return $value;
		}
		$value = strtolower( $value );
		return $value === 'true' || $value === 'yes' || $value === true || $value === 1 || $value === '1';
	}

	/**
	 * Complete standard empty() function with NULL key.
	 */
	public static function empty( $var ) {
		if ( ! $var ) {
			return true;
		}
		if ( empty( $var ) ) {
			return true;
		}
		$null_keys = array(
			'00000000-0000-0000-0000-000000000000',
			'00000000-0000-0000-0000-000000000001',
			// W4OS_NULL_KEY, // Not yet defined when this function is called early.
		);
		if ( in_array( $var, $null_keys ) ) {
			return true;
		}
		return false;
	}
	
	/**
	 * Check if a string is a valid UUID.
	 */
	public static function is_uuid( $uuid ) {
		return preg_match( '/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/', $uuid );
	}

	public static function connectionstring_to_array( $connectionstring ) {
		$parts = explode( ';', $connectionstring );
		$creds = array();
		foreach ( $parts as $part ) {
			$pair = explode( '=', $part );
			$creds[ $pair[0] ] = $pair[1] ?? '';
		}
		return $creds;
	}

	public static function update_credentials( $serverURI, $credentials ) {
		$console_enabled = W4OS3::validate_console_creds( $credentials['console'] );
		$credentials['console']['enabled'] = $console_enabled;
		if ( $console_enabled ) {
			$session = new W4OS3();
			$command = 'config get DatabaseService ConnectionString';
			$result = $session->console( $credentials['console'], 'config get DatabaseService ConnectionString' );
			if ( $result && is_array( $result ) ) {
				$result = array_shift( $result );
				$result = explode( ' : ', $result );
				$result = array_pop( $result );
				// $result = preg_replace( '/.*Data Source=', 'host=', $result );
				$data = self::connectionstring_to_array( $result );
				$db = array_filter( array(
					'host' => $data['Data Source'],
					'port' => $data['Port'] ?? 3306,
					'name' => $data['Database'],
					'user' => $data['User ID'],
					'pass' => $data['Password'],
				) );
				if ( $db['host'] == 'localhost' && $credentials['host'] !=  $_SERVER['SERVER_ADDR'] ) {
					$db['host'] = $credentials['host'];
				}
				$credentials['db'] = $db;
			}
		}

		$credentials['db']['enabled'] = self::validate_db_credentials( $credentials['db'] );

		$options = self::decrypt( get_option( 'w4os-credentials' ) );
		$options[ $serverURI ] = self::encrypt( $credentials );
		update_option( 'w4os-credentials', $options );
	}

	public static function get_credentials( $instance ) {
		$key = self::$key;

		if( empty ($key ) ) {
			error_log( __FUNCTION__ . ' called before key is set, abort' );
			return false;
		}

		if ( is_string( $instance ) ) {
			$parts = parse_url( $instance );
			$serverURI = $parts['host'] . ':' . $parts['port'];
			$options = get_option( 'w4os-credentials' ); // Shoud somewhere else, but it's where it's currently.
			$server_credentials = self::decrypt( $options[ $serverURI ] ?? array() );
		} else {
			$server_credentials = array();
		}

		$credentials = wp_parse_args(
			$server_credentials,
			array(
				'host' => null,
				'port' => null,
				'use_default' => false,
				'console' => array(
					'host' => null,
					'port' => null,
					'user' => null,
					'pass' => null,
				),
				'db' => array(
					'host' => null,
					'port' => null,
					'name' => null,
					'user' => null,
					'pass' => null,
				),
			)
		);

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
		$combine = array( $login_uri, $grid_info['gridnick'], $grid_info['platform'] );
		$key = md5(sanitize_title( implode( ' ', $combine ) ) );
		self::$key = $key;
    }

	/**
	 * Encrypt data with self::$key, in such a way that it can be decrypted later with the same key
	 */

	public static function encrypt( $data ) {
		if ( ! extension_loaded( 'openssl' ) || ! function_exists( 'openssl_encrypt' ) ) {
			// Return data unencrypted or handle error
			return $data;
		}
		$key = self::$key;
		if ( ! is_string( $data ) ) {
			$data = json_encode( $data );
		}
		$iv = openssl_random_pseudo_bytes(16);
		$encrypted = openssl_encrypt($data, 'aes-256-cbc', $key, 0, $iv);
		return base64_encode($encrypted . '::' . $iv);
	}

	/**
	 * Decrypt data with self::$key, encrypted with self::encrypt
	 */
	public static function decrypt( $data ) {
		if ( ! extension_loaded( 'openssl' ) || ! function_exists( 'openssl_decrypt' ) ) {
			// Return raw data if OpenSSL is not available
			return $data;
		}
		if ( ! is_string( $data ) ) {
			return $data;
		}
		if ( ! preg_match('/^[a-zA-Z0-9\/\r\n+]*={0,2}$/', $data) ) {
			return $data;
		}
		$key = self::$key;
		list($encrypted_data, $iv) = explode('::', base64_decode($data), 2);
		$data = openssl_decrypt($encrypted_data, 'aes-256-cbc', $key, 0, $iv);
		$decode = json_decode( $data, true );
		if ( json_last_error() === JSON_ERROR_NONE ) {
			return $decode;
		}
		return $data;
	}

	public static function grid_info( $force = false ) {
		$transient_key = 'w4os_grid_info';
		$grid_info = get_transient( $transient_key );
		if ( $grid_info && ! $force ) {
			return $grid_info;
		}

		$local_uri       = 'http://localhost:8002';
		$check_login_uri = ( get_option( 'w4os_login_uri' ) ) ? 'http://' . get_option( 'w4os_login_uri' ) : $local_uri;
		$check_login_uri = preg_replace( '+http://http+', 'http', $check_login_uri );

		$xml = W4OS3::fast_xml( $check_login_uri . '/get_grid_info' );
	
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
			'uri' 	   => $console_creds['host'] . ':' . $console_creds['port'],
			'ConsoleUser' => $console_creds['user'],
			'ConsolePass' => $console_creds['pass'],
		);
		if( empty( $rest_args['uri'] ) || empty( $rest_args['ConsoleUser'] ) || empty( $rest_args['ConsolePass'] ) ) {
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
				return true;
			}
		}
	}

	public static function validate_db_credentials( $db_creds ) {
		if ( empty( $db_creds['host'] ) || empty( $db_creds['user'] ) || empty( $db_creds['pass'] ) || empty( $db_creds['name'] ) ) {
			error_log( __FUNCTION__ . ' missing arguments ' . print_r( $db_creds, true ) );
			return null;
		}
		@$db_conn = new mysqli( $db_creds['host'], $db_creds['user'], $db_creds['pass'], $db_creds['name'], $db_creds['port'] );
		if( $db_conn && ! $db_conn->connect_error ) {
			$db_conn->close();
			return true;
		} else {
			return false;
		}
	}
}

$w4os3 = new W4OS3();
$w4os3->init();
