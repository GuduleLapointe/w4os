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
	public $robust_db;
	public $assets_db;
	public $profile_db;
	private $console = null;
	private $ini = array();

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

		self::constants();
		self::includes();

		// Connect to the robust database and make it available to all classes.
		$this->robust_db = new W4OS_WPDB( W4OS_DB_ROBUST );
		$this->assets_db = $this->robust_db;
		$this->profile_db = $this->robust_db;

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
		if ( ! $this->get_console_config( $instance ) ) {
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
		$connections = W4OS3::get_option( 'w4os-settings:connections', array() );

		$console_prefs = $connections[$instance]['console'] ?? false;
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

		// $connections = W4OS3::get_option( 'w4os-settings:connections', array() );
		// $console_prefs = $connections[$instance]['console'] ?? false;
		// if ( ! $console_prefs ) {
		// 	return new WP_Error( 'no_console_prefs', sprintf( __( 'No console preferences not set for %s.', 'w4os'), $instance ) );
		// }
		// if ( empty ( $console_prefs['host'] ) || empty( $console_prefs['port'] ) || empty( $console_prefs['user'] ) || empty( $console_prefs['pass'] ) ) {
		// 	return new WP_Error( 'incomplete_console_prefs', sprintf( __( 'Incomplete console prefs found for %s.', 'w4os'), $instance ) );
		// }
		// $rest_args = array(
		// 	'uri' 	   => $console_prefs['host'] . ':' . $console_prefs['port'],
		// 	'ConsoleUser' => $console_prefs['user'],
		// 	'ConsolePass' => $console_prefs['pass'],
		// );
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
		require_once W4OS_INCLUDES_DIR . '2to3-instance.php';
		
		require_once W4OS_INCLUDES_DIR . 'helpers/2to3-helper-list.php';
		require_once W4OS_INCLUDES_DIR . 'helpers/2to3-helper-models.php';
		require_once W4OS_PLUGIN_DIR . 'v2/admin-helpers/class-opensim-rest.php';
		
		// Load v3 features if enabled
		if ( W4OS_ENABLE_V3 ) {
			// Include v3 feature files
			require_once W4OS_INCLUDES_DIR . '2to3-avatar.php';
			require_once W4OS_INCLUDES_DIR . '2to3-region.php';
		}

		// Once all files are loaded, we start the classes.
		$Settings = new W4OS3_Settings();
		$Settings->init();
		$Instances = new W4OS_Instance();
		$Instances->init();
		
		if ( W4OS_ENABLE_V3 ) {
			$AvatarClass = new W4OS3_Avatar();
			$AvatarClass->init();
			$RegionClass = new W4OS3_Region();
			$RegionClass->init();
			$ModelClass = new W4OS3_Model();
			$ModelClass->init();
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
			W4OS_NULL_KEY,
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
}

$w4os3 = new W4OS3();
$w4os3->init();
