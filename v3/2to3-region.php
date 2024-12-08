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

        add_filter( 'manage_opensimulator_region_posts_columns', [ __CLASS__, 'add_custom_columns' ] );
        add_action( 'manage_opensimulator_region_posts_custom_column', [ __CLASS__, 'render_custom_columns' ], 10, 2 );

        add_action( 'add_meta_boxes', [ __CLASS__, 'add_meta_boxes' ] );
        add_action( 'save_post', [ __CLASS__, 'save_region_meta' ], 10, 2 );

        add_filter( 'parent_file', [ __CLASS__, 'set_active_menu' ] );
        add_filter( 'submenu_file', [ __CLASS__, 'set_active_submenu' ] );
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
			'regions'  => __( 'Regions', 'w4os' ), // Added 'Regions' tab
			'settings' => __( 'Settings', 'w4os' ),
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
        $tab = isset( $_GET['tab'] ) ? $_GET['tab'] : 'settings';
        $section = $option_group . '_section_' . $tab;

        // Add settings sections and fields based on the current tab
        if ( $tab == 'settings' ) {
            add_settings_section(
                $section,
                null, // No title for the section
                [ __CLASS__, 'section_callback' ],
                $option_name // Use dynamic option name
            );

            add_settings_field(
                'w4os_settings_region_settings_field_1', 
                'First Tab Fields Title',
                [ __CLASS__, 'render_settings_field' ],
                $option_name, // Use dynamic option name as menu slug
                $section,
                array(
                    'id' => 'w4os_settings_region_settings_field_1',
                    'type' => 'checkbox',
                    'label' => __( 'Enable settings option 1.', 'w4os' ),
                    'description' => __( 'This is a placeholder parameter.', 'w4os' ),
                    'option_name' => $option_name, // Reference the unified option name
                    'label_for' => 'w4os_settings_region_settings_field_1',
                    'tab' => 'settings', // Added tab information
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
        $current_tab = isset($_GET['tab']) ? sanitize_text_field($_GET['tab']) : 'regions';
        $current_section = $option_group . '_section_' . $current_tab;

        ?>
        <div class="wrap w4os">
            <header>
                <h1><?php echo $page_title; ?></h1>
                <?php echo isset($action_links_html) ? $action_links_html : ''; ?>
                <!-- echo $tabs_navigation; -->
                <h2 class="nav-tab-wrapper">
					<a href="?page=<?php echo esc_attr($menu_slug); ?>" class="nav-tab <?php echo $current_tab === 'regions' ? 'nav-tab-active' : ''; ?>">
						<?php _e('Regions', 'w4os'); ?>
					</a>
					<a href="?page=<?php echo esc_attr($menu_slug); ?>&tab=settings" class="nav-tab <?php echo $current_tab === 'settings' ? 'nav-tab-active' : ''; ?>">
						<?php _e('Settings', 'w4os'); ?>
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
                    if ( $current_tab === 'regions' ) {
                        self::display_regions_list();
                    } else {
                    ?>
                    <form method="post" action="options.php">
                        <input type="hidden" name="<?php echo esc_attr($option_name); ?>[<?php echo esc_attr($current_tab); ?>][prevent-empty-array]" value="1">
                        <?php
                            settings_fields($option_group); // Use dynamic $option_group
                            do_settings_sections($menu_slug); // Use dynamic $menu_slug
                            submit_button();
                        ?>
                    </form>
					<?php } ?>
                </div>
            </body>
        </div>
        <?php
    }

    /**
     * Display the list of Regions
     */
    public static function display_regions_list() {
        if ( ! class_exists( 'WP_List_Table' ) ) {
            require_once ABSPATH . 'wp-admin/includes/class-wp-list-table.php';
        }

        // Instantiate and display the list table
        $regionsTable = new W4OS_Region_List();
        $regionsTable->prepare_items();
        ?>
        <div class="wrap">
            <form method="post">
                <?php
                    $regionsTable->search_box( 'Search Regions', 's' ); // Add search box
                    $regionsTable->display();
                ?>
            </form>
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
        $tab = isset($args['tab']) ? sanitize_key($args['tab']) : 'settings';

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
		$options = get_option( 'w4os-region-settings', array( 'settings' => array(), 'advanced' => array() ) );
		if( ! is_array( $input ) ) {
			return $options;
		}
		
		foreach ( $input as $key => $value ) {
			// We don't want to clutter the options with temporary check values
			if(isset($value['prevent-empty-array'])) {
				unset($value['prevent-empty-array']);
			}
			$options[ $key ] = $value;
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

		register_post_meta( 'opensimulator_region', 'region_uuid', array(
			'show_in_rest' => true,
			'single' => true,
			'type' => 'string',
		) );
		register_post_meta( 'opensimulator_region', 'region_size', array(
			'show_in_rest' => true,
			'single' => true,
			'type' => 'string',
		) );
		register_post_meta( 'opensimulator_region', 'region_owner', array(
			'show_in_rest' => true,
			'single' => true,
			'type' => 'string',
		) );
		register_post_meta( 'opensimulator_region', 'region_status', array(
			'show_in_rest' => true,
			'single' => true,
			'type' => 'string',
		) );
		register_post_meta( 'opensimulator_region', 'region_type', array(
			'show_in_rest' => true,
			'single' => true,
			'type' => 'string',
		) );
		register_post_meta( 'opensimulator_region', 'region_location', array(
			'show_in_rest' => true,
			'single' => true,
			'type' => 'string',
		) );
		register_post_meta( 'opensimulator_region', 'region_ip', array(
			'show_in_rest' => true,
			'single' => true,
			'type' => 'string',
		) );
		register_post_meta( 'opensimulator_region', 'region_port', array(
			'show_in_rest' => true,
			'single' => true,
			'type' => 'string',
		) );
		register_post_meta( 'opensimulator_region', 'region_grid', array(
			'show_in_rest' => true,
			'single' => true,
			'type' => 'string',
			) );
			
		// Register 'region_enabled' meta
		register_post_meta( 'opensimulator_region', 'region_enabled', array(
			'show_in_rest' => true,
			'single'       => true,
			'type'         => 'boolean',
			'default'      => true, // Enabled by default
		) );

		// Register 'region_online' meta
		register_post_meta( 'opensimulator_region', 'region_online', array(
			'show_in_rest' => true,
			'single'       => true,
			'type'         => 'boolean',
			'default'      => false, // Offline by default
		) );
	}

    // Add custom admin columns
    public static function add_custom_columns( $columns ) {
        $new_columns = array();
        foreach ( $columns as $key => $value ) {
            $new_columns[$key] = $value;
            if ( $key === 'title' ) {
                $new_columns['region_owner'] = __( 'Owner', 'w4os' );
            }
        }
        return $new_columns;
    }

    public static function render_custom_columns( $column, $post_id ) {
        if ( $column === 'region_owner' ) {
            $owner = get_post_meta( $post_id, 'region_owner', true );
            echo esc_html( $owner );
        }
    }

	function register_fields() {
		// Register fields for the Region post type
		register_post_meta( 'opensimulator_region', 'region_uuid', array(
			'show_in_rest' => true,
			'single' => true,
			'type' => 'string',
		) );
		register_post_meta( 'opensimulator_region', 'region_size', array(
			'show_in_rest' => true,
			'single' => true,
			'type' => 'string',
		) );
		register_post_meta( 'opensimulator_region', 'region_owner', array(
			'show_in_rest' => true,
			'single' => true,
			'type' => 'string',
			// Remove 'admin_columns' parameter
			// 'admin_columns' => array(
			//     'position'   => 'after title',
			//     'sort'       => true,
			//     'searchable' => true,
			//     'filterable' => true,
			// ),
		) );
		register_post_meta( 'opensimulator_region', 'region_status', array(
			'show_in_rest' => true,
			'single' => true,
			'type' => 'string',
		) );
		register_post_meta( 'opensimulator_region', 'region_type', array(
			'show_in_rest' => true,
			'single' => true,
			'type' => 'string',
		) );
		register_post_meta( 'opensimulator_region', 'region_location', array(
			'show_in_rest' => true,
			'single' => true,
			'type' => 'string',
		) );
		register_post_meta( 'opensimulator_region', 'region_ip', array(
			'show_in_rest' => true,
			'single' => true,
			'type' => 'string',
		) );
		register_post_meta( 'opensimulator_region', 'region_port', array(
			'show_in_rest' => true,
			'single' => true,
			'type' => 'string',
		) );
		register_post_meta( 'opensimulator_region', 'region_grid', array(
			'show_in_rest' => true,
			'single' => true,
			'type' => 'string',
		) );
	}

    /**
     * Add metaboxes for Region custom fields.
     */
    public static function add_meta_boxes() {
        add_meta_box(
            'w4os_region_details',                // ID
            __( 'Region Details', 'w4os' ),      // Title
            [ __CLASS__, 'render_region_meta_box' ], // Callback
            'opensimulator_region',               // Post type
            'normal',                             // Context
            'default'                             // Priority
        );
    }

    /**
     * Render the Region Details metabox.
     */
    public static function render_region_meta_box( $post ) {
        // Add a nonce field for security
        wp_nonce_field( 'w4os_save_region_meta', 'w4os_region_meta_nonce' );

        // Retrieve existing values from the database
        $region_owner = get_post_meta( $post->ID, 'region_owner', true );
        $region_uuid = get_post_meta( $post->ID, 'region_uuid', true );
        // Add more fields as needed

        // Retrieve existing values from the database
        $region_enabled = get_post_meta( $post->ID, 'region_enabled', true );
        $region_online  = get_post_meta( $post->ID, 'region_online', true );

        ?>
        <p>
            <label for="region_owner"><?php _e( 'Owner:', 'w4os' ); ?></label>
            <input type="text" id="region_owner" name="region_owner" value="<?php echo esc_attr( $region_owner ); ?>" class="widefat" />
        </p>
        <p>
            <label for="region_uuid"><?php _e( 'UUID:', 'w4os' ); ?></label>
            <input type="text" id="region_uuid" name="region_uuid" value="<?php echo esc_attr( $region_uuid ); ?>" class="widefat" />
        </p>
        <!-- Add more fields as necessary -->
        <p>
            <label for="region_enabled"><?php _e( 'Enabled:', 'w4os' ); ?></label>
            <input type="checkbox" id="region_enabled" name="region_enabled" value="1" <?php checked( $region_enabled, 1 ); ?> />
        </p>
        <p>
            <label for="region_online"><?php _e( 'Online:', 'w4os' ); ?></label>
            <input type="checkbox" id="region_online" name="region_online" value="1" <?php checked( $region_online, 1 ); ?> disabled />
        </p>
        <!-- ...existing code... -->
        <?php
    }

    /**
     * Save the Region custom fields.
     */
    public static function save_region_meta( $post_id, $post ) {
        // Check if our nonce is set.
        if ( ! isset( $_POST['w4os_region_meta_nonce'] ) ) {
            return;
        }

        // Verify that the nonce is valid.
        if ( ! wp_verify_nonce( $_POST['w4os_region_meta_nonce'], 'w4os_save_region_meta' ) ) {
            return;
        }

        // If this is an autosave, our form has not been submitted, so we don't want to do anything.
        if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
            return;
        }

        // Check the user's permissions.
        if ( ! current_user_can( 'edit_post', $post_id ) ) {
            return;
        }

        // Sanitize and save the Region Owner
        if ( isset( $_POST['region_owner'] ) ) {
            $region_owner = sanitize_text_field( $_POST['region_owner'] );
            update_post_meta( $post_id, 'region_owner', $region_owner );
        }

        // Sanitize and save the Region UUID
        if ( isset( $_POST['region_uuid'] ) ) {
            $region_uuid = sanitize_text_field( $_POST['region_uuid'] );
            update_post_meta( $post_id, 'region_uuid', $region_uuid );
        }

        // Add more fields as necessary

        // Sanitize and save the Region Enabled
        if ( isset( $_POST['region_enabled'] ) ) {
            $region_enabled = sanitize_text_field( $_POST['region_enabled'] ) ? 1 : 0;
            update_post_meta( $post_id, 'region_enabled', $region_enabled );
        } else {
            // If checkbox is unchecked, set to 0
            update_post_meta( $post_id, 'region_enabled', 0 );
        }

        // 'region_online' is readonly and handled by another process, so we skip saving it here.
    }

    public static function set_active_menu( $parent_file ) {
        global $pagenow;

        if ( $pagenow === 'post.php' || $pagenow === 'post-new.php' ) {
            $current_screen = get_current_screen();
            if ( $current_screen->post_type === 'opensimulator_region' ) {
                $parent_file = 'w4os'; // Set to main plugin menu slug
            }
        }

        return $parent_file;
    }

    public static function set_active_submenu( $submenu_file ) {
        global $pagenow, $typenow;

        if ( $pagenow === 'post.php' || $pagenow === 'post-new.php' ) {
            if ( $typenow === 'opensimulator_region' ) {
                $submenu_file = 'w4os-region-settings'; // Set to submenu slug
            }
        }

        return $submenu_file;
    }

}

// Ensure WP_List_Table is loaded before using it
if ( ! class_exists( 'WP_List_Table' ) ) {
    require_once ABSPATH . 'wp-admin/includes/class-wp-list-table.php';
}

class W4OS_Region_List extends WP_List_Table {
    /** Class constructor */
    public function __construct() {
        parent::__construct( [
            'singular' => __( 'Region', 'w4os' ), // Singular name
            'plural'   => __( 'Regions', 'w4os' ), // Plural name
            'ajax'     => false // Disable AJAX
        ] );
    }

    /** Define the columns */
    public function get_columns() {
        return [
            'cb'           => '<input type="checkbox" />',
            'title'        => __( 'Region Name', 'w4os' ), // Renamed from 'Title' to 'Region Name'
            'region_estate' => __( 'Estate', 'w4os' ),
            'region_owner' => __( 'Owner', 'w4os' ),
            'status'       => __( 'Status', 'w4os' ),       // Added 'Status' column
            'date'         => __( 'Date', 'w4os' ),
        ];
    }

    /** Define sortable columns */
    public function get_sortable_columns() {
        return [
            'title'        => [ 'title', true ],          // Made 'Region Name' sortable
            'region_estate' => [ 'region_estate', false ],  // Made 'Owner' sortable
            'region_owner' => [ 'region_owner', false ],  // Made 'Owner' sortable
            'status'       => [ 'status', false ],        // Made 'Status' sortable
            'date'         => [ 'date', false ],
        ];
    }

    /**
     * Extra controls for the table navigation.
     * Replaces the dropdown filter with status filter links.
     */
    protected function extra_tablenav( $which ) {
        if ( $which === 'top' ) {
            $status_filters = [
                'all'      => __( 'All', 'w4os' ),
                'online'   => __( 'Online', 'w4os' ),
                'offline'  => __( 'Offline', 'w4os' ),
                'disabled' => __( 'Disabled', 'w4os' ),
            ];

            // Initialize counts
            $counts = [
                'all'      => 0,
                'online'   => 0,
                'offline'  => 0,
                'disabled' => 0,
            ];

            // Fetch counts for each status
            foreach ( $status_filters as $key => $label ) {
                if ( 'all' === $key ) {
                    $counts[$key] = wp_count_posts( 'opensimulator_region' )->publish;
                } elseif ( 'online' === $key ) {
                    $counts[$key] = (int) get_posts( [
                        'post_type'      => 'opensimulator_region',
                        'post_status'    => 'publish',
                        'meta_query'     => [
                            [
                                'key'     => 'region_enabled',
                                'value'   => '1',
                                'compare' => '=',
                            ],
                            [
                                'key'     => 'region_online',
                                'value'   => '1',
                                'compare' => '=',
                            ],
                        ],
                        'fields'         => 'ids',
                        'posts_per_page' => -1,
                    ] );
                } elseif ( 'offline' === $key ) {
                    $counts[$key] = (int) get_posts( [
                        'post_type'      => 'opensimulator_region',
                        'post_status'    => 'publish',
                        'meta_query'     => [
                            [
                                'key'     => 'region_enabled',
                                'value'   => '1',
                                'compare' => '=',
                            ],
                            [
                                'key'     => 'region_online',
                                'value'   => '0',
                                'compare' => '=',
                            ],
                        ],
                        'fields'         => 'ids',
                        'posts_per_page' => -1,
                    ] );
                } elseif ( 'disabled' === $key ) {
                    $counts[$key] = (int) get_posts( [
                        'post_type'      => 'opensimulator_region',
                        'post_status'    => 'publish',
                        'meta_query'     => [
                            [
                                'key'     => 'region_enabled',
                                'value'   => '0',
                                'compare' => '=',
                            ],
                        ],
                        'fields'         => 'ids',
                        'posts_per_page' => -1,
                    ] );
                }
            }

            // Get current filter
            $current_filter = isset( $_GET['status_filter'] ) ? sanitize_text_field( $_GET['status_filter'] ) : 'all';

            // Build filter links
            echo '<div class="alignleft actions">';
            foreach ( $status_filters as $key => $label ) {
                // Skip 'all' if no posts
                if ( 'all' === $key && $counts[$key] === 0 ) {
                    continue;
                }
                // Skip other statuses if no posts
                if ( 'all' !== $key && $counts[$key] === 0 ) {
                    continue;
                }

                $class = 'button';
                if ( $current_filter === $key ) {
                    $class .= ' button-primary';
                }

                if ( 'all' === $key ) {
                    $url = remove_query_arg( 'status_filter' );
                } else {
                    $url = add_query_arg( 'status_filter', $key );
                }

                printf(
                    '<a href="%s" class="%s">%s (%d)</a> ',
                    esc_url( $url ),
                    esc_attr( $class ),
                    esc_html( $label ),
                    $counts[$key]
                );
            }
            echo '</div>';
        }
    }

    /** Prepare the items for the table */
    public function prepare_items() {
        $columns  = $this->get_columns();
        $hidden   = [];
        $sortable = $this->get_sortable_columns();

        $this->_column_headers = [ $columns, $hidden, $sortable ];

        $query_args = [
            'post_type'      => 'opensimulator_region',
            'posts_per_page' => -1,
            'post_status'    => 'publish',
        ];

        $meta_query = [];

        // Handle search
        if ( ! empty( $_REQUEST['s'] ) ) {
            $search = sanitize_text_field( $_REQUEST['s'] );

            // Modify the search to include title, content, and meta fields
            $query_args['s'] = $search;

            // Add meta_query to search in 'region_owner' and 'region_uuid'
            $meta_query[] = [
                'relation' => 'OR',
                [
                    'key'     => 'region_owner',
                    'value'   => $search,
                    'compare' => 'LIKE',
                ],
                [
                    'key'     => 'region_uuid',
                    'value'   => $search,
                    'compare' => 'LIKE',
                ],
            ];
        }

        // Handle status filter
        if ( isset( $_GET['status_filter'] ) && in_array( $_GET['status_filter'], ['all', 'disabled', 'online', 'offline'], true ) ) {
            $status = sanitize_text_field( $_GET['status_filter'] );

            if ( 'all' === $status ) {
                // No additional meta_query needed for 'all'
            } elseif ( 'disabled' === $status ) {
                $meta_query[] = [
                    'key'     => 'region_enabled',
                    'value'   => '0',
                    'compare' => '=',
                ];
            } elseif ( 'online' === $status ) {
                $meta_query[] = [
                    'relation' => 'AND',
                    [
                        'key'     => 'region_enabled',
                        'value'   => '1',
                        'compare' => '=',
                    ],
                    [
                        'key'     => 'region_online',
                        'value'   => '1',
                        'compare' => '=',
                    ],
                ];
            } elseif ( 'offline' === $status ) {
                $meta_query[] = [
                    'relation' => 'AND',
                    [
                        'key'     => 'region_enabled',
                        'value'   => '1',
                        'compare' => '=',
                    ],
                    [
                        'key'     => 'region_online',
                        'value'   => '0',
                        'compare' => '=',
                    ],
                ];
            }
        }

        if ( ! empty( $meta_query ) ) {
            $query_args['meta_query'] = [
                'relation' => 'AND',
                ...$meta_query,
            ];
        }

        // Handle sorting
        if ( ! empty( $_REQUEST['orderby'] ) && ! empty( $_REQUEST['order'] ) ) {
            $orderby = sanitize_text_field( $_REQUEST['orderby'] );
            $order   = sanitize_text_field( $_REQUEST['order'] );

            switch ( $orderby ) {
                case 'region_owner':
                    $query_args['orderby']  = 'meta_value';
                    $query_args['meta_key'] = 'region_owner';
                    break;
				case 'region_estate':
                    $query_args['orderby']  = 'meta_value';
                    $query_args['meta_key'] = 'region_estate';
                    break;
                case 'status':
                    // Sorting by status: Disabled, Online, Offline
                    // Sort by 'region_enabled' then 'region_online'
                    $query_args['orderby']  = [
                        'region_enabled' => 'ASC',
                        'region_online'  => 'ASC',
                    ];
                    $query_args['meta_query'][] = [
                        'relation' => 'AND',
                        [
                            'key'     => 'region_enabled',
                            'type'    => 'NUMERIC',
                        ],
                        [
                            'key'     => 'region_online',
                            'type'    => 'NUMERIC',
                        ],
                    ];
                    break;
                default:
                    $query_args['orderby'] = $orderby;
            }

            $query_args['order'] = strtoupper( $order ) === 'DESC' ? 'DESC' : 'ASC';
        }

        $query = new WP_Query( $query_args );
        $this->items = $query->posts;

        // Set pagination if needed
        // $this->set_pagination_args( [
        //     'total_items' => $query->found_posts,
        //     'per_page'    => $this->get_items_per_page( 'regions_per_page', 20 ),
        // ] );
    }

    /** Render a column when no specific column handler is provided */
    public function column_default( $item, $column_name ) {
        switch ( $column_name ) {
            case 'title':
                $edit_link = get_edit_post_link( $item->ID );
                return '<a href="' . esc_url( $edit_link ) . '">' . esc_html( $item->post_title ) . '</a>';
			case 'region_estate':
				$owner = get_post_meta( $item->ID, 'region_estate', true );
				return esc_html( $owner );
            case 'region_owner':
                $owner = get_post_meta( $item->ID, 'region_owner', true );
                return esc_html( $owner );
            case 'status':
                $enabled = get_post_meta( $item->ID, 'region_enabled', true );
                $online  = get_post_meta( $item->ID, 'region_online', true );

                if ( ! $enabled ) {
                    return __( 'Disabled', 'w4os' );
                } elseif ( $online ) {
                    return __( 'Online', 'w4os' );
                } else {
                    return __( 'Offline', 'w4os' );
                }
            case 'date':
                return esc_html( get_the_date( '', $item ) );
            default:
                return print_r( $item, true ); // Show the whole object for troubleshooting
        }
    }

    /** Render the bulk actions dropdown */
    protected function bulk_actions( $which = '' ) {
        if ( $which === 'top' || $which === 'bottom' ) {
            ?>
            <label class="screen-reader-text" for="bulk-action-selector-<?php echo $which; ?>"><?php _e( 'Select bulk action', 'w4os' ); ?></label>
            <select name="action" id="bulk-action-selector-<?php echo $which; ?>">
                <option value=""><?php _e( 'Bulk Actions', 'w4os' ); ?></option>
                <option value="delete"><?php _e( 'Delete', 'w4os' ); ?></option>
                <!-- Add more bulk actions if needed -->
            </select>
            <?php
            submit_button( __( 'Apply', 'w4os' ), 'button', 'submit', false );
        }
    }

    /** Render the checkbox column */
    function column_cb( $item ) {
        return sprintf(
            '<input type="checkbox" name="region[]" value="%s" />', $item->ID
        );
    }
}
