<?php

add_filter( 'mb_settings_pages', 'w4os_register_settings_dashboard' );
function w4os_register_settings_dashboard( $settings_pages ) {
	$settings_pages[] = [
    'id' => 'w4os',
    'menu_title'  => __( 'OpenSimulator', 'w4os' ),
    'submenu_title' => __( 'Dashboard', 'band-tools' ),
    'page_title'  => sprintf(__( 'OpenSimulator Dashboard', 'band-tools' ), 'w4os'),
    'position'    => 2,
    'capability'  => 'manage_options',
    'icon_url'    => 		plugin_dir_url(__FILE__) . 'images/opensimulator-logo-24x14.png', // icon url
  ];
  return $settings_pages;
}

add_filter( 'mb_settings_pages', 'w4os_register_settings_settings' );
function w4os_register_settings_settings( $settings_pages ) {
	$settings_pages[] = [
    'id' => 'w4os2_settings',
    'menu_title' => __( 'Settings (new)', 'w4os' ),
    'position'   => 25,
    'parent'     => 'w4os',
    'capability' => 'manage_options',
    // 'style'      => 'no-boxes',
    'columns'    => 1,
    // 'icon_url'   => 'dashicons-admin-generic',
  ];

	return $settings_pages;
}


add_filter( 'rwmb_meta_boxes', 'w4os_register_settings_group_shortcodes' );

function w4os_register_settings_group_shortcodes( $meta_boxes ) {
  $prefix = '';

  $meta_boxes[] = [
    'title'          => __( 'Shortcodes', 'w4os' ),
    'id'             => 'options-dashboard',
    'settings_pages' => ['w4os'],
    'fields'         => [
      [
        'name'     => __( '[gridinfo]', 'w4os' ),
        'id'       => $prefix . 'shortcodes',
        'type'     => 'custom_html',
        'callback' => 'w4os_gridinfo_html',
      ],
      [
        'name'     => __( ' [gridstatus]', 'w4os' ),
        'id'       => $prefix . 'gridstatus',
        'type'     => 'custom_html',
        'callback' => 'w4os_gridstatus_html',
      ],
      [
        'name'     => __( '[gridprofile]', 'w4os' ),
        'id'       => $prefix . 'gridprofile',
        'type'     => 'custom_html',
        'callback' => 'w4os_gridprofile_html',
      ],
    ],
  ];

  return $meta_boxes;
}

add_filter( 'rwmb_meta_boxes', 'w4os_register_settings_group_users' );

function w4os_register_settings_group_users( $meta_boxes ) {
  $prefix = '';

  $meta_boxes[] = [
  'title'          => __( 'Users', 'w4os' ),
  'id'             => 'users',
  'settings_pages' => ['w4os'],
  'fields'         => [
  [
  'id'       => $prefix . 'users',
  'type'     => 'custom_html',
  'callback' => 'w4os_dashboard_users_html',
  ],
  ],
  ];

  return $meta_boxes;
}

add_filter( 'rwmb_meta_boxes', 'w4os_register_settings_group_dependencies' );

function w4os_register_settings_group_dependencies( $meta_boxes ) {
  $prefix = '';

  $meta_boxes[] = [
  'title'          => __( 'Dependencies', 'w4os' ),
  'id'             => 'dependencies',
  'settings_pages' => ['w4os'],
  'fields'         => [
  [
  'id'   => $prefix . 'dependencies_description',
  'type' => 'custom_html',
  'std'  => 'These extensions are required by some w4os features. While the plugin should be functional without them, some features would be disabled, so it is recommended to install and activate all of them on your web server.',
  ],
  [
  'name'            => __( 'PHP curl', 'w4os' ),
  'id'              => $prefix . 'php_curl_extension',
  'type'            => 'custom_html',
  'callback'        => 'w4os_dependencies_html',
  'function_exists' => 'curl_init',
  ],
  [
  'name'            => __( 'PHP xml', 'w4os' ),
  'id'              => $prefix . 'php_xml_extension',
  'type'            => 'custom_html',
  'callback'        => 'w4os_dependencies_html',
  'function_exists' => 'simplexml_load_string',
  ],
  [
  'name'             => __( 'PHP imagick', 'w4os' ),
  'id'               => $prefix . 'php_imagick_extension',
  'type'             => 'custom_html',
  'callback'         => 'w4os_dependencies_html',
  'extension_loaded' => 'imagick',
  ],
  ],
  ];

  return $meta_boxes;
}

add_filter( 'rwmb_meta_boxes', 'w4os_register_settings_group_pages' );
function w4os_register_settings_group_pages( $meta_boxes ) {
  $prefix = '';

  $meta_boxes[] = [
    'title'          => __( 'OpenSimulator pages', 'w4os' ),
    'id'             => 'opensimulator-pages',
    'settings_pages' => ['w4os'],
    'fields'         => [
      [
        'id'       => $prefix . 'pages',
        'type'     => 'custom_html',
        'callback' => 'w4os_dashboard_pages_html',
      ],
    ],
  ];

  return $meta_boxes;
}

// add_filter( 'rwmb_meta_boxes', 'w4os_register_settings_group_grid_info' );
function w4os_register_settings_group_grid_info( $meta_boxes ) {
  $prefix = '';

  $meta_boxes[] = [
    'id'             => 'grid_info_new',
    'title'          => __( 'Grid info', 'w4os' ),
    'settings_pages' => ['w4os2_settings'],
    'fields'         => [
      [
        'name' => __( 'Login URI', 'w4os' ),
        'id'   => $prefix . 'login_uri',
        'type' => 'text',
      ],
      [
        'name' => __( 'Grid name', 'w4os' ),
        'id'   => $prefix . 'grid_name',
        'type' => 'text',
      ],
    ],
  ];

  return $meta_boxes;
}
