<?php

/**
 * The public-facing functionality of the plugin.
 *
 * @link       http://speculoos.world
 * @since      0.1.0
 *
 * @package    OpenSim
 * @subpackage OpenSim/public
 */

/**
 * The public-facing functionality of the plugin.
 *
 * Defines the plugin name, version, and two examples hooks for how to
 * enqueue the public-facing stylesheet and JavaScript.
 *
 * @package    OpenSim
 * @subpackage OpenSim/public
 * @author     Your Name <email@example.com>
 */
class OpenSim_Public {

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
	 * @param      string    $opensim       The name of the plugin.
	 * @param      string    $version    The version of this plugin.
	 */
	public function __construct( $opensim, $version ) {

		$this->opensim = $opensim;
		$this->version = $version;

	}

	/**
	 * Register the stylesheets for the public-facing side of the site.
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

		wp_enqueue_style( $this->opensim, plugin_dir_url( __FILE__ ) . 'css/w4os-public.css', array(), $this->version, 'all' );

	}

	/**
	 * Register the JavaScript for the public-facing side of the site.
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

		wp_enqueue_script( $this->opensim, plugin_dir_url( __FILE__ ) . 'js/w4os-public.js', array( 'jquery' ), $this->version, false );

	}
}
