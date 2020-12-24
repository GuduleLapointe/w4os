<?php

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

include plugin_dir_path(__FILE__) . 'plugin-update-checker/plugin-update-checker.php';
if ( !class_exists('Puc_v4_Factory', false) ) {
	if(is_admin) {
		add_action(
			'admin_notices',
			function() {
				$class   = 'notice notice-error is-dismissible';
				$label   = PLUGIN_NAME;
				$file    = 'https://magiiic.com/updates/?action=download&slug=' . PLUGIN_SLUG;
				// $file    = wp_nonce_url(
				// 	add_query_arg(
				// 		array(
				// 			'action' => 'install-plugin',
				// 			'plugin' => urlencode('https://magiiic.com/updates/?action=download&slug=' . PLUGIN_SLUG)
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
} else {
	$myUpdateChecker = Puc_v4_Factory::buildUpdateChecker(
		'https://magiiic.com/updates/?action=get_metadata&slug=' . PLUGIN_SLUG,
		__FILE__, //Full path to the main plugin file or functions.php.
		PLUGIN_SLUG
	);
}
