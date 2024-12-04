<?php
/**
 * Beta v3 settings class.
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
        add_action( 'admin_init', [ __CLASS__, 'register_transition_settings' ] );
    }
    
    public static function add_submenus() {
        W4OS3::add_submenu_page(
            'w4os',                         // Parent slug
            __( 'Beta Settings (v3 backports)', 'w4os' ),  // Page title
            __( 'Beta Settings', 'w4os' ),        // Menu title
            'manage_options',               // Capability
            'settings',               // Menu slug
            [ 'W4OS3', 'render_settings_page' ],  // Callback
            2,                             // Position
        );
    }

    public static function register_transition_settings() {
        register_setting( 
            'w4os_settings_beta',         // Option group
            'w4os_settings',                    // Option name
            [ __CLASS__, 'sanitize_options' ],  // Sanitize callback
        );

        add_settings_section(
            'w4os_section_beta',
            null,
            null,
            'w4os_settings_beta'
        );

        add_settings_field(
            'enable-v3-features',
            __( 'Beta test', 'w4os' ),
            [ __CLASS__, 'enable_v3_features_callback' ],
            'w4os_settings_beta',
            'w4os_section_beta'
        );

        if(! W4OS_ENABLE_V3) {
            return;
        }
        // Add v3 settings below

    }

    public static function sanitize_options( $input ) {
        // Retrieve existing options
        $options = W4OS::get_option( 'w4os_settings', [] );

        // Sanitize the new input
        $new_options = [];
        $new_options['enable-v3-features'] = isset( $input['enable-v3-features'] ) ? true : false;

        // Merge new input with existing options
        $options = array_merge( $options, $new_options );

        return $options;
    }

    public static function enable_v3_features_callback() {
        $value = W4OS3::get_option( 'enable-v3-features' );
        printf(
            '<label>
            <input type="checkbox" name="w4os_settings[enable-v3-features]" value="1" %s />%s</label>',
            checked( 1, $value, false ),
            __( 'Enable beta v3 features', 'w4os' ),
        );
        echo '<p class="description">' . __( 'Warning: These features are in beta and may not be stable.', 'w4os' ) . '</p>';
    }
}
