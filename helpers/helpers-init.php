<?php namespace OpenSimulator\Helpers;

if ( ! defined( 'W4OS_PLUGIN' ) ) die;

if(get_option('w4os_provide_economy_helpers') &! empty(W4OS_GRID_INFO['economy']) ) {
  $economy = parse_url(W4OS_GRID_INFO['economy'])['path'];
  $url = getenv('REDIRECT_URL');
  if(preg_match(":^$economy(currency.php|landtool.php):", $url)) {
    $helper = preg_replace(":^$economy:", "", $url);
    require($helper);
    die();
  }
}
