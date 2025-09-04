<?php
/**
 * Beta v3 settings class.
 *
 * Defines the general settings pages, not related to any specific feature.
 * Main menu is already defined in init and has the slug 'w4os'.
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

/**
 * Main settings class.
 *
 * Add main settings page and submenu. Use templates to display the page.
 */
class W4OS3_Settings {
	protected $settings = array();

	public function init() {
		// add_action( 'admin_menu', array( __CLASS__, 'build_settings' ) );
		add_action( 'admin_menu', array( $this, 'add_submenus' ) );
		add_action( 'admin_init', array( $this, 'register_settings' ) );

		add_filter( 'w4os_settings', array( $this, 'register_w4os_settings' ) );
		add_filter( 'w4os_settings', array( $this, 'register_w4os_settings_beta' ), 20 );
	}

	public function register_w4os_settings( $settings, $args = array(), $atts = array() ) {
		$settings['w4os-settings'] = array(
			'parent_slug'       => 'w4os',
			'page_title'        => __( 'Settings', 'w4os' ) . ' (dev)',
			'menu_title'        => '(dev) ' . __( 'Settings', 'w4os' ),
			'capability'        => 'manage_options',
			'menu_slug'         => 'w4os-settings',
			'sanitize_callback' => array( $this, 'sanitize_options' ),
			// 'tabs' => array(
			// 'pages' => array(
			// 'title' => __( 'Pages', 'w4os' ),
			// ),
			// )
		);
		return $settings;
	}

	public function register_w4os_settings_beta( $settings, $args = array(), $atts = array() ) {
		$settings['w4os-settings']['tabs']['beta'] = array(
			'title'    => __( 'Beta Features', 'w4os' ),
			'priority' => 9,
			'fields'   => array(
				'debug_html' => array(
					'label'       => __( 'Enable HTML debug', 'w4os' ),
					'type'        => 'switch',
					'description' => __( 'Warning: this might expose critical debug information on the front end.', 'w4os' ),
				),
			),
		);
		return $settings;
	}

	/**
	 * Get settings from the filter.
	 *
	 * @param string $menu_slug The menu slug to get settings for.
	 * @return array|bool The settings array or false if the menu slug is not found.
	 *
	 * array $settings {
	 *      @type string $menu_slug
	 *      @type string $page_title
	 *      @type string $menu_title
	 *      @type string $capability
	 *      @type string $menu_slug
	 *      @type string $option_name
	 *      @type string $option_group
	 *      @type string $page
	 *      @type array $tabs
	 * }
	 */
	public function get_settings( $menu_slug = '' ) {
		$settings = apply_filters( 'w4os_settings', array() );

		if ( ! empty( $menu_slug ) && isset( $settings[ $menu_slug ] ) ) {
			$settings[ $menu_slug ] = wp_parse_args(
				$settings[ $menu_slug ],
				array(
					'parent_slug'  => 'w4os',
					'page_title'   => esc_html( get_admin_page_title() ),
					'menu_title'   => esc_html( get_admin_page_title() ),
					'capability'   => 'manage_options',
					'menu_slug'    => 'w4os-settings',
					'option_name'  => $menu_slug,
					'option_group' => $menu_slug . '_group',
					'page'         => isset( $_GET['page'] ) ? esc_html( $_GET['page'] ) : $menu_slug,
				)
			);
			return $settings[ $menu_slug ] ?? false;
		}
		return $settings;
	}

	public function add_submenus() {
		$this->settings = self::get_settings();
		foreach ( $this->settings as $setting ) {
			$parent_slug = $setting['parent_slug'] ?? 'w4os';
			$menu_slug   = preg_match( '/^' . $parent_slug . '/', $parent_slug ) ? $setting['menu_slug'] : $parent_slug . '-' . $setting['menu_slug'];
			add_submenu_page(
				$setting['parent_slug'],
				$setting['page_title'],
				$setting['menu_title'],
				$setting['capability'] ?? 'manage_options',
				$setting['menu_slug'],
				$setting['callback'] ?? array( $this, 'render_settings_page' ),
				$setting['position'] ?? null,
			);
			// $this->register_settings( $menu_slug );
		}
	}

	public function register_settings() {
		if ( ! W4OS_ENABLE_V3 ) {
			return;
		}

		// All settings pages must be registered to be allowed by options.php
		$settings = self::get_settings();
		foreach ( $settings as $menu_slug => $setting ) {
			$page_settings = self::get_settings( $menu_slug );
			$page_settings = self::get_settings( $menu_slug );
			if ( ! $page_settings ) {
				error_log( 'No settings found for ' . $menu_slug );
				continue;
			}
			$option_name       = $page_settings['option_name'] ?? '';
			$option_group      = $page_settings['option_group'] ?? '';
			$sanitize_callback = $page_settings['sanitize_callback'] ?? array( __CLASS__, 'sanitize_options' );
			register_setting(
				$option_group,         // Option group
				$option_name,                    // Option name
				array(
					'type'              => 'array',
					'default'           => array(),
					'sanitize_callback' => $sanitize_callback,
					'option_name'       => $option_name,
				)
				// array( __CLASS__, 'sanitize_options' ),  // Sanitize callback
			);
		}

		// The settings page content however can be defined only for the current page/tab
		$page      = $_GET['page'] ?? 'w4os-settings';
		$menu_slug = sanitize_key( $page );

		$page_settings = self::get_settings( $page );
		$option_name   = $page_settings['option_name'] ?? '';
		$option_group  = $page_settings['option_group'] ?? '';

		if ( empty( $page_settings['tabs'] ) ) {
			// Handle single settings pages, without tabs
			// Not used for now, but might come in handy in the future
			$fields  = $page_settings['fields'] ?? array();
			$section = $option_group . '_section';
		} else {
			$selected_tab = $_GET['tab'] ?? array_key_first( $page_settings['tabs'] );
			if ( ! empty( $selected_tab && ! empty( $page_settings['tabs'][ $selected_tab ] ) ) ) {
				$section = $option_group . '_section_' . $selected_tab;
				$fields  = $page_settings['tabs'][ $selected_tab ]['fields'] ?? array();
			} else {
				error_log( 'Invalid tab ' . $selected_tab );
				return;
			}
		}

		if ( ! empty( $fields ) ) {
			// Adding main section
			add_settings_section(
				$section,
				null, // No title for the first section
				array( __CLASS__, 'section_callback' ),
				$page,
				array(),
			);

			foreach ( $fields as $field => $field_data ) {
				$field_data = wp_parse_args(
					$field_data,
					array(
						'id'          => $field,
						'type'        => 'text',
						'default'     => null,
						'description' => null,
						'option_name' => $option_name,
						'tab'         => $selected_tab,
					)
				);
				add_settings_field(
					$field,
					$field_data['label'],
					array( __CLASS__, 'render_settings_field' ),
					$option_name,
					$section,
					$field_data,
				);
			}
		}

		// TODO: process sections if any
	}

	public static function section_callback( $args = '' ) {
		// This is a placeholder for a section callback.
	}

	public static function sanitize_options( $input, $menu_slug = 'w4os-settings' ) {
		if ( ! $menu_slug ) {
			return $input;
		}

		$options = get_option( $menu_slug, array() );
		if ( ! is_array( $input ) ) {
			return $options;
		}

		if ( isset( $input['connections']['robust'] ) ) {
			// $credentials = $input['connections'];
			$creds = $input['connections']['robust'] ?? array();
			if ( ! empty( $creds ) ) {
				$creds['type'] = 'robust';
				$server_uri    = $creds['host'] . ':' . $creds['port'];
			}
			unset( $input['connections'] );
		} elseif ( isset( $input['edit']['sim_credentials'] ) ) {
			$creds         = $input['edit']['sim_credentials'];
			$creds['type'] = 'simulator';
			unset( $input['edit']['sim_credentials'] );
		}

		if ( ! empty( $creds ) ) {
			$server_uri = $creds['host'] . ':' . $creds['port'];

			W4OS3::update_credentials( $server_uri, $creds );
		}

		foreach ( $input as $key => $value ) {
			// We don't want to clutter the options with temporary check values
			if ( isset( $value['prevent-empty-array'] ) ) {
				unset( $value['prevent-empty-array'] );
			}
			$options[ $key ] = $value;
		}

		return $options;
	}

	public static function enqueue_select2() {
		// Enqueue Select2 assets
		wp_enqueue_style( 'select2-css', 'https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.13/css/select2.min.css', array(), '4.0.13' );
		wp_enqueue_script( 'select2-js', 'https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.13/js/select2.min.js', array( 'jquery' ), '4.0.13', true );

		W4OS3::enqueue_script( 'v3-admin-settings', 'v3/js/admin-settings.js' );
	}

	public static function get_tabs_html( $menu_slug = null, $default = 'default' ) {
		if ( empty( $menu_slug ) ) {
			$menu_slug = $_GET['page'];
		}
		$page_title   = esc_html( get_admin_page_title() );
		$option_group = $menu_slug . '_group';

		$settings     = apply_filters( 'w4os_settings', array() );
		$page_tabs    = $settings[ $menu_slug ]['tabs'] ?? array();
		$selected_tab = isset( $_GET['tab'] ) ? sanitize_text_field( $_GET['tab'] ) : array_key_first( $page_tabs );
		if ( isset( $_GET['action'] ) && $_GET['action'] == 'edit' ) {
			$page_tabs['edit'] = array(
				'title' => __( 'Edit', 'w4os' ),
				'url'   => '#',
			);
			$selected_tab      = 'edit';
		}
		$current_section = $option_group . '_section_' . $selected_tab;

		// $tabs            = apply_filters( 'w4os_settings_tabs', array() );
		// $page_tabs       = isset( $tabs[ $menu_slug ] ) ? $tabs[ $menu_slug ] : array();
		if ( count( $page_tabs ) <= 1 ) {
			// Not need for tabs navigation if there is only one tab.
			return;
		}
		$tabs_navigation = '';
		foreach ( $page_tabs as $tab => $tab_data ) {
			$url              = $tab_data['url'] ?? admin_url( 'admin.php?page=' . $menu_slug . '&tab=' . $tab );
			$title            = $tab_data['title'] ?? $tab;
			$tabs_navigation .= sprintf(
				'<a href="%s" class="nav-tab %s">%s</a>',
				esc_url( $url ),
				$selected_tab === $tab ? 'nav-tab-active' : '',
				esc_html( $title )
			);
		}
		if ( ! empty( $tabs_navigation ) ) {
			return sprintf(
				'<h2 class="nav-tab-wrapper">%s</h2>',
				$tabs_navigation
			);
		}
		return 'no tabs';
	}

	public function settings_error( $message, $type = 'error' ) {
		$page_title = esc_html( get_admin_page_title() );
		printf(
			'<h1>%s</h1>',
			$page_title,
		);
		w4os_admin_notice( $message, $type );
		do_action( 'admin_notices' );
	}

	public static function check_connections( $creds ) {
		$creds            = wp_parse_args(
			$creds,
			array(
				'status' => null,
				'error'  => null,
			),
		);
		$creds['db']      = wp_parse_args(
			$creds['db'],
			array(
				'status' => null,
				'error'  => null,
			),
		);
		$creds['console'] = wp_parse_args(
			$creds['console'],
			array(
				'status' => null,
				'error'  => null,
			),
		);

		if ( $creds['use_defaults'] ?? false ) {
			return $creds;
		}

		// Check url is online
		if ( empty( $creds['host'] ) || empty( $creds['port'] ) ) {
			$creds['status'] = null;
		} else {
			$creds['status'] = w4os_grid_online( $creds['host'] . ':' . $creds['port'] );
			if ( ! $creds['status'] ) {
				$creds['error'] = sprintf(
					__( 'Could not connect to %s', 'w4os' ),
					$creds['host'] . ':' . $creds['port']
				);
			}
		}

		// Check database credentials
		// TODO: replace with W4OS3::validate_db_credentials
		if ( ! empty( $creds['db']['host'] ) && ! empty( $creds['db']['user'] ) && ! empty( $creds['db']['pass'] && ! empty( $creds['db']['name'] ) ) ) {
			// Check for localhost with custom port configuration issue
			if ( $creds['db']['host'] === 'localhost' && ! empty( $creds['db']['port'] ) && $creds['db']['port'] != '3306' ) {
				$creds['db']['status'] = false;
				$creds['db']['error']  = __( 'Warning: MySQL uses a socket when set to localhost and ignores the custom port. Use 127.0.0.1 or the host address to allow TCP connections with a custom port.', 'w4os' );
			} else {
				@$db_conn = new mysqli( $creds['db']['host'], $creds['db']['user'], $creds['db']['pass'], $creds['db']['name'], $creds['db']['port'] ?: 3306 );
				if ( $db_conn && ! $db_conn->connect_error ) {
					$creds['db']['status'] = true;
					$db_conn->close();
				} else {
					$creds['db']['status'] = false;
					$creds['db']['error']  = $db_conn->connect_error ?? __( 'Unknown DB connection error', 'w4os' );
				}
			}
		} else {
			$creds['db']['status'] = null;
		}

		// Check console credentials
		// TODO: replace with W4OS3::validate_console_credentials
		if ( empty( $creds['console']['port'] ) || empty( $creds['console']['host'] ) || empty( $creds['console']['user'] ) || empty( $creds['console']['pass'] ) ) {
			$creds['console']['status'] = null;
		} else {
			$rest_args = array(
				'uri'         => $creds['console']['host'] . ':' . $creds['console']['port'],
				'ConsoleUser' => $creds['console']['user'],
				'ConsolePass' => $creds['console']['pass'],
			);
			$rest      = new OpenSim_Rest( $rest_args );
			if ( isset( $rest->error ) && is_opensim_rest_error( $rest->error ) ) {
				$creds['console']['error']  = $rest->error->getMessage();
				$creds['console']['status'] = false;
			} else {
				$responseLines = $rest->sendCommand( 'show info' );
				if ( is_opensim_rest_error( $responseLines ) ) {
					$creds['console']['error']  = $responseLines->getMessage();
					$creds['console']['status'] = false;
				} else {
					$creds['console']['status']   = true;
					$creds['console']['response'] = join( "\n", $responseLines );
				}
			}
		}

		return $creds;
	}

	/**
	 * Check if the current console configuration differs from stored credentials.
	 * This detects when configuration has changed after a server restart.
	 */
	public static function check_config_drift() {
		// Only check if V3 console mode is enabled
		if ( ! W4OS3::$console_enabled ) {
			return;
		}

		// Get current stored credentials
		$login_uri = get_option( 'w4os_login_uri' );
		if ( empty( $login_uri ) ) {
			return;
		}

		$compare_keys = array_flip( array( 'host', 'port', 'name', 'user', 'pass' ) );

		$stored_credentials = W4OS3::get_credentials( $login_uri );
		if ( empty( $stored_credentials ) || empty( $stored_credentials['console'] ) ) {
			return;
		}
		
		// Keep only relevant DB fields
		$stored_config = array_filter(array_intersect_key(
			$stored_credentials['db'] ?? array(),
			$compare_keys,
		));

		// Get current configuration from console
		try {
			$session = new W4OS3();
			$result  = $session->console( $stored_credentials['console'], 'config get DatabaseService ConnectionString' );
			
			if ( $result && is_array( $result ) ) {
				$result = array_shift( $result );
				$result = explode( ' : ', $result );
				$result = array_pop( $result );
				
				$live_config = connectionstring_to_array( $result );
				$live_config = array_filter(array_intersect_key( $live_config, $compare_keys ));

				$drift_detected = ( $live_config !== $stored_config );

				if( $drift_detected ) {
					$message = __( 'Current Robust instance database configuration differs from the W4OS stored settings.', 'w4os' );

					if ( $_GET['page'] ?? null === 'w4os-settings' ) {
						$message .= ' ' . __( 'Click Save Changes button to refresh W4OS configuration.', 'w4os' );
					} else {
						$message .= ' ' . sprintf( 
							__( 'Go to the %ssettings%s page to refresh configuration.', 'w4os' ),
							'<a href="' . admin_url( 'admin.php?page=w4os-settings' ) . '">',
							'</a>'
						);
					}

					// Cannot send admin notice here, as this method is called in contexts where admin_notices should not be displayed
				}

				return array(
					'success' => ! $drift_detected, 	 // v3 compatibility
					'message' => $message ?? null,		 // v3 compatibility
					'drift_detected' => $drift_detected, // v2 compatibility
					'stored_config' => $stored_config,	 // v2 compatibility
					'live_config' => $live_config,	 	 // v2 compatibility
				);
			}
		} catch ( Exception $e ) {
			// Silently fail - console connection issues shouldn't break the admin
			error_log( 'W4OS config drift check failed: ' . $e->getMessage() );
		}
	}

	public static function format_error( $error ) {
		if ( empty( $error ) ) {
			return '';
		}
		return sprintf(
			'<p class="notice error">%s</p>',
			$error
		);
	}

	/**
	 * This method is called by several classes defined in several scripts for several settings pages.
	 * It uses only the values provided by w4os_settings filter.
	 */
	public function render_settings_page() {
		$screen = get_current_screen();
		if ( ! $screen || ! isset( $screen->id ) ) {
			self::settings_error( __( 'This page is not available. You probably did nothing wrong, the developer did.', 'w4os' ), 'error' );
			return;
		}
		$menu_slug = preg_replace( '/^.*_page_/', '', sanitize_key( $screen->id ) );

		// Check for configuration drift on w4os-settings page
		if ( $menu_slug === 'w4os-settings' ) {
			self::check_config_drift();
		}

		$settings = self::get_settings( $menu_slug );
		if ( ! $settings ) {
			$this->settings_error( sprintf( __( 'No settings registered for %s.', 'w4os' ), $menu_slug ), 'error' );
			return;
		}

		$page_title    = $settings['page_title'];
		$page          = $settings['page'];
		$page_template = W4OS_TEMPLATES_DIR . 'admin-settings-page.php';
		// $all_tabs        = apply_filters( 'w4os_settings_tabs', array() );
		// $tabs            = isset( $all_tabs[ $page ] ) ? $all_tabs[ $page ] : array();
		$tabs = $settings['tabs'] ?? array();
		if ( ! empty( $tabs ) ) {
			$selected_tab = isset( $_GET['tab'] ) ? sanitize_text_field( $_GET['tab'] ) : array_key_first( $tabs );
		} else {
			$selected_tab = null;
		}

		// Main section, will be changed in the process if page contains more than one
		$section       = $menu_slug . ( $selected_tab ? '_' . $selected_tab : '' );
		$section_title = $settings['section_title'] ?? '';

		$option_name   = $settings['option_name'];
		$options_group = $settings['option_group'];

		self::enqueue_select2();

		if ( file_exists( $page_template ) ) {
			include $page_template;
		} else {
			self::settings_error( __( 'Template page missing.', 'w4os' ) );
			return;
		}
	}

	public static function render_settings_section( $args ) {
		if ( ! is_array( $args ) ) {
			return;
		}
		$args = wp_parse_args(
			$args,
			array(
				'id'          => null,
				'title'       => null,
				'description' => null,
			)
		);

		if ( $args['title'] ) {
			printf(
				'<h2>%s</h2>',
				$args['title'],
			);
		}
		if ( $args['description'] ) {
			printf(
				'<p class="description">%s</p>',
				$args['description'],
			);
		}
	}

	/**
	 * Render a settings field.
	 *
	 * This method should be agnostic, it will be moved in another class later and used by different settings pages.
	 */
	public static function render_settings_field( $args ) {
		if ( ! is_array( $args ) ) {
			return;
		}
		$args = wp_parse_args(
			$args,
			array(
				// 'id' => null,
				// 'label' => null,
				// 'label_for' => null,
				'type'        => 'text',
				// 'options' => [],
				// 'default' => null,
				'description' => null,
				'readonly'    => false,
				'disabled'    => false,
			// 'option_name' => null,
			// 'tab' => null, // Added tab
			)
		);

		// Retrieve options main name and $tab from args
		$prefs_name = isset( $args['option_name'] ) ? sanitize_key( $args['option_name'] ) : '';
		$tab        = isset( $args['tab'] ) ? sanitize_key( $args['tab'] ) : 'settings';

		// Construct the field name to match the options array structure
		$field_name = "{$prefs_name}[{$tab}][{$args['id']}]";

		$prefs = get_option( $prefs_name, array() );
		if ( isset( $args['value'] ) ) {
			$value = $args['value'];
		} elseif ( isset( $prefs[ $tab ][ $args['id'] ] ) ) {
			$value = $prefs[ $tab ][ $args['id'] ];
		} else {
			$value = isset( $args['default'] ) ? $args['default'] : '';
		}

		$readonly = ( $args['readonly'] ) ? 'readonly' : '';
		$disabled = ( $args['disabled'] ) ? 'disabled' : '';

		// if ( empty( $value ) && isset( $args['default'] ) ) {
		// $value = $args['default'];
		// }

		// Adjust field_name and value for multiple select fields
		if ( isset( $args['multiple'] ) && $args['multiple'] ) {
			$field_name .= '[]';
			if ( ! is_array( $value ) ) {
				$value = isset( $value ) ? array( $value ) : array();
			}
		}

		switch ( $args['type'] ) {
			case 'instance_credentials':
			case 'db_credentials':
				// Grouped fields for database credentials
				$login_uri    = get_option( 'w4os_login_uri', home_url() );
				$default_host = parse_url( $login_uri, PHP_URL_HOST ) ?? 'yourgrid.org';
				$creds        = WP_parse_args(
					$value,
					array(
						'type'         => ( $args['id'] == 'robust' ) ? 'robust' : null,
						'use_defaults' => ( $args['id'] == 'robust' ) ? false : ( $value['use_default'] ?? true ),
						'host'         => null,
						'port'         => 8002,
						'db'           => array(
							'type' => 'mysql',
							'host' => 'localhost',
							'port' => '3306',
							'name' => 'opensim',
							'user' => 'opensim',
							'pass' => null,
						),
						'console'      => array(
							'host' => $default_host,
							'port' => null,
							'user' => null,
							'pass' => null,
						),
					)
				);

				$creds = self::check_connections( $creds );

				$input_field = '';

				if ( $creds['type'] !== 'robust' ) {
					$input_field .= sprintf(
						'<div class="w4os-credentials  credentials-use-defaults">
							<label class="use-defaults">
								<input type="hidden" name="%2$s[use_defaults]" value="0" %5$s />
								<input type="checkbox" id="%1$s_use_defaults" name="%2$s[use_defaults]" class="use-defaults" value="1" %3$s %5$s />
								%4$s
							</label>
						</div>',
						esc_attr( $args['id'] ),
						$field_name,
						checked( $creds['use_defaults'], true, false ),
						esc_html__( 'Use defaults', 'w4os' ),
						( $readonly || $disabled ) ? 'disabled' : '',
					);
				}

				if ( $args['type'] !== 'db_credentials' ) {
					$input_field .= sprintf(
						'<div class="w4os-credentials  credentials-host">
							<label class="section-label">%2$s</label>
							<input type="text" id="%1$s-host" name="%3$s[host]" placeholder="%4$s" value="%5$s" %10$s />
							<input type="number" id="%1$s-port" name="%3$s[port]" placeholder="%6$s" value="%7$s" min="1" %10$s />
							%8$s %9$s
						</div>
						',
						esc_attr( $args['id'] ),
						esc_html__( 'Service URI', 'w4os' ),
						$field_name,
						esc_html__( 'Hostname', 'w4os' ),
						esc_attr( $creds['host'] ),
						esc_html__( 'Port', 'w4os' ),
						esc_attr( $creds['port'] ),
						w4os_status_icon( $creds['status'] ),
						self::format_error( $creds['error'] ),
						$readonly,
					);

					$input_field .= sprintf(
						'<div class="w4os-credentials  credentials-console">
						<label>%2$s</label>
						<input type="text" id="%1$s-console-host" name="%3$s[console][host]" value="%4$s" placeholder="%5$s" %14$s />
						<input type="number" id="%1$s-console-port" name="%3$s[console][port]" value="%6$s" min=1 placeholder="%7$s" style="width:5rem" %14$s />
						<input type="text" id="%1$s-console-user" name="%3$s[console][user]" value="%8$s" placeholder="%9$s" %14$s />
						<input type="password" id="%1$s-console-pass" name="%3$s[console][pass]" value="%10$s" placeholder="%11$s" %14$s />
						%12$s %13$s
						</div>',
						esc_attr( $args['id'] ),
						esc_html__( 'Console', 'w4os' ),
						$field_name,
						esc_attr( $creds['console']['host'] ),
						esc_html__( 'Hostname', 'w4os' ),
						esc_attr( $creds['console']['port'] ),
						esc_html__( 'Port', 'w4os' ),
						esc_attr( $creds['console']['user'] ),
						esc_html__( 'User', 'w4os' ),
						esc_attr( $creds['console']['pass'] ),
						esc_html__( 'Password', 'w4os' ),
						w4os_status_icon( $creds['console']['status'] ),
						self::format_error( $creds['console']['error'] ),
						$readonly,
					);
				}

				$dbreadonly = ( W4OS3::$console_enabled || ( isset( $creds['console']['enabled'] ) && $creds['console']['enabled'] ) ) ? 'readonly' : '';

				$input_field .= sprintf(
					'<div class="w4os-credentials  credentials-db">
						<label class="section-label">%2$s</label>
						<input type="text" id="%1$s-db-host" name="%3$s[db][host]" placeholder="%4$s" value="%5$s" %16$s />
						<input type="number" id="%1$s-db-port" name="%3$s[db][port]" placeholder="%6$s" value="%7$s" min="1" %16$s />
						<input type="text" id="%1$s-db-name" name="%3$s[db][name]" placeholder="%8$s" value="%9$s" %16$s />
						<input type="text" id="%1$s-db-user" name="%3$s[db][user]" placeholder="%10$s" value="%11$s" %16$s />
						<input type="password" id="%1$s-db-pass" name="%3$s[db][pass]" placeholder="%12$s" value="%13$s" %16$s />
						%14$s %15$s
					</div>',
					esc_attr( $args['id'] ),
					esc_html__( 'Database', 'w4os' ),
					$field_name,
					esc_html__( 'Hostname', 'w4os' ),
					esc_attr( $creds['db']['host'] ),
					esc_html__( 'Port', 'w4os' ),
					esc_attr( $creds['db']['port'] ),
					esc_html__( 'Database name', 'w4os' ),
					esc_attr( $creds['db']['name'] ),
					esc_html__( 'User', 'w4os' ),
					esc_attr( $creds['db']['user'] ),
					esc_html__( 'Password', 'w4os' ),
					esc_attr( $creds['db']['pass'] ),
					w4os_status_icon( $creds['db']['status'] ),
					self::format_error( $creds['db']['error'] ),
					$dbreadonly,
				);

				break;
			case 'button_group':
				$input_field = '';
				foreach ( $args['options'] as $option_value => $option_label ) {
					$input_field .= sprintf(
						'<label>
                            <input type="radio" id="%1$s_%2$s" name="%3$s" value="%2$s" %4$s %6$s />
                            %5$s
                        </label>',
						esc_attr( $args['id'] ),
						esc_attr( $option_value ),
						esc_attr( $field_name ),
						checked( $value, $option_value, false ),
						esc_html( $option_label ),
						$readonly,
					);
				}
				break;
			case 'select2':
			case 'select_advanced':
				$multiple_attr = $args['multiple'] ? 'multiple' : '';
				// Add a specific class for Select2 initialization
				$select_class = 'select2-field';
				$input_field  = sprintf(
					'<select id="%1$s" name="%2$s" class="%3$s" %4$s %6$s />
					<script> jQuery( function($){
						$( \'#%1$s\' ).select2( {
							width: \'100%%\',
							placeholder: \'%5$s\',
							allowClear: true,
						} );
					} );
					</script>',
					esc_attr( $args['id'] ),
					esc_attr( $field_name ),
					esc_attr( $select_class ),
					$multiple_attr,
					esc_html( $args['placeholder'] ),
					( $readonly || $disabled ) ? 'disabled' : '',
				);
				foreach ( $args['options'] as $option_value => $option_label ) {
					$selected     = ( is_array( $value ) && in_array( $option_value, $value ) ) ? 'selected' : '';
					$input_field .= sprintf(
						'<option value="%1$s" %2$s>%3$s</option>',
						esc_attr( $option_value ),
						$selected,
						esc_html( $option_label )
					);
				}
				$input_field .= '</select>';
				break;

			case 'page_select2':
				$input_field = sprintf(
					'<select id="%1$s" name="%2$s" class="select2-field" %4$s />
						<option value="">%3$s</option>',
					esc_attr( $args['id'] ),
					esc_attr( $field_name ),
					esc_html( $args['placeholder'] ),
					( $readonly || $disabled ) ? 'disabled' : '',
				);
				$pages       = self::get_pages();
				foreach ( $pages as $page_id => $page_title ) {
					$selected     = selected( $value, $page_id, false );
					$input_field .= sprintf(
						'<option value="%1$s" %2$s>%3$s</option>',
						esc_attr( $page_id ),
						$selected,
						esc_html( $page_title )
					);
				}
				$input_field .= '</select>';
				break;

			case 'page_select2_url':
				$pages = array_merge(
					$args['options'] ?? array(),
					self::get_pages_urls(),
				);
				// $value = is_array($value) ? $value['select'] : $value;

				// Create dropdown for existing pages
				$input_field = sprintf(
					'<select id="%1$s" name="%1s" class="select2-field select2-combined" %3$s />' .
						'<option value="" %3$s>%2$s</option>',
					esc_attr( $args['id'] ) . '_dropdown',
					esc_html__( 'Select a page', 'w4os' ),
					( $readonly || $disabled ) ? 'disabled' : '',
				);
				foreach ( $pages as $page_url => $page_title ) {
					// $selected = $value == $page_url ? 'selected' : '';
					$selected     = ( ( ! empty( $value['select'] ) && $value['select'] == $page_url ) || $value == $page_url ) ? 'selected' : '';
					$input_field .= sprintf(
						'<option value="%1$s" %2$s>%3$s</option>',
						esc_attr( $page_url ),
						$selected,
						esc_html( $page_title )
					);
				}
				$input_field .= '</select>';

				// Custom URL input
				$custom_url   = ! empty( $value['custom'] ) ? esc_attr( $value['custom'] ) : '';
				$input_field .= sprintf(
					' <input type="url" id="%1$s" name="%2$s" value="%3$s" placeholder="%4$s" class="regular-text" %5$s />',
					esc_attr( $args['id'] ),
					esc_attr( $field_name ),
					esc_attr( is_array( $value ) ? $value['select'] : $value ),
					esc_html__( 'Or enter a custom URL...', 'w4os' ),
					$readonly,
				);

				break;

			case 'switch':
			case 'checkbox':
			case 'checkboxes':
				if ( empty( $args['options'] ) ) {
					$args['options'] = array( '1' => __( 'Yes', 'w4os' ) );
				} elseif ( key( $args['options'] ) === 0 ) {
					$args['options'] = array_combine( range( 1, count( $args['options'] ) ), $args['options'] );
				}
				$fields = array();
				foreach ( $args['options'] as $option_value => $option_label ) {
					$fields[] = sprintf(
						'<label>
							<input type="checkbox" id="%1$s" name="%2$s" value="%3$s" %4$s %6$s />
							%5$s
						</label>',
						esc_attr( $args['id'] ),
						esc_attr( $field_name ),
						esc_attr( $option_value ),
						checked( $value, $option_value, false ),
						esc_html( $option_label ),
						$readonly,
					);
				}
				$input_field = join( '<br>', $fields );
				break;

			case 'custom_html':
				$input_field = sprintf(
					'<div id="%1$s" name="%1$s">%2$s</div>',
					esc_attr( $args['id'] ),
					$args['value'],
				);
				break;

			case 'text':
			default:
				$input_field = sprintf(
					'<input type="text" id="%1$s" name="%2$s" value="%3$s" class="regular-text" %4$s />',
					esc_attr( $args['id'] ),
					esc_attr( $field_name ),
					esc_attr( $value ),
					$readonly,
				);
		}

		echo $input_field;
		if ( ! empty( $args['description'] ) ) {
			printf(
				'<p class="description">%s</p>',
				$args['description'],
			);
		}
	}

	/**
	 * Get pages
	 */
	public static function get_pages() {
		$pages     = get_pages();
		$page_list = array();
		foreach ( $pages as $page ) {
			$page_list[ $page->ID ] = $page->post_title;
		}
		return $page_list;
	}

	/**
	 * Get pages urls
	 */
	public static function get_pages_urls() {
		$pages     = get_pages();
		$page_list = array();
		foreach ( $pages as $page ) {
			$url               = get_permalink( $page->ID );
			$page_list[ $url ] = $page->post_title;
		}
		return $page_list;
	}

	/**
	 * Get all pages containing a specific shortcode
	 *
	 * @param string $shortcode The shortcode to search for.
	 * @return array Array of WP_Post objects.
	 *
	 * @since 2.9.1
	 */
	public static function get_pages_with_shortcode( $shortcode ) {
		global $wpdb;
		$shortcode = esc_sql( $shortcode );
		$query     = "
        SELECT ID, post_title FROM {$wpdb->posts}
        WHERE post_type = 'page' 
        AND post_status = 'publish'
        AND post_content LIKE '%[" . $shortcode . "]%'
        ";
		$results   = $wpdb->get_results( $query );

		// Filter out translated pages if WPML is active
		if ( function_exists( 'icl_object_id' ) ) {
			$original_results = array();
			foreach ( $results as $page ) {
				$original_id = icl_object_id( $page->ID, 'page', true, wpml_get_default_language() );
				if ( $original_id == $page->ID ) {
					$original_results[] = $page;
				}
			}
			return $original_results;
		}

		return $results;
	}
}
