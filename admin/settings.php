<?php if ( ! defined( 'W4OS_ADMIN' ) ) die;

function w4os_register_settings() {
	$grid_info = w4os_update_grid_info();
	// $check_login_uri = 'http://' . (!empty(get_option('w4os_login_uri'))) ? esc_attr(get_option('w4os_login_uri')) : 'http://localhost:8002';
	$default_loginuri = (isset($grid_info['login'])) ? $grid_info['login'] : '';
	$default_gridname = (isset($grid_info['gridname'])) ? $grid_info['gridname'] : '';

	$settings_pages = array(
		'w4os_status' => array(
			'sections' => array(
				'default' => array(
					'fields' => array(
						'w4os_sync_users' => array(
							'type' => 'hidden',
							'value' => 1,
							'name' => 'Synchronize users now',
						),
						// 'w4os_check_urls_now' => array(
						// 	'type' => 'hidden',
						// 	'value' => 1,
						// 	'name' => 'Check urls now',
						// ),
					),
				),
			),
		),
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
				'w4os_options_helpers' => array(
					'name' => __('Helpers', 'w4os'),
					// 'section_callback' => 'w4os_settings_callback_webassets',
					'fields' => array(
						'w4os_provide' => array(
							'type' => 'checkbox',
							'label' => __('Provide services', 'w4os'),
							'default' => W4OS_DEFAULT_PROVIDE_ASSET_SERVER,
							'values' => array(
								'asset_server' => __('Web assets server', 'w4os'),
								'offline_messages' => __('Offline messages', 'w4os'),
							),
							'onchange' => 'onchange="valueChanged(this)"',
						),
						'w4os_internal_asset_server_uri' => array(
							'label' => __('Web asset server', 'w4os'),
							'default' => get_home_url(NULL, '/' . get_option('w4os_assets_slug') . '/'),
							'readonly' => true,
							'description' => __('A web assets server is required to display in-world assets (from the grid) on the website (e.g. profile pictures).', 'w4os')
							. sprintf(
								'<br>' . __('You can change the asset slug in %spermalinks settings%s.', 'w4os'),
								'<a href=' . get_admin_url('', 'options-permalink.php').'>', '</a>',
							),
						),
						'w4os_external_asset_server_uri' => array(
							'label' => __('External assets server URI', 'w4os'),
							'default' => W4OS_DEFAULT_ASSET_SERVER_URI,
							'description' => __('A web assets server is required to display in-world assets (from the grid) on the website (e.g. profile pictures).', 'w4os')
							. '<br>' . __('If W4OS web assets service is disabled, you need a third-party web application.', 'w4os'),
						),
						'w4os_offline_helper_uri' => array(
						  'label' => __('Offline helper URI', 'w4os'),
						  'default' => (!empty(W4OS_GRID_INFO['message'])) ? W4OS_GRID_INFO['message'] : get_home_url(NULL, '/helpers/offline/'),
						  'readonly' => true,
						  'description' => (empty(W4OS_GRID_INFO['message'])) ? (
								__('Set the URL in Robust and OpenSimulator configurations.', 'w4os')
								. w4os_format_ini(array(
									'Robust.HG.ini' => array(
										'[GridInfoService]' => array(
											'message' => get_option('w4os_offline_helper_uri', get_home_url(NULL, '/helpers/offline/')),
										),
									),
									'OpenSim.ini' => array(
										'[Messaging]' => array(
											'OfflineMessageURL' => get_option('w4os_offline_helper_uri', get_home_url(NULL, '/helpers/offline/')),
										),
									),
								))
							): NULL,
						),
					),
				),
				'w4os_options_users' => array(
					'name' => __('Grid users', 'w4os'),
					'fields' => array(
						'w4os_profile_page' => array(
							'type' => 'radio',
							'label' => __('Profile page', 'w4os'),
							'values' => array(
								'provide' => __('Provide web profile page for avatars', 'w4os'),
								// 'custom' => __('Custom page (with shortcode)', 'w4os'),
								'default' =>  __('Defaults', 'w4os'),
							),
							'default' => 'provide',
							'description' => sprintf(
								__('The page %s must exist, as defined in %spermalinks settings%s.', 'w4os'),
								'<code>' . get_home_url(NULL, get_option('w4os_profile_slug', 'profile')) . '</code>',
								'<a href=' . get_admin_url('', 'options-permalink.php').'>', '</a>',
							),
						),
						'w4os_login_page' => array(
						  'type' => 'radio',
						  'label' => __('Login page', 'w4os'),
						  'values' => array(
						    'profile' => __('Use profile page as login page', 'w4os'),
						    // 'custom' => __('Custom page (with shortcode)', 'w4os'),
						    'default' =>  __('Default', 'w4os'),
						  ),
						  'default' => 'profile',
						  'description' => __('', 'w4os'),
						),
						'w4os_userlist_replace_name' => array(
							'type' => 'boolean',
							'label' => __('Replace user name', 'w4os'),
							'description' => __('Show avatar name instead of user name in users list.', 'w4os'),
						),
						'w4os_exclude' => array(
							'type' => 'checkbox',
							'label' => __('Exclude from stats', 'w4os'),
							'values' => array(
								'models' =>  __('Models', 'w4os'),
								'nomail' => __('Accounts without mail address', 'w4os'),
								'hypergrid' => __('Hypergrid visitors', 'w4os'),
							),
							'description' => __('Accounts without email address are usually test accounts created from the console. Uncheck only if you have real avatars without email address.', 'w4os'),
						),
					),
				),
				// 'w4os_options_misc' => array(
				// 	'name' => __('Misc', 'w4os'),
				// 	'fields' => array(
				// 		'w4os_assets_permalink' => array(
				// 			'type' => 'description',
				// 			'label' => __('Permalinks', 'w4os'),
				// 			'description' => sprintf(__('Set w4os slugs on %spermalink options page%s.', 'w4os'), '<a href=' . get_admin_url('', 'options-permalink.php').'>', '</a>'),
				// 		),
				// 	),
				// ),
			),
		),
	);

	foreach($settings_pages as $page_slug => $page) {
		add_settings_section( $page_slug, '', '', $page_slug );

		foreach($page['sections'] as $section_slug => $section) {
			add_settings_section( $section_slug, (isset($section['name'])) ? $section['name'] : $section_slug, (isset($section['section_callback'])) ? $section['section_callback'] : '', $page_slug );
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

function w4os_register_setting($option_page, $option_slug, $args = array() ) {
	if(empty($args['type'])) $args['type'] = 'string';
	switch($args['type']) {
		case 'checkbox':
		foreach($args['values'] as $option_key => $option_value) {
			register_setting( $option_page, $option_slug . '_' .$option_key , $args );
		}
		break;

		case 'radio':
		$register_args = [ 'label' => $args['label'], 'type' => 'string'];
		register_setting( $option_page, $option_slug, $register_args );
		break;

		default:
		// w4os_admin_notice("register_setting( $option_page, $option_slug, <pre>" . print_r($args, true) . "</pre> );");
		// if(!in_array($args['type'], [ 'string', 'boolean', 'integer', 'number', 'array', 'object'] ) ) $args['type'] = 'string';
		register_setting( $option_page, $option_slug, $args );
	}
	// if(!isset($args['label'])) $args['label'] = $option_slug;
	if(empty($args['sanitize_callback'])) $args['sanitize_callback'] = 'w4os_settings_field';
	// add_settings_field( $option_slug, $args['label'], $args['sanitize_callback'], 'w4os_settings', $option_page, $args);
	$args['option_slug']=$option_slug;
	add_settings_field(
		$option_slug,                   // Field ID
		(isset($args['label'])) ? $args['label'] : $option_slug,  // Title
		$args['sanitize_callback'],            // Callback to display the field
		$option_page,                // Page
		$args['section'],
		$args,                      // Section
	);
}

function w4os_settings_field($args, $user = false) {
	if($args['option_slug']) $field_id = $args['option_slug'];
	else if($args['label_for']) $field_id = $args['label_for'];
	else return;
	// echo "<pre>" . print_r($args, true) . "</pre>";
	// return;

	$parameters = array();
	if(isset($args['readonly']) && $args['readonly']) $parameters[] .= 'readonly';
	if(isset($args['disabled']) && $args['disabled']) $parameters[] .= 'disabled';
	if(isset($args['onchange'])) $parameters[] = $args['onchange'];
	if(isset($args['placeholder'])) $parameters[] = "placeholder='" . esc_attr($args['placeholder']) . "'";
	if(isset($args['autocomplete'])) $parameters[] = "autocomplete='" . $args['autocomplete'] . "'";
	if(isset($args['onfocus'])) $parameters[] = "onfocus='" . esc_attr($args['onfocus']) . "'";
	if(isset($args['value'])) $value = $args['value'];
	else if($user) $value = get_user_meta( $user->ID, $field_id, true );
	else $value = esc_attr(get_option($field_id));

	switch ($args['type']) {
		case 'url':
		if($args['readonly']) {
			if(!empty($value)) echo sprintf('<a href="%1$s">%1$s</a>', $value);
			break;
		}

		case 'string':
		echo "<input type='text' class='regular-text input-${args['type']}' id='$field_id' name='$field_id' value='" . $value . "' " . join(' ', $parameters) . " />";
		break;

		case 'password':
		echo "<input type='password' class='regular-text' id='$field_id' name='$field_id' value='" . esc_attr(get_option($field_id)) . "' " . join(' ', $parameters) . " />";
		break;

		case 'radio':
		foreach($args['values'] as $option_key => $option_name) {
			$option_id = $field_id ."_" . $option_key;
			$option = "<input type='radio' id='$option_id' name='$field_id' value='$option_key'";
			// if (get_option($option_key)==$option_key) $parameters[] = "checked";
			$parameters['checked'] = checked(get_option($field_id), $option_key, false);
			$option .= ' ' . join(' ', $parameters) . ' ';
			$option .= "/>";
			$option .= " <label for='$option_id'>$option_name</label>";
			$options[] = $option;
		}
		if(is_array($options)) echo join("<br>", $options);
		break;

		case 'boolean':
		$args['values'][$field_id] = true;
		case 'checkbox':
		foreach($args['values'] as $option_key => $option_name) {
			if($args['type']=='checkbox') $option_id = $field_id ."_" . $option_key;
			else {
				$option_id = $field_id;
				$option_name = $args['description'];
				unset($args['description']);
			}
			if($user) $value = get_user_meta( $user->ID, $option_id, true );
			else $value = esc_attr(get_option($option_id));

			$option = "<input type='checkbox' id='$option_id' name='$option_id' value='1'";
			$parameters['checked'] = checked($value, true, false);
			$option .= ' ' . join(' ', $parameters) . ' ';
			$option .= "/>";
			// if($args['type']=='checkbox')
			$option .= " <label for='$option_id'>$option_name</label>";
			// else if($args['type']=='boolean') $option .= " <label for='$option_id'>$option_name</label>";
			$options[] = $option;
		}
		if(is_array($options)) echo join("<br>", $options);
		break;

		case 'hidden':
		echo "<input type='hidden' class='regular-text input-${args['type']}' id='$field_id' name='$field_id' value='" . esc_attr(get_option($field_id)) . "' " . join(' ', $parameters) . " />";
		break;

		case 'description':
		break;

		case 'os_asset':
		echo (!empty($value)) ? w4os_render_asset($value) : $args['placeholder'];
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
		__('Values must match Robust.HG.ini (or Robust.HG.ini) config file.', 'w4os'),
		__('Robust server must be running. Values entered here will be checked against your Robust server and updated if needed.', 'w4os'),
	);
}

function w4os_settings_callback_models($arg) {
	echo "<p>" . __('Grid accounts matching first name or last name set below are considered as avatar models. They will appear on the avatar registration form, with their in-world profile picture.', 'w4os') . "</p>";
}

// function w4os_settings_callback_webassets($arg) {
// 	echo "<p class=help>" . __('A web assets server is needed to display in-world assets (from the grid) on the website (e.g. profile pictures). You can use an external web assets server if you already have one installed, or use the one provided by w4os plugin.', 'w4os') . "</p>";
// }

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
