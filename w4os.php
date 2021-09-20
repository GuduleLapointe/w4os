<?php
/**
 * Plugin Name:       W4OS OpenSimulator Interface
 * Description:       WordPress interface for OpenSimulator.
 * Version:           1.2.5
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
 * @package	w4os
 *
 * Icon1x: https://git.magiiic.com/wordpress/w4os/-/raw/master/assets/icon-128x128.png
 * Icon2x: https://git.magiiic.com/wordpress/w4os/-/raw/master/assets/icon-256x256.png
 * BannerHigh: https://git.magiiic.com/wordpress/w4os/-/raw/master/assets/banner-1544x500.jpg
 * BannerLow: https://git.magiiic.com/wordpress/w4os/-/raw/master/assets/banner-772x250.jpg
 *
 * Contributing: If you improve this software, please give back to the
 * community, by submitting your changes on the git repository or sending them
 * to the authors. That's one of the meanings of Affero GPL!
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

/** Enable plugin updates **/
require_once plugin_dir_path( __FILE__ ) . 'lib/wp-package-updater/class-wp-package-updater.php';
$w4os_updater = new WP_Package_Updater(
	'https://magiiic.com',
	wp_normalize_path( __FILE__ ),
	wp_normalize_path( plugin_dir_path( __FILE__ ) )
);

require_once plugin_dir_path( __FILE__ ) . 'includes/init.php';
require_once plugin_dir_path( __FILE__ ) . 'includes/shortcodes.php';
require_once plugin_dir_path( __FILE__ ) . 'includes/woocommerce-fix.php';

if ( !class_exists('Puc_v4_Factory', false) ) {
	if(is_admin) {
		add_action(
			'admin_notices',
			function() {
				$class   = 'notice notice-error is-dismissible';
				$label   = 'W4OS';
				$file    = 'https://magiiic.com/updates/?action=download&slug=w4ps';
				// $file    = wp_nonce_url(
				// 	add_query_arg(
				// 		array(
				// 			'action' => 'install-plugin',
				// 			'plugin' => urlencode('https://magiiic.com/updates/?action=download&slug=' . 'w4os')
				// 		),
				// 		admin_url( 'update.php' )
				// 	),
				// 	$action.'_'.$slug
				// );
				$message = __( 'Stable updates are now delivered from a plugin server instead of the git repository. You need to reinstall the plugin from official repository to get future stable updates. GitHub Updater is no longer required for official releases.', 'w4os' );
				printf( '<div class="%1$s"><p><strong>%2$s:</strong> %3$s</p><a href="%4$s">%5$s</a></div>', esc_attr( $class ), esc_html( $label ), esc_html( $message ), esc_html( $file ), __("Download") );
			},
			1
		);
	}
}

if(is_admin()) {
	require_once (plugin_dir_path(__FILE__) . 'admin/settings.php');
	if($pagenow == "index.php")
	require_once (plugin_dir_path(__FILE__) . 'admin/dashboard.php');
}

if(W4OS_DB_CONNECTED) {
	// if($pagenow == "profile.php" || $pagenow == "user-edit.php")
	require_once plugin_dir_path( __FILE__ ) . 'includes/profile.php';
}

wp_register_style('w4os_css', plugin_dir_url(__FILE__) . 'css/w4os-min.css');
wp_enqueue_style( 'w4os_css');

function w4os_load_textdomain() {
	load_plugin_textdomain( 'w4os', false, basename( dirname( __FILE__ ) ) . '/languages' );
}
add_action( 'init', 'w4os_load_textdomain' );
