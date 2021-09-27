<?php
// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

/** Enable plugin updates **/
require_once plugin_dir_path( __FILE__ ) . 'lib/wp-package-updater/class-wp-package-updater.php';
$w4os_updater = new WP_Package_Updater(
	'https://magiiic.com',
	wp_normalize_path( plugin_dir_path( __FILE__ ) . "/w4os.php" ),
	wp_normalize_path( plugin_dir_path( __FILE__ ) )
);

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
