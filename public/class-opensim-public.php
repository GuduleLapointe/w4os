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
		$info=array(
			"Grid name" => get_option('opensim_grid_name'),
			"Login URI" => get_option('opensim_login_uri'),
		);
		if(!empty($info)) {
			$content .= w4os_array2table($info);
		} else {
			$content .= "OpenSim " . __("not configured");
		}
		return $content;
	}
	add_shortcode('gridinfo', 'opensim_gridinfo_shortcode');

	function opensim_gridstatus_shortcode($atts = [], $content = null)
	{
		global $w4osdb;
		global $wp_locale;
		empty($atts['title']) ? $title=__("Grid status") : $title=$atts['title'];
		if(!empty($content)) $content="<div>$content</div>";

		$content="<h4>$title</h4>$content";

		if($w4osdb -> check_connection())
		{
			$lastmonth=time() - 30*86400;

			$urlinfo=explode(":", get_option('opensim_login_uri'));
			$host=$urlinfo['0'];
			$port=$urlinfo['1'];
			// $port = variable_get('opensim_default_users_server_port', '8002');
			$fp = @fsockopen($host, $port, $errno, $errstr, 1.0);
			if ($fp) {
				$gridonline = __("Yes");
			} else {
				$gridonline=__("No");
			}
			$stats = array(
				'World online' => $gridonline,
				'Citizens' => number_format_i18n($w4osdb->get_var("SELECT COUNT(*)
				FROM UserAccounts" )),
				'Citizens in world' => number_format_i18n($w4osdb->get_var("SELECT COUNT(*)
				FROM Presence AS p, UserAccounts AS u
				WHERE RegionID != '00000000-0000-0000-0000-000000000000'
				AND p.UserID = u.PrincipalID;" )),
				// 'Active citizens (30 days)' => number_format_i18n($w4osdb->get_var("SELECT COUNT(*)
				// FROM GridUser as g, UserAccounts as u WHERE g.UserID = u.PrincipalID AND Login > $lastmonth" )),
				'Users in world' => number_format_i18n($w4osdb->get_var("SELECT COUNT(*)
				FROM Presence
				WHERE RegionID != '00000000-0000-0000-0000-000000000000';	")),
				'Active users (30 days)' => number_format_i18n($w4osdb->get_var("SELECT COUNT(*)
				FROM GridUser WHERE Login > $lastmonth" )),
				// 'Known users' => number_format_i18n($w4osdb->get_var("SELECT COUNT(*)
				// FROM GridUser")),
				// 'Known online users' => number_format_i18n($w4osdb->get_var("SELECT COUNT(*)
				// FROM GridUser WHERE Online = 'true'")),
				'Regions' => number_format_i18n($w4osdb->get_var("SELECT COUNT(*)
				FROM Regions")),
				// 'Total area (m²)' => number_format_i18n($w4osdb->get_var("SELECT sum(sizex * sizey)
				// FROM regions") . "km²", 2),
				'Total area (km²)' => number_format_i18n($w4osdb->get_var("SELECT round(sum(sizex * sizey / 1000000),2)
				FROM regions") . "km²", 2),
			);
			$result=w4os_array2table($stats);
		}
		if(empty($result)) $result=__("No result") ;
		return $content . $result;
	}
	add_shortcode('gridstatus', 'opensim_gridstatus_shortcode');
}
add_action('init', 'opensim_shortcodes_init');
