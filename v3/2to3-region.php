<?php
/**
 * This is a test class to finetune menu integration.
 * - Create a Settings page for Regions-specific settings, as a submenu of the main 'w4os' menu
 * - We don't care about the main menu here, it is defined in another file.
 * - The rendering is made efficiently, by W4OS3_Settings::render_settings_page()
 * - We don't include html code of the pages here, only the settings registration.
 * - The header and content are managed by the render_settings_page() method.
 */

## Region table fields:

// Field	Type	Null	Key	Default	Extra
// uuid	varchar(36)	NO	PRI	NULL	 
// regionHandle	bigint(20) unsigned	NO	MUL	NULL	
// regionName	varchar(32)	YES	MUL	NULL	
// regionRecvKey	varchar(128)	YES		NULL	
// regionSendKey	varchar(128)	YES		NULL	
// regionSecret	varchar(128)	YES		NULL	
// regionDataURI	varchar(255)	YES		NULL	
// serverIP	varchar(64)	YES		NULL	
// serverPort	int(10) unsigned	YES		NULL	
// serverURI	varchar(255)	YES		NULL	
// locX	int(10) unsigned	YES		NULL	
// locY	int(10) unsigned	YES		NULL	
// locZ	int(10) unsigned	YES		NULL	
// eastOverrideHandle	bigint(20) unsigned	YES	MUL	NULL	
// westOverrideHandle	bigint(20) unsigned	YES		NULL	
// southOverrideHandle	bigint(20) unsigned	YES		NULL	
// northOverrideHandle	bigint(20) unsigned	YES		NULL	
// regionAssetURI	varchar(255)	YES		NULL	
// regionAssetRecvKey	varchar(128)	YES	PRI	NULL	
// regionAssetSendKey	varchar(128)	YES	PRI	NULL	
// regionUserURI	varchar(255)	YES		NULL	
// regionUserRecvKey	varchar(128)	YES		NULL	
// regionUserSendKey	varchar(128)	YES		NULL	
// regionMapTexture	varchar(36)	YES		NULL	
// serverHttpPort	int(10)	YES		NULL	
// serverRemotingPort	int(10)	YES		NULL	
// owner_uuid	varchar(36)	NO		00000000-0000-0000-0000-000000000000	
// originUUID	varchar(36)	YES		NULL	
// access	int(10) unsigned	YES		1	
// ScopeID	char(36)	NO		00000000-0000-0000-0000-000000000000	
// sizeX	int(11)	NO		0	
// sizeY	int(11)	NO		0	
// flags	int(11)	NO		0	
// last_seen	int(11)	NO		0	
// PrincipalID	char(36)	NO		00000000-0000-0000-0000-000000000000	
// Token	varchar(255)	NO		None	
// parcelMapTexture	varchar(36)	YES		NULL	


class W4OS3_Region {
	private $db;

    public function __construct() {
        // Initialize the custom database connection with credentials
        $this->db = new W4OS_WPDB( W4OS_DB_ROBUST );
    }

    /**
     * Initialize the class. Register actions and filters.
     */
    public function init() {
        add_action( 'admin_init', [ __CLASS__, 'register_settings_page' ] );
        add_action( 'admin_menu', [ $this, 'add_submenus' ] );

		add_filter ( 'w4os_settings_tabs', [ __CLASS__, 'add_menu_tabs' ] );


        // add_filter( 'parent_file', [ __CLASS__, 'set_active_menu' ] );
        // add_filter( 'submenu_file', [ __CLASS__, 'set_active_submenu' ] );

    }

    /**
	 * Add submenu for Region settings page
	 */
	public function add_submenus() {
        W4OS3::add_submenu_page(
            'w4os',                         
            __( 'Regions', 'w4os' ),
            __( 'Regions', 'w4os' ),
            'manage_options',
            'w4os-region',
            [ $this, 'render_settings_page' ],
            3,
        );
    }

	static function add_menu_tabs( $tabs ) {
		$tabs['w4os-region'] = array(
			'regions'  => __( 'List', 'w4os' ), // Added 'Regions' tab
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

        $option_name = 'w4os-region'; // Hard-coded here is fine to make sure it matches intended submenu slug
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
	public function render_settings_page() {
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
						<?php _e('List', 'w4os'); ?>
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
                        $this->display_regions_list();
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
     * Display the list of Regions from the custom database.
     */
    public function display_regions_list() {
        // if ( ! class_exists( 'WP_List_Table' ) ) {
        //     require_once ABSPATH . 'wp-admin/includes/class-wp-list-table.php';
        // }

        // Instantiate and display the list table
        $regionsTable = new W4OS_List_Table( $this->db, 'regions', [
			'singular' => 'Region',
			'plural'   => 'Regions',
			'menu'     => 'Regions',
			'columns' => array(
				'regionName'  => __( 'Region Name', 'w4os' ), // Renamed from 'Title' to 'Region Name'
				'owner_uuid'        => __( 'Owner', 'w4os' ),
				'status'       => __( 'Status', 'w4os' ),       // Added 'Status' column
				'last_seen' => __( 'Last Seen', 'w4os' ),
			),
			'sortable' => [
				'regionName'  => [ 'regionName', true ],
				'owner_uuid'        => [ 'owner_uuid', false ],
				'status'       => [ 'status', false ],
				'last_seen' => [ 'last_seen', false ],
			],
		] );
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
	 * General class for field sanitization. Used by different classes to save settings from different settings pages.
	 * 
	 * This method should be agnostic, it will be moved in another class later and used by different settings pages.
	 */
	public static function sanitize_options( $input ) {
		
		// Initialize the output array with existing options
		$options = get_option( 'w4os-region', array( 'settings' => array(), 'advanced' => array() ) );
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

}

// Ensure WP_List_Table is loaded before using it
add_action( 'admin_menu', function() {
	/**
	 * - Extend WP_List_Table class for custom post-types
	 * - use parameters from registered post_type and registered meta fields
	 * - create a submenu to combined list/settings page with tabs. Default tab shows the list.
	 * - disable default edit.php access for the custom post type.
	 * 
	 * This class should be post-type agnostic, so it can be used for any custom post type.
	 */
	class W4OS_List_Table extends WP_List_Table {
		private $db;
		private $columns;
		private $sortable;

		/** Class constructor */
		public function __construct( $db, $table, $args ) {
			$args = WP_parse_args( $args, [
				'singular' => 'Item',
				'plural'   => 'Items',
				'ajax'     => false // Disable AJAX
			] );
			$this->columns = $args['columns'];
			error_log( 'columns: ' . print_r( $args['columns'], true ) );
			$this->sortable = empty($args['sortable']) ? [] : $args['sortable'];
			error_log( 'sortable: ' . print_r( $this->sortable, true ) );
			parent::__construct( $this->labels );

			// Initialize database connection with credentials
			$this->db = new W4OS_WPDB( W4OS_DB_ROBUST );
		}

		/** Define the columns */
		public function get_columns() {
			$columns = WP_parse_args( $this->columns, [
				'cb'           => '<input type="checkbox" />',
			] );
			return $columns;
		}

		/** Define sortable columns */
		public function get_sortable_columns() {


			return [
				'regionName'  => [ 'regionName', true ],          // Made 'Region Name' sortable
				'owner_uuid'        => [ 'owner_uuid', false ],  // Made 'Owner' sortable
				'status'       => [ 'status', false ],        // Made 'Status' sortable
				'last_seen' => [ 'last_seen', false ],
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

			$query = "SELECT * FROM regions"; // Replace 'regions' with your actual table name

			// Handle search
			if ( ! empty( $_REQUEST['s'] ) ) {
				$search = $this->db->esc_like( $_REQUEST['s'] );
				$query  .= $this->db->prepare( " WHERE regionName LIKE %s OR owner_uuid LIKE %s", '%' . $search . '%', '%' . $search . '%' );
			}

			// Handle sorting
			if ( ! empty( $_REQUEST['orderby'] ) && ! empty( $_REQUEST['order'] ) ) {
				$orderby = sanitize_text_field( $_REQUEST['orderby'] );
				$order   = sanitize_text_field( $_REQUEST['order'] ) === 'desc' ? 'DESC' : 'ASC';
				$allowed_orderbys = [ 'regionName', 'owner_uuid', 'status', 'last_seen' ];

				if ( in_array( $orderby, $allowed_orderbys, true ) ) {
					$query .= " ORDER BY {$orderby} {$order}";
				}
			}

			$results = $this->db->get_results( $query );

			$this->items = $results;
		}

		/** Render a column when no specific column handler is provided */
		public function column_default( $item, $column_name ) {
			switch ( $column_name ) {
				case 'regionName':
					return esc_html( $item->regionName );
				case 'owner_uuid':
					return esc_html( $item->owner_uuid );
				case 'status':
					return esc_html( ucfirst( $item->status ) );
				case 'last_seen':
					return esc_html( $item->last_seen );
				default:
					return print_r( $item, true ); // Show the whole object for troubleshooting
			}
		}

		/** Render the bulk actions dropdown */
		protected function bulk_actions( $which = '' ) {
			// Not implemented yet, keep for future reference
			if ( $which === 'top' || $which === 'bottom' ) {
				?>
				<label class="screen-reader-text" for="bulk-action-selector-<?php echo $which; ?>"><?php _e( 'Select bulk action', 'w4os' ); ?></label>
				<select name="action" id="bulk-action-selector-<?php echo "$which"; ?>" disabled>
					<option value=""><?php _e( 'Bulk Actions', 'w4os' ); ?></option>
					<option value="start"><?php _e( 'Start', 'w4os' ); ?></option>
					<option value="restart"><?php _e( 'Restart', 'w4os' ); ?></option>
					<option value="stop"><?php _e( 'Stop', 'w4os' ); ?></option>
					<option value="disable"><?php _e( 'Disable', 'w4os' ); ?></option>
				</select>
				<?php
				submit_button( __( 'Apply', 'w4os' ), 'button', 'submit', false, array( 'disabled' => "1" ) );
			}
		}

		/** Render the checkbox column */
		function column_cb( $item ) {
			return sprintf(
				'<input type="checkbox" name="region[]" value="%s" />', $item->ID
			);
		}

		/** Process bulk actions */
		protected function process_bulk_action() {
			if ( 'delete' === $this->current_action() ) {
				// Bulk delete regions
				if ( isset( $_POST['region'] ) && is_array( $_POST['region'] ) ) {
					foreach ( $_POST['region'] as $region_id ) {
						$this->db->delete( 'regions', [ 'id' => intval( $region_id ) ], [ '%d' ] );
					}
				}
			}
		}
	}
});
