<?php

/**
 * The plugin bootstrap file
 *
 * This file is read by WordPress to generate the plugin information in the plugin
 * admin area. This file also includes all of the dependencies used by the plugin,
 * registers the activation and deactivation functions, and defines a function
 * that starts the plugin.
 *
 * @link              http://speculoos.world
 * @since             0.1.0
 * @package           OpenSim
 *
 * @wordpress-plugin
 * Plugin Name:       OpenSimulator
 * Plugin URI:        https://git.magiiic.com/opensimulator/w4os
 * Description:       This is a short description of what the plugin does. It's displayed in the WordPress admin area.
 * Version:           0.3.0
 * Author:            Speculoos
 * Author URI:        http://speculoos.world
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain:       w4os
 * Domain Path:       /languages
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
define( 'W4OS_VERSION', '0.1.0' );

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
