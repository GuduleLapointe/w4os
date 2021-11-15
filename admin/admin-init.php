<?php if ( ! defined( 'WPINC' ) ) die;

function w4os_enqueue_admin_script( $hook ) {
    // if ( 'edit.php' != $hook ) {
    //     return;
    // }
    //
    wp_enqueue_style( 'w4os-admin', plugin_dir_url( __FILE__ ) . 'css/admin.css', array(), W4OS_VERSION );
}
add_action( 'admin_enqueue_scripts', 'w4os_enqueue_admin_script' );

function w4os_register_options_pages() {
	// add_options_page('OpenSimulator settings', 'w4os', 'manage_options', 'w4os', 'w4os_settings_page');
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
		'w4os_settings_page' // function
	);
}
add_action('admin_menu', 'w4os_register_options_pages');

function w4os_status_page()
{
	if ( ! current_user_can( 'manage_options' ) ) {
			return;
	}
	require(plugin_dir_path(__FILE__) . 'status-inc.php');
}

function w4os_users_page()
{
	if ( ! current_user_can( 'manage_options' ) ) return;
	require(plugin_dir_path(__FILE__) . 'users.php');
}

function w4os_settings_page()
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

require_once __DIR__ . '/settings.php';
if($pagenow == "index.php") require_once __DIR__ .'/dashboard.php';
