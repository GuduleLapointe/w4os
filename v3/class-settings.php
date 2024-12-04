<?php

/**
 * Register all actions and filters for the plugin
 *
 * @link       http://example.com
 * @since      1.0.0
 *
 * @package    W4OS
 * @subpackage W4OS/includes
 */

/**
 * Register all actions and filters for the plugin.
 *
 * Maintain a list of all hooks that are registered throughout
 * the plugin, and register them with the WordPress API. Call the
 * run function to execute the list of actions and filters.
 *
 * @package    W4OS
 * @subpackage W4OS/includes
 * @author     Your Name <email@example.com>
 */
class W4OS3_Settings extends W4OS {

	/**
	 * The array of actions registered with WordPress.
	 *
	 * @since    1.0.0
	 * @access   protected
	 * @var      array    $actions    The actions registered with WordPress to fire when the plugin loads.
	 */
	protected $actions;

	/**
	 * The array of filters registered with WordPress.
	 *
	 * @since    1.0.0
	 * @access   protected
	 * @var      array    $filters    The filters registered with WordPress to fire when the plugin loads.
	 */
	protected $filters;

	/**
	 * Initialize the collections used to maintain the actions and filters.
	 *
	 * @since    1.0.0
	 */
	public function __construct() {

		$this->actions = array();
		$this->filters = array();

	}

	/**
	 * Add a new action to the collection to be registered with WordPress.
	 *
	 * @since    1.0.0
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
	 * @since    1.0.0
	 * @param    string $hook             The name of the WordPress filter that is being registered.
	 * @param    object $component        A reference to the instance of the object on which the filter is defined.
	 * @param    string $callback         The name of the function definition on the $component.
	 * @param    int    $priority         Optional. The priority at which the function should be fired. Default is 10.
	 * @param    int    $accepted_args    Optional. The number of arguments that should be passed to the $callback. Default is 1
	 */
	public function add_filter( $hook, $component, $callback, $priority = 10, $accepted_args = 1 ) {
		$this->filters = $this->add( $this->filters, $hook, $component, $callback, $priority, $accepted_args );
	}

	/**
	 * A utility function that is used to register the actions and hooks into a single
	 * collection.
	 *
	 * @since    1.0.0
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
	 * @since    1.0.0
	 */
	public function run() {

		$actions = array(
			array(
				'hook'     => 'admin_menu',
				'callback' => 'legacy_admin_menu',
				// 'priority' => 5,
			),
			array(
				'hook'     => 'admin_menu',
				'callback' => 'legacy_admin_submenus',
				'priority' => 15,
			),
		);
		$filters = array(
			array(
				'hook'     => 'mb_settings_pages',
				'callback' => 'register_settings_pages',
			),
			array(
				'hook'     => 'rwmb_meta_boxes',
				'callback' => 'register_settings_fields',
			),
		);

		foreach ( $filters as $hook ) {
			( empty( $hook['component'] ) ) && $hook['component']         = __CLASS__;
			( empty( $hook['priority'] ) ) && $hook['priority']           = 10;
			( empty( $hook['accepted_args'] ) ) && $hook['accepted_args'] = 1;
			add_filter( $hook['hook'], array( $hook['component'], $hook['callback'] ), $hook['priority'], $hook['accepted_args'] );
		}

		foreach ( $actions as $hook ) {
			( empty( $hook['component'] ) ) && $hook['component']         = __CLASS__;
			( empty( $hook['priority'] ) ) && $hook['priority']           = 10;
			( empty( $hook['accepted_args'] ) ) && $hook['accepted_args'] = 1;
			add_action( $hook['hook'], array( $hook['component'], $hook['callback'] ), $hook['priority'], $hook['accepted_args'] );
		}

	}

	static function legacy_admin_menu() {
		// add_options_page('OpenSimulator settings', 'w4os', 'manage_options', 'w4os', 'w4os_settings_page');
		add_menu_page(
			'OpenSimulator', // page title
			'OpenSimulator', // menu title
			'manage_options', // capability
			'w4os', // slug
			'', // callable function
			// plugin_dir_path(__FILE__) . 'options.php', // slug
			// null,	// callable function
			plugin_dir_url( __DIR__ ) . 'images/w4os-logo-16x16.png', // icon url,
			2 // position
		);

	}

	static function legacy_admin_submenus() {
		add_submenu_page(
			'w4os', // parent
			__( 'OpenSimulator Settings', 'w4os' ), // page title
			__( 'Legacy Settings', 'w4os' ), // menu title
			'manage_options', // capability
			'w4os_settings_legacy', // menu slug
			'w4os_settings_page' // function
		);

		if ( function_exists( 'xmlrpc_encode_request' ) ) {
			add_submenu_page(
				'w4os', // parent
				__( 'OpenSimulator Helpers', 'w4os' ), // page title
				__( 'Legacy Helpers', 'w4os' ), // menu title
				'manage_options', // capability
				'w4os_helpers', // menu slug
				'w4os_helpers_page' // function
			);
		}
		add_submenu_page( 'w4os', __( 'OpenSimulator Status', 'w4os' ), __( 'Status' ), 'manage_options', 'w4os', 'w4os_status_page', 20 );
	}

	static function register_settings_pages( $settings_pages ) {
		// $settings_pages[] = [
		// 'menu_title'    => __( 'OpenSimulator', 'w4os' ),
		// 'id'            => 'w4os',
		// 'position'      => 2,
		// 'submenu_title' => 'Settings',
		// 'capability'    => 'manage_options',
		// 'style'         => 'no-boxes',
		// 'columns'       => 1,
		// 'icon_url'      => plugin_dir_url(__DIR__) . 'images/w4os-logo-16x16.png', // icon url,
		// ];

		$settings_pages[] = array(
			'menu_title' => __( 'Settings', 'w4os' ),
			'page_title' => sprintf(
				__( '%s Settings', 'w4os' ),
				OPENSIM_GRID_NAME,
			),
			'id'         => 'w4os_settings',
			// 'position'   => 2,
			'parent'     => 'w4os',
			'capability' => 'manage_options',
			'style'      => 'no-boxes',
			// 'icon_url'   => 'dashicons-admin-generic',
		);

		return $settings_pages;
	}

	static function register_settings_fields( $meta_boxes ) {
		$prefix = '';

		$meta_boxes[] = array(
			'title'          => __( 'W4OS Settings', 'w4os' ),
			'id'             => 'w4os-settings',
			'settings_pages' => array( 'w4os_settings' ),
			'fields'         => array(
				array(
					'name'              => __( 'Login URI', 'w4os' ),
					'id'                => $prefix . 'login_uri',
					'type'              => 'text',
					'placeholder'       => __( 'yourgrid.org:8002', 'w4os' ),
					'sanitize_callback' => 'W4OS3_Settings::sanitize_login_uri',
				),
				// array(
				// 'name'              => __( 'Grid Name', 'w4os' ),
				// 'id'                => $prefix . 'grid_name',
				// 'type'              => 'text',
				// 'placeholder'       => __( 'Your Grid', 'w4os' ),
				// 'readonly'          => true,
				// 'sanitize_callback' => 'W4OS3_Settings::sanitize_grid_name',
				// ),
				array(
					'id'                => $prefix . 'simulator_config',
					'type'              => 'group',
					'sanitize_callback' => 'W4OS3_Settings::sanitize_config_files',
					'fields'            => array(
						array(
							'name'        => __( 'Binaries folder', 'w4os' ),
							'id'          => $prefix . 'binaries',
							'type'        => 'text',
							'placeholder' => __( 'Full path of OpenSimulator binaries folder (containing OpenSim.exe or Robust.exe)', 'w4os' ),
						),
						array(
							'name'  => __( 'Use custom config files', 'w4os' ),
							'id'    => $prefix . 'custom_config',
							'type'  => 'switch',
							'desc'  => __( 'Enable to use custom config files locations.', 'w4os' ),
							'style' => 'rounded',
							'class' => 'inline',
						),
						array(
							'name'              => __( 'Robust ini file', 'w4os' ),
							'id'                => $prefix . 'robust_ini',
							'type'              => 'text',
							'label_description' => __( 'Grid mode only, leave empty for standalone simulators.', 'w4os' ),
							'placeholder'       => __( 'Full path of Robust.ini or Robust.HG.ini', 'w4os' ),
							'visible'           => array(
								'when'     => array( array( 'custom_config', '=', 1 ) ),
								'relation' => 'or',
							),
						),
						array(
							'name'              => __( 'Core Robust ini', 'w4os' ),
							'id'                => $prefix . 'core_robust_ini',
							'type'              => 'text',
							'label_description' => __( 'Grid mode only, empty for standalone simulators.', 'w4os' ),
							'readonly'          => true,
							'visible'           => array(
								'when'     => array( array( 'custom_config', '=', '' ) ),
								'relation' => 'or',
							),
						),
						array(
							'name'              => __( 'OpenSim ini file', 'w4os' ),
							'id'                => $prefix . 'opensim_ini',
							'type'              => 'text',
							'label_description' => __( 'Simulator global config file.', 'w4os' ),
							'placeholder'       => __( 'Full path of OpenSim.ini', 'w4os' ),
							'visible'           => array(
								'when'     => array( array( 'custom_config', '=', 1 ) ),
								'relation' => 'or',
							),
						),
						array(
							'name'              => __( 'Core OpenSim ini', 'w4os' ),
							'id'                => $prefix . 'core_opensim_ini',
							'type'              => 'text',
							'label_description' => __( 'Simulator global config file.', 'w4os' ),
							'readonly'          => true,
							'visible'           => array(
								'when'     => array( array( 'custom_config', '=', '' ) ),
								'relation' => 'or',
							),
						),
						array(
							'name'              => __( 'OpenSimDefaults ini file', 'w4os' ),
							'id'                => $prefix . 'opensimdefaults_ini',
							'type'              => 'text',
							'label_description' => __( 'Leave empty, we\'ll guess it.', 'w4os' ),
							'placeholder'       => __( 'Full path of OpenSimDefault.ini', 'w4os' ),
							'visible'           => array(
								'when'     => array( array( 'custom_config', '=', 1 ) ),
								'relation' => 'or',
							),
						),
						array(
							'name'              => __( 'Core OpenSimDefaults ini', 'w4os' ),
							'id'                => $prefix . 'core_opensimdefaults_ini',
							'type'              => 'text',
							'label_description' => __( 'Leave empty, we\'ll guess it.', 'w4os' ),
							'readonly'          => true,
							'visible'           => array(
								'when'     => array( array( 'custom_config', '=', '' ) ),
								'relation' => 'or',
							),
						),
						array(
							'name'              => __( 'Simulators Folder', 'w4os' ),
							'id'                => $prefix . 'opensim_d',
							'type'              => 'text',
							'label_description' => __( 'If your setup allows sharing OpenSimulator binaries for several sims.', 'w4os' ),
							'placeholder'       => __( 'Full path of folder containing individual sims config files', 'w4os' ),
						),
					),
				),
				array(
					'name'              => __( 'Create WP accounts', 'w4os' ),
					'id'                => $prefix . 'create_wp_account',
					'type'              => 'switch',
					'label_description' => __( '(work in progress, not implemented)', 'w4os' ),
					'desc'              => __( 'Create a WordPress account for new avatars. If an account already exists with the same name or email address, force user to login first.', 'w4os' ),
					'style'             => 'rounded',
				),
				array(
					'name'              => __( 'Restrict Multiple Avatars', 'w4os' ),
					'id'                => $prefix . 'multiple_avatars',
					'type'              => 'switch',
					'label_description' => __( '(work in progress, not implemented)', 'w4os' ),
					'desc'              => __( 'Multple avatars sharing a single email address and/or WordPress account. Restriction only apply to end users. Multiple avatars are always possible from admin or from OpenSimulator console. (not implemented)', 'w4os' ),
					'style'             => 'rounded',
					'std'               => true,
				),
				array(
					'name'     => __( 'Debug', 'w4os' ),
					'id'       => $prefix . 'debug_html',
					'type'     => 'custom_html',
					'callback' => 'W4OS3_Settings::debug_callback',
				),
			),
		);

		return $meta_boxes;
	}

	public static function get_constant_value( $config, $value ) {
		if ( preg_match( '/\${.*}/', $value ) ) {
			$array = preg_split( '/[\$}]/', $value );
			foreach ( $array as $index => $string ) {
				if ( preg_match( '/{.*\|/', $string ) ) {
					$section = preg_replace( '/{(.*)\|.*/', '$1', $string );
					$param   = preg_replace( '/^{.*\|(.*)/', '$1', $string );
					if ( isset( $config[ $section ] ) && is_array( $config[ $section ] ) ) {
						$array[ $index ] = $config[ $section ][ $param ];
					} else {
						error_log( "Could not parse \$$string}" );
						$array[ $index ] = "\$$string}";
						break;
					}
				}
			}
			$value = join( '', $array );
		}

		return $value;
	}

	public static function parse_values( $config, $values ) {
		foreach ( $values as $key => $value ) {
			switch ( gettype( $value ) ) {
				case 'array':
					$values[ $key ] = self::parse_values( $config, $value );
					break;

				case 'string':
					$values[ $key ] = self::get_constant_value( $config, $value );
					break;
			}
		}
		return $values;
	}

	public static function parse_config_file( $config_file, $config = array() ) {
		$cleanup = self::cleanup_ini( $config_file );
		if ( empty( $cleanup ) ) {
			error_log( "$config_file is empty or unreadable" );
			return $config;
		}

		$tempfile = wp_tempnam( 'w4os-config-clean' );
		file_put_contents( $tempfile, $cleanup );
		$parse  = parse_ini_file( $tempfile, true );
		$config = array_replace_recursive( $config, $parse );
		unlink( $tempfile );

		foreach ( $parse as $section => $options ) {
			foreach ( $options as $option => $value ) {
				if ( preg_match( '/^Include-/', $option ) ) {
					$include = self::get_constant_value( $config, $value );
					$config  = array_replace_recursive( $config, self::parse_config_file( $include, $config ) );
				}
			}
		}

		return $config;
	}

	public static function cleanup_ini( $ini_file ) {
		$cleanup = file( $ini_file );
		if ( ! $cleanup ) {
			return array();
		}
		if ( ! is_array( $cleanup ) ) {
			error_log( "$cleanup $ini_file is not an array" );
		}
		$cleanup = preg_replace( '/^[[:blank:]]*([^=]*)[[:blank:]]*=[[:blank:]]*([^"]*\${.*)$/', '$1 = "$2"', $cleanup );
		$cleanup = preg_replace( "/\n/", '', $cleanup );
		// $cleanup = preg_replace('/^/', 'begin', $cleanup);
		$cleanup = preg_replace( '/\$/', '\\\$', $cleanup );
		// return '<pre>' . print_r($cleanup, true) . '</pre>';
		return join( "\n", $cleanup );
	}

	public static function debug_callback() {
		$html             = '';
		$simulator_config = W4OS::get_option( 'simulator_config', false );

		$simulator_config = array_merge(
			array(
				'robust_ini'               => null,
				'opensim_ini'              => null,
				'opensimdefaults_ini'      => null,
				'opensim_d'                => null,
				'core_robust_ini'          => null,
				'core_opensim_ini'         => null,
				'core_opensimdefaults_ini' => null,
			),
			$simulator_config
		);

		if ( isset( $simulator_config['custom_config'] ) ) {
			$inis = $simulator_config;
		} else {
			$inis = array(
				'robust_ini'          => $simulator_config['core_robust_ini'],
				'opensim_ini'         => $simulator_config['core_opensim_ini'],
				'opensimdefaults_ini' => $simulator_config['core_opensimdefaults_ini'],
			);
		}

		$config = array();
		if ( ! empty( $inis['opensim_ini'] ) ) {
			$config = self::parse_config_file( $inis['opensimdefaults_ini'] );
			$config = self::parse_config_file( $inis['opensim_ini'], $config );
			$values = self::parse_values( $config, $config );
			$values = self::parse_values( $values, $values );
			$html  .= '<pre>' . print_r( $values, true ) . '</pre>';
		}

		return $html;
	}

	public static function sanitize_local_path( $path, $field = null, $oldvalue = null ) {
		if ( empty( $path ) ) {
			return;
		}
		$path = esc_attr( $path );
		if ( ! is_readable( $path ) ) {
			if ( empty( $field['silent'] ) ) {
				w4os_admin_notice( sprintf( __( 'Could not access %s.', 'w4os' ), "<code>$path</code>.", ), 'error' );
			}
			return $oldvalue;
		}

		return $path;
	}

	public static function sanitize_local_file( $path, $field = null, $oldvalue = null ) {
		$path = self::sanitize_local_path( $path, $field, $oldvalue );
		if ( empty( $path ) ) {
			return;
		}
		if ( ! is_file( $path ) ) {
			if ( empty( $field['silent'] ) ) {
				w4os_admin_notice( sprintf( __( '%s is not a valid file.', 'w4os' ), "<code>$path</code>", ), 'error' );
			}
			return $oldvalue;
		}

		return $path;
	}

	public static function sanitize_local_dir( $path, $field = null, $oldvalue = null ) {
		$path = self::sanitize_local_path( $path, $field, $oldvalue );
		if ( empty( $path ) ) {
			return;
		}
		if ( ! is_dir( $path ) ) {
			if ( empty( $field['silent'] ) ) {
				w4os_admin_notice( sprintf( __( '%s is not a valid directory.', 'w4os' ), "<code>$path</code>", ), 'error' );
			}
			return $oldvalue;
		}

		return preg_replace( ':/*$:', '', $path );
	}

	public static function sanitize_binaries_dir( $path, $field = null, $oldvalue = null ) {
		$path = self::sanitize_local_dir( $path, null, $oldvalue );
		if ( empty( $path ) ) {
			return;
		}

		if ( ! is_file( "$path/OpenSim.exe" ) ) {
			$missing[] = 'OpenSim.exe';
		}
		if ( ! is_file( "$path/Robust.exe" ) ) {
			$missing[] = 'Robust.exe';
		}
		if ( ! empty( $missing ) ) {
			w4os_admin_notice( sprintf( __( '%1$s does not appear to be an OpenSim drectory (missing %2$s).', 'w4os' ), '<code>' . $path . '</code>', join( ', ', $missing ), ), 'error' );
			return $oldvalue;
		}
		return $path;
	}

	public static function sanitize_config_files( $fieldgroup, $field = null, $oldvalue = null ) {
		if ( ! is_array( $fieldgroup ) ) {
			return array();
		}

		foreach ( $fieldgroup as $option => $path ) {
			if ( ! empty( $path ) ) {
				$fallback = ( isset( $oldvalue[ $option ] ) ) ? $oldvalue[ $option ] : null;
				switch ( $option ) {
					case 'custom_config':
						break;

					case 'binaries':
						$fieldgroup[ $option ] = self::sanitize_binaries_dir( $path, '', $fallback );
						break;

					case 'opensim_d':
						$fieldgroup[ $option ] = self::sanitize_local_dir( $path, '', $fallback );
						break;

					default:
						$fieldgroup[ $option ] = self::sanitize_local_file( $path, '', $fallback );
				}
			}
		}

		if ( empty( $fieldgroup['opensimdefaults_ini'] ) & ! empty( $fieldgroup['opensim_ini'] ) ) {
			$fallback                          = ( isset( $oldvalue['opensimdefaults_ini'] ) ) ? $oldvalue['opensimdefaults_ini'] : null;
			$fieldgroup['opensimdefaults_ini'] = self::sanitize_local_file( dirname( $fieldgroup['opensim_ini'] ) . '/OpenSimDefaults.ini', '', $fallback );
		}

		if ( ! empty( $fieldgroup['binaries'] ) ) {
			$bin        = $fieldgroup['binaries'];
			$robust_ini = self::sanitize_local_file( "$bin/Robust.HG.ini", array( 'silent' => true ) );
			$robust_ini = ( empty( $robust_ini ) ) ? self::sanitize_local_file( "$bin/Robust.ini", array( 'silent' => true ) ) : $robust_ini;
			$fieldgroup = array_merge(
				$fieldgroup,
				array(
					'core_robust_ini'          => $robust_ini,
					'core_opensim_ini'         => self::sanitize_local_file( "$bin/OpenSim.ini" ),
					'core_opensimdefaults_ini' => self::sanitize_local_file( "$bin/OpenSimDefaults.ini" ),
				)
			);
			// $bindir = sanitize_local_dir($option['binaries']);
			// $options = array(
			// 'robust_'
			// )
		}

		return $fieldgroup;
	}

	public static function sanitize_login_uri( $uri, $field = null, $oldvalue = null ) {
		if ( empty( $uri ) ) {
			return;
		}
		$submitted = $uri;
		$uri       = preg_replace( '+.*://([^[/\?]]*)+', '$1', $uri );
		$uri       = preg_replace( '+[/\?].*+', '', $uri );
		if ( empty( 'http://' . sanitize_url( $uri ) ) ) {
			w4os_admin_notice( sprintf( __( '%s is not a valid login URI.', 'w4os' ), '<code>' . $uri . '</code>' ), 'error' );
			return $oldvalue;
		}
		return $uri;
	}

	public static function sanitize_grid_name( $value, $field = null, $oldvalue = null ) {
		$submitted = $value;
		$grid_info = W4OS::grid_info();
		$value     = ( isset( $grid_info['gridname'] ) ) ? $grid_info['gridname'] : null;
		error_log( __FUNCTION__ . "\n$submitted\n$value\n" . print_r( $grid_info, true ) );
		return $value;
	}
}

$this->loaders[] = new W4OS3_Settings();
