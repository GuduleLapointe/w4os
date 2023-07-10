<?php if ( ! defined( 'W4OS_PLUGIN' ) ) {
	die;}

class W4OS_Settings extends W4OS_Loader {
	protected $login_uri;

	public function __construct() {
	}

	public function init() {
		$this->actions = array(
			array(
				'hook' => 'init',
				'callback' => 'sanitize_options',
				// 'priority' => 1,
			),
			array(
				'hook' => 'admin_menu',
				'callback' => 'register_admin_menu',
				'priority' => 5,
			),
			// array(
			// 	'hook' => 'init',
			// 	'callback' => 'rewrite_rules',
			// ),
			// array(
			// 	'hook' => 'admin_init',
			// 	'callback' => 'register_permalinks_options',
			// ),
			// array(
			// 	'hook' => 'template_include',
			// 	'callback' => 'template_include',
			// ),
		);

		$this->filters = array(
			array(
				'hook' => 'mb_settings_pages',
				'callback' => 'register_settings_pages',
				'priority' => 5,
			),
			array(
				'hook' => 'rwmb_meta_boxes',
				'callback' => 'register_settings_fields',
				// 'priority' => 5,
			),
			array(
				'hook' => 'rwmb_w4osdb_field_type_html',
				'callback' => 'db_field_html',
				'accepted_args' => 3,
			),
			array(
				'hook' => 'rwmb_w4osdb_field_type_value',
				'callback' => 'db_field_value',
				'accepted_args' => 4,
			),

			// array(
			// 	'hook' => 'query_vars',
			// 	'callback' => 'register_query_vars',
			// ),
		);
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
		add_submenu_page( 'w4os', __( 'OpenSimulator Status', 'w4os' ), __( 'Status' ), 'manage_options', 'w4os', 'w4os_status_page' );
	}

	function register_settings_pages( $settings_pages ) {
		$settings_pages[] = [
			'menu_title' => __( 'Settings', 'w4os' ),
			'id'         => 'w4os_settings',
			'position'   => 0,
			'parent'     => 'w4os',
			'capability' => 'manage_options',
			'style'      => 'no-boxes',
			'icon_url'   => 'dashicons-admin-generic',
		];

		return $settings_pages;
	}

	function register_settings_fields( $meta_boxes ) {
		$prefix = 'w4os_';

		$meta_boxes[] = [
			'title'          => __( 'Grid Info', 'w4os' ),
			'name'          => __( 'Grid Info', 'w4os' ),
			'desc'          => __( 'Grid Info', 'w4os' ),
			'description'          => __( 'Grid Info', 'w4os' ),
			'id'             => 'grid-info',
			'settings_pages' => ['w4os_settings'],
			'fields'         => [
				[
						// 'name'       => ,
						'id'         => $prefix . 'grid_info_section',
						'type'       => 'custom_html',
						'std'        => '<h2>' . __( 'Grid Info', 'w4os' ) . '</h2>',
						'std'        => __( 'Grid Info', 'w4os' ),
						'save_field' => false,
				],
				[
					'name'       => __( 'Login URI', 'w4os' ),
					'id'         => $prefix . 'login_uri',
					'type'       => 'url',
					'std'        => w4os_grid_login_uri(),
					'required'   => true,
					'save_field' => false,
					'placeholder' => 'http://yourgrid.org:8002',
				],
				[
						'name'     => __( 'Grid Name', 'w4os' ),
						'id'       => $prefix . 'grid_name',
						'type'     => 'custom_html',
						'callback' => 'w4os_grid_name',
				],
				[
						'name'       => __( 'Grid Status', 'w4os' ),
						'id'         => $prefix . 'grid_status',
						'type'       => 'custom_html',
						'callback'   => 'w4os_grid_status',
						'save_field' => false,
				],
			],
		];

		$meta_boxes[] = [
			'id'             => 'robust-db',
			'settings_pages' => ['w4os_settings'],
			'visible'        => [
				'when'     => [['w4os_login_uri', '!=', '']],
				'relation' => 'or',
			],
			'fields'         => [
				// [
				// 	'name'   => __( 'Database', 'w4os' ),
				// 	'id'     => $prefix . 'db',
				// 	'type'   => 'group',
				// 	'class'  => 'inline',
				// 	'save_field' => false,
				// 	'fields' => $this->db_fields(array(
				// 		'is_main' => true,
				// 		'host' => get_option( 'w4os_db_host' ),
				// 		'database' => get_option( 'w4os_db_database' ),
				// 		'user' => get_option( 'w4os_db_user' ),
				// 		'pass' => get_option( 'w4os_db_pass' ),
				// 	)),
				// ],
				array(
	        'name' => __('ROBUST Database', 'w4os'),
	        'id' => $prefix . 'maindb',
	        'type' => 'w4osdb_field_type',
					'save_field' => false,
					'std' => array(
						'is_main' => true,
						'use_default' => false,
						'type' => get_option( 'w4os_db_type', 'mysql' ),
						'port' => get_option( 'w4os_db_port', 3306 ),
						'host' => get_option( 'w4os_db_host', 'localhost' ),
						'database' => get_option( 'w4os_db_database', 'robust' ),
						'user' => get_option( 'w4os_db_user', 'opensim' ),
						'pass' => get_option( 'w4os_db_pass' ),
					),
	      ),
				// array(
	      //   'name' => __('Search Database', 'w4os'),
	      //   'id' => $prefix . 'searchdb',
	      //   'type' => 'w4osdb_field_type',
				// 	'save_field' => false,
				// 	'std' => array(
				// 		'use_default' => true,
				// 		'type' => get_option( 'w4os_search_db_type', 'mysql' ),
				// 		'port' => get_option( 'w4os_search_db_port', 3306 ),
				// 		'host' => get_option( 'w4os_search_db_host', 'zlocalhost' ),
				// 		'database' => get_option( 'w4os_search_db_database', 'zossearch' ),
				// 		'user' => get_option( 'w4os_search_db_user', 'zopensim' ),
				// 		'pass' => get_option( 'w4os_search_db_pass' ),
				// 	),
	      // ),
			],
		];

		return $meta_boxes;
	}

	function sanitize_options() {
		if (empty($_POST)) return;

		if( isset($_POST['nonce_grid-info']) && wp_verify_nonce( $_POST['nonce_grid-info'], 'rwmb-save-grid-info' ) ) {
			if(isset($_POST['w4os_login_uri'])) {
				$login_uri = w4os_sanitize_login_uri($_POST['w4os_login_uri']);
				if(empty($login_uri)) {
					w4os_admin_notice(__('Invalid Login URI', 'w4os'), 'error');
				}
				update_option('w4os_login_uri', $login_uri);
			}
		}

		if( isset($_POST['nonce_robust-db']) && wp_verify_nonce( $_POST['nonce_robust-db'], 'rwmb-save-robust-db' ) ) {
			// error_log(print_r($_POST, true));
		}
	}

	private function db_fields( $values = [], $field=[] ) {
		$use_default = isset($values['use_default']) ? $values['use_default'] : true;
		$is_main = isset($values['is_main']) ? $values['is_main'] : false;
		$field = [];
		if($is_main) {
			$visible_condition = true;
			$use_default = false;
		} else {
			$fields[] =
			$visible_condition = [
				'when'     => [['use_default', '!=', 1]],
				'relation' => 'or',
			];
		}
		$fields = [
			'use_default' => [
					'name'  => __( 'Default', 'w4os' ),
					'id'    => 'use_default',
					'type'  => 'switch', // ($is_main) ? 'hidden' : 'switch',
					'disabled' => $is_main,
					// 'hidden' => $is_main,
					'style' => 'rounded',
					'std' => $use_default,
			],
			[
				'name'    => __( 'Type', 'w4os' ),
				'id'      => 'type',
				'type'    => 'select',
				'options' => [
					'mysql' => __( 'MySQL', 'w4os' ),
				],
				'std' => empty($values['type']) ? 'mysql' : $values['type'],
				'visible' => $visible_condition,
			],
			[
				'name' => __( 'Hostname', 'w4os' ),
				'id'   => 'host',
				'type' => 'text',
				'std' => empty($values['host']) ? 'localhost' : $values['host'],
				'visible' => $visible_condition,
			],
			[
				'name' => __( 'Port', 'w4os' ),
				'id'   => 'port',
				'type' => 'number',
				'std' => empty($values['port']) ? 3306 : $values['port'],
				'visible' => $visible_condition,
			],
			[
				'name' => __( 'DB Name', 'w4os' ),
				'id'   => 'database',
				'type' => 'text',
				'std' => empty($values['database']) ? 'opensim' : $values['database'],
				'visible' => $visible_condition,
			],
			[
				'name' => __( 'Username', 'w4os' ),
				'id'   => 'user',
				'type' => 'text',
				'std' => empty($values['user']) ? 'opensim' : $values['user'],
				'visible' => $visible_condition,
			],
			[
				'name' => __( 'Password', 'w4os' ),
				'id'   => 'pass',
				'type' => 'password',
				'std' => empty($values['pass']) ? null : $values['pass'],
				'visible' => $visible_condition,
			],
		];
		if($is_main) {
			unset($fields['use_default']);
		}
		return $fields;
	}

	public function render_field($value, $field ) {
		// switch ($field['type']) {
		// }

		switch($field['type']) {
			case 'switch':
			case 'checkbox':
			// $type = 'checkbox';
			$checked = checked($value, true, false);
			break;

			default:
			$checked = null;
		}

		$classes[]='w4osdb-field';
		$label_args = '';
		$input_args = ( ! empty($field['disabled'])) ? 'disabled=' . $field['disabled'] : '';

		switch($field['type']) {
			case 'switch':
				$field['type'] = 'checkbox';
				$label_args = 'class="rwmb-switch-label rwmb-switch-label--rounded"';
				$input = '<input id="[field_id]" value="[value]" type="[field_type]" class="rwmb-switch" name="[field_id]" [input_args] />';
				$input .= '<div class="rwmb-switch-status">
					<span class="rwmb-switch-slider"></span>
					<span class="rwmb-switch-on"></span>
					<span class="rwmb-switch-off"></span>
				</div>';
				$output = '<div>[field_label]<br/><label [label_args]>' . $input . '</label></div>';
			break;

			case 'select':
			$input = '<select id="w4os_db_type" class="rwmb-select" name="[field_id]" data-selected="[value]">';
			foreach($field['options'] as $option_key => $option_name) {
				$selected = selected($option_key, $value, false);
				$input .= w4os_replace( '<option value="[value]" [selected]>[label]</option>', array(
					'value' => esc_attr($option_key),
					'selected' => $selected,
					'label' => esc_attr($option_name),
				));
			}
			$input .= '</select>';
			$output = '<label for="[field_id]" [label_args]>[field_label]' . $input . '</label>';

			break;

			default:
			$label_args = '';
			$input = '<input type="[field_type]" name="[field_id]" id="[field_id]" value="[value]" [input_args] />';
			$output = '<label for="[field_id]" [label_args]>[field_label]' . $input . '</label>';
		}
		$field_id = (empty($field['parent_id'])) ? $field['id'] : $field['parent_id'] . '[' . $field['id'] . ']';
		$classes[]='db-field-' . $field['id'];
		$classes[]='db-field-type-' . $field['type'];

		$output = '<div class="[classes]">' . $output . '</div>';
		$output = preg_replace('/<input +/', '<input ' . $input_args . ' ', $output);

		$output = w4os_replace($output, array(
			'field_id' => $field_id,
			'field_label' => $field['name'],
			'field_type' => $field['type'],
			'value' => $value,
			'input_args' => $checked,
			'label_args' => $label_args,
			'classes' => join(' ', $classes),
		));

		return $output;
	}

	public function db_field_html( $html, $field = null, $values = [] ) {
		// Render the HTML output for the w4os db field type
		// Use $field and $meta to access field settings and saved values
		// $fields = array('use_default', 'type', 'host', 'port', 'database', 'user', 'pass');
		$subfields = $this->db_fields($values);

		$output = '';
		foreach($subfields as $subfield) {
			$subfield['parent_id'] = $field['id'];
			$output .= $this->render_field(
				(isset($values[$subfield['id']])) ? $values[$subfield['id']] : null,
				$subfield
			);
		}
		return $output;
	}

	public static function db_field_value( $new, $field, $old, $object_id ) {
		error_log(__CLASS__ . '::' . __METHOD__ . ' ' . print_r($new, true));
		// Save the custom field value
		// $new: New field value
		// $old: Old field value
		// $post_id: Current post ID
		// $field: Field settings
		return $new;
	}
}

$this->loaders[]=new W4OS_Settings();