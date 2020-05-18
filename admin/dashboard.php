<?php

/**
 * The admin-specific functionality of the plugin.
 */

add_action( 'wp_dashboard_setup', 'w4os_dashboard_add_widgets' );
function w4os_dashboard_add_widgets() {
  wp_add_dashboard_widget(
    'w4os_dashboard_widget_gridinfo',
    "OpenSim " . __( 'Grid info', 'w4os' ),
    'w4os_dashboard_widget_gridinfo_handler'
  );
	wp_add_dashboard_widget(
    'w4os_dashboard_widget_gridstatus',
    "OpenSim " . __( 'Grid status', 'w4os' ),
    'w4os_dashboard_widget_gridstatus_handler'
  );
}

function w4os_dashboard_widget_gridstatus_handler() {
	print(w4os_gridstatus_shortcode(array("title"=>"")));
}

function w4os_dashboard_widget_gridinfo_handler() {
	print(w4os_gridinfo_shortcode(array("title"=>"")));
}
