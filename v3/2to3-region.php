<?php
/**
 * This is a test class to finetune menu integration.
 * - Create a Settings page for Regions-specific settings, as a submenu of the main 'w4os' menu
 * - We don't care about the main menu here, it is defined in another file.
 * - The rendering is made efficiently, by W4OS3_Settings::render_settings_page()
 * - We don't include html code of the pages here, only the settings registration.
 * - The header and content are managed by the render_settings_page() method.
 */

// Region table fields:

// Field    Type    Null    Key Default Extra
// uuid varchar(36) NO  PRI NULL
// regionHandle bigint(20) unsigned NO  MUL NULL
// regionName   varchar(32) YES MUL NULL
// regionRecvKey    varchar(128)    YES     NULL
// regionSendKey    varchar(128)    YES     NULL
// regionSecret varchar(128)    YES     NULL
// regionDataURI    varchar(255)    YES     NULL
// serverIP varchar(64) YES     NULL
// serverPort   int(10) unsigned    YES     NULL
// serverURI    varchar(255)    YES     NULL
// locX int(10) unsigned    YES     NULL
// locY int(10) unsigned    YES     NULL
// locZ int(10) unsigned    YES     NULL
// eastOverrideHandle   bigint(20) unsigned YES MUL NULL
// westOverrideHandle   bigint(20) unsigned YES     NULL
// southOverrideHandle  bigint(20) unsigned YES     NULL
// northOverrideHandle  bigint(20) unsigned YES     NULL
// regionAssetURI   varchar(255)    YES     NULL
// regionAssetRecvKey   varchar(128)    YES PRI NULL
// regionAssetSendKey   varchar(128)    YES PRI NULL
// regionUserURI    varchar(255)    YES     NULL
// regionUserRecvKey    varchar(128)    YES     NULL
// regionUserSendKey    varchar(128)    YES     NULL
// regionMapTexture varchar(36) YES     NULL
// serverHttpPort   int(10) YES     NULL
// serverRemotingPort   int(10) YES     NULL
// owner_uuid   varchar(36) NO      00000000-0000-0000-0000-000000000000
// originUUID   varchar(36) YES     NULL
// access   int(10) unsigned    YES     1
// ScopeID  char(36)    NO      00000000-0000-0000-0000-000000000000
// sizeX    int(11) NO      0
// sizeY    int(11) NO      0
// flags    int(11) NO      0
// last_seen    int(11) NO      0
// PrincipalID  char(36)    NO      00000000-0000-0000-0000-000000000000
// Token    varchar(255)    NO      None
// parcelMapTexture varchar(36) YES     NULL


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
		add_action( 'admin_init', array( __CLASS__, 'register_settings' ) );
		add_action( 'admin_menu', array( $this, 'add_submenus' ) );

		add_filter( 'w4os_settings_tabs', array( __CLASS__, 'register_tabs' ) );

		// add_filter( 'parent_file', [ __CLASS__, 'set_active_menu' ] );
		// add_filter( 'submenu_file', [ __CLASS__, 'set_active_submenu' ] );
	}

	/**
	 * Add submenu for Region settings page
	 */
	public function add_submenus() {
		W4OS3::add_submenu_page(
			'w4os',
			__( 'Regions', 'w4os' ) . ' (dev)',
			'(dev) ' . __( 'Regions', 'w4os' ),
			'manage_options',
			'w4os-regions',
			array( $this, 'render_settings_page' ),
			3,
		);
	}

	static function register_tabs( $tabs ) {
		$tabs['w4os-regions'] = array(
			'regions'  => __( 'List', 'w4os' ), // Added 'Regions' tab
			'settings' => __( 'Settings', 'w4os' ),
			'advanced' => __( 'Advanced', 'w4os' ),
		);
		return $tabs;
	}

	/**
	 * Register settings using the Settings API, templates and the method W4OS3_Settings::render_settings_section().
	 */
	public static function register_settings() {
		if ( ! W4OS_ENABLE_V3 ) {
			return;
		}

		$option_name  = 'w4os-regions'; // Hard-coded here is fine to make sure it matches intended submenu slug
		$option_group = $option_name . '_group';

		// Register the main option with a sanitize callback
		register_setting( $option_group, $option_name, array( __CLASS__, 'sanitize_options' ) );

		// Get the current tab
		$tab     = isset( $_GET['tab'] ) ? $_GET['tab'] : 'settings';
		$section = $option_group . '_section_' . $tab;

		// Add settings sections and fields based on the current tab
		if ( $tab == 'settings' ) {
			// Add default section for the current tab
			add_settings_section(
				$section,
				null, // No title for the section
				null, // [ __CLASS__, 'section_callback' ],
				$option_name // Use dynamic option name
			);

			$fields = array(
				array(
					'id'          => 'w4os_settings_region_settings_field_1',
					'name'        => __( 'First Tab Field 1', 'w4os' ),
					'type'        => 'checkbox',
					'label'       => __( 'Enable settings option 1.', 'w4os' ),
					'description' => __( 'This is a placeholder parameter.', 'w4os' ),
				),
				array(
					'id'          => 'w4os_settings_region_settings_field_2',
					'name'        => __( 'First Tab Field 2', 'w4os' ),
					'type'        => 'checkbox',
					'label'       => __( 'Enable settings option 2.', 'w4os' ),
					'description' => __( 'This is a placeholder parameter.', 'w4os' ),
				),
			);
		} elseif ( $tab == 'advanced' ) {
			// Add default section for the current tab
			add_settings_section(
				$section,
				null, // No title for the section
				null, // [ __CLASS__, 'section_callback' ],
				$option_name // Use dynamic option name
			);

			$fields = array(
				array(
					'id'          => 'w4os_settings_region_advanced_field_1',
					'name'        => __( 'Advanced Tab Field 1', 'w4os' ),
					'type'        => 'checkbox',
					'label'       => __( 'Enable advanced option 1.', 'w4os' ),
					'description' => __( 'This is a placeholder parameter.', 'w4os' ),
				),
				array(
					'id'          => 'w4os_settings_region_advanced_field_2',
					'name'        => __( 'Advanced Tab Field 2', 'w4os' ),
					'type'        => 'checkbox',
					'label'       => __( 'Enable advanced option 2.', 'w4os' ),
					'description' => __( 'This is a placeholder parameter.', 'w4os' ),
				),
			);
		}

		if ( empty( $fields ) ) {
			return;
		}
		foreach ( $fields as $field ) {
			$field_id = $field['id'];
			$field    = wp_parse_args(
				$field,
				array(
					'option_name' => $option_name,
					'tab'         => $tab,
				// 'label_for'   => $field_id,
				)
			);
			$field['option_name'] = $option_name;
			add_settings_field(
				$field_id,
				$field['name'] ?? '',
				'W4OS3_Settings::render_settings_field',
				$option_name, // Use dynamic option name as menu slug
				$section,
				$field,
			);
		}
	}

	public static function sanitize_options( $input ) {

		// Initialize the output array with existing options
		$options = get_option( 'w4os-regions', array() );
		if ( ! is_array( $input ) ) {
			return $options;
		}

		foreach ( $input as $key => $value ) {
			// We don't want to clutter the options with temporary check values
			if ( isset( $value['prevent-empty-array'] ) ) {
				unset( $value['prevent-empty-array'] );
			}
			$options[ $key ] = $value;
		}

		return $options;
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
		if ( ! $screen || ! isset( $screen->id ) ) {
			w4os_admin_notice( 'This page is not available. You probably did nothing wrong, the developer did.', 'error' );
			// End processing page, display pending admin notices and return.
			do_action( 'admin_notices' );
			return;
		}

		$menu_slug    = preg_replace( '/^.*_page_/', '', sanitize_key( get_current_screen()->id ) );
		$option_name  = isset( $args[0]['option_name'] )
		? sanitize_key( $args[0]['option_name'] )
		: sanitize_key( $menu_slug ); // no need to add settings suffix, it's already in menu slug by convention
		$option_group = isset( $args[0]['option_group'] )
		? sanitize_key( $args[0]['option_group'] )
		: sanitize_key( $menu_slug . '_group' );

		$page_title      = esc_html( get_admin_page_title() );
		$current_tab     = isset( $_GET['tab'] ) ? sanitize_text_field( $_GET['tab'] ) : 'regions';
		$current_section = $option_group . '_section_' . $current_tab;

		?>
		<div class="wrap w4os">
			<header>
				<h1><?php echo $page_title; ?></h1>
				<?php echo isset( $action_links_html ) ? $action_links_html : ''; ?>
				<!-- echo $tabs_navigation; -->
				<h2 class="nav-tab-wrapper">
					<a href="?page=<?php echo esc_attr( $menu_slug ); ?>" class="nav-tab <?php echo $current_tab === 'regions' ? 'nav-tab-active' : ''; ?>">
						<?php _e( 'List', 'w4os' ); ?>
					</a>
					<a href="?page=<?php echo esc_attr( $menu_slug ); ?>&tab=settings" class="nav-tab <?php echo $current_tab === 'settings' ? 'nav-tab-active' : ''; ?>">
						<?php _e( 'Settings', 'w4os' ); ?>
					</a>
					<a href="?page=<?php echo esc_attr( $menu_slug ); ?>&tab=advanced" class="nav-tab <?php echo $current_tab === 'advanced' ? 'nav-tab-active' : ''; ?>">
						<?php _e( 'Advanced', 'w4os' ); ?>
					</a>
				</h2>
			</header>
			<?php settings_errors( $menu_slug ); ?>
			<body>
				<div class="wrap <?php echo esc_attr( $menu_slug ); ?>">
					<?php
					if ( $current_tab === 'regions' ) {
						$this->display_regions_list();
					} else {
						?>
					<form method="post" action="options.php">
						<input type="hidden" name="<?php echo esc_attr( $option_name ); ?>[<?php echo esc_attr( $current_tab ); ?>][prevent-empty-array]" value="1">
						<?php
							settings_fields( $option_group ); // Use dynamic $option_group
							do_settings_sections( $menu_slug ); // Use dynamic $menu_slug
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
		// require_once ABSPATH . 'wp-admin/includes/class-wp-list-table.php';
		// }

		// Instantiate and display the list table

		$regionsTable = new W4OS_List_Table(
			$this->db,
			'regions',
			array(
				'singular'      => 'Region',
				'plural'        => 'Regions',
				'ajax'          => false,
				'query'         => "SELECT * FROM (
				SELECT regions.*, CONCAT(UserAccounts.FirstName, ' ', UserAccounts.LastName) AS owner_name
				FROM `regions`
				LEFT JOIN UserAccounts ON regions.owner_uuid = UserAccounts.PrincipalID
			) AS subquery",
				'admin_columns' => array(
					'regionName'    => array(
						'title'           => __( 'Region Name', 'w4os' ),
						'sortable'        => true, // optional, defaults to false
						'sort_column'     => 'regionName', // optional, defaults to column key, use 'callback' to use render_callback value
						'order'           => 'ASC', // optional, defaults to 'ASC'
						'searchable'      => true, // optional, defaults to false
						'search_column'   => 'regionName', // optional, defaults to column key, use 'callback' to use render_callback value
						'filterable'      => true, // optional, defaults to false, enable action links filter
						'render_callback' => array( $this, 'region_name_column' ), // optional, defaults to 'column_' . $key
						'size'            => null, // optional, defaults to null (auto)
					),
					'owner_name'    => array(
						'title'      => __( 'Owner', 'w4os' ),
						'sortable'   => true,
						'searchable' => true,
						'order'      => 'ASC',
						'filterable' => true,
					),
					'teleport_link' => array(
						'title'           => __( 'Teleport', 'w4os' ),
						'render_callback' => array( $this, 'region_tp_link' ),
					),
					'serverURI'     => array(
						'title'           => __( 'Simulator URI', 'w4os' ),
						'render_callback' => array( $this, 'server_uri' ),
						'size'            => '10%',
					),
					'serverPort'    => array(
						'title'           => __( 'Internal Port', 'w4os' ),
						'render_callback' => array( $this, 'server_port_column' ),
						'size'            => '8%',
					),
					'status'        => array(
						'title'           => __( 'Status', 'w4os' ),
						'render_callback' => array( $this, 'region_status' ),
						'sortable'        => true,
						'sort_column'     => 'callback',
						'size'            => '8%',
						'views'           => 'callback', // Add subsubsub links based on the rendered value
					),
					'last_seen'     => array(
						'title'           => __( 'Last Activity', 'w4os' ),
						'render_callback' => array( $this, 'last_seen' ),
						'size'            => '10%',
						'sortable'        => true,
						'order'           => 'DESC',
					),
				),
			)
		);
		$regionsTable->prepare_items();
		$regionsTable->styles();
		?>

		<?php $regionsTable->views(); ?>
		<div class="wrap w4os-list w4os-list-regions">
			<form method="post">
				<?php
					$regionsTable->search_box( 'Search Regions', 's' ); // Add search box
					$regionsTable->display();
				?>
			</form>
		</div>
		<?php
	}

	public function owner_name( $item ) {
		$uuid = $item->owner_uuid;
		if ( ! $this->db ) {
			return "not found ($uuid)";
		}
		$query  = "SELECT CONCAT(FirstName, ' ', LastName) AS Name FROM UserAccounts WHERE PrincipalID = %s";
		$result = $this->db->get_var( $this->db->prepare( $query, $uuid ) );
		return esc_html( $result );
	}

	/**
	 * Check if Region is online by trying to connect to the server URI.
	 */
	public function region_status( $item ) {
		$server_uri = $item->serverURI;
		if ( empty( $server_uri ) ) {
			return 'Unknown';
		}
		$server_uri = esc_url( $server_uri );
		$server_uri = trailingslashit( $server_uri );
		$server_uri = set_url_scheme( $server_uri, 'http' );
		$response   = wp_remote_get( $server_uri );
		if ( is_wp_error( $response ) ) {
			return 'Offline';
		}
		return 'Online';
	}

	/**
	 * Format Region hop URL for list table.
	 */
	public function region_tp_link( $item ) {
		// error_log( 'Region hop URL callback ' . print_r( $item, true ) );
		$regionName = $item->regionName;
		$gateway    = get_option( 'w4os_login_uri' );
		if ( empty( $gateway ) ) {
			return __( 'Gateway not set', 'w4os' );
		}
		// Strip protocol from $gateway
		$gateway = trailingslashit( preg_replace( '/^https?:\/\//', '', $gateway ) );
		$string  = trim( $gateway . $regionName );
		$link    = w4os_hop( $gateway . $regionName, $string );
		return $link;
	}

	/**
	 * Format the last seen date.
	 */
	public function last_seen( $item ) {
		$last_seen = intval( $item->last_seen );
		if ( $last_seen === 0 ) {
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
		if ( empty( $server_uri ) ) {
			return;
		}
		$server_uri = untrailingslashit( $server_uri );
		$server_uri = preg_replace( '/^https?:\/\//', '', $server_uri );

		return esc_html( $server_uri );
	}
}
