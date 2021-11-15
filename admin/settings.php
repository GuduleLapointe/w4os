<?php

function w4os_register_setting($option_page, $option_slug, $args = array() ) {
	if(empty($args['type'])) $args['type'] = 'string';
	register_setting( $option_page, $option_slug, $args );
	if($args['type']=='checkbox') {
		foreach($args['values'] as $option_key => $option_value) {
			register_setting( $option_page, $option_slug . '_' .$option_key , $args );
		}
	}
	// if(!isset($args['label'])) $args['label'] = $option_slug;
	if(empty($args['sanitize_callback'])) $args['sanitize_callback'] = 'w4os_settings_field';
	// add_settings_field( $option_slug, $args['label'], $args['sanitize_callback'], 'w4os_settings', $option_page, $args);
	$args['option_slug']=$option_slug;
	add_settings_field(
		$option_slug,                   // Field ID
		$args['label'],  // Title
		$args['sanitize_callback'],            // Callback to display the field
		$option_page,                // Page
		$args['section'],
		$args,                      // Section
	);
}

function w4os_settings_field($args) {
	if($args['option_slug']) $field_id = $args['option_slug'];
	else if($args['label_for']) $field_id = $args['label_for'];
	else return;
	// echo "<pre>" . print_r($args, true) . "</pre>";
	// return;

	$parameters = array();
	if(isset($args['readonly'])) $parameters[] .= 'readonly';
	if(isset($args['onchange'])) $parameters[] = $args['onchange'];
	if(isset($args['placeholder'])) $parameters[] = "placeholder='" . esc_attr($args['placeholder']) . "'";
	if(isset($args['autocomplete'])) $parameters[] = "autocomplete='" . $args['autocomplete'] . "'";
	if(isset($args['onfocus'])) $parameters[] = "onfocus='" . esc_attr($args['onfocus']) . "'";

	switch ($args['type']) {
		case 'string':
		echo "<input type='text' class='regular-text input-${args['type']}' id='$field_id' name='$field_id' value='" . esc_attr(get_option($field_id)) . "' " . join(' ', $parameters) . " />";
		break;

		case 'password':
		echo "<input type='password' class='regular-text' id='$field_id' name='$field_id' value='" . esc_attr(get_option($field_id)) . "' " . join(' ', $parameters) . " />";
		break;

		case 'boolean':
		$args['values'][$field_id] = 1;
		case 'checkbox':
		foreach($args['values'] as $option_key => $option_name) {
			if($args['type']=='checkbox') $option_id = $field_id ."_" . $option_key;
			else $option_id = $field_id;
			$option = "<input type='checkbox' id='$option_id' name='$option_id' value='1'";
			if (get_option($option_id)==1) $parameters[] = "checked";
			$option .= ' ' . join(' ', $parameters) . ' ';
			$option .= "/>";
			if($args['type']=='checkbox') $option .= " <label for='$option_id'>$option_name</label>";
			$options[] = $option;
		}
		echo join("<br>", $options);
		break;


		case 'description':
		break;

		default:
		echo "type ${args['type']} not recognized";
	}
	if(!empty($args['description'])) {
		echo "<p class=description>${args['description']}</p>";
	}
	// echo "<input type=text value='$one'/><pre>" . print_r($args, true) . "</pre>";
}

function w4os_settings_callback_gridinfo() {
	echo sprintf(
		'<p>%1$s %2$s</p>',
		__('Values must match Robust.HG.ini (or Robust.ini) config file.', 'w4os'),
		__('Robust server must be running. Values entered here will be checked against your Robust server and updated if needed.', 'w4os'),
	);
}

function w4os_settings_callback_models($arg) {
	echo "<p>" . __('Grid accounts matching first name or last name set below are considered as avatar models. They will appear on the avatar registration form, with their in-world profile picture.', 'w4os') . "</p>";
}

function w4os_settings_callback_webassets($arg) {
	echo "<p class=help>" . __('A web assets server is needed to display in-world assets (from the grid) on the website (e.g. profile pictures). You can use an external web assets server if you already have one installed, or use the one provided by w4os plugin.', 'w4os') . "</p>";
}

function w4os_register_settings() {
	$grid_info = w4os_update_grid_info();
	// $check_login_uri = 'http://' . (!empty(get_option('w4os_login_uri'))) ? esc_attr(get_option('w4os_login_uri')) : 'http://localhost:8002';
	// $default_loginuri = $grid_info['login'];
	// $default_gridname = $grid_info['gridname'];

	$settings_pages = array(
		'w4os_settings' => array(
			'sections' => array(
				'w4os_options_gridinfo' => array(
					'name' => __('Grid info', 'w4os'),
					'section_callback' => 'w4os_settings_callback_gridinfo',
					'fields' => array(
						'w4os_login_uri' => array(
							'label' => 'Login URI',
							'placeholder' => 'example.org:8002',
							'default' => $default_loginuri,
							// 'type' => 'string',
							// 'sanitize_callback' => 'w4os_settings_field',
							// 'default' => 'Hippo',
							// 'placeholder' => 'Grid Name',
						),
						'w4os_grid_name' => array(
							'label' => __('Grid name', 'w4os'),
							'placeholder' => 'MyGrid',
							'default' => $default_gridname,
						),
					),
				),
				'w4os_options_database' => array(
					'name' => __('Robust server database', 'w4os'),
					'fields' => array(
						'w4os_db_host' => array(
							'label' => __('Hostname', 'w4os'),
						),
						'w4os_db_database' => array(
							'label' => __('Database name', 'w4os'),
						),
						'w4os_db_user' => array(
							'label' => __('Username', 'w4os'),
							'autocomplete' => 'off',
						),
						'w4os_db_pass' => array(
							'label' => __('Password', 'w4os'),
							'type' => 'password',
							'autocomplete' => 'off',
						),
					),
				),
				'w4os_options_avatarcreation' => array(
					'name' => __('Avatar models', 'w4os'),
					'section_callback' => 'w4os_settings_callback_models',
					'fields' => array(
						'w4os_model_firstname' => array(
							'label' => __('First Name = ', 'w4os'),
							'default' => 'Default'
						),
						'w4os_model_lastname' => array(
							'label' => __('OR Last Name = ', 'w4os'),
							'default' => 'Default'
						),
					),
				),
				'w4os_options_webassets' => array(
					'name' => __('Web assets', 'w4os'),
					'section_callback' => 'w4os_settings_callback_webassets',
					'fields' => array(
						'w4os_provide' => array(
							'type' => 'checkbox',
							'label' => __('Web asset server', 'w4os'),
							'default' => W4OS_DEFAULT_PROVIDE_ASSET_SERVER,
							'values' => array(
								'asset_server' => __('Provide web assets service', 'w4os'),
							),
							'onchange' => 'onchange="valueChanged(this)"',
						),
						'w4os_internal_asset_server_uri' => array(
							'label' => '',
							'default' => get_home_url(NULL, '/' . get_option('w4os_assets_slug') . '/'),
							'readonly' => true,
						),
						'w4os_external_asset_server_uri' => array(
							'label' => __('External assets server URI', 'w4os'),
							'default' => W4OS_DEFAULT_ASSET_SERVER_URI,
						),
					),
				),
				'w4os_options_misc' => array(
					'name' => __('Misc', 'w4os'),
					'fields' => array(
						'w4os_exclude' => array(
							'type' => 'checkbox',
							'label' => __('Exclude from stats', 'w4os'),
							'values' => array(
								'models' =>  __('Models', 'w4os'),
								'nomail' => __('Accounts without mail address', 'w4os'),
							),
							'description' => __('Accounts without email address are usually test accounts created from the console. Uncheck only if you have real avatars without email address.', 'w4os'),
						),
						'w4os_assets_permalink' => array(
							'type' => 'description',
							'label' => __('Permalinks', 'w4os'),
							'description' => sprintf(__('Set w4os slugs on %spermalink options page%s.', 'w4os'), '<a href=' . get_admin_url('', 'options-permalink.php').'>', '</a>'),
						),
					),
				),
			),
		),
	);

	foreach($settings_pages as $page_slug => $page) {
		add_settings_section( $page_slug, '', '', $page_slug );

		foreach($page['sections'] as $section_slug => $section) {
			add_settings_section( $section_slug, $section['name'], $section['section_callback'], $page_slug );
			foreach($section['fields'] as $field_slug => $field) {
				$field['section'] = $section_slug;
				w4os_register_setting( $page_slug, $field_slug, $field );
			}
		}
	}
	// die();
	return;
}
add_action( 'admin_init', 'w4os_register_settings' );

function w4os_register_options_pages() {
	// add_options_page('OpenSimulator settings', 'w4os', 'manage_options', 'w4os', 'w4os_options_page');
	add_menu_page(
		'OpenSimulator', // page title
		'OpenSimulator', // menu title
		'manage_options', // capability
		'w4os', // slug
		'w4os_status_page', // callable function
		// plugin_dir_path(__FILE__) . 'options.php', // slug
		// null,	// callable function
		plugin_dir_url(__FILE__) . 'images/w4os-logo-24x14.png', // icon url
		2 // position
	);
	add_submenu_page('w4os', __('OpenSimulator Status', "w4os"), __('Status'), 'manage_options', 'w4os', 'w4os_status_page');
	add_submenu_page(
		'w4os', // parent
		__('OpenSimulator Settings', "w4os"), // page title
		__('Settings'), // menu title
		'manage_options', // capability
		'w4os_settings', // menu slug
		'w4os_options_page' // function
	);
}
add_action('admin_menu', 'w4os_register_options_pages');

function w4os_options_page()
{
	if ( ! current_user_can( 'manage_options' ) ) {
			return;
	}
?>
	<div class="wrap">
		<h1>OpenSimulator</h1>	<?php screen_icon(); ?>
		<form method="post" action="options.php" autocomplete="off">
			<?php
			settings_fields( 'w4os_settings' );
			do_settings_sections( 'w4os_settings' );
			submit_button();
			 ?>
		</form>
	</div>
<?php
	wp_enqueue_script( 'w4os-admin-settings-form-js', plugins_url( 'js/settings.js', __FILE__ ), array(), W4OS_VERSION );
}

function w4os_status_page()
{
	if ( ! current_user_can( 'manage_options' ) ) {
			return;
	}
	require(plugin_dir_path(__FILE__) . 'status-inc.php');
}

function w4os_settings_link( $links ) {
	$url = esc_url( add_query_arg(
		'page',
		'w4os_settings',
		get_admin_url() . 'admin.php'
	) );

	array_push(
		$links,
		"<a href='$url'>" . __('Settings') . "</a>"
	);

	return $links;
}
add_filter( 'plugin_action_links_w4os/w4os.php', 'w4os_settings_link' );


function w4os_register_avatar_column($columns) {
    $columns['avatar'] = 'Avatar';
    return $columns;
}
add_action('manage_users_columns', 'w4os_register_avatar_column');

function w4os_register_avatar_column_view($value, $column_name, $user_id) {
    // $user_info = get_userdata( $user_id );
    if($column_name == 'avatar') {
			if(!empty(get_the_author_meta( 'w4os_uuid', $user_id ))) {
				return get_the_author_meta( 'w4os_firstname', $user_id ) . " "
				. get_the_author_meta( 'w4os_lastname', $user_id );
			}
		}
    return $value;

}
add_action('manage_users_custom_column', 'w4os_register_avatar_column_view', 10, 3);

function w4os_avatar_sortable_columns( $columns ) {
	$columns['avatar'] = 'avatar';
  return $columns;
}
add_filter( 'manage_edit-w4os_avatar_sortable_columns', 'w4os_avatar_sortable_columns');
