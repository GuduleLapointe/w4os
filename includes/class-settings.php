<?php if ( ! defined( 'W4OS_PLUGIN' ) ) {
	die;}

class W4OS_Settings extends W4OS_Loader {
	protected $login_uri;

	public function __construct() {
	}

	public function init() {
		$this->actions = array(
			array(
				'hook'     => 'init',
				'callback' => 'sanitize_options',
				// 'priority' => 1,
			),
			array(
				'hook'     => 'admin_menu',
				'callback' => 'register_admin_menu',
				'priority' => 5,
			),
			array(
				'hook'     => 'admin_bar_menu',
				'callback' => 'admin_bar_menus',
				'priority' => 20,
			),
			array(
				'hook'     => 'admin_menu',
				'callback' => 'register_admin_submenus',
				'priority' => 20,
			),
			// array(
			// 'hook' => 'init',
			// 'callback' => 'rewrite_rules',
			// ),
			// array(
			// 'hook' => 'admin_init',
			// 'callback' => 'register_permalinks_options',
			// ),
			// array(
			// 'hook' => 'template_include',
			// 'callback' => 'template_include',
			// ),
		);

		$this->filters = array(
			array(
				'hook'     => 'mb_settings_pages',
				'callback' => 'register_settings_pages',
				'priority' => 5,
			),
			array(
				'hook'     => 'rwmb_meta_boxes',
				'callback' => 'register_settings_fields',
				// 'priority' => 5,
			),
			array(
				'hook'          => 'rwmb_w4osdb_field_type_html',
				'callback'      => 'db_field_html',
				'accepted_args' => 3,
			),

			// array(
			// 'hook' => 'query_vars',
			// 'callback' => 'register_query_vars',
			// ),
		);
	}

	/**
	 * Add the "OpenSimulator" link in admin bar main menu.
	 *
	 * @since 2.5.1
	 * @param WP_Admin_Bar $wp_admin_bar Admin bar instance.
	 */
	public function admin_bar_menus( $wp_admin_bar ) {
		if ( current_user_can( 'manage_options' ) ) {
			$wp_admin_bar->add_node(
				array(
					'parent' => 'appearance',
					'id'     => 'w4os-menu',
					'title'  => __( 'OpenSimulator', 'w4os' ),
					'href'   => get_admin_url( '', 'admin.php?page=w4os' ),
					'meta'   => array(
						'title' => __( 'OpenSimulator Administration', 'w4os' ), // This title will show on hover
					),
				)
			);
		}
	}

	function register_admin_menu() {
		add_menu_page(
			'OpenSimulator', // page title
			'OpenSimulator', // menu title
			'manage_options', // capability
			'w4os', // slug
			'w4os_status_page', // callable function
			// plugin_dir_path(__FILE__) . 'options.php', // slug
			// null,	// callable function
			plugin_dir_url( W4OS_PLUGIN ) . 'images/opensimulator-logo-24x14.png', // icon url
			2 // position
		);
		add_submenu_page( 'w4os', __( 'OpenSimulator Status', 'w4os' ), __( 'Status', 'w4os' ), 'manage_options', 'w4os', 'w4os_status_page' );
	}

	function register_admin_submenus() {
		add_submenu_page( 'w4os', __( 'Available Shortcodes', 'w4os' ), __( 'Shortcodes', 'w4os' ), 'manage_options', 'w4os-shortcodes', array( $this, 'w4os_shortcodes_page' ) );
	}

	function w4os_shortcodes_page() {
		if ( ! current_user_can( 'manage_options' ) ) {
				return;
		}

		require_once W4OS_DIR . '/admin/templates/shortcodes.php';
	}

	function register_settings_pages( $settings_pages ) {
		$settings_pages[] = array(
			'menu_title' => __( 'Settings', 'w4os' ),
			'id'         => 'w4os_settings',
			'parent'     => 'w4os',
			'capability' => 'manage_options',
			'style'      => 'no-boxes',
			'icon_url'   => 'dashicons-admin-generic',
		);

		return $settings_pages;
	}

	function register_settings_fields( $meta_boxes ) {
		$prefix = 'w4os_';

		// Grid Info
		$meta_boxes[] = array(
			'title'          => __( 'Grid Info', 'w4os' ),
			'name'           => __( 'Grid Info', 'w4os' ),
			'desc'           => __( 'Grid Info', 'w4os' ),
			'description'    => __( 'Grid Info', 'w4os' ),
			'id'             => 'grid-info',
			'class'          => 'w4os-settings',
			'settings_pages' => array( 'w4os_settings' ),
			'fields'         => array(
				array(
					'name'        => __( 'Login URI', 'w4os' ),
					'id'          => $prefix . 'login_uri',
					'type'        => 'url',
					'std'         => w4os_grid_login_uri(),
					'required'    => true,
					'save_field'  => false,
					'placeholder' => 'http://yourgrid.org:8002',
				),
				array(
					'name'     => __( 'Grid Name', 'w4os' ),
					'id'       => $prefix . 'grid_name',
					'type'     => 'custom_html',
					'callback' => 'w4os_grid_name',
				),
				array(
					'name'       => __( 'Grid Status', 'w4os' ),
					'id'         => $prefix . 'grid_status',
					'type'       => 'custom_html',
					'callback'   => 'w4os_grid_status',
					'save_field' => false,
				),
			),
		);

		// Database
		$meta_boxes[] = array(
			'id'             => 'robust-db',
			'settings_pages' => array( 'w4os_settings' ),
			'class'          => 'w4os-settings',
			'visible'        => array(
				'when'     => array( array( 'w4os_login_uri', '!=', '' ) ),
				'relation' => 'or',
			),
			'fields'         => array(
				array(
					'id'   => $prefix . 'db_connected',
					'type' => 'hidden',
					'std'  => W4OS_DB_CONNECTED,
				),
				array(
					'name'       => __( 'ROBUST Database', 'w4os' ),
					'id'         => $prefix . 'robust-db',
					'type'       => 'w4osdb_field_type',
					'save_field' => false,
					'std'        => array(
						'is_main'     => true,
						'use_default' => false,
						'type'        => get_option( 'w4os_db_type', 'mysql' ),
						'port'        => get_option( 'w4os_db_port', 3306 ),
						'host'        => get_option( 'w4os_db_host', 'localhost' ),
						'database'    => get_option( 'w4os_db_database', 'robust' ),
						'user'        => get_option( 'w4os_db_user', 'opensim' ),
						'pass'        => get_option( 'w4os_db_pass' ),
					),
				),
			),
		);

		// Users
		$meta_boxes[] = array(
			'title'          => __( 'Grid Users', 'w4os' ),
			'id'             => 'grid-users',
			'settings_pages' => array( 'w4os_settings' ),
			'class'          => 'w4os-settings',
			'visible'        => array(
				'when'     => array( array( 'w4os_db_connected', '=', '1' ) ),
				'relation' => 'or',
			),
			'fields'         => array(
				array(
					'name'       => __( 'Profile Page', 'w4os' ),
					'id'         => $prefix . 'profile_page_options',
					'type'       => 'group',
					'save_field' => false,
					'fields'     => array(
						array(
							'name'       => __( 'URL', 'w4os' ),
							'id'         => 'url',
							'type'       => 'custom_html',
							'callback'   => 'W4OS_Profile::url',
							'class'      => get_page_by_path( W4OS_Profile::slug() ) ? '' : 'field-error',
							'desc'       => W4OS::sprintf_safe(
								preg_replace(
									'/\[(.*)\]/',
									'<a href="' . get_admin_url( '', 'options-permalink.php' ) . '">$1</a>',
									(
										get_page_by_path( W4OS_Profile::slug() )
										? __( 'A profile page exists with permalink set as %1$s, as defined in [permalinks settings].', 'w4os' )
										: __( 'A profile page must be created with permalink set as %1$s, as defined in [permalinks settings].', 'w4os' )
									),
								),
								'<code>' . W4OS_Profile::slug() . '</code>',
							),
							'save_field' => false,
						),
						array(
							'name'       => __( 'Public Profile Page', 'w4os' ),
							'id'         => 'provide',
							'type'       => 'switch',
							'desc'       => W4OS::sprintf_safe(
								__( 'Provide avatars with a public web profile page in the following format: %1$s.', 'w4os' ),
								'<code>' . W4OS_Profile::url( 'John', 'Smith' ) . '</code>',
							),
							'style'      => 'rounded',
							'std'        => empty( get_option( 'w4os_profile_page' ) ) ? 'provide' : ( get_option( 'w4os_profile_page' ) == 'provide' ),
							'save_field' => false,
						),
						array(
							'name'       => __( 'Login Page', 'w4os' ),
							'id'         => 'login_page',
							'type'       => 'switch',
							'desc'       => __( 'Use profile page as login page.', 'w4os' ),
							'style'      => 'rounded',
							'save_field' => false,
							'std'        => empty( get_option( 'w4os_login_page' ) ) ? 'profile' : ( get_option( 'w4os_login_page' ) == 'profile' ),
							'visible'    => array(
								'when'     => array( array( 'provide', '=', 1 ) ),
								'relation' => 'or',
							),
						),
						array(
							'name'       => __( 'Instructions', 'w4os' ),
							'id'         => 'instructions',
							'type'       => 'switch',
							'desc'       => __( 'Show configuration instructions to new users on their profile page.', 'w4os' ),
							'style'      => 'rounded',
							'std'        => w4os_option_exists( 'w4os_configuration_instructions' ) ? get_option( 'w4os_configuration_instructions' ) : true,
							'save_field' => false,
						),
					),
				),
				array(
					'name'       => __( 'Replace User Names', 'w4os' ),
					'id'         => $prefix . 'userlist_replace_name',
					'type'       => 'switch',
					'desc'       => __( 'Show avatar name instead of user name in users list.', 'w4os' ),
					'style'      => 'rounded',
					'std'        => w4os_option_exists( 'w4os_userlist_replace_name' ) ? get_option( 'w4os_userlist_replace_name' ) : true,
					'save_field' => false,
				),
				array(
					'name'       => __( 'Exclude from stats', 'w4os' ),
					'id'         => $prefix . 'exclude',
					'type'       => 'checkbox_list',
					'desc'       => __( 'Accounts without email address are usually test or technical accounts created from the console. Uncheck only if you have real avatars without email address.', 'w4os' ),
					'options'    => array(
						'models'    => __( 'Models', 'w4os' ),
						'nomail'    => __( 'Accounts without mail address', 'w4os' ),
						'hypergrid' => __( 'Hypergrid visitors', 'w4os' ),
					),
					'std'        => $this->get_exclude_options(),
					'inline'     => true,
					'save_field' => false,
				),
			),
		);

		$this->get_exclude_options();

		return $meta_boxes;
	}

	function get_exclude_options() {
		if ( w4os_option_exists( 'w4os_exclude' ) ) {
			return array_keys(
				array_filter(
					array(
						'models'    => get_option( 'w4os_exclude_models', false ),
						'nomail'    => get_option( 'w4os_exclude_nomail', false ),
						'hypergrid' => get_option( 'w4os_exclude_hypergrid', false ),
					)
				)
			);
		} else {
			return array( 'models', 'nomail' );
		}
	}

	function sanitize_options() {
		if ( empty( $_POST ) ) {
			return;
		}

		if ( isset( $_POST['nonce_grid-info'] ) && wp_verify_nonce( $_POST['nonce_grid-info'], 'rwmb-save-grid-info' ) ) {
			if ( isset( $_POST['w4os_login_uri'] ) ) {
				$login_uri = w4os_sanitize_login_uri( $_POST['w4os_login_uri'] );
				if ( empty( $login_uri ) ) {
					w4os_admin_notice( __( 'Invalid Login URI', 'w4os' ), 'error' );
				}
				update_option( 'w4os_login_uri', $login_uri );
			}
		}

		if ( isset( $_POST['nonce_robust-db'] ) && isset( $_POST['w4os_robust-db'] ) && wp_verify_nonce( $_POST['nonce_robust-db'], 'rwmb-save-robust-db' ) ) {
			global $w4osdb;
			$credentials                = array_map( 'esc_attr', $_POST['w4os_robust-db'] );
			$credentials['use_default'] = isset( $credentials['use_default'] ) ? $credentials['use_default'] : false;

			update_option( 'w4os_db_use_default', $credentials['use_default'] );
			if ( ! $credentials['use_default'] ) {
				update_option( 'w4os_db_host', $credentials['host'] );
				update_option( 'w4os_db_database', $credentials['database'] );
				update_option( 'w4os_db_user', $credentials['user'] );
				update_option( 'w4os_db_port', $credentials['port'] );
				update_option( 'w4os_db_pass', $credentials['pass'] );
			}
		}

		if ( isset( $_POST['nonce_grid-users'] ) && wp_verify_nonce( $_POST['nonce_grid-users'], 'rwmb-save-grid-users' ) ) {
			// update_option( 'w4os_profile_page', ( ( isset( $_POST['w4os_profile_page'] ) && $_POST['w4os_profile_page'] ) ? 'provide' : 'default' ) );

			$options = $_POST['w4os_profile_page_options'];

			update_option( 'w4os_profile_page', ( ( isset( $options['provide'] ) && $options['provide'] ) ? 'provide' : 'default' ) );
			update_option( 'w4os_configuration_instructions', ( isset( $options['instructions'] ) ? $options['instructions'] : false ) );
			update_option( 'w4os_login_page', ( ( isset( $options['login_page'] ) && $options['login_page'] ) ? 'profile' : 'default' ) );
			update_option( 'w4os_userlist_replace_name', ( isset( $_POST['w4os_userlist_replace_name'] ) ? $_POST['w4os_userlist_replace_name'] : false ) );

			$excludes = wp_parse_args(
				array_fill_keys( isset( $_POST['w4os_exclude'] ) ? $_POST['w4os_exclude'] : array(), 1 ),
				array(
					'models'    => false,
					'nomail'    => false,
					'hypergrid' => false,
				)
			);
			foreach ( $excludes as $key => $value ) {
				update_option( 'w4os_exclude_' . $key, $value );
			}
		}
	}

	private function db_fields( $values = array(), $field = array() ) {
		$use_default = isset( $values['use_default'] ) ? $values['use_default'] : true;
		$is_main     = isset( $values['is_main'] ) ? $values['is_main'] : false;
		$field       = array();
		if ( $is_main ) {
			$visible_condition = true;
			$use_default       = false;
		} else {
			$fields[]          =
			$visible_condition = array(
				'when'     => array( array( 'use_default', '!=', 1 ) ),
				'relation' => 'or',
			);
		}
		$fields = array(
			'use_default' => array(
				'name'     => __( 'Default', 'w4os' ),
				'id'       => 'use_default',
				'type'     => 'switch', // ($is_main) ? 'hidden' : 'switch',
				'disabled' => $is_main,
				// 'hidden' => $is_main,
				'style'    => 'rounded',
				'std'      => $use_default,
			),
			/**
			 * WPDB only supports MySQL, if we want to support other databases
			 * supported by OpenSimulator we will to migrate to PDO instead.
			 */
			// [
			// 'name'    => __( 'Type', 'w4os' ),
			// 'id'      => 'type',
			// 'type'    => 'select',
			// 'options' => [
			// 'mysql' => __( 'MySQL', 'w4os' ),
			// ],
			// 'std' => empty($values['type']) ? 'mysql' : $values['type'],
			// 'visible' => $visible_condition,
			// ],
			array(
				'name'    => __( 'Hostname', 'w4os' ),
				'id'      => 'host',
				'type'    => 'text',
				'std'     => empty( $values['host'] ) ? 'localhost' : $values['host'],
				'visible' => $visible_condition,
			),
			array(
				'name'    => __( 'Port', 'w4os' ),
				'id'      => 'port',
				'type'    => 'number',
				'std'     => empty( $values['port'] ) ? 3306 : $values['port'],
				'visible' => $visible_condition,
			),
			array(
				'name'    => __( 'DB Name', 'w4os' ),
				'id'      => 'database',
				'type'    => 'text',
				'std'     => empty( $values['database'] ) ? 'opensim' : $values['database'],
				'visible' => $visible_condition,
			),
			array(
				'name'    => __( 'Username', 'w4os' ),
				'id'      => 'user',
				'type'    => 'text',
				'std'     => empty( $values['user'] ) ? 'opensim' : $values['user'],
				'visible' => $visible_condition,
			),
			array(
				'name'    => __( 'Password', 'w4os' ),
				'id'      => 'pass',
				'type'    => 'password',
				'std'     => empty( $values['pass'] ) ? null : $values['pass'],
				'visible' => $visible_condition,
			),
		);
		if ( $is_main ) {
			unset( $fields['use_default'] );
		}
		return $fields;
	}

	public function render_field( $value, $field ) {
		// switch ($field['type']) {
		// }

		switch ( $field['type'] ) {
			case 'switch':
			case 'checkbox':
				// $type = 'checkbox';
				$checked = checked( $value, true, false );
				break;

			default:
				$checked = null;
		}

		$classes[]  = 'w4osdb-field';
		$label_args = '';
		$input_args = ( ! empty( $field['disabled'] ) ) ? 'disabled=' . $field['disabled'] : '';

		switch ( $field['type'] ) {
			case 'switch':
				$field['type'] = 'checkbox';
				$label_args    = 'class="rwmb-switch-label rwmb-switch-label--rounded"';
				$input         = '<input id="[field_id]" name="[field_name]" value="[value]" type="[field_type]" class="rwmb-switch" [input_args] />';
				$input        .= '<div class="rwmb-switch-status">
					<span class="rwmb-switch-slider"></span>
					<span class="rwmb-switch-on"></span>
					<span class="rwmb-switch-off"></span>
				</div>';
				$output        = '<div>[field_label]<br/><label [label_args]>' . $input . '</label></div>';
				break;

			case 'select':
				$input = '<select id="w4os_db_type" name="[field_name]" class="rwmb-select" data-selected="[value]">';
				foreach ( $field['options'] as $option_key => $option_name ) {
					$selected = selected( $option_key, $value, false );
					$input   .= w4os_replace(
						'<option value="[value]" [selected]>[label]</option>',
						array(
							'value'    => esc_attr( $option_key ),
							'selected' => $selected,
							'label'    => esc_attr( $option_name ),
						)
					);
				}
				$input .= '</select>';
				$output = '<label for="[field_id]" [label_args]>[field_label]' . $input . '</label>';

				break;

			default:
				$label_args = '';
				$input      = '<input type="[field_type]" name="[field_name]" id="[field_id]" value="[value]" [input_args] />';
				$output     = '<label for="[field_id]" [label_args]>[field_label]' . $input . '</label>';
		}
		$field_id   = ( empty( $field['parent_id'] ) ) ? $field['id'] : $field['parent_id'] . '_' . $field['id'];
		$field_name = ( empty( $field['parent_id'] ) ) ? $field['id'] : $field['parent_id'] . '[' . $field['id'] . ']';
		$classes[]  = 'db-field-' . $field['id'];
		$classes[]  = 'db-field-type-' . $field['type'];

		$output = '<div class="[classes]">' . $output . '</div>';
		$output = preg_replace( '/<input +/', '<input ' . $input_args . ' ', $output );

		$output = w4os_replace(
			$output,
			array(
				'field_id'    => $field_id,
				'field_name'  => $field_name,
				'field_label' => $field['name'],
				'field_type'  => $field['type'],
				'value'       => $value,
				'input_args'  => $checked,
				'label_args'  => $label_args,
				'classes'     => join( ' ', $classes ),
			)
		);

		return $output;
	}

	public function db_field_html( $html, $field = null, $values = array() ) {
		error_log( __METHOD__ . ' field = ' . print_r( $field, true ) . ' values = ' . print_r( $values, true ) );

		// Fix apparent change in RWMB behavior
		if ( isset( $field['std'] ) & ! is_array( $values ) ) {
			$values = $field['std'];
		}
		// Render the HTML output for the w4os db field type
		// Use $field and $meta to access field settings and saved values
		// $fields = array('use_default', 'type', 'host', 'port', 'database', 'user', 'pass');
		$subfields = $this->db_fields( $values );

		$output = '';
		foreach ( $subfields as $subfield ) {
			$subfield['parent_id'] = $field['id'];
			$output               .= $this->render_field(
				( isset( $values[ $subfield['id'] ] ) ) ? $values[ $subfield['id'] ] : null,
				$subfield
			);
		}
		if ( ! empty( $output ) ) {
			$output = '<div class="w4osdb-field-group w4osdb-field-input">' . $output . '</div>';
		}
		return $output;
	}

	// public static function db_field_value( $new, $field, $old, $object_id ) {
	// Save the custom field value
	// $new: New field value
	// $old: Old field value
	// $post_id: Current post ID
	// $field: Field settings
	// return $new;
	// }
}

$this->loaders[] = new W4OS_Settings();
