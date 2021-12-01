<?php if ( ! defined( 'W4OS_PLUGIN' ) ) die;
/*
 * Disabled, we rely now on action scheduler to get asynchronous background tasks
 */

// add_filter( 'cron_schedules', 'w4os_add_cron_intervals' );
// function w4os_add_cron_intervals( $schedules ) {
// 	if(!isset($schedules['hourly'])) {
// 		$schedules['hourly'] = array(
// 			'interval' => 3600,
//       'display'  => esc_html__( 'hourly', 'w4os' ),
// 		);
// 	}
// 	return $schedules;
// }
//
// register_activation_hook( WP_PLUGIN_DIR . "/" . W4OS_PLUGIN, 'w4os_activate' );
// function w4os_activate($args = array()) {
//   // $args = array( $args_1, $args_2 );
//   if ( ! wp_next_scheduled( 'w4os_sync_users_cron' ) ) {
//     wp_schedule_event( time(), 'hourly', 'w4os_sync_users_cron' );
//   }
// }
//
// add_action( 'w4os_sync_users_cron', 'w4os_sync_users_exec', 10, 0 );
// function w4os_sync_users_exec($args=array()) {
//   update_option('w4os_sync_users', true);
// }
//
// register_deactivation_hook( WP_PLUGIN_DIR . "/" . W4OS_PLUGIN, 'w4os_deactivate' );
// function w4os_deactivate($args = array()) {
// 	wp_unschedule_event( wp_next_scheduled( 'w4os_sync_users_cron' ), 'w4os_sync_users_cron' );
// 	wp_unschedule_event( wp_next_scheduled( 'w4os_get_urls_statuses' ), 'w4os_get_urls_statuses' );
// 	wp_unschedule_event( wp_next_scheduled( 'w4os_sync_users' ), 'w4os_sync_users' );
// }
