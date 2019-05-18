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
	 * @var      string    $opensim    The ID of this plugin.
	 */
	private $opensim;

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
	 * @param      string    $opensim       The name of this plugin.
	 * @param      string    $version    The version of this plugin.
	 */
	public function __construct( $opensim, $version ) {

		$this->opensim = $opensim;
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

		wp_enqueue_style( $this->opensim, plugin_dir_url( __FILE__ ) . 'css/opensim-admin.css', array(), $this->version, 'all' );

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

		wp_enqueue_script( $this->opensim, plugin_dir_url( __FILE__ ) . 'js/opensim-admin.js', array( 'jquery' ), $this->version, false );

	}

}

function opensim_register_settings() {
	add_option( 'opensim_grid_name', 'Hippo');
	register_setting( 'opensim_options_group', 'opensim_grid_name', 'opensim_callback' );
	add_option( 'opensim_login_uri', 'localhost:8002');
	register_setting( 'opensim_options_group', 'opensim_login_uri', 'opensim_callback' );
	add_option( 'opensim_db_host', 'localhost');
	register_setting( 'opensim_options_group', 'opensim_db_host', 'opensim_callback' );
	add_option( 'opensim_db_database', 'opensim');
	register_setting( 'opensim_options_group', 'opensim_db_database', 'opensim_callback' );
	add_option( 'opensim_db_user', 'localhost');
	register_setting( 'opensim_options_group', 'opensim_db_user', 'opensim_callback' );
	add_option( 'opensim_db_pass', 'localhost');
	register_setting( 'opensim_options_group', 'opensim_db_pass', 'opensim_callback' );
}
add_action( 'admin_init', 'opensim_register_settings' );

function opensim_register_options_pages() {
	// add_options_page('OpenSim settings', 'OpenSim', 'manage_options', 'opensim', 'opensim_options_page');
	add_menu_page(
		'OpenSimulator', // page title
		'OpenSimulator', // menu title
		'manage_options', // capability
		'opensim', // slug
		'opensim_status_page', // callable function
		// plugin_dir_path(__FILE__) . 'options.php', // slug
		// null,	// callable function
		plugin_dir_url(__FILE__) . 'images/opensim-logo-24x14.png', // icon url
		2 // position
	);
	add_submenu_page('opensim', __('OpenSim Status'), __('Status'), 'manage_options', 'opensim', 'opensim_status_page');
	add_submenu_page(
		'opensim', // parent
		__('OpenSim Settings'), // page title
		__('Settings'), // menu title
		'manage_options', // capability
		'opensim_settings', // menu slug
		'opensim_options_page' // function
	);
}
add_action('admin_menu', 'opensim_register_options_pages');

function opensim_options_page()
{
	if ( ! current_user_can( 'manage_options' ) ) {
			return;
	}
	require(plugin_dir_path(__FILE__) . 'options.php');
}

function opensim_status_page()
{
	if ( ! current_user_can( 'manage_options' ) ) {
			return;
	}
	require(plugin_dir_path(__FILE__) . 'status.php');
}
