<?php if(!defined('W4OS_SLUG')) die();

define('W4OS_ASSETS_SERVER', 'http://' . esc_attr(get_option('w4os_login_uri')) . '/assets/'); // (OpenSim.ini: asset_server_url . "/assets/")

add_action( 'init',  function() {
  add_rewrite_rule( 'assets/([a-fA-F0-9-]+)(\.[a-zA-Z0-9]+)?[/]?$', 'index.php?asset_uuid=$matches[1]&asset_format=$matches[2]', 'top' );
} );

add_filter( 'query_vars', function( $query_vars ) {
  $query_vars[] = 'asset_uuid';
  $query_vars[] = 'asset_format';
  return $query_vars;
} );

add_action( 'template_include', function( $template ) {
  if ( get_query_var( 'asset_uuid' ) == false || get_query_var( 'asset_uuid' ) == '' ) {
    return $template;
  }
  return dirname(__FILE__) . '/assets-render.php';
} );
