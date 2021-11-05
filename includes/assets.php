<?php if(!defined('W4OS_SLUG')) die();
error_reporting(E_ERROR | E_WARNING | E_PARSE);

add_action( 'init',  function() {
  add_rewrite_rule( esc_attr(get_option('w4os_assets_slug'), 'assets') . '/([a-fA-F0-9-]+)(\.[a-zA-Z0-9]+)?[/]?$', 'index.php?asset_uuid=$matches[1]&asset_format=$matches[2]', 'top' );
} );

add_action('admin_init', function() {
  add_settings_field('w4os_assets_slug', __('W4OS Assets base', 'w4os'), 'w4os_assets_slug_output', 'permalink', 'optional');
  if (isset($_POST['permalink_structure'])) {
    $newslug = sanitize_title($_REQUEST['w4os_assets_slug']);
    if(esc_attr(get_option('w4os_assets_slug')) != $newslug) {
      update_option('w4os_assets_slug', $newslug);
      flush_rewrite_rules(false);
    }
  }
});

function w4os_assets_slug_output() {
	?>
	<input name="w4os_assets_slug" type="text" class="regular-text code" value="<?php echo esc_attr(get_option('w4os_assets_slug')); ?>" placeholder="<?php echo 'assets'; ?>" />
	<?php
}

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
