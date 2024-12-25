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
	private $uuid;
	private $item;
	private $data;
	private $main_query = "SELECT * FROM (
		SELECT regions.*,
		CONCAT(UserAccounts.FirstName, ' ', UserAccounts.LastName) AS owner_name,
		sizeX * sizeY AS size,
		(SELECT COUNT(*) FROM Presence WHERE Presence.RegionID = regions.uuid) AS presence
		FROM `regions`
		LEFT JOIN UserAccounts ON regions.owner_uuid = UserAccounts.PrincipalID
	) AS subquery";

	private $name;
	private $owner_name;
	private $owner_uuid;

	public function __construct( $mixed = null ) {
		// Initialize the custom database connection with credentials
		$this->db = new W4OS_WPDB( W4OS_DB_ROBUST );

		if( ! W4OS3::empty( $mixed ) ) {
			$this->fetch_region_data( $mixed );
		} else if ( isset( $_GET['region'] ) ) {
			$this->fetch_region_data( $_GET['region'] );
		}
	}

	/**
	 * Initialize the class. Register actions and filters.
	 */
	public function init() {
		add_filter( 'w4os_settings', array( $this, 'register_w4os_settings' ), 10, 3 );

		$this->constants();
	}

	private function constants() {
		define( 'W4OS_REGION_FLAGS', array(
			// 4 => __( 'Region Online', 'w4os' ), // Not reliable, fake positives
			1 => __( 'Default Region', 'w4os' ),
			1024 => __( 'Default HG Region', 'w4os' ),
			2 => __( 'Fallback Region', 'w4os' ),
			256 => __( 'Authenticate', 'w4os' ),
			512 => __( 'Hyperlink', 'w4os' ),
			32 => __( 'Locked Out', 'w4os' ),
			8 => __( 'No Direct Login', 'w4os' ),
			64 => __( 'No Move', 'w4os' ),
			16 => __( 'Persistent', 'w4os' ),
			128 => __( 'Reservation', 'w4os' ),
		) );
	}

	public function register_w4os_settings( $settings, $args = array(), $atts = array() ) {
		$settings['w4os-regions'] = array(
			'parent_slug'       => 'w4os',
			'page_title'        => __( 'Regions', 'w4os' ) . ' (dev)',
			'menu_title'        => '(dev) ' . __( 'Regions', 'w4os' ),
			// 'capability'  => 'manage_options',
			'menu_slug'         => 'w4os-regions',
			// 'callback'    => array( $this, 'render_settings_page' ),
			// 'position'    => 3,
			'sanitize_callback' => array( $this, 'sanitize_options' ),
			'tabs'              => array(
				'regions'  => array(
					'title'    => __( 'List', 'w4os' ), // Added 'Regions' tab
					'callback' => array( $this, 'display_regions_list' ),
				),
				'settings' => array(
					'title'  => __( 'Settings', 'w4os' ),
					'fields' => array(
						array(
							'id'    => 'make_coffee',
							'title' => __( 'Make Coffee', 'w4os' ),
							'type'  => 'checkbox',
							'label' => __( 'Make coffee after boot', 'w4os' ),
							// 'options'     => array(
							// 1 => __('Yes, please', 'w4os'),
							// ),
							// 'description' => __( 'This is a placeholder parameter.', 'w4os' ),
						),
						// array(
						// 'id'          => 'w4os_settings_region_settings_field_2',
						// 'name'        => __( 'First Tab Field 2', 'w4os' ),
						// 'type'        => 'checkbox',
						// 'label'       => __( 'Enable settings option 2.', 'w4os' ),
						// 'description' => __( 'This is a placeholder parameter.', 'w4os' ),
						// ),
					),
				),
			),
		);
		$get = wp_parse_args( $_GET, array(
			'page'   => null,
			'action' => null,
			'region' => null,
		) );
		if ( $get['page'] === 'w4os-regions' && $get['action'] === 'edit' && ! W4OS3::empty( $get['region'] ) ) {
			$_GET['tab'] = 'edit';
			$region_title = sprintf( 
				'<h1 class="wp-heading-inline">%s</h1>', 
				sprintf( __('Region: %s', 'w4os'), $this->item->regionName ),
			);
			$actions = $this->get_actions( 'page-title' );
			unset( $actions['edit'] );
			// unset( $actions['teleport'] );
			$region_title .= ' ' . implode( ' ', $actions );
			$region_title .= '<p><code>' . $_GET['region'] . '</code><input type="hidden" name="regionuuid" value="' . $_GET['region'] . '"></p>';
			// $server_uri = $this->format_server_uri( $this->item, true );
			$parts = parse_url( $this->item->serverURI );
			$sim_credentials = wp_parse_args( 
				array(
					'host' => $parts['host'] ?? '',
					'port' => $parts['port'] ?? '',
				),
				W4OS3::get_credentials( $this->item->serverURI ),
			);
			$settings['w4os-regions']['tabs']['edit'] = array(
				'title'    => __( 'Edit (improved)', 'w4os' ),
				'sidebar-content'  => '<h3>' . __('Parcels', 'w4os') . '</h3>' . $this->get_parcels(),
				'before-form' => $region_title,
				'fields'   => array(
					'sim_credentials' => array(
						// 'id'    => 'edit1',
						'type'  => 'instance_credentials',
						'label' => __( 'Simulator Credentials', 'w4os' ),
						'value' => $sim_credentials,
					),
					'status' => array(
						'id'    => 'status',
						'label' => __( 'Status', 'w4os' ),
						'type'  => 'custom_html',
						'value' => $this->format_region_status( $this->item )
						. sprintf( ' (%s %s)', __( 'last seen', 'w4os' ), $this->last_seen( $this->item ) )
						. $this->format_flags( $this->item->flags ),
					),
					'simulator' => array(
						'id'    => 'serverURI',
						'label' => __( 'Simulator', 'w4os' ),
						'type'  => 'custom_html',
						'value' => $this->format_server_uri( $this->item ),
					),
					'owner' => array(
						'id'    => 'owner',
						'label' => __( 'Owner', 'w4os' ),
						'type'  => 'custom_html',
						'value' => $this->owner_name( $this->item ),
					),
					'teleport' => array(
						'id'    => 'teleport',
						'label' => __( 'Teleport', 'w4os' ),
						'type'  => 'custom_html',
						'value' => $this->get_tp_link(),
					),
					'map' => array(
						'id'    => 'map',
						'label' => __( 'Map', 'w4os' ),
						'type'  => 'custom_html',
						'value' => ( ! W4OS3::empty( $this->item->regionMapTexture ) ) ? sprintf(
							'<img src="%1$s" class="asset asset-%3$d region-map" alt="%2$s" loading="lazy" width="%3$d" height="%4$d">',
							w4os_get_asset_url( $this->item->regionMapTexture ),
							sprintf( __( '%s region map', 'w4os' ), esc_attr( $this->item->regionName ) ),
							$this->item->sizeX ?? 256,
							$this->item->sizeY ?? 256,
						) : '',
					),
				),
			);
		}

		return $settings;
	}

	/**
	 * Sanitize the options for this specific page.
	 *
	 * The calling page is not available in this method, so we need to use the option name to get the options.
	 */
	public static function sanitize_options( $input ) {
		return W4OS3_Settings::sanitize_options( $input, 'w4os-regions' );
	}

	/**
	 * Display the list of Regions from the custom database.
	 */
	public function display_regions_list() {
		// if ( ! class_exists( 'WP_List_Table' ) ) {
		// require_once ABSPATH . 'wp-admin/v2/class-wp-list-table.php';
		// }

		if( isset( $_GET['action'] ) && $_GET['action'] === 'edit' && ! empty( $_GET['region'] ) ) {
			$region = new W4OS3_Region( $_GET['region'] );
			
			$template = W4OS_INCLUDES_DIR . 'templates/admin-region-edit.php';
			if( file_exists( $template ) ) {
				require_once( $template );
			} else {
				error_log( __FUNCTION__ . ' template missing ' . $template );
			}

			return;
			// $region = new W4OS3_Region( $_GET['region'] );
			// $region->edit_region();
		}
		// Instantiate and display the list table

		$regionsTable = new W4OS_List_Table(
			$this->db,
			'regions',
			array(
				'singular'      => 'Region',
				'plural'        => 'Regions',
				'ajax'          => false,
				'query'         => $this->main_query,
				'admin_columns' => array(
					'regionName'    => array(
						'title'           => __( 'Region Name', 'w4os' ),
						'sortable'        => true, // optional, defaults to false
						'sort_column'     => 'regionName', // optional, defaults to column key, use 'callback' to use render_callback value
						'order'           => 'ASC', // optional, defaults to 'ASC'
						'searchable'      => true, // optional, defaults to false
						'search_column'   => 'regionName', // optional, defaults to column key, use 'callback' to use render_callback value
						// 'filterable'      => false, // optional, defaults to false, enable filter menu
						'render_callback' => array( $this, 'format_region_name' ), // optional, defaults to 'column_' . $key
						'size'            => null, // optional, defaults to null (auto)
					),
					'owner_name'    => array(
						'title'      => _n( 'Owner', 'Owners', 1, 'w4os' ),
						'plural'     => _n( 'Owner', 'Owners', 2, 'w4os' ), // Optional, defaults to singular form
						'sortable'   => true,
						'searchable' => true,
						'filterable' => true,
						'order'      => 'ASC',
					),
					'teleport_link' => array(
						'title'           => __( 'Region URI', 'w4os' ),
						'render_callback' => array( $this, 'region_tp_uri' ),
					),
					'serverURI'     => array(
						'title'           => _n( 'Simulator', 'Simulators', 1, 'w4os' ),
						'plural'          => _n( 'Simulator', 'Simulators', 2, 'w4os' ),
						'render_callback' => array( $this, 'format_server_uri' ),
						'sortable'        => true,
						'filterable'      => true,
					),
					'serverControl' => array(
						'title'           => __( 'Control', 'w4os' ),
						'size'            => '6%',
						'sortable'        => true,
						'render_callback' => array( $this, 'format_server_control' ),
					),
					'presence' => array(
						'title'           => __( 'Presence', 'w4os' ),
						'size'            => '8%',
						'sortable'		=> true,
						'order'			=> 'DESC',
					),
					'serverPort'    => array(
						'title' => __( 'Internal Port', 'w4os' ),
						'size'  => '8%',
					),
					'size'          => array(
						'title'           => __( 'Size', 'w4os' ),
						'sortable'        => true,
						'size'            => '8%',
						'render_callback' => array( $this, 'format_region_size' ),
						'views'           => 'callback',
					),
					'status'        => array(
						'title'           => __( 'Status', 'w4os' ),
						'render_callback' => array( $this, 'format_region_status' ),
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

		<div class="wrap w4os-list w4os-list-regions">
			<?php $regionsTable->views(); ?>
			<form method="get">
				<input type="hidden" name="page" value="w4os-regions">
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
	 * Fetch the Region data from the custom database.
	 * 
	 * @param mixed $mixed The UUID of the Region or the Region data.
	 * @return void
	 */
	public function fetch_region_data( $mixed ) {
		if ( W4OS3::empty( $mixed ) ) {
			return;
		}
		
		if ( is_object( $mixed ) ) {
			$this->uuid = $mixed->uuid;
			$this->item = $mixed;
		} else if ( is_string( $mixed ) && W4OS3::is_uuid( $mixed ) ) {
			$this->uuid = $mixed;
			$query = $this->main_query . " WHERE uuid = %s";
			$this->item = $this->db->get_row( $this->db->prepare( $query, $this->uuid ) );
		} else {
			return;
		}
	}

	/**
	 * Get region name
	 */
	public function get_name() {
		if ( empty( $this->item ) ) {
			return;
		}
		return $this->item->regionName;
	}

	/**
	 * Get region teleport uri
	 */
	public function get_tp_uri( $string = null ) {
		if ( empty( $this->item ) ) {
			return;
		}
		return $this->region_tp_uri( $this->item );
	}

	/**
	 * Get region teleport link
	 */
	public function get_tp_link( $string = null ) {
		$url = $this->get_tp_uri();
		return empty( $url ) ? null : w4os_hop($url, $string);
	}

	public static function format_flags( $bitwise = null ) {
		$matches = self::match_flags( $bitwise );
		if (empty($matches)) {
			return;
		}

		// asort( $matches );

		$output_html = '<ul class="region-flags">';
		foreach ( $matches as $flag ) {
			$output_html .= '<li>' . esc_html( $flag ) . '</li>';
		}
		$output_html .= '</ul>';

		return $output_html;
	}

	public static function match_flags( $bitwise ) {
		$matches = array();
		foreach (W4OS_REGION_FLAGS as $flag => $label) {
			if ($bitwise & $flag) {
				$matches[$flag] = $label;
			}
		}
		return $matches;
	}

	public function flags( $bitwise = null ) {
		if( $bitwise === null ) {
			$bitwise = $this->item->flags;
		}
		return self::match_flags( $bitwise );
	}

	/**
	 * Get region actions
	 */
	public function get_actions( $context = null ) {
		switch( $context ) {
			case 'page-title':
				$classes[] = 'page-title-action';
				break;

			default:
				$classes[] = esc_attr($context) . '-action';
		}
		// if($this->console) {
		// 	error_log("console enabled");
		// }
		$actions = array(
			'edit'   => sprintf( '<a href="?page=%s&action=%s&region=%s" class="%s">%s</a>', $_REQUEST['page'], 'edit', $this->uuid, implode(' ', $classes), __('Edit', 'w4os') ),
			// 'view'   => sprintf( '<a href="?page=%s&action=%s&region=%s" class="%s">%s</a>', $_REQUEST['page'], 'view', $this->uuid, implode(' ', $classes), __('View', 'w4os') ),
			// 'message' => sprintf( '<a href="?page=%s&action=%s&region=%s" class="%s">%s</a>', $_REQUEST['page'], 'message', $this->uuid, implode(' ', $classes), __('Message Region', 'w4os') ),
			'teleport' => sprintf( '<a href="%s" class="%s">%s</a>', opensim_format_tp( $this->get_tp_uri(), TPLINK_HOP ), implode(' ', $classes),  __('Teleport', 'w4os' ) ),
			// 'delete' => sprintf( '<a href="?page=%s&action=%s&region=%s">Delete</a>', $_REQUEST['page'], 'delete', $this->uuid ),
		);
		return $actions;
	}

	/**
	 * Get region parcels.
	 * 
	 * TODO: use Console instead, if possible.
	 * 
	 * The collector method is not satisfying, 
	 *	- it seems not always able to get the all the parcels.
	 *  - it gets often rejected by the simulator as spam.
	 */
	public function get_parcels( $output_html = null ) {
		$server_uri = $this->item->serverURI;
		if ( empty( $server_uri ) ) {
			return 'Unknown';
		}

		$transient_key = 'w4os_collector_' . $server_uri;
		$collector = $server_uri . '?method=collector';

		$content = get_transient( $transient_key );

		if ( ! $content ) {
			$content = @file_get_contents( $collector );
			if ( empty( $content ) ) {
				error_log( 'No content' );
				return 'Offline';
			}
			set_transient( $transient_key, $content, 60 * 60 );			
		}

		$xml = simplexml_load_string( $content );
		if ( ! $xml ) {
			return 'not an xml';
		}

		$region_data = $xml->xpath("/regiondata/region[info/uuid='{$this->uuid}']");
		if( ! $region_data ) {
			return 'No region data found for ' . $this->uuid;
		}
		$parcels = $region_data[0]->xpath('./data/parceldata/parcel');

		// TODO: use list api instead.
		$output_html = array();
		foreach( $parcels as $parcel_data ) {
			$args = (array) $parcel_data;
			$args['regionURI'] = $this->get_tp_uri();
			// $parcel_data->regionURI = $this->get_tp_uri();
			$parcel = new W4OS_Parcel( $args );
			$output_html[] = $parcel->display();
		}

		if ( ! empty($output_html) ) {
			return '<ul class="parcels"><li>' . implode( '</li><li>', $output_html ) . '</li></ul>';
		}
	}

	/**
	 * Format the Region name column.
	 */
	public function format_region_name( $item ) {
		$region = new W4OS3_Region( $item );
		// $name = $region->get_name();
		// $status = $region->get_status();
		// $actions = $region->get_actions();

		// $regionName = $item->regionName;
		// $regon_id = $item->uuid;
		$name = $region->get_name();
		$flags = $region->flags( $region->item->flags &~ 4 &~ 16 ); // Any flag except Online and Persistent
		$states = empty( $flags ) ? '' : ' — ' . implode( ', ', $flags );

		$actions = $region->get_actions();
		$actions_container = '';
		if( ! empty( $actions ) ) {
			$actions_container = '<div class="row-actions">' . implode( ' | ', $actions ) . '</div>';
			$title_link = admin_url( 'admin.php?page=w4os-regions&action=edit&region=' . $item->uuid );
		}
		return sprintf(
			'<strong><a href="%s">%s</a> %s</strong> %s',
			$title_link,
			$name,
			$states,
			$actions_container
		);
	}

	/**
	 * Format the Region size.
	 */
	public function format_region_size( $item ) {
		if ( empty( $item->size ) ) {
			return null;
		}

		$size = $item->sizeX . '×' . $item->sizeY;
		return $size;
	}

	/**
	 * Check if Region is online by trying to connect to the server URI.
	 */
	public function format_region_status( $item ) {
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
	public function region_tp_uri( $item ) {
		$regionName = $item->regionName;
		$gateway    = get_option( 'w4os_login_uri' );
		if ( empty( $gateway ) ) {
			return __( 'Gateway not set', 'w4os' );
		}
		// Strip protocol from $gateway
		$gateway = trailingslashit( preg_replace( '/^https?:\/\//', '', $gateway ) );
		$string  = trim( $gateway . $regionName );
		return $string;
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
	public function format_server_uri( $item, $use_dns = true ) {
		$server_uri = $item->serverURI;
		if ( empty( $server_uri ) ) {
			return;
		}

		$parts = parse_url( $server_uri );
		$hostname = $parts['host'];
		
		if ( $use_dns ) {
			$hostname = gethostbyaddr( $parts['host'] );
			$hostname = empty($hostname) ? $parts['host'] : $hostname;
			// count dots and fallback to $parts['host'] if no dots or more than 5
			$dot_count = substr_count( $hostname, '.' );
			if ( $dot_count === 0 || $dot_count > 4 ) {
				$hostname = $parts['host'];
			}
		}

		$server_uri = $hostname . ':' . $parts['port'];

		return esc_html( $server_uri );
	}

	public function format_server_control( $item ) {
		$server_uri = $item->serverURI;
		if ( empty( $server_uri ) ) {
			return;
		}
		$icons = array();

		$credentials = W4OS3::get_credentials( $server_uri );
		error_log( 'Server credentials ' . print_r( $credentials, true ) );
		// if ( $credentials['rest']['enabled'] ?? false ) {
		// 	$icons['rest'] = '<span class="dashicons dashicons-rest-api"></span>';
		// }
		if ( $credentials['console']['enabled'] ?? false ) {
			$icons['console'] = '<span class="dashicons dashicons-desktop"></span>';
			$icons['console'] = '<span class="dashicons dashicons-analytics"></span>';
			$icons['console'] = '<span class="dashicons dashicons-embed-generic"></span>';
		}
		if ( $credentials['db']['enabled'] ?? false ) {
			$icons['db'] = '<span class="dashicons dashicons-database"></span>';
		}
		
		return implode( ' ', $icons );
	}
}

/**
 * Parcel class
 * 
 * Sample parcel SimpleXMLElement Object
		(
			[@attributes] => Array
				(
					[showinsearch] => true
					[scripts] => true
					[build] => true
					[public] => true
					[category] => 6
					[forsale] => false
					[salesprice] => 0
				)
		
			[name] => Way's backup
			[description] => SimpleXMLElement Object
				(
				)
		
			[uuid] => 3728f4a2-6b3a-43e3-98c7-38e1f38bd77e
			[area] => 65536
			[location] => 128/128/0
			[infouuid] => 00d22000-00d3-2000-8000-000080000000
			[dwell] => 6
			[image] => 67ee8590-066c-49e9-8a26-92e9869b1d02
			[owner] => SimpleXMLElement Object
				(
					[uuid] => 0208b609-b205-495f-9399-56f456a86d62
					[name] => Way Forest
				)
		
		)
 */

class W4OS_Parcel {
	private $db;
	private $uuid;
	private $data;

	public function __construct( $args = null ) {
		if(empty( $args ) ) {
			return new WP_Error( 'no_parcel_data', 'No parcel data' );
		}
		if( empty( $args['uuid'] ) || empty( $args['name'] ) || empty( $args['location'] ) || empty( $args['regionURI'] ) ) {
			error_log( 'Incomplete parcel data ' . print_r( $args, true ) );
			return new WP_Error( 'incomplete_parcel_data', 'Incomplete parcel data' );
		}

		if ( is_array( $args ) ) {
			$this->data = (object) $args;
			$this->uuid = $args['uuid'];
			$this->infouuid = $args['infouuid']; // Still don't know how to use this
			$this->name = $args['name'];
			$this->owner = $args['owner']['name' ] ?? '';
			$this->image = $args['image'];
			$this->location = $args['location'];
			$this->description = $args['description'];
			$this->regionURI = $args['regionURI'];
			$this->area = $args['area'];
			$this->tp_uri = $this->regionURI . '/' . $this->location;
			$this->tp_link = w4os_hop( $this->tp_uri, $this->tp_uri );
			// error_log( 'Parcel ' . print_r( $this, true ) );
		}
	}

	/**
	 * Display the parcel details.
	 * 
	 * (Already) deprecated. Will use list api instead.
	 */
	public function display() {
		// Display the parcel data
		$image = ( ! W4OS3::empty($this->image) ) ? sprintf( '<img src="%s" alt="%s">', w4os_get_asset_url( $this->image ), $this->name ) : '';
		$output = '<div class="parcel">';
		$output .= $image;
		$output .= '<h3>' . $this->name . '</h3>';
		$output .= '<p class="description">' . $this->description . '</p>';
		$owner = ( $this->owner ) ? '<p class="owner">Owner: ' . $this->owner . '</p>' : '';
		$output .= '<p class="teleport">Teleport: ' . $this->tp_link . '</p>';
		$output .= '<p class="area">Area: ' . $this->area . '</p>';
		$output .= '</div>';
		// Display the parcel data
		return $output;
	}
}
