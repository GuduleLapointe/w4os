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
		add_action( 'admin_init', [ __CLASS__, 'register_settings_page' ] );
		add_action( 'admin_menu', [ __CLASS__, 'add_submenus' ], 20 );
    }
    
    public static function add_submenus() {
        W4OS3::add_submenu_page(
            'w4os',                         // Parent slug
            __( 'Beta Settings (v3 backports)', 'w4os' ),  // Page title
            __( 'Beta Settings', 'w4os' ),        // Menu title
            'manage_options',               // Capability
            'settings',               // Menu slug
            [ 'W4OS3_Settings', 'render_settings_page' ],  // Callback
            90,                             // Position
        );
    }

    public static function register_settings_page() {
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
            'w4os_section_beta',
            array(
                'short_description' => 'Enable beta v3 features for testing purposes.', // Added short description
            )
        );

        add_settings_field(
            'debug_html',
            __( 'Debug', 'w4os' ),
            [ __CLASS__, 'debug_callback' ],
            'w4os_settings_beta',
            'w4os_section_beta',
            array(
                'short_description' => 'Display critical debug information on the front end.', // Added short description
            )
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
        $new_options['debug_html'] = isset( $input['debug_html'] ) ? true : false;

        // Merge new input with existing options
        $options = array_merge( $options, $new_options );

        return $options;
    }

    public static function enable_v3_features_callback() {
        $args = func_get_args();

        $value = W4OS3::get_option( 'enable-v3-features' );
        printf(
            '<label>
            <input type="checkbox" name="w4os_settings[enable-v3-features]" value="1" %s />%s</label>',
            checked( 1, $value, false ),
            __( 'Enable beta v3 features', 'w4os' ),
        );
        echo '<p class="description">' . __( 'Warning: These features are in beta and may not be stable.', 'w4os' ) . '</p>';
    }

    public static function debug_callback() {
        $value = W4OS3::get_option( 'debug_html' );
        printf(
            '<label>
            <input type="checkbox" name="w4os_settings[debug_html]" value="1" %s />%s</label>',
            checked( 1, $value, false ),
            __( 'Enable HTML debug', 'w4os' ),
        );
        echo '<p class="description">' . __( 'Warning: This will display critical debug information on the front end.', 'w4os' ) . '</p>';
    }

    public static function render_settings_page() {
        $args = func_get_args();
        $page_title = esc_html( get_admin_page_title() );
        $menu_slug = preg_replace( '/^.*_page_/', '', esc_html( get_current_screen()->id ) );
        $page = isset( $_GET['page'] ) ? esc_html( $_GET['page'] ) : '';
        $page_template = W4OS_TEMPLATES_DIR . 'admin-settings-page.php';
        $all_tabs = apply_filters( 'w4os_settings_tabs', [] );
        $tabs = isset( $all_tabs[ $page ] ) ? $all_tabs[ $page ] : [];
        $current_tab = isset( $_GET['tab'] ) ? $_GET['tab'] : 'general';
        $current_section = 'w4os_settings_region_section_' . $current_tab;
        
        if( file_exists( $page_template ) ) {
            include $page_template;
        } else {
            printf(
                '<h1>%s</h1><p>%s</p>', 
                __( 'No template available for this page.', 'w4os' ),
                W4OS3::get_option('debug_html') ? $page_template : '',
            );
        }
    }

    public static function render_settings_section( $args ) {
        if ( ! is_array( $args ) ) {
            return;
        }
        $args = wp_parse_args( $args, [
            'id' => null,
            'title' => null,
            'description' => null,
        ] );

        if ( $args['title'] ) {
            printf(
                '<h2>%s</h2>',
                $args['title'],
            );
        }
        if ( $args['description'] ) {
            printf(
                '<p class="description">%s</p>',
                $args['description'],
            );
        }
    }

    /**
     * Render a settings field.
     * 
     * @param array $args {
     *     Array of arguments for the settings field.
     * 
     *      @type string $id Field ID.
     *      @type string $label Field label.
     *      @type string $label_for Field label for attribute.
     *      @type string $type Field type.
     *      @type array $options Field options.
     *      @type string $default Default value.
     *      @type string $description Field description.
     *      @type string $option_name Option name.
     * }
     */
    public static function render_settings_field( $args ) {
        if ( ! is_array( $args ) ) {
            return;
        }
        $args = wp_parse_args( $args, [
            // 'id' => null,
            // 'label' => null,
            // 'label_for' => null,
            // 'type' => 'text',
            // 'options' => [],
            // 'default' => null,
            // 'description' => null,
            // 'option_name' => null,
        ] );
        error_log('args = ' . print_r($args, true));
        
        $option_name = $args['option_name'];
        error_log( __METHOD__ . ' option_name: ' . print_r( $option_name, true ) );
        
        $field_name = $args['id'];
        $option = get_option( $option_name );
        $value = isset( $option[ $args['id'] ] ) ? $option[ $args['id'] ] : '';
        
        switch ( $args['type'] ) {
            case 'checkbox':
                $input_field = sprintf(
                    '<label>
                        <input type="checkbox" id="%1$s" name="%2$s" value="1" %3$s />
                        %4$s
                        </label>',
                        esc_attr( $args['id'] ),
                        esc_attr( $field_name ),
                        checked( $value, '1', false ),
                        esc_html( $args['label'] ) // Added short description inside label
                );
                break;
            case 'text':
            default:
                $input_field = sprintf(
                    '<input type="text" id="%1$s" name="%2$s" value="%3$s" />',
                    esc_attr( $args['id'] ),
                    esc_attr( $field_name ),
                    esc_attr( $value )
                );
        }

        echo $input_field;
        printf(
            '<p class="description">%s</p>',
            esc_html( $args['description'] )
        );
    }
}
