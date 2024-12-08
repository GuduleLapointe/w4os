<?php
/**
 * This is a test class to finetune menu integration.
 * - Create a Settings page for Regions-specific settings, as a submenu of the main 'w4os' menu
 * - We don't care about the main menu here, it is defined in another file.
 * - The rendering is made efficiently, by W4OS3_Settings::render_settings_page()
 * - We don't include html code of the pages here, only the settings registration.
 * - The header and content are managed by the render_settings_page() method.
 */

class W4OS3_Region {
    public function __construct() {
        // We will define region objects properties here later, not implemented now.

        // $args = func_get_args();
        // if ( ! empty ( $args ) ) {
        // }
    }

    /**
     * Initialize the class. Register actions and filters.
     */
    public function init() {
        add_action( 'admin_init', [ __CLASS__, 'register_settings_page' ] );
        add_action( 'admin_menu', [ __CLASS__, 'add_submenus' ] );

		add_action( 'init', [ __CLASS__, 'register_post_types' ] );

		add_filter ( 'w4os_settings_tabs', [ __CLASS__, 'add_menu_tabs' ] );
    }

    /**
	 * Add submenu for Region settings page
	 */
	public static function add_submenus() {
        W4OS3::add_submenu_page(
            'w4os',                         
            __( 'Regions Settings', 'w4os' ),
            __( 'Regions', 'w4os' ),
            'manage_options',
            'w4os-region-settings',
            [ __CLASS__, 'render_settings_page' ],
            3,
        );
    }

	static function add_menu_tabs( $tabs ) {
		$tabs['w4os-region-settings'] = array(
			'general' => __( 'General', 'w4os' ),
			'advanced' => __( 'Advanced', 'w4os' ),
		);
		return $tabs;
	}
		
    /**
     * Register settings using the Settings API, templates and the method W4OS3_Settings::render_settings_section().
     */
    public static function register_settings_page() {
        if (! W4OS_ENABLE_V3) {
            return;
        }

        $option_name = 'w4os-region-settings'; // Hard-coded here is fine to make sure it matches intended submenu slug
        $option_group = $option_name . '_group';

        // Register the main option with a sanitize callback
        register_setting( $option_group, $option_name, [ __CLASS__, 'sanitize_options' ] );

        // Get the current tab
        $tab = isset( $_GET['tab'] ) ? $_GET['tab'] : 'general';
        $section = $option_group . '_section_' . $tab;

        // Add settings sections and fields based on the current tab
        if ( $tab == 'general' ) {
            add_settings_section(
                $section,
                null, // No title for the section
                [ __CLASS__, 'section_callback' ],
                $option_name // Use dynamic option name
            );

            add_settings_field(
                'w4os_settings_region_general_field_1', 
                'First Tab Fields Title',
                [ __CLASS__, 'render_settings_field' ],
                $option_name, // Use dynamic option name as menu slug
                $section,
                array(
                    'id' => 'w4os_settings_region_general_field_1',
                    'type' => 'checkbox',
                    'label' => __( 'Enable general option 1.', 'w4os' ),
                    'description' => __( 'This is a placeholder parameter.', 'w4os' ),
                    'option_name' => $option_name, // Reference the unified option name
                    'label_for' => 'w4os_settings_region_general_field_1',
                    'tab' => 'general', // Added tab information
                )
            );
        } else if ( $tab == 'advanced' ) {
            add_settings_section(
                $section,
                null, // No title for the section
                null, // No callback for the section
                $option_name // Use dynamic option name as menu slug
            );

            add_settings_field(
                'w4os_settings_region_advanced_field_1', 
                'Second Tab Fields Title',
                [ __CLASS__, 'render_settings_field' ],
                $option_name, // Use dynamic option name as menu slug
                $section,
                array(
                    'id' => 'w4os_settings_region_advanced_field_1',
                    'type' => 'checkbox',
                    'label' => __( 'Enable advanced option 1.', 'w4os' ),
                    'description' => __( 'This is a placeholder parameter.', 'w4os' ),
                    'option_name' => $option_name, // Reference the unified option name
                    'label_for' => 'w4os_settings_region_advanced_field_1',
                    'tab' => 'advanced', // Added tab information
                )
            );
        }
    }

	public static function section_callback( $args = '' ) {
		// This is a placeholder for a section callback.
	}
	
	/**
	 * This method is called by several classes defined in several scripts for several settings pages.
	 * It uses only the values passed by args parameter and WP settings API.
	 * Particularly, $menu_slug, $option_name, and $option_group are retrieved dynamically.
	 */
	public static function render_settings_page() {
        $args = func_get_args();
        
		$screen = get_current_screen();
		if( ! $screen || ! isset($screen->id) ) {
			w4os_admin_notice( 'This page is not available. You probably did nothing wrong, the developer did.', 'error' );
			// End processing page, display pending admin notices and return.
			do_action( 'admin_notices' );
			return;
		}

		$menu_slug = preg_replace( '/^.*_page_/', '', sanitize_key( get_current_screen()->id ) );
		$option_name = isset($args[0]['option_name']) 
		? sanitize_key($args[0]['option_name']) 
		: sanitize_key($menu_slug); // no need to add settings suffix, it's already in menu slug by convention
		$option_group = isset($args[0]['option_group']) 
		? sanitize_key($args[0]['option_group']) 
		: sanitize_key($menu_slug . '_group');

        $page_title = esc_html(get_admin_page_title());
        $current_tab = isset($_GET['tab']) ? sanitize_text_field($_GET['tab']) : 'general';
        $current_section = $option_group . '_section_' . $current_tab;

        ?>
        <div class="wrap w4os">
            <header>
                <h1><?php echo $page_title; ?></h1>
                <?php echo isset($action_links_html) ? $action_links_html : ''; ?>
                <!-- echo $tabs_navigation; -->
                <h2 class="nav-tab-wrapper">
					<a href="?page=<?php echo esc_attr($menu_slug); ?>" class="nav-tab <?php echo $current_tab === 'general' ? 'nav-tab-active' : ''; ?>">
						<?php _e('General', 'w4os'); ?>
					</a>
					<a href="?page=<?php echo esc_attr($menu_slug); ?>&tab=advanced" class="nav-tab <?php echo $current_tab === 'advanced' ? 'nav-tab-active' : ''; ?>">
						<?php _e('Advanced', 'w4os'); ?>
					</a>
				</h2>
            </header>
            <?php settings_errors($menu_slug); ?>
            <body>
                <div class="wrap <?php echo esc_attr($menu_slug); ?>">
                    <?php
                    // ...existing code...
                    ?>
                    <form method="post" action="options.php">
                        <input type="hidden" name="<?php echo esc_attr($option_name); ?>[<?php echo esc_attr($current_tab); ?>][prevent-empty-array]" value="1">
                        <?php
                            settings_fields($option_group); // Use dynamic $option_group
                            do_settings_sections($menu_slug); // Use dynamic $menu_slug
                            submit_button();
                        ?>
                    </form>
                </div>
            </body>
        </div>
        <?php
    }

    public static function render_settings_field($args) {
        if (!is_array($args)) {
            return;
        }
        $args = wp_parse_args($args, [
            // 'id' => null,
            // 'label' => null,
            // 'label_for' => null,
            // 'type' => 'text',
            // 'options' => [],
            // 'default' => null,
            // 'description' => null,
            // 'option_name' => null,
            // 'tab' => null, // Added tab
        ]);

        // Retrieve $option_name and $tab from args
        $option_name = isset($args['option_name']) ? sanitize_key($args['option_name']) : '';
        $tab = isset($args['tab']) ? sanitize_key($args['tab']) : 'general';

        // Construct the field name to match the options array structure
        $field_name = "{$option_name}[{$tab}][{$args['id']}]";
        $option = get_option($option_name, []);
        $value = isset($option[$tab][$args['id']]) ? $option[$tab][$args['id']] : '';

        switch ($args['type']) {
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
        printf(
            '<p class="description">%s</p>',
            esc_html($args['description'])
        );
    }

	/**
	 * Global class for field sanitization.
	 * Used by different classes to save settings from different settings pages.
	 */
	public static function sanitize_options( $input ) {
		
		// Initialize the output array with existing options
		$options = get_option( 'w4os_region_settings', array( 'general' => array(), 'advanced' => array() ) );
		if( ! is_array( $input ) ) {
			return $options;
		}
		
		foreach ( $input as $key => $value ) {
			if(isset($value['prevent-empty-array'])) {
				unset($value['prevent-empty-array']);
			}
			$options[ $key ] = $value;
		}
		
		// Preserve the 'prevent-empty' field
		if ( isset( $input['prevent-empty'] ) ) {
			$options['prevent-empty'] = sanitize_text_field( $input['prevent-empty'] );
		}

		return $options;
	}

	/**
	 * Register post types for the plugin.
	 */
	public static function register_post_types() {
		$labels = array(
			'name' => __( 'Regions', 'w4os' ),
			'singular_name' => __( 'Region', 'w4os' ),
			'add_new' => __( 'Add New', 'w4os' ),
			'add_new_item' => __( 'Add New Region', 'w4os' ),
			'edit_item' => __( 'Edit Region', 'w4os' ),
			'new_item' => __( 'New Region', 'w4os' ),
			'view_item' => __( 'View Region', 'w4os' ),
			'search_items' => __( 'Search Regions', 'w4os' ),
			'not_found' => __( 'No Regions found', 'w4os' ),
			'not_found_in_trash' => __( 'No Regions found in Trash', 'w4os' ),
			'parent_item_colon' => __( 'Parent Region:', 'w4os' ),
			'menu_name' => __( 'Regions', 'w4os' ),
		);

		$args = array(
			'labels' => $labels,
			'hierarchical' => true,
			'description' => __( 'Regions for the plugin.', 'w4os' ),
			'supports' => array( 'title', 'editor', 'thumbnail', 'page-attributes' ),
			'public' => true,
			'show_ui' => true,
			'show_in_menu' => 'w4os-region-settings',
			'show_in_nav_menus' => true,
			'publicly_queryable' => true,
			'exclude_from_search' => false,
			'has_archive' => true,
			'query_var' => true,
			'can_export' => true,
			'rewrite' => array( 'slug' => 'region' ),
			'capability_type' => 'post',
		);

		register_post_type( 'opensimulator_region', $args );
	}

}
