<?php

/**
 * The file that defines the core plugin class
 *
 * A class definition that includes attributes and functions used across both the
 * public-facing side of the site and the admin area.
 *
 * @link       http://speculoos.world
 * @since      0.1.0
 *
 * @package    OpenSim
 * @subpackage OpenSim/includes
 */

/**
 * The core plugin class.
 *
 * This is used to define internationalization, admin-specific hooks, and
 * public-facing site hooks.
 *
 * Also maintains the unique identifier of this plugin as well as the current
 * version of the plugin.
 *
 * @since      0.1.0
 * @package    OpenSim
 * @subpackage OpenSim/includes
 * @author     Your Name <email@example.com>
 */
class OpenSim {

	/**
	 * The loader that's responsible for maintaining and registering all hooks that power
	 * the plugin.
	 *
	 * @since    0.1.0
	 * @access   protected
	 * @var      OpenSim_Loader    $loader    Maintains and registers all hooks for the plugin.
	 */
	protected $loader;

	/**
	 * The unique identifier of this plugin.
	 *
	 * @since    0.1.0
	 * @access   protected
	 * @var      string    $w4os    The string used to uniquely identify this plugin.
	 */
	protected $w4os;

	/**
	 * The current version of the plugin.
	 *
	 * @since    0.1.0
	 * @access   protected
	 * @var      string    $version    The current version of the plugin.
	 */
	protected $version;

	/**
	 * Define the core functionality of the plugin.
	 *
	 * Set the plugin name and the plugin version that can be used throughout the plugin.
	 * Load the dependencies, define the locale, and set the hooks for the admin area and
	 * the public-facing side of the site.
	 *
	 * @since    0.1.0
	 */
	public function __construct() {
		if ( defined( 'OPENSIM_VERSION' ) ) {
			$this->version = OPENSIM_VERSION;
		} else {
			$this->version = '0.1.0';
		}
		$this->w4os = 'w4os';

		$this->load_dependencies();
		$this->set_locale();
		$this->define_admin_hooks();
		$this->define_public_hooks();

	}

	/**
	 * Load the required dependencies for this plugin.
	 *
	 * Include the following files that make up the plugin:
	 *
	 * - OpenSim_Loader. Orchestrates the hooks of the plugin.
	 * - OpenSim_i18n. Defines internationalization functionality.
	 * - OpenSim_Admin. Defines all hooks for the admin area.
	 * - OpenSim_Public. Defines all hooks for the public side of the site.
	 *
	 * Create an instance of the loader which will be used to register the hooks
	 * with WordPress.
	 *
	 * @since    0.1.0
	 * @access   private
	 */
	private function load_dependencies() {

		/**
		 * The class responsible for orchestrating the actions and filters of the
		 * core plugin.
		 */
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/class-w4os-loader.php';

		/**
		 * The class responsible for defining internationalization functionality
		 * of the plugin.
		 */
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/class-w4os-i18n.php';

		/**
		 * The class responsible for defining all actions that occur in the admin area.
		 */
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'admin/class-w4os-admin.php';

		/**
		 * The class responsible for defining all actions that occur in the public-facing
		 * side of the site.
		 */
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'public/class-w4os-public.php';

		$this->loader = new OpenSim_Loader();

	}

	/**
	 * Define the locale for this plugin for internationalization.
	 *
	 * Uses the OpenSim_i18n class in order to set the domain and to register the hook
	 * with WordPress.
	 *
	 * @since    0.1.0
	 * @access   private
	 */
	private function set_locale() {

		$plugin_i18n = new OpenSim_i18n();

		$this->loader->add_action( 'plugins_loaded', $plugin_i18n, 'load_plugin_textdomain' );

	}

	/**
	 * Register all of the hooks related to the admin area functionality
	 * of the plugin.
	 *
	 * @since    0.1.0
	 * @access   private
	 */
	private function define_admin_hooks() {

		$plugin_admin = new OpenSim_Admin( $this->get_w4os(), $this->get_version() );

		$this->loader->add_action( 'admin_enqueue_scripts', $plugin_admin, 'enqueue_styles' );
		$this->loader->add_action( 'admin_enqueue_scripts', $plugin_admin, 'enqueue_scripts' );

	}

	/**
	 * Register all of the hooks related to the public-facing functionality
	 * of the plugin.
	 *
	 * @since    0.1.0
	 * @access   private
	 */
	private function define_public_hooks() {

		$plugin_public = new OpenSim_Public( $this->get_w4os(), $this->get_version() );

		$this->loader->add_action( 'wp_enqueue_scripts', $plugin_public, 'enqueue_styles' );
		$this->loader->add_action( 'wp_enqueue_scripts', $plugin_public, 'enqueue_scripts' );

	}

	/**
	 * Run the loader to execute all of the hooks with WordPress.
	 *
	 * @since    0.1.0
	 */
	public function run() {
		$this->loader->run();
	}

	/**
	 * The name of the plugin used to uniquely identify it within the context of
	 * WordPress and to define internationalization functionality.
	 *
	 * @since     0.1.0
	 * @return    string    The name of the plugin.
	 */
	public function get_w4os() {
		return $this->w4os;
	}

	/**
	 * The reference to the class that orchestrates the hooks with the plugin.
	 *
	 * @since     0.1.0
	 * @return    OpenSim_Loader    Orchestrates the hooks of the plugin.
	 */
	public function get_loader() {
		return $this->loader;
	}

	/**
	 * Retrieve the version number of the plugin.
	 *
	 * @since     0.1.0
	 * @return    string    The version number of the plugin.
	 */
	public function get_version() {
		return $this->version;
	}

}

$w4osdb = new WPDB(
	get_option('w4os_db_user'),
	get_option('w4os_db_pass'),
	get_option('w4os_db_database'),
	get_option('w4os_db_host')
);

function w4os_array2table($array, $class="") {
	if(empty($array)) return;
	while (list($key, $value) = each($array)) {
		$result.="<tr><td class=gridvar>" . __($key) . "</td><td class=gridvalue>$value</td></tr>";
	}
	if($result) $result="<table class='$class'>$result</table>";
	return $result;
}

function w4os_shortcodes_init()
{
	function w4os_gridinfo_shortcode($atts = [], $content = null)
	{
		// Gridinfo: http://robust.server:8002/get_grid_info
		isset($atts['title']) ? $title=$atts['title'] : $title=__("Grid info");
		empty($content) ? : $content="<div>$content</div>";
		$content="<h4>$title</h4>$content";
		$info=array(
			"Grid name" => get_option('w4os_grid_name'),
			"Login URI" => get_option('w4os_login_uri'),
		);
		if(!empty($info)) {
			$content .= w4os_array2table($info);
		} else {
			$content .= "OpenSim " . __("not configured");
		}
		return $content;
	}
	add_shortcode('gridinfo', 'w4os_gridinfo_shortcode');

	function w4os_gridstatus_shortcode($atts = [], $content = null)
	{
		global $w4osdb;
		global $wp_locale;
		isset($atts['title']) ? $title=$atts['title'] : $title=__("Grid info");
		if(!empty($content)) $content="<div>$content</div>";

		$content="<h4>$title</h4>$content";

		$cached="in cache";
		$status = wp_cache_get( 'gridstatus', 'w4os' );
		if (false === $status) {
			$cached="uncached";
			if($w4osdb -> check_connection())
			{
				$lastmonth=time() - 30*86400;

				$urlinfo=explode(":", get_option('w4os_login_uri'));
				$host=$urlinfo['0'];
				$port=$urlinfo['1'];
				$fp = @fsockopen($host, $port, $errno, $errstr, 1.0);
				if ($fp) {
					$gridonline = __("Yes");
				} else {
					$gridonline=__("No");
				}
				$status = array(
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
			}
			wp_cache_add( 'gridstatus', $status, 'w4os');
		}
		$result=w4os_array2table($status) .  " ($cached)";

		if(empty($result)) $result=__("No result") ;
		return $content . $result;
	}
	add_shortcode('gridstatus', 'w4os_gridstatus_shortcode');
}
add_action('init', 'w4os_shortcodes_init');
