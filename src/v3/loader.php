<?php
/**
 * Register all actions and filters for the plugin
 *
 * @link       https://github.com/magicoli/w4os
 * @since      0.1.0
 *
 * @package    w4os
 * @subpackage w4os/includes
 */

/**
 * Register all actions and filters for the plugin.
 *
 * Maintain a list of all hooks that are registered throughout
 * the plugin, and register them with the WordPress API. Call the
 * run function to execute the list of actions and filters.
 *
 * @package    w4os
 * @subpackage w4os/includes
 * @author     Magiiic <info@magiiic.com>
 */
class W4OS {

	/**
	 * The array of actions registered with WordPress.
	 *
	 * @since    0.1.0
	 * @access   protected
	 * @var      array    $actions    The actions registered with WordPress to fire when the plugin loads.
	 */
	protected $actions;

	/**
	 * The array of filters registered with WordPress.
	 *
	 * @since    0.1.0
	 * @access   protected
	 * @var      array    $filters    The filters registered with WordPress to fire when the plugin loads.
	 */
	protected $filters;

	/**
	 * Initialize the collections used to maintain the actions and filters.
	 *
	 * @since    0.1.0
	 */
	public function __construct() {
		$this->define_constants();

		$this->load_dependencies();

		$this->actions = array(
			array(
				'hook'     => 'wp_enqueue_scripts',
				'callback' => 'enqueue_scripts',
			),
			array(
				'hook'     => 'admin_enqueue_scripts',
				'callback' => 'enqueue_admin_scripts',
			),
		);
		$this->filters = array();

		$this->init();
	}

	private function define_constants() {
		/**
		 * General plugin constants
		 */
		define( 'W4OS_DIR', wp_normalize_path( dirname( __DIR__ ) ) );
		define( 'W4OS_SLUG', basename( W4OS_DIR ) );
		define( 'W4OS_PLUGIN', W4OS_SLUG . '/w4os.php' );
		$plugin_data = get_file_data(
			WP_PLUGIN_DIR . '/' . W4OS_PLUGIN,
			array(
				'Name'       => 'Plugin Name',
				// 'PluginURI' => 'Plugin URI',
				'Version'    => 'Version',
				// 'Description' => 'Description',
				// 'Author' => 'Author',
				// 'AuthorURI' => 'Author URI',
				'TextDomain' => 'Text Domain',
			// 'DomainPath' => 'Domain Path',
			// 'Network' => 'Network',
			)
		);
		define( 'W4OS_PLUGIN_NAME', $plugin_data['Name'] );
		if ( file_exists( '.git/refs/heads/master' ) ) {
			$hash = trim( file_get_contents( '.git/refs/heads/master' ) );
		}
		if ( ! empty( $hash ) ) {
			$plugin_data['Version'] .= '-git #' . substr( $hash, 0, 8 );
		}

		define( 'W4OS_VERSION', $plugin_data['Version'] );
		define( 'W4OS_TXDOM', ( $plugin_data['TextDomain'] ) ? $plugin_data['TextDomain'] : W4OS_SLUG );

		/**
		 * OpenSimulator constants
		 */
		define( 'W4OS_NULL_KEY', '00000000-0000-0000-0000-000000000000' );
		// define('W4OS_ZERO_VECTOR', '<0,0,0>');
		// define('W4OS_W4OS_DEFAULT_AVATAR_HEIGHT', '1.7');
		// define('W4OS_DEFAULT_AVATAR_PARAMS', '33,61,85,23,58,127,63,85,63,42,0,85,63,36,85,95,153,63,34,0,63,109,88,132,63,136,81,85,103,136,127,0,150,150,150,127,0,0,0,0,0,127,0,0,255,127,114,127,99,63,127,140,127,127,0,0,0,191,0,104,0,0,0,0,0,0,0,0,0,145,216,133,0,127,0,127,170,0,0,127,127,109,85,127,127,63,85,42,150,150,150,150,150,150,150,25,150,150,150,0,127,0,0,144,85,127,132,127,85,0,127,127,127,127,127,127,59,127,85,127,127,106,47,79,127,127,204,2,141,66,0,0,127,127,0,0,0,0,127,0,159,0,0,178,127,36,85,131,127,127,127,153,95,0,140,75,27,127,127,0,150,150,198,0,0,63,30,127,165,209,198,127,127,153,204,51,51,255,255,255,204,0,255,150,150,150,150,150,150,150,150,150,150,0,150,150,150,150,150,0,127,127,150,150,150,150,150,150,150,150,0,0,150,51,132,150,150,150');
		define( 'W4OS_DEFAULT_AVATAR', 'Default Ruth' );
		define( 'W4OS_DEFAULT_HOME', 'Welcome' );
		define( 'W4OS_DEFAULT_RESTRICTED_NAMES', array( 'Default', 'Test', 'Admin', str_replace( ' ', '', get_option( 'w4os_grid_name' ) ) ) );
		define( 'W4OS_DEFAULT_ASSET_SERVER_URI', '/assets/asset.php?id=' );
		define( 'W4OS_DEFAULT_PROVIDE_ASSET_SERVER', true );
		define( 'W4OS_ASSETS_DEFAULT_FORMAT', 'jpg' );
		define( 'W4OS_NOTFOUND_IMG', '201ce950-aa38-46d8-a8f1-4396e9d6be00' );
		define( 'W4OS_NOTFOUND_PROFILEPIC', '201ce950-aa38-46d8-a8f1-4396e9d6be00' );

		// define( 'W4OS_PATTERN_NAME', '[A-Za-z][A-Za-z0-9]* [A-Za-z][A-Za-z0-9]*' ); // Moved to v3 init class
		define( 'OPENSIM_GRID_NAME', self::get_option( 'grid_name' ) );
		define( 'W4OS_LOGIN_PAGE', get_home_url( null, get_option( 'w4os_profile_slug' ) ) );
		define( 'W4OS_GRID_LOGIN_URI', self::login_uri() );
		if ( empty( get_option( 'w4os_assets_slug' ) ) ) {
			update_option( 'w4os_assets_slug', 'assets' );
		}
		define( 'W4OS_GRID_ASSETS_SERVER', W4OS_GRID_LOGIN_URI . '/assets/' );
		if ( get_option( 'w4os_profile_page' ) == 'provide' ) {
			define( 'W4OS_PROFILE_URL', get_home_url( null, get_option( 'w4os_profile_slug' ) ) );
		}
		define( 'W4OS_GRID_INFO', self::grid_info() );

		define(
			'W4OS_WEB_ASSETS_SERVER_URI',
			( get_option( 'w4os_provide_asset_server' ) == 1 )
			? get_home_url( null, '/' . get_option( 'w4os_assets_slug' ) . '/' )
			: esc_attr( get_option( 'w4os_external_asset_server_uri' ) )
		);
		if ( get_option( 'w4os_provide_asset_server' ) == 1 ) {
			update_option( 'w4os_internal_asset_server_uri', W4OS_WEB_ASSETS_SERVER_URI );
		}
		if ( ! get_option( 'w4os_login_page' ) ) {
			update_option( 'w4os_login_page', 'profile' );
		}
	}

	private function load_dependencies() {

		require_once W4OS_DIR . '/legacy/init.php';

		/**
		 * Template overrides
		 */
		// require_once W4OS_DIR . '/templates/templates.php';

		/**
		 * The standard plugin classes.
		 */
		require_once W4OS_DIR . '/includes/class-i18n.php';
		// require_once W4OS_DIR . '/admin/class-admin.php';
		// require_once W4OS_DIR . '/public/class-public.php';

		/**
		 * The class responsible for defining all actions that occur in the admin area.
		 */
		require_once W4OS_DIR . '/admin/class-admin.php';

		/**
		 * The class responsible for defining all actions that occur in the public-facing
		 * side of the site.
		 */
		require_once W4OS_DIR . '/public/class-public.php';

		/**
		 * Specific plugin classes.
		 */
		require_once W4OS_DIR . '/includes/class-settings.php';
		require_once W4OS_DIR . '/includes/class-avatar.php';
		require_once W4OS_DIR . '/includes/class-model.php';

		/**
		 * External libraries.
		 */
		require_once W4OS_DIR . '/vendor/autoload.php';

		/**
		 * Database updates
		 */
		require_once W4OS_DIR . '/includes/updates.php';
		if ( file_exists( W4OS_DIR . '/lib/package-updater.php' ) ) {
			include_once W4OS_DIR . '/lib/package-updater.php';
		}

		// if(is_plugin_active('woocommerce/woocommerce.php')) {
		// require_once W4OS_DIR . '/includes/modules/class-woocommerce.php';
		// $this->loaders[] = new W4OS_WooCommerce();
		//
		// require_once W4OS_DIR . '/includes/modules/class-woocommerce-payment.php';
		// $this->loaders[] = new W4OS_WooCommerce_Payment();
		// }
	}

	/**
	 * Add a new action to the collection to be registered with WordPress.
	 *
	 * @since    0.1.0
	 * @param    string $hook             The name of the WordPress action that is being registered.
	 * @param    object $component        A reference to the instance of the object on which the action is defined.
	 * @param    string $callback         The name of the function definition on the $component.
	 * @param    int    $priority         Optional. The priority at which the function should be fired. Default is 10.
	 * @param    int    $accepted_args    Optional. The number of arguments that should be passed to the $callback. Default is 1.
	 */
	public function add_action( $hook, $component, $callback, $priority = 10, $accepted_args = 1 ) {
		$this->actions = $this->add( $this->actions, $hook, $component, $callback, $priority, $accepted_args );
	}

	/**
	 * Add a new filter to the collection to be registered with WordPress.
	 *
	 * @since    0.1.0
	 * @param    string $hook             The name of the WordPress filter that is being registered.
	 * @param    object $component        A reference to the instance of the object on which the filter is defined.
	 * @param    string $callback         The name of the function definition on the $component.
	 * @param    int    $priority         Optional. The priority at which the function should be fired. Default is 10.
	 * @param    int    $accepted_args    Optional. The number of arguments that should be passed to the $callback. Default is 1.
	 */
	public function add_filter( $hook, $component, $callback, $priority = 10, $accepted_args = 1 ) {
		$this->filters = $this->add( $this->filters, $hook, $component, $callback, $priority, $accepted_args );
	}

	/**
	 * A utility function that is used to register the actions and hooks into a single
	 * collection.
	 *
	 * @since    0.1.0
	 * @access   private
	 * @param    array  $hooks            The collection of hooks that is being registered (that is, actions or filters).
	 * @param    string $hook             The name of the WordPress filter that is being registered.
	 * @param    object $component        A reference to the instance of the object on which the filter is defined.
	 * @param    string $callback         The name of the function definition on the $component.
	 * @param    int    $priority         The priority at which the function should be fired.
	 * @param    int    $accepted_args    The number of arguments that should be passed to the $callback.
	 * @return   array                                  The collection of actions and filters registered with WordPress.
	 */
	private function add( $hooks, $hook, $component, $callback, $priority, $accepted_args ) {

		$hooks[] = array(
			'hook'          => $hook,
			'component'     => $component,
			'callback'      => $callback,
			'priority'      => $priority,
			'accepted_args' => $accepted_args,
		);

		return $hooks;
	}

	/**
	 * Register the filters and actions with WordPress.
	 *
	 * @since    0.1.0
	 */
	public function init() {

		if ( ! empty( $this->loaders ) && is_array( $this->loaders ) ) {
			foreach ( $this->loaders as $key => $loader ) {
				if ( method_exists( $loader, 'init' ) ) {
					$loader->init();
				}
				$loader->register_hooks();
			}
		}

		if ( get_transient( 'w4os_rewrite_flush' ) || get_transient( 'w4os_rewrite_version' ) != W4OS_VERSION ) {
			wp_cache_flush();
			add_action( 'init', 'flush_rewrite_rules' );
			delete_transient( 'w4os_rewrite_flush' );
			set_transient( 'w4os_rewrite_version', W4OS_VERSION );
			// admin_notice( 'Rewrite rules flushed' );
		}

		$this->register_hooks();
	}

	function register_hooks() {
		if ( is_array( $this->filters ) ) {
			foreach ( $this->filters as $hook ) {
				$hook = array_merge(
					array(
						'component'     => $this,
						'priority'      => 10,
						'accepted_args' => 1,
					),
					$hook
				);
				add_filter( $hook['hook'], array( $hook['component'], $hook['callback'] ), $hook['priority'], $hook['accepted_args'] );
			}
		}

		if ( is_array( $this->actions ) ) {
			foreach ( $this->actions as $hook ) {
				$hook = array_merge(
					array(
						'component'     => $this,
						'priority'      => 10,
						'accepted_args' => 1,
					),
					$hook
				);
				add_action( $hook['hook'], array( $hook['component'], $hook['callback'] ), $hook['priority'], $hook['accepted_args'] );
			}
		}
	}

	public function enqueue_scripts() {
		wp_enqueue_style( 'w4os-public', plugin_dir_url( __FILE__ ) . 'public/public.css', array(), W4OS_VERSION );
		wp_enqueue_script( 'w4os-public', plugin_dir_url( __FILE__ ) . 'public/public.js', array( 'jquery' ), W4OS_VERSION );
	}

	public function enqueue_admin_scripts() {

		if ( is_admin() ) {
			wp_enqueue_style( 'w4os-admin', plugin_dir_url( __FILE__ ) . 'admin/admin.css', array(), W4OS_VERSION );
			wp_enqueue_script( 'w4os-admin', plugin_dir_url( __FILE__ ) . 'admin/admin.js', array( 'jquery' ), W4OS_VERSION );
		}
	}

	// Replaced by W4OS3::is_new_post() in W4OS3 class
	// static function is_new_post( $args = null ) {
	// global $pagenow;
	// make sure we are on the backend
	// if ( ! is_admin() ) {
	// return false;
	// }
	// return in_array( $pagenow, array( 'post-new.php' ) );
	// return in_array( $pagenow, array( 'post.php', 'post-new.php' ) );
	// }

	static function get_option( $option, $default = false ) {
		$settings_page = null;
		$result        = $default;
		if ( preg_match( '/:/', $option ) ) {
			$settings_page = strstr( $option, ':', true );
			$option        = trim( strstr( $option, ':' ), ':' );
		} else {
			$settings_page = 'w4os_settings';
		}
		$settings = get_option( $settings_page );
		if ( $settings && isset( $settings[ $option ] ) ) {
			$result = $settings[ $option ];
		}

		return $result;
	}

	static function login_uri() {
		if ( defined( 'W4OS_GRID_LOGIN_URI' ) ) {
			return W4OS_GRID_LOGIN_URI;
		}
		return 'http://' . esc_attr( get_option( 'w4os_login_uri' ) );
	}

	function grid_info( $rechecknow = false ) {
		$grid_info = get_option( 'w4os_grid_info' );

		if ( $rechecknow || get_option( 'w4os_check_urls_now' ) ) {
			return self::fetch_grid_info( true );
		}

		if ( ! empty( $grid_info ) ) {
			return json_decode( $grid_info, true );
		}

		return self::fetch_grid_info();
	}

	static function fetch_grid_info( $rechecknow = false ) {
		if ( defined( 'W4OS_GRID_INFO_CHECKED' ) & ! $rechecknow ) {
			return get_option( 'w4os_grid_info' );
		}
		define( 'W4OS_GRID_INFO_CHECKED', true );
		$local_uri       = 'http://localhost:8002';
		$check_login_uri = ( get_option( 'w4os_login_uri' ) ) ? 'http://' . get_option( 'w4os_login_uri' ) : $local_uri;
		$check_login_uri = preg_replace( '+http://http+', 'http', $check_login_uri );
		// $xml = simplexml_load_file($check_login_uri . '/get_grid_info');
		$xml = w4os_fast_xml( $check_login_uri . '/get_grid_info' );

		if ( ! $xml ) {
			return false;
		}
		if ( $check_login_uri == $local_uri ) {
			w4os_admin_notice( __( 'A local Robust server has been found. Please check Login URI and Grid Name configuration.', 'w4os' ), 'success' );
		}

		$grid_info = (array) $xml;
		if ( 'provide' === get_option( 'w4os_profile_page' ) && empty( $grid_info['profile'] ) && defined( 'W4OS_PROFILE_URL' ) ) {
			$grid_info['profile'] = W4OS_PROFILE_URL;
		}
		if ( ! empty( $grid_info['login'] ) ) {
			update_option( 'w4os_login_uri', preg_replace( '+/*$+', '', preg_replace( '+https*://+', '', $grid_info['login'] ) ) );
		}
		if ( ! empty( $grid_info['gridname'] ) ) {
			update_option( 'w4os_grid_name', $grid_info['gridname'] );
		}
		if ( isset( $grid_info['message'] ) ) {
			update_option( 'w4os_offline_helper_uri', $grid_info['message'] );
		}

		if ( isset( $urls ) && is_array( $urls ) ) {
			w4os_get_urls_statuses( $urls, get_option( 'w4os_check_urls_now' ) );
		}

		update_option( 'w4os_grid_info', json_encode( $grid_info ) );
		return $grid_info;
	}
}

$w4os_loader = new W4OS();
