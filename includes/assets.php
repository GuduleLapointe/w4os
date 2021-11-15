<?php if(!defined('W4OS_SLUG')) die();

add_action( 'init',  function() {
  // rewrite rule for /assets/uuid
  add_rewrite_rule( esc_attr(get_option('w4os_assets_slug'), 'assets') . '/([a-fA-F0-9-]+)(\.[a-zA-Z0-9]+)?[/]?$', 'index.php?asset_uuid=$matches[1]&asset_format=$matches[2]', 'top' );
} );

function w4os_redirect_if_asset() {
  $url = getenv('REDIRECT_URL');
  $uuid_pattern='[a-fA-F0-9-]{8}-[a-fA-F0-9-]{4}-[a-fA-F0-9-]{4}-[a-fA-F0-9-]{4}-[a-fA-F0-9-]{12}';
  $ext_pattern='[a-zA-Z0-9]{3}[a-zA-Z0-9]?';
  if(! preg_match(
    '#' . preg_replace(':^/:', '', esc_attr(parse_url(wp_upload_dir()['baseurl'],  PHP_URL_PATH ) ) ) . '/w4os/assets/images/' . $uuid_pattern . '\.' . $ext_pattern . '$' . '#',
    $url,
  )) return false;

  $image = explode('.', basename($url));
  if(count($image) != 2) return false;
  $query_asset = $image[0];
  $query_format = $image[1];
  if ( ! preg_match('/^(jpg|png)$/i', $query_format)) return false;

  require(dirname(__FILE__) . '/assets-render.php');
  die();
}
w4os_redirect_if_asset();

add_action('admin_init', function() {
  add_settings_field('w4os_assets_slug', __('W4OS Assets base', 'w4os'), 'w4os_assets_slug_output', 'permalink', 'optional');
  if (isset($_POST['permalink_structure'])) {
    $newslug = sanitize_title($_REQUEST['w4os_assets_slug']);
    if(esc_attr(get_option('w4os_assets_slug')) != $newslug) {
      update_option('w4os_assets_slug', $newslug);
      update_option('w4os_rewrite_rules', true);
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
