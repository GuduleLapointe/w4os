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

		wp_enqueue_style( $this->opensim, plugin_dir_url( __FILE__ ) . 'css/opensim-public.css', array(), $this->version, 'all' );

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

		wp_enqueue_script( $this->opensim, plugin_dir_url( __FILE__ ) . 'js/opensim-public.js', array( 'jquery' ), $this->version, false );

	}
}

function opensim_shortcodes_init()
{
	function opensim_gridinfo_shortcode($atts = [], $content = null)
	{
		// Gridinfo: http://robust.server:8002/get_grid_info
		isset($atts['title']) ? $title=$atts['title'] : $title=__("Grid info");
		empty($content) ? : $content="<div>$content</div>";
		$content="<h4>$title</h4>$content";
		$content.="
			<table>
				<tr valign='top'>
					<th scope='row'><label for='opensim_grid_name'>" . __("Grid name") . "</label></th>
					<td> ". get_option('opensim_grid_name') . "&nbsp;</td>
				</tr>
				<tr valign='top'>
					<th scope='row'><label for='opensim_login_uri'>" . __("Login URI") . "</label></th>
					<td> ". get_option('opensim_login_uri') . "&nbsp;</td>
				</tr>
			</table>
		";
		return $content;
	}
	add_shortcode('gridinfo', 'opensim_gridinfo_shortcode');

	function opensim_gridstatus_shortcode($atts = [], $content = null)
	{
		// not ready
		// return;
		// [ ! empty($atts['title']) ] && $title=$atts['title'] ||  $title="Grid status";
		empty($atts['title']) ? $title=__("Grid status") : $title=$atts['title'];
		if(!empty($content)) $content="<div>$content</div>";
		$content="<h4>$title</h4>$content";

		$status=opensim_grid_status();
		if(empty($status)) $status=__("No result") ;
		$content.=$status;
		return $content;
	}
	add_shortcode('gridstatus', 'opensim_gridstatus_shortcode');
}
add_action('init', 'opensim_shortcodes_init');
