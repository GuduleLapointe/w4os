<?php

function w4os_register_settings() {
	add_option( 'w4os_grid_name', 'Hippo');
	register_setting( 'w4os_options_group', 'w4os_grid_name', 'w4os_callback' );
	add_option( 'w4os_login_uri', 'localhost:8002');
	register_setting( 'w4os_options_group', 'w4os_login_uri', 'w4os_callback' );

	add_option( 'w4os_db_host', 'localhost');
	register_setting( 'w4os_options_group', 'w4os_db_host', 'w4os_callback' );
	add_option( 'w4os_db_database', 'w4os');
	register_setting( 'w4os_options_group', 'w4os_db_database', 'w4os_callback' );
	add_option( 'w4os_db_user', 'localhost');
	register_setting( 'w4os_options_group', 'w4os_db_user', 'w4os_callback' );
	add_option( 'w4os_db_pass', 'localhost');
	register_setting( 'w4os_options_group', 'w4os_db_pass', 'w4os_callback' );

	add_option( 'w4os_asset_server_uri', ASSET_SERVER_URI);
	register_setting( 'w4os_options_group', 'w4os_asset_server_uri', 'w4os_callback' );

	add_option( 'w4os_model_firstname', 'Default');
	register_setting( 'w4os_options_group', 'w4os_model_firstname', 'w4os_callback' );
	add_option( 'w4os_model_lastname', 'Default');
	register_setting( 'w4os_options_group', 'w4os_model_lastname', 'w4os_callback' );

	add_option( 'w4os_exclude_models', true);
	register_setting( 'w4os_options_group', 'w4os_exclude_models', 'w4os_callback' );
	add_option( 'w4os_exclude_tests', true);
	register_setting( 'w4os_options_group', 'w4os_exclude_tests', 'w4os_callback' );
	add_option( 'w4os_exclude_nomail', true);
	register_setting( 'w4os_options_group', 'w4os_exclude_nomail', 'w4os_callback' );
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
	require(plugin_dir_path(__FILE__) . 'settings-inc.php');
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


function register_avatar_column($columns) {
    $columns['avatar'] = 'Avatar';
    return $columns;
}
add_action('manage_users_columns', 'register_avatar_column');

function register_avatar_column_view($value, $column_name, $user_id) {
    // $user_info = get_userdata( $user_id );
    if($column_name == 'avatar') {
			if(!empty(get_the_author_meta( 'w4os_uuid', $user_id ))) {
				return get_the_author_meta( 'w4os_firstname', $user_id ) . " "
				. get_the_author_meta( 'w4os_lastname', $user_id );
			}
		}
    return $value;

}
add_action('manage_users_custom_column', 'register_avatar_column_view', 10, 3);

function avatar_sortable_columns( $columns ) {
	$columns['avatar'] = 'avatar';
  return $columns;
}
add_filter( 'manage_edit-avatar_sortable_columns', 'avatar_sortable_columns');
