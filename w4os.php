<?php
/**
 * Plugin Name:       OpenSimulator
 * Description:       WordPress interface for OpenSimulator.
 * Version:           0.9.1
 * Author:            Speculoos World
 * Author URI:        https://speculoos.world
 * Plugin URI:        https://git.magiiic.com/opensimulator/w4os
 * GitLab Plugin URI: https://git.magiiic.com/opensimulator/w4os
 * Release Asset:     true
 * License:           AGPLv3 (GNU Affero General Public License)
 * License URI:       https://www.gnu.org/licenses/agpl-3.0.txt
 * Domain Path:       /languages
 * Text Domain:       w4os
 *
 * Contributing:
 * If you improve this software, please give back to the community, by
 * submitting your changes on the git repository or sending them to the authors.
 * That's one of the meanings of Affero GPL!
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
