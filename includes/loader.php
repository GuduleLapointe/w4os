<?php
/**
 * Register all actions and filters for the plugin
 *
 * @link       https://github.com/magicoli/w4os
 * @since      0.1.0
 *
 * @package    GuduleLapointe/w4os
 * @subpackage w4os/includes
 */

/**
 * Register all actions and filters for the plugin.
 *
 * Maintain a list of all hooks that are registered throughout
 * the plugin, and register them with the WordPress API. Call the
 * run function to execute the list of actions and filters.
 *
 * @package    GuduleLapointe/w4os
 * @subpackage w4os/includes
 * @author     Magiiic <info@magiiic.com>
 */
class W4OS_Loader {

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
		$this->load_dependencies();

		$this->actions = array(
			array(
				'hook'     => 'init',
				'callback' => 'add_shortcodes',
			),
			array(
				'hook'     => 'wp_enqueue_scripts',
				'callback' => 'enqueue_scripts',
			),
			array(
				'hook'     => 'admin_enqueue_scripts',
				'callback' => 'enqueue_admin_scripts',
			),
		);
		$this->filters = array(
			// array(
			// 'hook' => 'mod_rewrite_rules',
			// 'callback'=> 'mod_rewrite_rules_filter',
			// ),
		);

		$this->init();
	}

	private function load_dependencies() {

		/**
		 * External libraries.
		 */
		// require_once W4OS_DIR . '/vendor/autoload.php';

		/**
		 * Template overrides
		 */
		// require_once W4OS_DIR . '/templates/templates.php';
		require_once W4OS_DIR . '/includes/class-helpers-assets.php';

		if ( is_admin() ) {
			require_once W4OS_DIR . '/admin/admin-loader.php';
		}
		/**
		 * The standard plugin classes.
		 */
		require_once W4OS_DIR . '/helpers/includes/functions.php';
		require_once W4OS_DIR . '/includes/class-w4os.php';
		require_once W4OS_DIR . '/includes/class-db.php';
		require_once W4OS_DIR . '/includes/class-i18n.php';
		require_once W4OS_DIR . '/includes/class-settings.php';
		require_once W4OS_DIR . '/includes/class-tos.php';
		// require_once W4OS_DIR . '/admin/class-admin.php';
		// require_once W4OS_DIR . '/public/class-public.php';

		/**
		 * Specific plugin classes.
		 */
		require_once W4OS_DIR . '/includes/class-avatar-model.php';
		require_once W4OS_DIR . '/includes/class-avatar-profile.php';
		require_once W4OS_DIR . '/includes/class-helpers.php';
		if ( function_exists( 'xmlrpc_encode_request' ) ) {
			// TODO: more accurate dependencies checking
			require_once W4OS_DIR . '/includes/class-helpers-economy.php';
			require_once W4OS_DIR . '/includes/class-helpers-search.php';
		}
		require_once W4OS_DIR . '/includes/class-helpers-guide.php';
		require_once W4OS_DIR . '/includes/class-helpers-offline.php';

		/**
		 * Database updates
		 */

		require_once W4OS_DIR . '/includes/updates.php';

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

	function add_shortcodes() {
		add_shortcode( 'base_url', 'baseurl_shortcode' );
		function baseurl_shortcode( $atts ) {

			return site_url();

		}
	}

	/**
	 * Update .htaccess rewrite rules to optimize speed for helpers not relying on WP framework.
	 */
	// function mod_rewrite_rules_filter($rules) {
	// $permalink_slug = 'guide';
	// $new_rule = 'RewriteRule ^' . $permalink_slug . '(/.*)?$ ' . W4OS_DIR . '/helpers/guide.php [QSA,L]';
	// $rules = $new_rule . "\n" . $rules;
	// error_log("rules\n" . print_r($rules, true));
	//
	// return $rules;
	// }
}

$w4os_loader = new W4OS_Loader();
