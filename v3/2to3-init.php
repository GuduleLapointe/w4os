<?php
/**
 * Tranisition initialisation class for v2 to v3.
 * 
 * This class loads the classes and functions needed to test v3 features
 * while keeping v2 features available.
 * 
 * It will replace both legacy/init.php and includes/loader.php when all 
 * new v3 features are validated, and all remaining v2 or legacy features 
 * are ported to v3.
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
    die;
}

/**
 * Main plugin class.
 * 
 * This class loads the classes and functions needed to test v3 features
 */
class W4OS3 {
    // public function __construct() {
    //     // Safety only, this class should not be instantiated.
    //     self::init();
    // }

    public static function init() {
        self::constants();
        self::includes();

        // Register hooks
        // add_action( 'admin_menu', [ __CLASS__, 'add_submenus' ] );
    }

    public static function constants() {
        define( 'W4OS_PLUGIN_DIR', plugin_dir_path( __DIR__) );
        define( 'W4OS_INCLUDES_DIR', plugin_dir_path( __FILE__ ) );
        define( 'W4OS_TEMPLATES_DIR', W4OS_INCLUDES_DIR . 'templates/' );
    }

    public static function includes() {
        // Transition classes will be loaded here.

        // First we include all the files
        require_once W4OS_INCLUDES_DIR . '2to3-settings.php';

        // Once all files are loaded, we start the classes.
        W4OS3_Settings::init();
    }

    // Replicate core add_submenu_page to simplify other classes code.
    public static function add_submenu_page( $parent_slug, $page_title, $menu_title, $capability, $menu_slug, $callback = '', $position = null ) {
        $parent_slug = 'w4os';
        $prefix = $parent_slug . '-';
        if ( ! preg_match( '/^' . $prefix . '/', $menu_slug ) ) {
            $menu_slug = $prefix . $menu_slug;
        }
        add_submenu_page(
            $parent_slug,
            $page_title,
            $menu_title,
            $capability,
            $menu_slug,
            $callback,
            $position,
        );
    }

    public static function render_settings_page() {
        $page_title = esc_html( get_admin_page_title() );
        $menu_slug = preg_replace( '/^.*_page_/', '', esc_html( get_current_screen()->id ) );
        $template = W4OS_TEMPLATES_DIR . 'settings.php';
        $action_links_html = null; // TODO: Add action links

        if ( isset( $_GET['tab'] ) ) {
            $tab_template = W4OS_TEMPLATES_DIR . 'settings-' . $_GET['tab'] . '.php';
            if( file_exists( $tab_template ) ) {
                $template = $tab_template;
            }
        }

        printf( 
            '<h1>
            %1$s %2$s
            </h1>', 
            esc_html( $page_title ),
            $action_links_html,
            $menu_slug,
        );

        if( ! file_exists( $template ) ) {
            // trigger an error and display settings errors
            // admin_notice( 'error', __( 'No settings available for this page.', 'w4os' ) );
        }

        settings_errors( $menu_slug );

        printf( '<div class="wrap %s">', esc_attr( $menu_slug ) );

        if( file_exists( $template ) ) {
            include $template;
        } else {
            printf( '<p>%s</p>', __( 'No settings available for this page.', 'w4os' ) );
            echo $template;
        }

        printf( '</div>' );
    }
}

W4OS3::init();
