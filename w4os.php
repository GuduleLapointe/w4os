<?php
/**
 * Plugin Name:       OpenSimulator
 * Description:       OpenSimulator web interface for WordPress.
 * Version:           0.4.2
 * Author:            Speculoos
 * Author URI:        http://speculoos.world
 * Plugin URI:        https://git.magiiic.com/opensimulator/w4os
 * GitLab Plugin URI: https://git.magiiic.com/opensimulator/w4os
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain:       w4os
 * Domain Path:       /languages
 *
 * @link              http://speculoos.world
 * @since             0.1.0
 * @package           OpenSim
 *
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

/**
 * Currently plugin version.
 * Start at version 0.1.0 and use SemVer - https://semver.org
 * Rename this for your plugin and update it as you release new versions.
 */
// define( 'W4OS_VERSION', $version );

/**
 * The code that runs during plugin activation.
 * This action is documented in includes/class-w4os-activator.php
 */
function activate_w4os() {
	require_once plugin_dir_path( __FILE__ ) . 'includes/class-w4os-activator.php';
	OpenSim_Activator::activate();
}

/**
 * The code that runs during plugin deactivation.
 * This action is documented in includes/class-w4os-deactivator.php
 */
function deactivate_w4os() {
	require_once plugin_dir_path( __FILE__ ) . 'includes/class-w4os-deactivator.php';
	OpenSim_Deactivator::deactivate();
}

register_activation_hook( __FILE__, 'activate_w4os' );
register_deactivation_hook( __FILE__, 'deactivate_w4os' );

/**
 * The core plugin class that is used to define internationalization,
 * admin-specific hooks, and public-facing site hooks.
 */
require plugin_dir_path( __FILE__ ) . 'includes/class-w4os.php';

/**
 * Begins execution of the plugin.
 *
 * Since everything within the plugin is registered via hooks,
 * then kicking off the plugin from this point in the file does
 * not affect the page life cycle.
 *
 * @since    0.1.0
 */
function run_w4os() {

	$plugin = new OpenSim();
	$plugin->run();

}
run_w4os();
require_once plugin_dir_path( __FILE__ ) . 'includes/w4os-profile.php';
