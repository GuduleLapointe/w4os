<?php
/**
 * Plugin Name:       OpenSimulator
 * Description:       OpenSimulator web interface for WordPress.
 * Version:           0.7.5
 * Author:            Speculoos World
 * Author URI:        https://speculoos.world
 * Plugin URI:        https://git.magiiic.com/opensimulator/w4os
 * GitLab Plugin URI: https://git.magiiic.com/opensimulator/w4os
 * GitLab Languages:	https://git.magiiic.com/opensimulator/w4os-translations
 * Release Asset:     true
 * License:           GNU General Public License v2
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Domain Path:       /languages
 * Text Domain:       w4os
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

require_once plugin_dir_path( __FILE__ ) . 'includes/init.php';
require_once plugin_dir_path( __FILE__ ) . 'includes/shortcodes.php';
require_once plugin_dir_path( __FILE__ ) . 'includes/woocommerce-fix.php';

if(is_admin()) {
	require_once (plugin_dir_path(__FILE__) . 'admin/settings.php');
	if($pagenow == "index.php")
	require_once (plugin_dir_path(__FILE__) . 'admin/dashboard.php');
}

if(W4OS_DB_CONNECTED) {
	// if($pagenow == "profile.php" || $pagenow == "user-edit.php")
	require_once plugin_dir_path( __FILE__ ) . 'includes/profile.php';
}

wp_register_style('w4os_css', plugin_dir_url(__FILE__) . 'css/w4os.css');
wp_enqueue_style( 'w4os_css');
