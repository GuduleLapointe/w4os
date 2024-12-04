<?php
/**
 * New v3 settings class.
 * 
 * Defines the general settings pages, not related to any specific feature.
 * Main menu is already defined in init and has the slug 'w4os'.
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
    die;
}

/**
 * Main settings class.
 * 
 * Add main settings page and submenu. Use templates to display the page.
 */
class W4OS3_Settings {
    public static function init() {
        add_action( 'admin_menu', [ __CLASS__, 'add_submenus' ], 20 );
    }
    
    public static function add_submenus() {
        W4OS3::add_submenu_page(
            'w4os',                         // Parent slug
            __( 'v3 Settings', 'w4os' ),  // Page title
            __( 'v3 Settings', 'w4os' ),        // Menu title
            'manage_options',               // Capability
            'settings',               // Menu slug
            [ 'W4OS3', 'render_settings_page' ],  // Callback
            2,                             // Position
        );
    }
    
}
