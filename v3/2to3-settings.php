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
	}

	public function register_w4os_settings( $settings, $args = array(), $atts = array() ) {
		$settings['w4os-settings'] = array(
			'parent_slug' => 'w4os',
			'page_title'  => __( 'Settings', 'w4os' ) . ' (dev)',
			'menu_title'  => '(dev) ' . __( 'Settings', 'w4os' ),
			'capability'  => 'manage_options',
			'menu_slug'   => 'w4os-settings',
			'tabs' 		  => array(
				'beta' => array(
					'title'  => __( 'Beta Features', 'w4os' ),
					'fields' => array(
						'debug_html' => array(
							'label' => __( 'Enable HTML debug', 'w4os' ),
							'type'  => 'switch',
							'description' => __( 'Warning: this might expose critical debug information on the front end.', 'w4os' ),
						),
					),
				),
			)
			// 'callback'    => array( $this, 'render_settings_page' ),
			// 'position'    => 90,
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
	 * 		@type string $menu_slug
	 *		@type string $page_title
	 *		@type string $menu_title
	 *		@type string $capability
	 *		@type string $menu_slug
	 *		@type string $option_name
	 *		@type string $option_group
	 *		@type string $page
	 *		@type array $tabs
	 * }
	 */
	public function get_settings( $menu_slug = '' ) {
		$settings = apply_filters( 'w4os_settings', array() );
		
		if ( ! empty( $menu_slug ) && isset( $settings[ $menu_slug ] ) ) {
			$settings[ $menu_slug ] = wp_parse_args(
				$settings[ $menu_slug ],
				array(
					'parent_slug' => 'w4os',
					'page_title'  => esc_html( get_admin_page_title() ),
					'menu_title'  => esc_html( get_admin_page_title() ),
					'capability'  => 'manage_options',
					'menu_slug'   => 'w4os-settings',
					'option_name' => $menu_slug,
					'option_group' => $menu_slug . '_group',
					'page'		=> isset( $_GET['page'] ) ? esc_html( $_GET['page'] ) : $menu_slug,
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
			$menu_slug = preg_match( '/^' . $parent_slug . '/', $parent_slug ) ? $setting['menu_slug'] : $parent_slug . '-' . $setting['menu_slug'];
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
		foreach( $settings as $menu_slug => $setting ) {
			$page_settings = self::get_settings( $menu_slug );
			$page_settings = self::get_settings( $menu_slug );
			if (! $page_settings ) {
				error_log('No settings found for ' . $menu_slug);
				continue;
			}
			$option_name = $page_settings['option_name'] ?? '';
			$option_group = $page_settings['option_group'] ?? '';
			$sanitize_callback = $page_settings['sanitize_callback'] ?? array( __CLASS__, 'sanitize_options' );
			register_setting(
				$option_group,         // Option group
				$option_name,                    // Option name
				array(
					'type'              => 'array',
					'default'           => array(),
					'sanitize_callback' => $sanitize_callback,
					'option_name' => $option_name,
				)
				// array( __CLASS__, 'sanitize_options' ),  // Sanitize callback
			);
		}

		// The settings page content however can be defined only for the current page/tab
		$page = $_GET['page'] ?? 'w4os-settings';
		$menu_slug = sanitize_key( $page );

		$page_settings = self::get_settings( $page );
		$option_name = $page_settings['option_name'] ?? '';
		$option_group = $page_settings['option_group'] ?? '';

		if ( empty( $page_settings['tabs'] ) ) {
			// Handle single settings pages, without tabs
			// Not used for now, but might come in handy in the future
			$fields = $page_settings['fields'] ?? array();
			$section = $option_group . '_section';
		} else {
			$selected_tab = $_GET['tab'] ?? array_key_first( $page_settings['tabs'] );
			if ( ! empty( $selected_tab && ! empty( $page_settings['tabs'][ $selected_tab ] ) ) ) {
				$section = $option_group . '_section_' . $selected_tab;
				$fields = $page_settings['tabs'][ $selected_tab ]['fields'] ?? array();
			} else {
				error_log('Invalid tab ' . $selected_tab);
				return;
			}
		}

		if( ! empty( $fields ) ) {
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
						'id' 		  => $field,
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

	public static function sanitize_options( $input, $menu_slug = false ) {
		if ( ! $menu_slug ) {
			return $input;
		}

		$options = get_option( $menu_slug, array() );
		if ( ! is_array( $input ) ) {
			return $options;
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
	}

	// public static function enable_v3_features_callback() {
	// 	$args = func_get_args();

	// 	$value = W4OS3::get_option( 'w4os-enable-v3-beta' );
	// 	printf(
	// 		'<label>
    //         <input type="checkbox" name="w4os_settings[w4os-enable-v3-beta]" value="1" %s />%s</label>',
	// 		checked( 1, $value, false ),
	// 		__( 'Enable beta v3 features', 'w4os' ),
	// 	);
	// 	echo '<p class="description">' . __( 'Warning: These features are in beta and may not be stable.', 'w4os' ) . '</p>';
	// }

	// public static function debug_callback() {
	// 	$value = W4OS3::get_option( 'debug_html' );
	// 	printf(
	// 		'<label>
    //         <input type="checkbox" name="w4os_settings[debug_html]" value="1" %s />%s</label>',
	// 		checked( 1, $value, false ),
	// 		__( 'Enable HTML debug', 'w4os' ),
	// 	);
	// 	echo '<p class="description">' . __( 'Warning: This will display critical debug information on the front end.', 'w4os' ) . '</p>';
	// }

	public static function get_tabs_html( $menu_slug = null, $default = 'default' ) {
		if ( empty( $menu_slug ) ) {
			$menu_slug = $_GET['page'];
		}
		$page_title      = esc_html( get_admin_page_title() );
		$option_group    = $menu_slug . '_group';
		
		$settings = apply_filters( 'w4os_settings', array() );
		$page_tabs = $settings[$menu_slug]['tabs'] ?? array();
		$selected_tab     = isset( $_GET['tab'] ) ? sanitize_text_field( $_GET['tab'] ) : array_key_first( $page_tabs );
		$current_section = $option_group . '_section_' . $selected_tab;
		
		// $tabs            = apply_filters( 'w4os_settings_tabs', array() );
		// $page_tabs       = isset( $tabs[ $menu_slug ] ) ? $tabs[ $menu_slug ] : array();
		if( count( $page_tabs ) <= 1 ) {
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
		$menu_slug    = preg_replace( '/^.*_page_/', '', sanitize_key( $screen->id ) );

		$settings = self::get_settings( $menu_slug );
		if ( ! $settings ) {
			$this->settings_error( sprintf( __( 'No settings registered for %s.', 'w4os' ), $menu_slug ), 'error' );
			return;
		}

		$page_title      = $settings['page_title'];
		$page 		  	 = $settings['page'];
		$page_template   = W4OS_TEMPLATES_DIR . 'admin-settings-page.php';
		// $all_tabs        = apply_filters( 'w4os_settings_tabs', array() );
		// $tabs            = isset( $all_tabs[ $page ] ) ? $all_tabs[ $page ] : array();
		$tabs = $settings['tabs'] ?? array();
		if( ! empty( $tabs ) ) {
			$selected_tab = isset( $_GET['tab'] ) ? sanitize_text_field( $_GET['tab'] ) : array_key_first( $tabs );
		} else {
			$selected_tab = null;
		}

		// Main section, will be changed in the process if page contains more than one
		$section = $menu_slug . ( $selected_tab ? '_' . $selected_tab : '' );
		$section_title = $settings['section_title'] ?? '';

		$option_name = $settings['option_name'];
		$options_group = $settings['option_group'];
		
		W4OS3_Settings::enqueue_select2();

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
			// 'option_name' => null,
			// 'tab' => null, // Added tab
			)
		);

		// Retrieve $option_name and $tab from args
		$option_name = isset( $args['option_name'] ) ? sanitize_key( $args['option_name'] ) : '';
		$tab         = isset( $args['tab'] ) ? sanitize_key( $args['tab'] ) : 'settings';

		// Construct the field name to match the options array structure
		$field_name = "{$option_name}[{$tab}][{$args['id']}]";
		$option     = get_option( $option_name, array() );
		$value      = isset( $option[ $tab ][ $args['id'] ] ) ? $option[ $tab ][ $args['id'] ] : '';
		if ( empty( $value ) && isset( $args['default'] ) ) {
			$value = $args['default'];
		}

		// Adjust field_name and value for multiple select fields
		if ( isset( $args['multiple'] ) && $args['multiple'] ) {
			$field_name .= '[]';
			if ( ! is_array( $value ) ) {
				$value = isset( $value ) ? array( $value ) : array();
			}
		}

		switch ( $args['type'] ) {
			case 'db_credentials':
				// Grouped fields for database credentials
				$creds       = WP_parse_args(
					$value,
					array(
						'user'     => null,
						'pass'     => null,
						'database' => null,
						'host'     => null,
						'port'     => null,
					)
				);
				$input_field = sprintf(
					'<label for="%1$s_user">%2$s</label>
					<input type="text" id="%1$s_user" name="%3$s[user]" value="%4$s" />
					<label for="%1$s_pass">%5$s</label>
					<input type="password" id="%1$s_pass" name="%3$s[pass]" value="%6$s" />
					<label for="%1$s_database">%7$s</label>
					<input type="text" id="%1$s_database" name="%3$s[database]" value="%8$s" />
					<label for="%1$s_host">%9$s</label>
					<input type="text" id="%1$s_host" name="%3$s[host]" value="%10$s" />
					<label for="%1$s_port">%11$s</label>
					<input type="text" id="%1$s_port" name="%3$s[port]" value="%12$s" />',
					esc_attr( $args['id'] ),
					esc_html__( 'User', 'w4os' ),
					esc_attr( $field_name ),
					esc_attr( $creds['user'] ),
					esc_html__( 'Password', 'w4os' ),
					esc_attr( $creds['pass'] ),
					esc_html__( 'Database', 'w4os' ),
					esc_attr( $creds['database'] ),
					esc_html__( 'Host', 'w4os' ),
					esc_attr( $creds['host'] ),
					esc_html__( 'Port', 'w4os' ),
					esc_attr( $creds['port'] )
				);
				break;
			case 'button_group':
				$input_field = '';
				foreach ( $args['options'] as $option_value => $option_label ) {
					$input_field .= sprintf(
						'<label>
                            <input type="radio" id="%1$s_%2$s" name="%3$s" value="%2$s" %4$s />
                            %5$s
                        </label>',
						esc_attr( $args['id'] ),
						esc_attr( $option_value ),
						esc_attr( $field_name ),
						checked( $value, $option_value, false ),
						esc_html( $option_label )
					);
				}
				break;
			case 'select2':
			case 'select_advanced':
				$multiple_attr = $args['multiple'] ? 'multiple' : '';
				// Add a specific class for Select2 initialization
				$select_class = 'select2-field';
				$input_field = sprintf(
					'<select id="%1$s" name="%2$s" class="%3$s" %4$s>
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
				);
				foreach ( $args['options'] as $option_value => $option_label ) {
					$selected = ( is_array( $value ) && in_array( $option_value, $value ) ) ? 'selected' : '';
					$input_field .= sprintf(
						'<option value="%1$s" %2$s>%3$s</option>',
						esc_attr( $option_value ),
						$selected,
						esc_html( $option_label )
					);
				}
				$input_field .= '</select>';
				break;

			case 'switch':
			case 'checkbox':
				$option_label = isset($args['options']) && is_array($args['options']) ? array_values( $args['options'] )[0] : __('Yes', 'w4os');
				$input_field = sprintf(
					'<label>
                        <input type="checkbox" id="%1$s" name="%2$s" value="1" %3$s />
                        %4$s
                    </label>',
					esc_attr( $args['id'] ),
					esc_attr( $field_name ),
					checked( $value, '1', false ),
					esc_html( $option_label ),
				);
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
					'<input type="text" id="%1$s" name="%2$s" value="%3$s" />',
					esc_attr( $args['id'] ),
					esc_attr( $field_name ),
					esc_attr( $value )
				);
		}

		echo $input_field;
		if ( ! empty( $args['description'] ) ) {
			printf(
				'<p class="description">%s</p>',
				esc_html( $args['description'] )
			);
		}
	}
}
