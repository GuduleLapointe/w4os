<?php namespace OpenSimulator\Helpers;
/*
 * wp-load.php
 *
 * Loader for helpers implmentation in W4OS WordPress plugin.
 * Not needed in standalone implementations.
 *
 * Part of w4os - WordPress interface For OpenSimulator
 *   https://github.com/GuduleLapointe/w4os/
 *   by Gudule Lapointe <gudule@speculoos.world>
 */

if ( ! defined( 'W4OS_PLUGIN' ) ) {
	die();
}

if ( ! function_exists( 'xmlrpc_encode_request' ) ) {
	return;
}


// Add Apache rewrite rules for helpers directory
add_action('init', function() {
	$plugin_dir = basename(W4OS_PLUGIN_DIR);
	
	$helpers_slug = w4os_get_option('w4os_helpers_slug', 'helpers');
	// Add external rewrite rules that will be written to .htaccess
	// This bypasses WordPress entirely for helpers/ URLs
	add_rewrite_rule(
		"^$helpers_slug/(.*)$",
		'wp-content/plugins/' . $plugin_dir . '/helpers/$1',
		'top'
	);
});

// Add filter to modify .htaccess content directly
add_filter('mod_rewrite_rules', function($rules) {
	$plugin_dir = basename(W4OS_PLUGIN_DIR);
	$helpers_slug = w4os_get_option('w4os_helpers_slug', 'helpers');
	
	// Add rule before WordPress rules to bypass WordPress entirely
	$helpers_rule = "# W4OS Helpers bypass\n";
	$helpers_rule .= "RewriteRule ^$helpers_slug/(.*)$ wp-content/plugins/" . $plugin_dir . "/helpers/$1 [L]\n\n";
	
	return $helpers_rule . $rules;
});

require_once W4OS_PLUGIN_DIR . 'helpers/bootstrap.php';

global $SearchDB, $AssetsDB, $ProfileDB, $OpenSimDB;

$helpers_dir = W4OS_PLUGIN_DIR . 'helpers/';

$grid_info = json_decode(get_option( 'w4os_grid_info' ), true);

if(empty($grid_info) ) {
	error_log( '[ERROR] Grid info not set or empty in ' . __FILE__ . ':' . __LINE__ );
	return;
}
if(! is_array( $grid_info ) ) {
	error_log( '[ERROR] Grid info is not an array in ' . __FILE__ . ':' . __LINE__ );
	return;
}

$url = parse_url( getenv( 'REQUEST_URI' ), PHP_URL_PATH );

// Handle Setup Wizard
if ( preg_match( ":^/helpers/install-wizard\.php:", $url ) ) {
	require $helpers_dir . 'install-wizard.php';
	die();
}

if ( get_option( 'w4os_provide_economy_helpers' ) == true & ! empty( $grid_info['economy'] ) ) {
	$economy = parse_url( $grid_info['economy'] )['path'] ?? 'helpers';
	if ( preg_match( ":^$economy(currency.php|landtool.php):", $url ) ) {
		// $helper = preg_replace( ":^$economy:", '', $url );
		require $helpers_dir . basename($url);
		die();
	} elseif ( $url !== '/' && $url == $economy ) {
		// Probably the url check, just accept it
		// TODO: redirect to support page if set
		die();
	}
}

if ( get_option( 'w4os_provide_offline_messages' ) == true & ! empty( $grid_info['OfflineMessageURL'] ) ) {
	$message = parse_url( $grid_info['OfflineMessageURL'] )['path'];
	if ( preg_match( ":^$message/(SaveMessage|RetrieveMessages|offlineim)/:", "$url/" ) ) {
		require $helpers_dir . 'offline.php';
	} elseif ( $message == $url ) {
		die(); // ignore but don't trigger an error
	}
}

if ( get_option( 'w4os_provide_search' ) == true ) {
	if ( ! empty( get_option( 'w4os_search_url' ) ) ) {
		$search = parse_url( get_option( 'w4os_search_url' ) )['path'];
		if ( preg_match( ":^$search/:", "$url/" ) ) {
			// error_log("search $search");
			require $helpers_dir . 'query.php';
			die();
		}
		$parser = preg_replace( ':^//:', '/', dirname( $search ) . '/parser.php' );
		if ( preg_match( ":^$parser/:", "$url/" ) ) {
			require $helpers_dir . 'parser.php';
			die();
		}

		if ( ! empty( get_option( 'w4os_hypevents_url' ) ) ) {
			$hypevents = preg_replace( ':^//:', '/', dirname( $search ) . '/eventsparser.php' );
			if ( preg_match( ":^$hypevents:", "$url/" ) ) {
				require $helpers_dir . 'eventsparser.php';
				die();
			}
		}
	}

	if ( ! empty( get_option( 'w4os_search_register' ) ) ) {
		$register = parse_url( get_option( 'w4os_search_register' ) )['path'];
		if ( preg_match( ":^$register:", "$url/" ) ) {
			require $helpers_dir . 'register.php';
			die();
		}
	}
}
