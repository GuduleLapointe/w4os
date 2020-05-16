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
}
add_action( 'admin_init', 'w4os_register_settings' );

function w4os_register_options_pages() {
	// add_options_page('OpenSim settings', 'w4os', 'manage_options', 'w4os', 'w4os_options_page');
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
	add_submenu_page('w4os', __('OpenSim Status'), __('Status'), 'manage_options', 'w4os', 'w4os_status_page');
	add_submenu_page(
		'w4os', // parent
		__('OpenSim Settings'), // page title
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
		"<a href='$url'>" . __( 'Settings' ) . "</a>"
	);

	return $links;
}
add_filter( 'plugin_action_links_w4os/w4os.php', 'w4os_settings_link' );
