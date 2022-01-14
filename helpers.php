<?php if ( ! defined( 'W4OS_PLUGIN' ) ) die;

if(get_option('w4os_provide_offline_messages')==true) {
  add_action( 'init',  function() {
    // rewrite rule for /helpers/uuid
    if()
    add_rewrite_rule( esc_attr(get_option('w4os_helpers_slug'), 'helpers') . '/offline(/[a-fA-F0-9-]+)?[/]?$', 'index.php?method=$matches[1]', 'top' );
  } );

}
