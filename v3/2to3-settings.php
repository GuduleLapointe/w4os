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
		add_action( 'admin_init', [ __CLASS__, 'register_settings' ] );
		add_action( 'admin_menu', [ __CLASS__, 'add_submenus' ] );
    }
    
    public static function add_submenus() {
        W4OS3::add_submenu_page(
            'w4os',                         // Parent slug
            __( 'Beta Settings (v3 backports)', 'w4os' ),  // Page title
            __( 'Settings (Beta)', 'w4os' ),        // Menu title
            'manage_options',               // Capability
            'settings',               // Menu slug
            [ 'W4OS3_Settings', 'render_settings_page' ],  // Callback
            // 90,                             // Position
        );
    }

    public static function register_settings() {
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

    public static function get_tabs_html( $menu_slug = null, $default = 'default' ) {
        if ( empty($menu_slug) ) {
            $menu_slug = $_GET['page'];
        }
        $page_title = esc_html(get_admin_page_title());
        $current_tab = isset($_GET['tab']) ? sanitize_text_field($_GET['tab']) : 'avatars';
        $option_group = $menu_slug . '_group';
        $current_section = $option_group . '_section_' . $current_tab;

		$tabs = apply_filters( 'w4os_settings_tabs', array() );
		$page_tabs = isset($tabs[$menu_slug]) ? $tabs[$menu_slug] : array();
		$tabs_navigation = '';
		foreach( $page_tabs as $tab => $tab_data ) {
			$url = $tab_data['url'] ?? admin_url( 'admin.php?page=' . $menu_slug . '&tab=' . $tab );
			$title = $tab_data['title'] ?? $tab;
			$tabs_navigation .= sprintf(
				'<a href="%s" class="nav-tab %s">%s</a>',
				esc_url( $url ),
				$current_tab === $tab ? 'nav-tab-active' : '',
				esc_html( $title )
			);
		}
        if( ! empty( $tabs_navigation ) ) {
            return sprintf(
                '<h2 class="nav-tab-wrapper">%s</h2>',
                $tabs_navigation
            );
        }
        return "no tabs";
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
	 * This method should be agnostic, it will be moved in another class later and used by different settings pages.
	 */
    public static function render_settings_field($args) {
        if (!is_array($args)) {
            return;
        }
        $args = wp_parse_args($args, [
            // 'id' => null,
            // 'label' => null,
            // 'label_for' => null,
            'type' => 'text',
            // 'options' => [],
            // 'default' => null,
            'description' => null,
            // 'option_name' => null,
            // 'tab' => null, // Added tab
        ]);

        // Retrieve $option_name and $tab from args
        $option_name = isset($args['option_name']) ? sanitize_key($args['option_name']) : '';
        $tab = isset($args['tab']) ? sanitize_key($args['tab']) : 'settings';

        // Construct the field name to match the options array structure
        $field_name = "{$option_name}[{$tab}][{$args['id']}]";
        $option = get_option($option_name, []);
        $value = isset($option[$tab][$args['id']]) ? $option[$tab][$args['id']] : '';
        if ( empty($value) && isset($args['default']) ) {
            $value = $args['default'];
        }
        switch ($args['type']) {
			case 'db_credentials':
				// Grouped fields for database credentials
				$creds = WP_parse_args( $value, [
					'user'     => null,
					'pass'     => null,
					'database' => null,
					'host'     => null,
					'port'     => null,
				] );
				$input_field = sprintf(
					'<label for="%1$s_user">%2$s</label>
					<input type="text" id="%1$s_user" name="%3$s[user]" value="%4$s" />
					<label for="%1$s_pass">%5$s</label>
					<input type="password" id="%1$s_pass" name="%3$s[pass]" value="%6$s" />
					<label for="%1$s_database">%7$s</label>
					<input type="text" id="%1$s_database" name="%3$s[database]" value="%8$s" />
					<label for="%1$s_host">%9$s</label>
					<input type="text" id="%1$s_host" name="%3$s[host]" value="%10$s" />
					<label for="%1$s_port">%11$s</label>
					<input type="text" id="%1$s_port" name="%3$s[port]" value="%12$s" />',
					esc_attr($args['id']),
					esc_html__('User', 'w4os'),
					esc_attr($field_name),
					esc_attr($creds['user']),
					esc_html__('Password', 'w4os'),
					esc_attr($creds['pass']),
					esc_html__('Database', 'w4os'),
					esc_attr($creds['database']),
					esc_html__('Host', 'w4os'),
					esc_attr($creds['host']),
					esc_html__('Port', 'w4os'),
					esc_attr($creds['port'])
				);
				break;
            case 'button_group':
                $input_field = '';
                foreach ($args['options'] as $option_value => $option_label) {
                    $input_field .= sprintf(
                        '<label>
                            <input type="radio" id="%1$s_%2$s" name="%3$s" value="%2$s" %4$s />
                            %5$s
                        </label>',
                        esc_attr($args['id']),
                        esc_attr($option_value),
                        esc_attr($field_name),
                        checked($value, $option_value, false),
                        esc_html($option_label)
                    );
                }
                break;
            case 'select2':
            case 'select_advanced':
                $input_field = sprintf(
                    '<select id="%1$s" name="%2$s" %3$s>
                        <option value="">%4$s</option>',
                    esc_attr($args['id']),
                    esc_attr($field_name),
                    $args['multiple'] ? 'multiple' : '',
                    esc_html($args['placeholder'])
                );
                foreach ($args['options'] as $option_value => $option_label) {
                    $input_field .= sprintf(
                        '<option value="%1$s" %2$s>%3$s</option>',
                        esc_attr($option_value),
                        selected($value, $option_value, false),
                        esc_html($option_label)
                    );
                }
                $input_field .= '</select>';
                break;
            case 'checkbox':
                $input_field = sprintf(
                    '<label>
                        <input type="checkbox" id="%1$s" name="%2$s" value="1" %3$s />
                        %4$s
                    </label>',
                    esc_attr($args['id']),
                    esc_attr($field_name),
                    checked($value, '1', false),
                    esc_html($args['label'])
                );
                break;

            case 'custom_html':
                $input_field = sprintf(
                    '<div id="%1$s" name="%1$s">%2$s</div>',
                    esc_attr($args['id']),
                    $args['value'],
                );
                break;

            case 'text':
            default:
                $input_field = sprintf(
                    '<input type="text" id="%1$s" name="%2$s" value="%3$s" />',
                    esc_attr($args['id']),
                    esc_attr($field_name),
                    esc_attr($value)
                );
        }

        echo $input_field;
        if( ! empty($args['description']) ) {
            printf(
                '<p class="description">%s</p>',
                esc_html($args['description'])
            );
        }
    }
}
