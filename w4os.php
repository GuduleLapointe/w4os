<?php
/**
 * Plugin Name:       w4os - OpenSimulator Web Interface (dev)
 * Plugin URI:        https://w4os.org/
 * Description:       WordPress interface for OpenSimulator (w4os).
 * Author:            Speculoos World
 * Author URI:        https://speculoos.world
 * Text Domain:       w4os
 * Domain Path:       /languages/
 * Version:           2.10.0-beta-2
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
if ( defined( 'W4OS_SLUG' ) ) {

	// If W4OS_SLUG is defined, another version of the plugin is active, abort.

	add_action(
		'admin_notices',
		function () {
			echo W4OS::sprintf_safe(
				"<div class='notice notice-error'><p><strong>W4OS:</strong> %s (%s).</p></div>",
				__( 'Another version of <strong>W4OS - OpenSimulator Web Interface</strong> is installed and active. Duplicate disabled.', 'w4os' ),
				basename( __DIR__ ) . '/' . basename( __FILE__ ),
			);
		}
	);
	deactivate_plugins( basename( __DIR__ ) . '/' . basename( __FILE__ ) );
} else {

	// No conflict, initialize the plugin.

	if ( file_exists( plugin_dir_path( __FILE__ ) . 'v3/2to3-init.php' ) ) {
		// Load v3 transitional files.
		require_once plugin_dir_path( __FILE__ ) . 'v3/2to3-init.php';
	} else {
		define( 'W4OS_ENABLE_V3', false );
	}

	// Legacy v1 and v2 inits. Both should be removed when v3 transition is complete.
	require_once plugin_dir_path( __FILE__ ) . 'v1/init.php';
	require_once plugin_dir_path( __FILE__ ) . 'v2/loader.php';

	// Plugin updater. Should definitively be moved to init class.
	if ( file_exists( plugin_dir_path( __FILE__ ) . 'lib/package-updater.php' ) ) {
		include_once plugin_dir_path( __FILE__ ) . 'lib/package-updater.php';
	}

	// Legacy admin init.
	if ( is_admin() ) {
		require_once plugin_dir_path( __FILE__ ) . 'v1/admin/admin-init.php';
	}
}
