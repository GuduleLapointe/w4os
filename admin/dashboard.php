<?php

/**
 * The admin-specific functionality of the plugin.
 */

add_action( 'wp_dashboard_setup', 'w4os_dashboard_add_widgets' );
function w4os_dashboard_add_widgets() {
  wp_add_dashboard_widget(
    'w4os_dashboard_widget_gridinfo',
    "OpenSimulator: " . __( 'Grid info', 'w4os' ),
    'w4os_dashboard_widget_gridinfo_handler'
  );
	wp_add_dashboard_widget(
    'w4os_dashboard_widget_gridstatus',
    "OpenSimulator: " . __( 'Grid status', 'w4os' ),
    'w4os_dashboard_widget_gridstatus_handler'
  );
  if (current_user_can( 'list_users' ) ) {
    wp_add_dashboard_widget(
      'w4os_dashboard_widget_newusers',
      "OpenSimulator: " . __( 'Recent users', 'w4os' ),
      'w4os_dashboard_widget_newusers_handler'
    );
  }
}

function w4os_dashboard_widget_gridstatus_handler() {
	print(w4os_gridstatus_shortcode(array("title"=>"")));
}

function w4os_dashboard_widget_gridinfo_handler() {
	print(w4os_gridinfo_shortcode(array("title"=>"")));
}
function w4os_dashboard_widget_newusers_handler() {
	print(w4os_newusers());
}
