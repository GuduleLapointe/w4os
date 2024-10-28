<?php
/**
 * Plugin Name:       w4os - OpenSimulator Web Interface
 * Description:       WordPress interface for OpenSimulator (w4os).
 * Version:           2.8
 * Author:            Speculoos World
 * Author URI:        https://speculoos.world
 * Plugin URI:        https://w4os.org/
 * License:           AGPLv3
 * License URI:       https://www.gnu.org/licenses/agpl-3.0.txt
 * Text Domain:       w4os
 * Domain Path:       /languages/
 *
 * @package GuduleLapointe/w4os
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

// error_reporting(E_ERROR | E_WARNING | E_PARSE);

/**
 * Plugin conflict checker. As the plugin slug changed when published on the
 * WordPress Directory, we want to make sure only one version of the plugin is
 * activated.
 */
$plugin_dir_check = basename( dirname( __FILE__ ) );
// First web check if official plugin release is active, even if not yet loaded, as it has priority
if ( $plugin_dir_check != 'w4os-opensimulator-web-interface' && in_array( 'w4os-opensimulator-web-interface/w4os.php', apply_filters( 'active_plugins', get_option( 'active_plugins' ) ) ) ) {
	add_action(
		'admin_notices',
		function() {
			echo W4OS::sprintf_safe(
				"<div class='notice notice-error'><p><strong>W4OS:</strong> %s</p></div>",
				__( 'You already installed the official release of <strong>W4OS - OpenSimulator Web Interface</strong> from WordPress plugins directory. The developer version has been deactivated and should be uninstalled.', 'w4os' ),
			);
		}
	);
	deactivate_plugins( $plugin_dir_check . '/' . basename( __FILE__ ) );
	// Then we check for any other plugin conflict, first loaded is kept
} elseif ( defined( 'W4OS_SLUG' ) ) {
	add_action(
		'admin_notices',
		function() {
			echo W4OS::sprintf_safe(
				"<div class='notice notice-error'><p><strong>W4OS:</strong> %s</p></div>",
				__( 'Another version of <strong>W4OS - OpenSimulator Web Interface</strong> is installed and active. Duplicate disabled.', 'w4os' ),
			);
		}
	);
	deactivate_plugins( $plugin_dir_check . '/' . basename( __FILE__ ) );
	// Finally, actually load if no conflict
} else {
	require_once plugin_dir_path( __FILE__ ) . 'legacy/init.php';
	require_once plugin_dir_path( __FILE__ ) . 'includes/loader.php';
	if ( file_exists( plugin_dir_path( __FILE__ ) . 'lib/package-updater.php' ) ) {
		include_once plugin_dir_path( __FILE__ ) . 'lib/package-updater.php';
	}

	if ( is_admin() ) {
		require_once plugin_dir_path( __FILE__ ) . 'legacy/admin/admin-init.php';
	}
}
