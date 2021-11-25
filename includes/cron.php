<?php if ( ! defined( 'W4OS_PLUGIN' ) ) die;

// add_filter( 'cron_schedules', 'w4os_add_cron_interval' );
// function w4os_add_cron_interval( $schedules ) {
// 	if(!isset($schedules['five_seconds'])) {
// 		$schedules['five_seconds'] = array(
// 			'interval' => 5,
// 			'display'  => esc_html__( 'Every Five Seconds' ), 'w4os'
// 		);
// 	}
// 	return $schedules;
// }

register_activation_hook( WP_PLUGIN_DIR . "/" . W4OS_PLUGIN, 'w4os_activate' );
function w4os_activate($args = array()) {
  // $args = array( $args_1, $args_2 );
  if ( ! wp_next_scheduled( 'w4os_sync_users_cron' ) ) {
    wp_schedule_event( time(), 'hourly', 'w4os_sync_users_cron' );
  }
}

register_deactivation_hook( WP_PLUGIN_DIR . "/" . W4OS_PLUGIN, 'w4os_deactivate' );
function w4os_deactivate($args = array()) {
	$timestamp = wp_next_scheduled( 'w4os_sync_users_cron' );
	wp_unschedule_event( $timestamp, 'w4os_sync_users_cron' );
}

add_action( 'w4os_sync_users_cron', 'w4os_sync_users_exec', 10, 0 );
function w4os_sync_users_exec($args=array()) {
  update_option('w4os_sync_users', true);
}
