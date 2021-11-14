<?php if ( ! defined( 'WPINC' ) ) die;

require_once __DIR__ . '/settings.php';
if($pagenow == "index.php") require_once __DIR__ .'/dashboard.php';

function w4os_enqueue_admin_script( $hook ) {
    // if ( 'edit.php' != $hook ) {
    //     return;
    // }
    //
    wp_enqueue_style( 'w4os-admin', plugin_dir_url( __FILE__ ) . 'css/admin.css', array(), W4OS_VERSION );
}
add_action( 'admin_enqueue_scripts', 'w4os_enqueue_admin_script' );
