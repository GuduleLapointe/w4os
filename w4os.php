<?php
/**
 * Plugin Name:       OpenSimulator Web Interface
 * Description:       WordPress interface for OpenSimulator (w4os).
 * Version:           2.0
 * Author:            Speculoos World
 * Author URI:        https://speculoos.world
 * Plugin URI:        https://github.com/GuduleLapointe/w4os/
 * License:           AGPLv3
 * License URI:       https://www.gnu.org/licenses/agpl-3.0.txt
 * Text Domain:       w4os
 * Domain Path:       /languages/
 *
 * @package	w4os
 *
 * Icon1x: https://github.com/GuduleLapointe/w4os/raw/master/assets/icon-128x128.png
 * Icon2x: https://github.com/GuduleLapointe/w4os/raw/master/assets/icon-256x256.png
 * BannerHigh: https://github.com/GuduleLapointe/w4os/raw/master/assets/banner-1544x500.jpg
 * BannerLow: https://github.com/GuduleLapointe/w4os/raw/master/assets/banner-772x250.jpg
 *
 * Contributing: If you improve this software, please give back to the
 * community, by submitting your changes on the git repository or sending them
 * to the authors. That's one of the meanings of Affero GPL!
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

require_once plugin_dir_path( __FILE__ ) . 'includes/init.php';
require_once plugin_dir_path( __FILE__ ) . 'includes/shortcodes.php';
require_once plugin_dir_path( __FILE__ ) . 'includes/woocommerce-fix.php';
if(W4OS_DB_CONNECTED) {
	// if($pagenow == "profile.php" || $pagenow == "user-edit.php")
	require_once plugin_dir_path( __FILE__ ) . 'includes/profile.php';
}

if(is_admin()) {
	require_once (plugin_dir_path(__FILE__) . 'admin/settings.php');
	if($pagenow == "index.php")
	require_once (plugin_dir_path(__FILE__) . 'admin/dashboard.php');
}

if(file_exists(plugin_dir_path( __FILE__ ) . 'updates.php'))
include_once plugin_dir_path( __FILE__ ) . 'updates.php';
