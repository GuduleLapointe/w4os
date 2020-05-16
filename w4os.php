<?php
/**
 * Plugin Name:       OpenSimulator
 * Description:       OpenSimulator web interface for WordPress.
 * Version:           0.6.2
 * Author:            Speculoos World
 * Author URI:        http://speculoos.world
 * Plugin URI:        https://git.magiiic.com/opensimulator/w4os
 * GitLab Plugin URI: https://git.magiiic.com/opensimulator/w4os
 * Release Asset:     true
 * License:           GNU General Public License v2
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain:       w4os
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}
//
require_once plugin_dir_path( __FILE__ ) . 'includes/init.php';
require_once plugin_dir_path( __FILE__ ) . 'includes/shortcodes.php';

if(is_admin()) {
	require_once (plugin_dir_path(__FILE__) . 'admin/settings.php');
	if($pagenow == "index.php")
	require_once (plugin_dir_path(__FILE__) . 'admin/dashboard.php');
}

if(W4OS_DB_CONNECTED) {
	if($pagenow == "profile.php" || $pagenow == "user-edit.php")
	require_once plugin_dir_path( __FILE__ ) . 'includes/profile.php';
}
