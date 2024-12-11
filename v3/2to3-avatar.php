<?php
/**
 * This is a test class to finetune menu integration.
 * - Create a Settings page for Avatars-specific settings, as a submenu of the main 'w4os' menu
 * - We don't care about the main menu here, it is defined in another file.
 * - The rendering is made efficiently, by W4OS3_Settings::render_settings_page()
 * - We don't include html code of the pages here, only the settings registration.
 * - The header and content are managed by the render_settings_page() method.
 */

## UserAccounts table fields:

// Field	Type	Collation	Attributes	Null	Default	Extra
// PrincipalID	char(36)	utf8_general_ci		No	None	
// ScopeID	char(36)	utf8_general_ci		No	00000000-0000-0000-0000-000000000000	
// FirstName	varchar(64)	utf8_general_ci		No	None	
// LastName	varchar(64)	utf8_general_ci		No	None	
// Email	varchar(64)	utf8_general_ci		Yes	NULL	
// ServiceURLs	text	utf8_general_ci		Yes	NULL	
// Created	int(11)			Yes	NULL	
// UserLevel	int(11)			No	0	
// UserFlags	int(11)			No	0	
// UserTitle	varchar(64)	utf8_general_ci		No		
// active	int(11)			No	1	


class W4OS3_Avatar {
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
	 * Add submenu for Avatar settings page
	 */
	public function add_submenus() {
        W4OS3::add_submenu_page(
            'w4os',                         
            __( 'Avatars', 'w4os' ),
            __( 'Avatars', 'w4os' ),
            'manage_options',
            'w4os-avatar',
            [ $this, 'render_settings_page' ],
            3,
        );
    }

	static function add_menu_tabs( $tabs ) {
		$tabs['w4os-avatar'] = array(
			'avatars'  => array(
				'title' => __('List', 'w4os'),
				// 'url'   => admin_url('admin.php?page=w4os-avatar')
			),
			'settings' => array(
				'title' => __('Avatar Settings', 'w4os'),
				// 'url'   => admin_url('admin.php?page=w4os-avatar&tab=settings')
			),
			// 'models' => array(
			// 	'title' => __('Avatar Models', 'w4os'),
			// 	'url'   => admin_url('admin.php?page=w4os-models')
			// ),
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

        $option_name = 'w4os-avatar'; // Hard-coded here is fine to make sure it matches intended submenu slug
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
                'create_wp_account', 
				__('Create WP accounts', 'w4os'), // title
                [ __CLASS__, 'render_settings_field' ],
                $option_name, // Use dynamic option name as menu slug
                $section,
                array(
					'id'	=> 'create_wp_account',
					'type' => 'checkbox',
					'label' => __('Create website accounts for avatars.', 'w4os'),
					'description' => __('This will create a WordPress account for avatars that do not have one. The password will synced between site and OpenSimulator.', 'w4os'),
					'option_name' => $option_name, // Pass option name
				)
            );

			add_settings_field(
				'allow_multiple_avatars',
				__('Allow multiple avatars', 'w4os'),
				[ __CLASS__, 'render_settings_field' ],
				$option_name,
				$section,
				array(
					'id' => 'allow_multiple_avatars',
					'type' => 'checkbox',
					'label' => __('Allow users to create multiple avatars.', 'w4os'),
					'description' => __('This will allow users to have more than one avatar on the site.', 'w4os')
					. ' ' . __( 'Disabling the option can only be enforeced for avatars created through the website.', 'w4os' ),
					'option_name' => $option_name,
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
        $current_tab = isset($_GET['tab']) ? sanitize_text_field($_GET['tab']) : 'avatars';
        $current_section = $option_group . '_section_' . $current_tab;

		$tabs = apply_filters( 'w4os_settings_tabs', array() );
		$page_tabs = isset($tabs[$menu_slug]) ? $tabs[$menu_slug] : array();
		error_log( 'Page tabs: ' . print_r( $page_tabs, true ) );
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
        ?>
        <div class="wrap w4os">
            <header>
                <h1><?php echo $page_title; ?></h1>
                <?php echo isset($action_links_html) ? $action_links_html : ''; ?>
                <!-- echo $tabs_navigation; -->
                <!-- <h2 class="nav-tab-wrapper"> -->
					<?php
					echo W4OS3_Settings::get_tabs_html();
					//  echo $tabs_navigation; 
					?>
				</h2>
            </header>
            <?php settings_errors($menu_slug); ?>
            <body>
                <div class="wrap <?php echo esc_attr($menu_slug); ?>">
                    <?php
                    if ( $current_tab === 'avatars' ) {
                        $this->display_avatars_list();
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
     * Display the list of Avatars from the custom database.
     */
    public function display_avatars_list() {
        // if ( ! class_exists( 'WP_List_Table' ) ) {
        //     require_once ABSPATH . 'wp-admin/includes/class-wp-list-table.php';
        // }

        // Instantiate and display the list table

        $avatarsTable = new W4OS_List_Table( $this->db, 'avatars', [
			'singular' => 'Avatar',
			'plural'   => 'Avatars',
			'ajax'     => false,
			'table'	=> 'UserAccounts',
			'query' => "SELECT * FROM (
				SELECT *, CONCAT(FirstName, ' ', LastName) AS avatarName 
				FROM UserAccounts 
				LEFT JOIN userprofile ON PrincipalID = userUUID 
				LEFT JOIN GridUser ON PrincipalID = UserID
			) AS subquery",
			'admin_columns' => array(
				'avatarName' => array(
					'title' => __( 'Avatar Name', 'w4os' ),
					'sortable' => true, // optional, defaults to false
					// 'sort_column' => 'avatarName', // optional, defaults to column key, use 'callback' to use render_callback value
					'order' => 'ASC', // optional, defaults to 'ASC'
					'searchable' => true, // optional, defaults to false
					// 'search_column' => 'avatarName', // optional, defaults to column key, use 'callback' to use render_callback value
					'filterable' => true, // optional, defaults to false, enable action links filter
					// 'render_callback' => [ $this, 'avatar_name_column' ], // optional, defaults to 'column_' . $key
					'size' => null, // optional, defaults to null (auto)
				),
				'Email' => array(
					'title' => __( 'Email', 'w4os' ),
					// 'type' => 'email',
					'sortable' => true,
					'searchable' => true,
					'filterable' => true,
					// 'size' => '20%',
				),
				'avatar_type' => array(
					'title' => __( 'Type', 'w4os' ),
					'render_callback' => [ $this, 'avatar_type' ],
					'sortable' => true,
					'sort_column' => 'callback',
					'size' => '10%',
					'views' => 'callback',
				),
				'active' => array(
					'title' => __( 'Active', 'w4os' ),
					'type' => 'boolean',
					'render_callback' => [ $this, 'active_column' ],
					'sortable' => true,
					'sort_column' => 'callback',
					'size' => '8%',
					'views' => 'callback',
				),
				'Online' => array(
					'title' => __( 'Online', 'w4os' ),
					'type' => 'boolean',
					'render_callback' => [ $this, 'online_status' ],
					'sortable' => true,
					'sort_column' => 'callback',
					'size' => '8%',
					'views' => 'callback', // Add subsubsub links based on the rendered value
				),
				'Login' => array(
					'title' => __( 'Last Seen', 'w4os' ),
					'type' => 'date',
					'render_callback' => [ $this, 'Login' ],
					'size' => '10%',
					'sortable' => true,
					'order' => 'DESC',
				),
				'Created' => array(
					'title' => __( 'Created', 'w4os' ),
					'type' => 'date',
					'size' => '10%',
					'sortable' => true,
				),
			),
		] );
        $avatarsTable->prepare_items();
		$avatarsTable->styles();
        ?>

		<?php $avatarsTable->views(); ?>
		<div class="wrap w4os-list w4os-list-avatars">
            <form method="post">
                <?php
                    $avatarsTable->search_box( 'Search Avatars', 's' ); // Add search box
                    $avatarsTable->display();
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
		$options = get_option( 'w4os-avatar', array( 'settings' => array(), 'advanced' => array() ) );
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

	public static function get_name( $item ) {
		if( is_object( $item ) ) {
			$uuid = $item->PrincipalID;
			if( isset( $item->avatarName ) ) {
				return trim( $avatarName = $item->avatarName );
			} else if ( isset( $item->FirstName ) && isset( $item->LastName ) ) {
				return trim( $item->FirstName . ' ' . $item->LastName );
			}
			return __('Invalid Avatar Object', 'w4os');
		} else if ( opensim_isuuid( $item ) ) {
			$uuid = $item;
			global $w4osdb;
			$query = "SELECT CONCAT(FirstName, ' ', LastName) AS Name FROM UserAccounts WHERE PrincipalID = %s";
			$result = $w4osdb->get_var( $w4osdb->prepare( $query, $uuid ) );
			if( $result && ! is_wp_error( $result ) ) {
				return esc_html( $result );
			}
		}
		return __('Unknown Avatar', 'w4os');
	}

	/**
	 * Avatar type
	 */
	public function avatar_type( $item ) {
		$models = W4OS3_Model::get_models();
		$email = $item->Email;
		if ( W4OS3_Model::is_model( $item ) ) {
			return __('Default Model', 'w4os');
		}
		if(empty($email)) {
			return __('Technical Account', 'w4os');
		}
		return __('User Avatar', 'w4os');
	}

	/**
	 * Format the active column.
	 */
	public function active_column( $item ) {
		$active = intval( $item->active );
		if( $active === 1 ) {
			return 'Active';
		}
		return 'Inactive';
	}
	
	/**
	 * Format the online column.
	 */
	public function online_status( $item ) {
		if (empty($item->Online)) {
			return '';
		}
		return W4OS3::is_true($item->Online) ? 'Online' : 'Offline';
	}

	/**
	 * Format Avatar hop URL for list table.
	 */
	public function avatar_tp_link( $item ) {
		// error_log( 'Avatar hop URL callback ' . print_r( $item, true ) );
		$avatarName = $item->avatarName;
		$gateway = get_option( 'w4os_login_uri' );
		if( empty( $gateway ) ) {
			return __( 'Gateway not set', 'w4os' );
		}
		// Strip protocol from $gateway
		$gateway = trailingslashit( preg_replace( '/^https?:\/\//', '', $gateway ) );
		$string = trim($gateway . $avatarName);
		$link = w4os_hop( $gateway . $avatarName, $string );
		return $link;
	}

	/**
	 * Format the last seen date.
	 */
	public function last_seen( $item ) {
		$last_seen = intval( $item->last_seen );
		if( $last_seen === 0 ) {
			return 'Never';
		}
		$last_seen = W4OS3::date( $last_seen );
		return esc_html( $last_seen );
	}

	/**
	 * Format the server URI column in a lighter way.
	 */
	public function server_uri( $item ) {
		$server_uri = $item->serverURI;
		if( empty( $server_uri ) ) {
			return;
		}
		$server_uri = untrailingslashit( $server_uri );
		$server_uri = preg_replace( '/^https?:\/\//', '', $server_uri );

		return esc_html( $server_uri );
	}
}
