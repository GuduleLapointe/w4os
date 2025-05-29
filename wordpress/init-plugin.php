<?php
/**
 * WordPress Integration Initialization
 * 
 * Loads all WordPress-specific functionality including:
 * - Admin pages and menus
 * - Settings management  
 * - WordPress hooks and filters
 * - Public-facing features
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Define plugin constants
define('W4OS_VERSION', '2.9.5-beta-9');
define('W4OS_PLUGIN_DIR', plugin_dir_path(__DIR__));
define('W4OS_PLUGIN_URL', plugin_dir_url(__DIR__));
define('W4OS_SLUG', basename( W4OS_PLUGIN_DIR ) );
define( 'W4OS_PLUGIN', W4OS_SLUG . '/w4os.php' );

// Enable all features (remove beta toggles)
if(!defined('W4OS_ENABLE_V3')) {
    define('W4OS_ENABLE_V3', true);
}

// Load engine first
require_once W4OS_PLUGIN_DIR . 'engine/bootstrap.php';
    
// Use bridge implementation to OpenSimulator engine
class W4OS3 {
    public static $robust_db;
    public static $assets_db;
    public static $profile_db;

	private static $console               = null;
	public static $console_enabled = null;
	public static $db_enabled      = null;
	public static $ini             = null;
	private static $key;
	private static $avatar = null;
	private static $user   = null;

    static function init() {
        self::set_key(); // Make sure to call first, so key is available for other functions.

        // Register WordPress hooks and filters
        self::register_filters();
        self::register_actions();
        
        // Initialize WordPress-specific functionality
        self::init_wordpress_features();

		$robust_creds          = self::get_credentials( 'robust' );
		self::$console_enabled = $robust_creds['console']['enabled'] ?? false;

		self::constants();
		self::includes();

		self::get_ini_config();

        $UserlessAuth = new UserlessAuth();
        $UserlessAuth->init();
        $UserMenu = new W4OS3_UserMenu();
        $UserMenu->init();
        $Instances = new W4OS3_Service();
        $Instances->init();
        $AvatarClass = new W4OS3_Avatar();
        $AvatarClass->init();
        $ModelClass = new W4OS3_Model();
        $ModelClass->init();
        $FluxClass = new W4OS3_Flux();
        $FluxClass->init();
        $RegionClass = new W4OS3_Region();
        $RegionClass->init();
    }

    /**
     * Register WordPress filters
     */
    private static function register_filters() {
        add_filter( 'script_loader_tag', __CLASS__ . '::w4os_add_crossorigin', 10, 2 );
        add_filter( 'body_class', __CLASS__ . '::w4os_css_classes_body' );
        
		// Allow 'w4os/avatar-menu' block within 'core/navigation'
        // add_filter( 'allowed_block_types_all', array( $this, 'w4os_allowed_navigation_blocks' ), 10, 2 );

        // Add more filters here as they're moved from v1/v2/v3
        // add_filter( 'example_filter', __CLASS__ . '::example_method' );
    }

    /**
     * Register WordPress actions
     */
    private static function register_actions() {
        // Add actions here as they're moved from v1/v2/v3
		add_action( 'rwmb_meta_boxes', array( __CLASS__, 'register_legacy_settings_fields' ), 20 );
        add_action( 'init', array( __CLASS__, 'viewer_session_auth' ) );

        // add_action( 'init', __CLASS__ . '::example_action' );
        // add_action( 'wp_enqueue_scripts', __CLASS__ . '::enqueue_assets' );
    }

    /**
     * Initialize WordPress-specific features
     */
    private static function init_wordpress_features() {
        // Initialize any WordPress-specific functionality
        // that doesn't fit into standard hooks/filters
    }

    static function w4os_css_classes_body( $classes ) {
        if ( ! is_array( W4OS_GRID_INFO ) ) {
            return $classes;
        }

        $post = get_post();
        if ( ! $post ) {
            return array();
        }
        $helper = array_search( $post->guid, W4OS_GRID_INFO );
        if ( ! empty( $helper ) ) {
            $classes[] = 'w4os-' . $helper;
        }
        return $classes;
    }

    static function w4os_add_crossorigin( $tag, $handle ) {
        if ( 'w4os-fa' === $handle ) {
            return str_replace( '>', ' crossorigin="anonymous" >', $tag );
        }
        return $tag;
    }

    public static function is_uuid($uuid, $accept_null = true) {
        if (class_exists('OpenSim')) {
            return OpenSim::is_uuid($uuid, $accept_null);
        }
        // Fallback implementation
        if (!is_string($uuid)) return false;
        return preg_match('/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/', $uuid);
    }
    
    // Additional methods that might be needed by v1 functions
    public static function empty($var) {
        if (class_exists('OpenSim')) {
            return OpenSim::empty($var);
        }
        if (!$var) return true;
        if (empty($var)) return true;
        $null_keys = ['00000000-0000-0000-0000-000000000000', '00000000-0000-0000-0000-000000000001'];
        if (in_array($var, $null_keys)) return true;
        return false;
    }

    public static function add_submenu_page($parent_slug, $page_title, $menu_title, $capability, $menu_slug, $callback = '', $position = null) {
        $parent_slug = 'w4os';
        $prefix = $parent_slug . '-';
        
        if (!preg_match('/^' . $prefix . '/', $menu_slug)) {
            $menu_slug = $prefix . $menu_slug;
        }
        
        return add_submenu_page(
            $parent_slug,
            $page_title,
            $menu_title,
            $capability,
            $menu_slug,
            $callback,
            $position
        );
    }
    
    public static function is_new_post($args = null) {
        global $pagenow;
        
        if (!is_admin()) {
            return false;
        }
        
        return in_array($pagenow, ['post-new.php']);
    }
    
    public static function format_date($timestamp, $format = 'MEDIUM', $timetype_str = 'NONE') {
        switch ($format) {
            case 'MEDIUM':
                $format = get_option('date_format');
                return date_i18n($format, $timestamp);
                
            case 'LONG':
            case 'DATE_TIME':
                return sprintf(
                    __('%s at %s', 'w4os'),
                    date_i18n(get_option('date_format'), $timestamp),
                    date_i18n(get_option('time_format'), $timestamp)
                );
                
            default:
                $format = get_option('date_format');
                return date_i18n($format, $timestamp);
        }
    }
    
    public static function get_localized_post_id($post_id = null, $default = true) {
        if (empty($post_id)) {
            $post_id = get_the_id();
        }
        
        // Check for WPML
        if (function_exists('icl_object_id')) {
            $default_language = apply_filters('wpml_default_language', null);
            if ($default) {
                $localized_post_id = icl_object_id($post_id, 'post', false, $default_language);
            } else {
                $localized_post_id = icl_object_id($post_id, 'post', false);
                $localized_post_id = (empty($localized_post_id)) ? icl_object_id($post_id, 'post', false, $default_language) : $localized_post_id;
            }
            
            return empty($localized_post_id) ? $post_id : $localized_post_id;
        }
        
        // Check for Polylang
        if (function_exists('pll_get_post')) {
            global $polylang;
            $languages = $polylang->model->get_languages_list();
            
            if ($default) {
                $default_language = $polylang->default_lang;
            } else {
                $default_language = get_locale();
            }
            
            $localized_post_id = $post_id;
            
            if (isset($languages[$default_language]) && $languages[$default_language]['slug'] !== get_locale()) {
                $translations = $polylang->model->post->get_translations($post_id);
                
                if (isset($translations[$default_language])) {
                    $localized_post_id = $translations[$default_language];
                }
            }
            
            return $localized_post_id;
        }
        
        return $post_id;
    }
    
    public static function get_localized_post_slug($post_id = null) {
        $localized_post_id = self::get_localized_post_id($post_id);
        $original = get_post($localized_post_id);
        $post_name = isset($original->post_name) ? $original->post_name : null;
        return $post_name;
    }

    public static function in_world_call() {
		$viewer = preg_match( '/SecondLife|OpenSim/', $_SERVER['HTTP_USER_AGENT'] );
		return ( $viewer ) ? true : false;
	}

	/**
	 * Detect if the current page is called by a viewer (e.g. like FireStorm or Singularity, or any other SecondLife compatible viewer).
	 */
	public static function viewer_session_auth() {
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
	public static function get_ini_config( $instance = 'robust' ) {
		if ( self::$ini ) {
			return self::$ini;
		}
		$ini = array();
		if ( ! self::get_console_config( $instance ) ) {
			// Console is not configured, ignore it.
			return $ini;
		}

		$response = self::console( $instance, 'config get' );
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

	public static function console( $instance = 'robust', $command = null ) {
		if ( ! is_array( $instance ) && ! self::get_console_config( $instance ) ) {
			return false;
		}

		$console = self::console_connect( $instance );
		if ( $console === false ) {
			return false;
		}

		if ( ! self::$console || is_wp_error( self::$console ) ) {
			return self::$console;
		}

		if ( self::$console && ! empty( $command ) ) {
			$response = self::$console->sendCommand( $command );
			if ( is_opensim_rest_error( $response ) ) {
				$error = new WP_Error( 'console_command_failed', $response->getMessage() );
			} else {
				return $response;
			}
		}
	}

	private static function get_console_config( $instance = 'robust' ) {
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

	public static function console_connect( $instance = 'robust' ) {
		if ( self::$console !== null ) {
			return self::$console;
		}

		$rest_args = self::get_console_config( $instance );
		if ( empty( $rest_args ) ) {
			error_log( "Console not set for $instance, that's OK" );
			return;
		}

		$rest = new OpenSim_Rest( $rest_args );
		if ( isset( $rest->error ) && is_opensim_rest_error( $rest->error ) ) {
			$error         = new WP_Error( 'console_connection_failed', $rest->error->getMessage() );
			self::$console = $error;
			return $error;
		} else {
			$response = $rest->sendCommand( 'show info' );
			if ( is_opensim_rest_error( $response ) ) {
				$error         = new WP_Error( 'console_command_failed', $response->getMessage() );
				self::$console = $error;
				return $error;
			} else {
				self::$console = $rest;
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
		define( 'W4OS_TEMPLATES_DIR', W4OS_PLUGIN_DIR . 'wordpress/templates/' );
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

		require_once W4OS_PLUGIN_DIR . 'v2/admin-helpers/class-opensim-rest.php';

        if ( function_exists( 'xmlrpc_encode_request' ) ) {
            // global $SearchDB, $AssetsDB, $ProfileDB, $OpenSimDB;
            require_once __DIR__ . '/includes/load-helpers.php';
        }

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

	public static function register_legacy_settings_fields( $meta_boxes ) {
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

		$credentials['db']['enabled'] = OpenSim::validate_db_credentials( $credentials['db'] );

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
	private static function set_key() {
		$login_uri = get_option( 'w4os_login_uri', home_url() );
		$grid_info = self::grid_info();
		$combine   = array( $login_uri, $grid_info['gridnick'], $grid_info['platform'] );
		$key       = md5( sanitize_title( implode( ' ', $combine ) ) );
		self::$key = $key;
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

		$xml = OpenSim::fast_xml( $check_login_uri . '/get_grid_info' );

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
}

// Load WordPress-specific functions and utilities
require_once __DIR__ . '/includes/public-functions.php';
require_once __DIR__ . '/includes/admin-functions.php';
require_once __DIR__ . '/includes/class-list-table.php';
require_once __DIR__ . '/includes/class-userless-auth.php';
require_once __DIR__ . '/includes/class-user-menu.php';

require_once __DIR__ . '/class-w4os3-service.php';

// Load WP classes
require_once __DIR__ . '/class-w4os3-settings.php';
require_once __DIR__ . '/class-w4os3-model.php';
require_once __DIR__ . '/class-w4os3-avatar.php';
require_once __DIR__ . '/class-w4os3-flux.php';
require_once __DIR__ . '/class-w4os3-region.php';

// Load templates.php only on the front end, exclude admin, feeds, ajax, REST API, jquery, etc.
if ( w4os_is_front_end() ) {
	require_once dirname( __DIR__ ) . '/templates/templates.php';
}

// Load legacy code until they are migrated to the new W4OS3 structure.
// Legacy v1 init
require_once W4OS_PLUGIN_DIR . 'v1/init.php';
// Legacy v2 loader
require_once W4OS_PLUGIN_DIR . 'v2/loader.php';

// Load admin functionality
if (is_admin()) {
    require_once W4OS_PLUGIN_DIR . 'v1/admin/admin-init.php';
}

// Temporary workaround, load legacy helpers configuration.
// This should be replaced with a proper configuration management system in the future.
try {
    require_once dirname(__DIR__) . '/helpers/includes/config.php';
} catch (Exception $e) {
    error_log("[ERROR] Failed to load legacy helpers configuration: " . $e->getMessage());
}

W4OS3::init();
