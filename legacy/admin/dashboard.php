<?php if ( ! defined( 'W4OS_ADMIN' ) ) die;

/**
 * The admin-specific functionality of the plugin.
 */

add_action( 'wp_dashboard_setup', 'w4os_dashboard_add_widgets' );
function w4os_dashboard_add_widgets() {
  if ( current_user_can('manage_options') ) $args['error-messages'] = true;

  wp_add_dashboard_widget(
    'w4os_dashboard_widget_gridinfo',
    "OpenSimulator: " . __( 'Grid info', 'w4os' ),
    'w4os_dashboard_widget_gridinfo_handler',
    '',
    $args,
  );
	wp_add_dashboard_widget(
    'w4os_dashboard_widget_gridstatus',
    "OpenSimulator: " . __( 'Grid status', 'w4os' ),
    'w4os_dashboard_widget_gridstatus_handler',
    '',
    $args,
  );
  if (current_user_can( 'list_users' ) ) {
    wp_add_dashboard_widget(
      'w4os_dashboard_widget_newusers',
      "OpenSimulator: " . __( 'Recent users', 'w4os' ),
      'w4os_dashboard_widget_newusers_handler',
      '',
      $args,
    );
  }
}

function w4os_dashboard_widget_gridstatus_handler($atts, $args) {
	print(w4os_gridstatus_html(array('title'=>''), $args));
}
function w4os_dashboard_widget_gridinfo_handler($atts, $args) {
	print(w4os_gridinfo_html(array('title'=>''), $args));
}
function w4os_dashboard_widget_newusers_handler($atts, $args) {
	print(w4os_newusers_html(array('title'=>''), $args));
}
