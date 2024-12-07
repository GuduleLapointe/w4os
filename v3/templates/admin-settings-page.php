<?php
/**
 * Settings page template.
 */
$template_base = W4OS_TEMPLATES_DIR . 'admin-' . preg_replace( '/^w4os-/', '', $menu_slug );
$template = $template_base . '-content.php';
if (empty($action_links_html)) {
    $action_links_html = '';
}
// 

$current_tab = isset( $_GET['tab'] ) ? $_GET['tab'] : 'general';

if ( isset( $_GET['tab'] ) ) {
    // $template = "$template_base-content-$tab.php";

    // # Option: fallback to main settings page if no template for tab:
    // $tab_template = "$template_base-content-$tab.php";
    // if( file_exists( $tab_template ) ) {
    //     $template = $tab_template;
    // }
}

?>
<div class="wrap w4os">
    <header>
        <h1><?php echo esc_html( $page_title ); ?></h1>
        <?php echo $action_links_html; ?>
        echo $tabs_navigation;
    </header>
    <?php settings_errors( $menu_slug ); ?>
    <body>
        <div class="wrap <?php echo esc_attr( $menu_slug ); ?>">
            <?php
            if( file_exists( $template ) ) {
                include $template;
            } else {
                printf( '<p>%s</p>', __( 'No content template available for this page.', 'w4os' ) );
                echo $template;
            }
            ?>
        </div>
    </body>
</div>
