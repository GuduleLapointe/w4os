<?php
/**
 * W4OS OpenSimulator Interface
 * @package	w4os
 * @author Olivier van Helden <olivier@van-helden.net>
 *
 * Plugin Name:       W4OS OpenSimulator Interface
 * Description:       WordPress interface for OpenSimulator.
 * Version:           0.12.3
 * Author:            Speculoos World
 * Author URI:        https://speculoos.world
 * Plugin URI:        https://git.magiiic.com/opensimulator/w4os
 * GitLab Plugin URI: https://git.magiiic.com/opensimulator/w4os
 * GitLab Languages:  https://git.magiiic.com/opensimulator/w4os-translations
 * License:           AGPLv3
 * License URI:       https://www.gnu.org/licenses/agpl-3.0.txt
 * Text Domain:       w4os
 * Domain Path:       /languages/
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

function w4os_load_textdomain() {
	load_plugin_textdomain( 'w4os', false, basename( dirname( __FILE__ ) ) . '/languages' );
}
add_action( 'init', 'w4os_load_textdomain' );
