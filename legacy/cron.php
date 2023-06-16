<?php if ( ! defined( 'W4OS_PLUGIN' ) ) {
	die;}

if ( get_option( 'w4os_provide_search' ) && ! empty( get_option( 'w4os_search_url' ) ) ) {
	if ( ! wp_next_scheduled( 'w4os_search_parser_cron' ) ) {
		add_action(
			'init',
			function() {
				wp_schedule_event( time(), 'every_five_minutes', 'w4os_search_parser_cron' );
			}
		);
	}
	// add_action('init','register_w4os_search_parser_async_cron');
} else {
	wp_unschedule_event( wp_next_scheduled( 'w4os_search_parser_cron' ), 'w4os_search_parser_cron' );
	// add_action('init','unregister_w4os_search_parser_async_cron');
}

add_filter( 'cron_schedules', 'w4os_add_cron_intervals' );
function w4os_add_cron_intervals( $schedules ) {
	if ( ! isset( $schedules['every_five_minutes'] ) ) {
		$schedules['every_five_minutes'] = array(
			'interval' => 300,
			'display'  => esc_html__( 'every_five_minutes', 'w4os' ),
		);
	}
	return $schedules;
}

add_action( 'w4os_search_parser_cron', 'w4os_search_parser_exec', 10, 0 );
function w4os_search_parser_exec( $args = array() ) {
	$search = get_option( 'w4os_search_url' );
	$parser = preg_replace( ':^//:', '/', dirname( $search ) . '/parser.php' );
	$result = file_get_contents( $parser );
	if ( ! empty( get_option( 'w4os_hypevents_url' ) ) ) {
		$eventsparser = preg_replace( ':^//:', '/', dirname( $search ) . '/eventsparser.php' );
		$result       = file_get_contents( $eventsparser );
	}
	// require(dirname(__DIR__) . '/helpers/parser.php');
}

// register_activation_hook( WP_PLUGIN_DIR . "/" . W4OS_PLUGIN, 'w4os_activate' );
// function w4os_activate($args = array()) {
// $args = array( $args_1, $args_2 );
// if ( ! wp_next_scheduled( 'w4os_search_parser_cron' ) ) {
// wp_schedule_event( time(), 'hourly', 'w4os_search_parser_cron' );
// }
// }

register_deactivation_hook( WP_PLUGIN_DIR . '/' . W4OS_PLUGIN, 'w4os_deactivate' );
function w4os_deactivate( $args = array() ) {
	wp_unschedule_event( wp_next_scheduled( 'w4os_search_parser_cron' ), 'w4os_search_parser_cron' );
}
