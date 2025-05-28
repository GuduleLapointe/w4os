<?php
/**
 * WordPress Admin Functions
 * 
 * Admin-specific functions that will be moved here from v1/v2/v3 folders
 */

// Prevent direct access
if ( ! defined( 'W4OS_PLUGIN' ) ) {
	die;
}

/**
 * Display admin notice
 */
function w4os_admin_notice( $notice, $class = 'info', $dismissible = true ) {
	if ( empty( $notice ) ) {
		return;
	}
	$dismissible_class = $dismissible ? 'is-dismissible' : '';
	echo '<div class="notice notice-' . esc_attr( $class ) . ' ' . $dismissible_class . '"><p>' . $notice . '</p></div>';
}

/**
 * Add admin notice to transient queue
 */
function w4os_transient_admin_notice( $notice, $class = 'info', $dismissible = true, $key = null ) {
	if ( empty( $notice ) ) {
		return;
	}
	$transient_key = sanitize_title( W4OS_PLUGIN_NAME . '_admin_notices' );

	$queue = get_transient( $transient_key );
	if ( ! is_array( $queue ) ) {
		$queue = array();
	}

	$queue[] = array(
		'notice'      => $notice,
		'class'       => $class,
		'dismissible' => $dismissible,
	);

	set_transient( $transient_key, $queue, 30 );
}

/**
 * Display queued admin notices
 */
function w4os_get_transient_admin_notices() {
	$transient_key = sanitize_title( W4OS_PLUGIN_NAME . '_admin_notices' );
	$queue = get_transient( $transient_key );
	if ( empty( $queue ) ) {
		return;
	}
	if ( ! is_array( $queue ) ) {
		$queue = array( $queue );
	}
	foreach ( $queue as $key => $notice ) {
		if ( ! is_array( $notice ) ) {
			continue;
		}
		w4os_admin_notice( $notice['notice'], $notice['class'], $notice['dismissible'] );
	}
	delete_transient( $transient_key );
}
add_action( 'admin_head', 'w4os_get_transient_admin_notices' );
