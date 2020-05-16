<?php
/**
 * Plugin Name:       OpenSimulator
 * Description:       OpenSimulator web interface for WordPress.
 * Version:           0.5.2
 * Author:            Speculoos
 * Author URI:        http://speculoos.world
 * Plugin URI:        https://git.magiiic.com/opensimulator/w4os
 * GitLab Plugin URI: https://git.magiiic.com/opensimulator/w4os
 * Release Asset:     true
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
//
require_once plugin_dir_path( __FILE__ ) . 'includes/init.php';
require_once plugin_dir_path( __FILE__ ) . 'includes/shortcodes.php';

if(is_admin()) {
	require(plugin_dir_path(__FILE__) . 'admin/settings.php');
	if($pagenow == "index.php") require(plugin_dir_path(__FILE__) . 'admin/dashboard.php');
}

if(W4OS_DB_CONNECTED) {
	if($pagenow == "profile.php") require_once plugin_dir_path( __FILE__ ) . 'includes/profile.php';
	if($pagenow == "user-edit.php") require_once plugin_dir_path( __FILE__ ) . 'includes/profile.php';

}
