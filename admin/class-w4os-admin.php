<?php

/**
 * The admin-specific functionality of the plugin.
 *
 * @link       http://speculoos.world
 * @since      0.1.0
 *
 * @package    OpenSim
 * @subpackage OpenSim/admin
 */

/**
 * The admin-specific functionality of the plugin.
 *
 * Defines the plugin name, version, and two examples hooks for how to
 * enqueue the admin-specific stylesheet and JavaScript.
 *
 * @package    OpenSim
 * @subpackage OpenSim/admin
 * @author     Your Name <email@example.com>
 */
class OpenSim_Admin {

	/**
	 * The ID of this plugin.
	 *
	 * @since    0.1.0
	 * @access   private
	 * @var      string    $w4os    The ID of this plugin.
	 */
	private $w4os;

	/**
	 * The version of this plugin.
	 *
	 * @since    0.1.0
	 * @access   private
	 * @var      string    $version    The current version of this plugin.
	 */
	private $version;

	/**
	 * Initialize the class and set its properties.
	 *
	 * @since    0.1.0
	 * @param      string    $w4os       The name of this plugin.
	 * @param      string    $version    The version of this plugin.
	 */
	public function __construct( $w4os, $version ) {

		$this->w4os = $w4os;
		$this->version = $version;

	}

	/**
	 * Register the stylesheets for the admin area.
	 *
	 * @since    0.1.0
	 */
	public function enqueue_styles() {

		/**
		 * This function is provided for demonstration purposes only.
		 *
		 * An instance of this class should be passed to the run() function
		 * defined in OpenSim_Loader as all of the hooks are defined
		 * in that particular class.
		 *
		 * The OpenSim_Loader will then create the relationship
		 * between the defined hooks and the functions defined in this
		 * class.
		 */

		wp_enqueue_style( $this->w4os, plugin_dir_url( __FILE__ ) . 'css/w4os-admin.css', array(), $this->version, 'all' );

	}

	/**
	 * Register the JavaScript for the admin area.
	 *
	 * @since    0.1.0
	 */
	public function enqueue_scripts() {

		/**
		 * This function is provided for demonstration purposes only.
		 *
		 * An instance of this class should be passed to the run() function
		 * defined in OpenSim_Loader as all of the hooks are defined
		 * in that particular class.
		 *
		 * The OpenSim_Loader will then create the relationship
		 * between the defined hooks and the functions defined in this
		 * class.
		 */

		wp_enqueue_script( $this->w4os, plugin_dir_url( __FILE__ ) . 'js/w4os-admin.js', array( 'jquery' ), $this->version, false );

	}

}

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
	require(plugin_dir_path(__FILE__) . 'options.php');
}

function w4os_status_page()
{
	if ( ! current_user_can( 'manage_options' ) ) {
			return;
	}
	require(plugin_dir_path(__FILE__) . 'status.php');
}
