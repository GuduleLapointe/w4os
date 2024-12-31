<?php
/**
 * This is a test class to finetune menu integration.
 * - Create a Settings page for Avatars-specific settings, as a submenu of the main 'w4os' menu
 * - We don't care about the main menu here, it is defined in another file.
 * - The rendering is made efficiently, by W4OS3_Settings::render_settings_page()
 * - We don't include html code of the pages here, only the settings registration.
 * - The header and content are managed by the render_settings_page() method.
 */

// UserAccounts table fields:

// Field    Type    Collation   Attributes  Null    Default Extra
// PrincipalID  char(36)    utf8_general_ci     No  None
// ScopeID  char(36)    utf8_general_ci     No  00000000-0000-0000-0000-000000000000
// FirstName    varchar(64) utf8_general_ci     No  None
// LastName varchar(64) utf8_general_ci     No  None
// Email    varchar(64) utf8_general_ci     Yes NULL
// ServiceURLs  text    utf8_general_ci     Yes NULL
// Created  int(11)         Yes NULL
// UserLevel    int(11)         No  0
// UserFlags    int(11)         No  0
// UserTitle    varchar(64) utf8_general_ci     No
// active   int(11)         No  1


class W4OS3_Avatar {
	private $db;
	public static $slug;
	public static $profile_page_url;
	
	public function __construct() {
		// Initialize the custom database connection with credentials
		$this->db = new W4OS_WPDB( W4OS_DB_ROBUST );
		self::$slug     = get_option( 'w4os_profile_slug', 'profile' );
		self::$profile_page_url = get_home_url( null, self::$slug );
	}
	
	/**
	 * Initialize the class. Register actions and filters.
	 */
	public function init() {
		add_filter( 'w4os_settings', array( $this, 'register_w4os_settings' ), 10, 3 );
		// Add rewrite rules for the profile page as $profile_page_url/$firstname.$lastname or $profile_page_url/?name=$firstname.$lastname
		add_action( 'init', array( $this, 'add_rewrite_rules' ) );

		// DEBUG ONLY force flush permalink rules
		add_action( 'init', 'flush_rewrite_rules' ); // DEBUG ONLY
	}

	/**
	 * Add rewrite rules for the profile page.
	 * as $profile_page_url/$firstname.$lastname or $profile_page_url/?name=$firstname.$lastname
	 */
	public function add_rewrite_rules() {
		$target = 'index.php?pagename=' . self::$slug . '&profile_firstname=$matches[1]&profile_lastname=$matches[2]&profile_args=$matches[3]';

		// Rewrite rule for $profile_page_url/$firstname.$lastname
		add_rewrite_rule(
			'^' . self::$slug . '/([^/]+)\.([^/\.\?&]+)(\?.*)?$',
			$target,
			'top'
		);

		// Rewrite rule for $profile_page_url/?name=$firstname.$lastname
		add_rewrite_rule(
			'^' . self::$slug . '/\?name=([^\.&]+)\.([^\.&]+)(&.*)?$',
			$target,
			'top'
		);
	}

	public function register_w4os_settings( $settings, $args = array(), $atts = array() ) {
		$settings['w4os-avatars'] = array(
			'parent_slug'       => 'w4os',
			'page_title'        => __( 'Avatars', 'w4os' ) . ' (dev)',
			'menu_title'        => '(dev) ' . __( 'Avatars', 'w4os' ),
			// 'capability'  => 'manage_options',
			'menu_slug'         => 'w4os-avatars',
			// 'callback'    => array( $this, 'render_settings_page' ),
			// 'position'    => 3,
			'sanitize_callback' => array( $this, 'sanitize_options' ),
			'tabs'              => array(
				'avatars'  => array(
					'title'    => __( 'List', 'w4os' ), // Added 'Avatars' tab
					'callback' => array( $this, 'display_avatars_list' ),
				),
				'settings' => array(
					'title'  => __( 'Settings', 'w4os' ),
					'fields' => array(
						'create_wp_account'      => array(
							'label'       => __( 'Create WP accounts', 'w4os' ),
							'type'        => 'checkbox',
							'options'     => array( __( 'Create website accounts for avatars.', 'w4os' ) ),
							'description' => __( 'This will create a WordPress account for avatars that do not have one. The password will synced between site and OpenSimulator.', 'w4os' ),
						),
						'allow_multiple_avatars' => array(
							'label'       => __( 'Allow multiple avatars', 'w4os' ),
							'type'        => 'checkbox',
							'options'     => array( __( 'Allow more than one avatar for a single email address.', 'w4os' ) ),
							'description' => __( 'This will allow users to have more than one avatar on the site.', 'w4os' )
							. ' ' . __( 'Disabling the option can only be enforced for avatars created through the website.', 'w4os' ),
						),
					),
				),
			),
		);
		if ( empty( $settings['w4os-settings']['tabs']['pages']['title'] ) ) {
			$settings['w4os-settings']['tabs']['pages']['title'] = __( 'Pages', 'w4os' );
		}

		$settings['w4os-settings']['tabs']['pages']['fields'] = array_merge(
			$settings['w4os-settings']['tabs']['pages']['fields'] ?? array(),
			array(
				'profile'      => array(
					'label'       => __( 'Profile Page', 'w4os' ),
					'type'        => 'page_select2',
					'placeholder' => __( 'Select the page to be used as profile page.', 'w4os' ),
					// 'default' => w4os::get_option( 'profile-page' ),
				),
				'registration' => array(
					'label'       => __( 'Registration', 'w4os' ),
					'type'        => 'page_select2_url',
					'default'     => W4OS3::$ini['GridInfoService']['register'] ?? '',
					'value'       => W4OS3::$ini['GridInfoService']['register'] ?? '',
					'options'     => array(
						''            => ' ' . __( 'Custom URL', 'w4os' ),
						'use-profile' => ' ' . __( 'Use profile page', 'w4os' ),
						'use-default' => ' ' . __( 'Use WordPress default', 'w4os' ),
					),
					'placeholder' => __( 'Select the page to be used as registration page.', 'w4os' ),
					'readonly'    => W4OS3::$console_enabled,
					// 'default' => w4os::get_option( 'registration-page' ),
				),
				'password'     => array(
					'label'       => __( 'Password Recovery', 'w4os' ),
					'type'        => 'page_select2_url',
					'value'       => W4OS3::$ini['GridInfoService']['password'] ?? '',
					'options'     => array(
						''            => ' ' . __( 'Custom URL', 'w4os' ),
						'use-profile' => ' ' . __( 'Use profile page', 'w4os' ),
						'use-default' => ' ' . __( 'Use WordPress default', 'w4os' ),
					),
					'placeholder' => __( 'Select the page to be used as password reset page.', 'w4os' ),
					'readonly'    => W4OS3::$console_enabled,
					// 'default' => w4os::get_option( 'password-page' ),
				),
			),
		);

		return $settings;
	}

	/**
	 * Display the list of Avatars from the custom database.
	 */
	public function display_avatars_list() {
		// if ( ! class_exists( 'WP_List_Table' ) ) {
		// require_once ABSPATH . 'wp-admin/v2/class-wp-list-table.php';
		// }

		// Instantiate and display the list table

		$avatarsTable = new W4OS_List_Table(
			$this->db,
			'avatars',
			array(
				'singular'      => 'Avatar',
				'plural'        => 'Avatars',
				'ajax'          => false,
				'table'         => 'UserAccounts',
				'query'         => "SELECT * FROM (
				SELECT *, CONCAT(FirstName, ' ', LastName) AS avatarName, GREATEST(Login, Logout) AS last_seen
				FROM UserAccounts 
				LEFT JOIN userprofile ON PrincipalID = userUUID 
				LEFT JOIN GridUser ON PrincipalID = UserID
			) AS subquery",
				'admin_columns' => array(
					'avatarName'  => array(
						'title'           => __( 'Avatar Name', 'w4os' ),
						'sortable'        => true, // optional, defaults to false
						// 'sort_column' => 'avatarName', // optional, defaults to column key, use 'callback' to use render_callback value
						'order'           => 'ASC', // optional, defaults to 'ASC'
						'searchable'      => true, // optional, defaults to false
						// 'search_column' => 'avatarName', // optional, defaults to column key, use 'callback' to use render_callback value
						// 'filterable' => false, // deprecated, use 'views' instead
						'render_callback' => array( $this, 'format_name' ), // optional, defaults to 'column_' . $key
						'size'            => null, // optional, defaults to null (auto)
					),
					'Email'       => array(
						'title'      => __( 'Email', 'w4os' ),
						// 'type' => 'email',
						'sortable'   => true,
						'searchable' => true,
						// 'size' => '20%',
					),
					'avatar_type' => array(
						'title'           => __( 'Type', 'w4os' ),
						'render_callback' => array( $this, 'format_avatar_type' ),
						'sortable'        => true,
						'sort_column'     => 'callback',
						'size'            => '10%',
						'views'           => 'callback',
					),
					'active'      => array(
						'title'           => __( 'Active', 'w4os' ),
						'type'            => 'boolean',
						'render_callback' => array( $this, 'format_active' ),
						'sortable'        => true,
						'sort_column'     => 'callback',
						'size'            => '8%',
						'views'           => 'callback',
					),
					'Online'      => array(
						'title'           => __( 'Online', 'w4os' ),
						'type'            => 'boolean',
						'render_callback' => array( $this, 'format_online_status' ),
						'sortable'        => true,
						'sort_column'     => 'callback',
						'size'            => '8%',
						'views'           => 'callback', // Add subsubsub links based on the rendered value
					),
					'last_seen'   => array(
						'title'           => __( 'Last Seen', 'w4os' ),
						'type'            => 'date',
						'render_callback' => array( $this, 'format_last_seen' ),
						'size'            => '10%',
						'sortable'        => true,
						'order'           => 'DESC',
					),
					'Created'     => array(
						'title'           => __( 'Created', 'w4os' ),
						'type'            => 'date',
						'size'            => '10%',
						'sortable'        => true,
						'render_callback' => array( $this, 'format_created' ),
					),
				),
			)
		);
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
	 * Sanitize the options for this specific page.
	 *
	 * The calling page is not available in this method, so we need to use the option name to get the options.
	 */
	public static function sanitize_options( $input ) {
		return W4OS3_Settings::sanitize_options( $input, 'w4os-avatars' );
	}

	public static function get_name( $item ) {
		if ( is_object( $item ) ) {
			$uuid = $item->PrincipalID;
			if ( isset( $item->avatarName ) ) {
				return trim( $avatarName = $item->avatarName );
			} elseif ( isset( $item->FirstName ) && isset( $item->LastName ) ) {
				return trim( $item->FirstName . ' ' . $item->LastName );
			}
			return __( 'Invalid Avatar Object', 'w4os' );
		} elseif ( opensim_isuuid( $item ) ) {
			$uuid = $item;
			global $w4osdb;
			$query  = "SELECT CONCAT(FirstName, ' ', LastName) AS Name FROM UserAccounts WHERE PrincipalID = %s";
			$result = $w4osdb->get_var( $w4osdb->prepare( $query, $uuid ) );
			if ( $result && ! is_wp_error( $result ) ) {
				return esc_html( $result );
			}
		}
		return __( 'Unknown Avatar', 'w4os' );
	}

	public function avatar_type( $item = null ) {
		if ( empty( $item ) ) {
			$item = $this->item;
		}

		$models = W4OS3_Model::get_models();
		$email  = $item->Email;
		if ( W4OS3_Model::is_model( $item ) ) {
			return 'model';
		}
		if ( empty( $email ) ) {
			return 'service';
		}
		return 'user';
	}

	/**
	 * Format the name column.
	 */
	public function format_name( $item ) {
		$PrincipalID = $item->PrincipalID;

		$type = $this->avatar_type( $item );
		$name = $this->get_name( $item );
		if ( $type === 'user' ) {
			$actions = array(
				// 'edit' => sprintf(
				// '<a href="%s" title="%s">%s</a>',
				// admin_url( 'user-edit.php?user_id=' . $item->PrincipalID ),
				// __( 'Edit this user', 'w4os' ),
				// __( 'Edit', 'w4os' )
				// ),
				'profile' => sprintf(
					'<a href="%s" title="%s">%s</a>',
					$this->profile_url( $item ),
					__( 'View profile page', 'w4os' ),
					__( 'Profile', 'w4os' )
				),
			);
		} else {
			return $this->get_name( $item );
		}
		$special_accounts = array();
		$user_level       = self::user_level( $item );
		if ( ! empty( $user_level ) ) {
			$special_accounts[] = $user_level;
		}
		$profile_html = $this->profile_html( $item );
		$output       = sprintf(
			'<strong><a href="#" data-modal-target="modal-%1$s">%2$s</a> %3$s</strong>',
			$PrincipalID,
			$this->get_name( $item ),
			( empty( $special_accounts ) ) ? '' : ' – ' . implode( ', ', $special_accounts )
		);
		$output      .= empty( $actions ) ? '' : '<div class="row-actions">' . implode( ' | ', $actions ) . '</div>';
		$output      .= W4OS3::modal( $PrincipalID, $this->profile_url( $item ), $profile_html );
		return $output;
	}

	public static function user_level( $item ) {
		if ( is_numeric( $item ) ) {
			$level = intval( $item );
		} else {
			$level = intval( $item->UserLevel );
		}
		if ( $level >= 200 ) {
			return __( 'God', 'w4os' );
		} elseif ( $level >= 150 ) {
			return __( 'Liaison', 'w4os' );
		} elseif ( $level >= 100 ) {
			return __( 'Customer Service', 'w4os' );
		} elseif ( $level >= 1 ) {
			return __( 'God-like', 'w4os' );
		}
	}

	/**
	 * Avatar type
	 */
	public function format_avatar_type( $item ) {
		$type = $this->avatar_type( $item );
		switch ( $type ) {
			case 'model':
				return __( 'Default Model', 'w4os' );
			case 'service':
				return __( 'Service Account', 'w4os' );
			case 'user':
				return __( 'User Avatar', 'w4os' );
			default:
				return __( 'Unknown', 'w4os' );
		}
	}

	/**
	 * Format the active column.
	 */
	public function format_active( $item ) {
		$avatar_type = $this->avatar_type( $item );
		if ( $avatar_type === 'model' || $avatar_type === 'service' ) {
			return null;
		}
		$active = intval( $item->active );
		if ( $active === 1 ) {
			return 'Active';
		}
		return 'Inactive';
	}

	/**
	 * Format the online column.
	 */
	public function format_online_status( $item ) {
		$avatar_type = $this->avatar_type( $item );
		if ( $avatar_type === 'model' || $avatar_type === 'service' ) {
			return null;
		}
		if ( empty( $item->Online ) ) {
			return null;
		}
		return W4OS3::is_true( $item->Online ) ? 'Online' : 'Offline';
	}

	/**
	 * Format Avatar hop URL for list table.
	 */
	public function avatar_tp_link( $item ) {
		$avatarName = $item->avatarName;
		$gateway    = get_option( 'w4os_login_uri' );
		if ( empty( $gateway ) ) {
			return __( 'Gateway not set', 'w4os' );
		}
		// Strip protocol from $gateway
		$gateway = trailingslashit( preg_replace( '/^https?:\/\//', '', $gateway ) );
		$string  = trim( $gateway . $avatarName );
		$link    = w4os_hop( $gateway . $avatarName, $string );
		return $link;
	}

	/**
	 * Format the last seen date.
	 */
	public function format_last_seen( $item ) {
		// No need to filter empty values, W4OS3::date() will return an empty string
		return esc_html( W4OS3::date( $item->last_seen ) );
	}

	/**
	 * Format the created date.
	 */
	public function format_created( $item ) {
		// No need to filter empty values, W4OS3::date() will return an empty string
		return esc_html( W4OS3::date( $item->Created ) );
	}

	/**
	 * Format the server URI column in a lighter way.
	 */
	public function server_uri( $item ) {
		$server_uri = $item->serverURI ?? '';
		if ( empty( $server_uri ) ) {
			return;
		}
		$server_uri = untrailingslashit( $server_uri );
		$server_uri = preg_replace( '/^https?:\/\//', '', $server_uri );

		return esc_html( $server_uri );
	}

	static function get_avatars( $format = OBJECT ) {
		global $w4osdb;
		if ( empty( $w4osdb ) ) {
			return false;
		}

		$avatars = array();

		$sql    = 'SELECT PrincipalID, FirstName, LastName FROM UserAccounts WHERE active = true';
		$result = $w4osdb->get_results( $sql, $format );
		if ( is_array( $result ) ) {
			foreach ( $result as $avatar ) {
				$avatars[ $avatar->PrincipalID ] = trim( "$avatar->FirstName $avatar->LastName" );
			}
		}
		return $avatars;
	}

	public static function profile_url( $item = null ) {
		$slug      = get_option( 'w4os_profile_slug', 'profile' );
		$profile_page_url  = get_home_url( null, $slug );
		$firstname = $item->FirstName;
		$lastname  = $item->LastName;

		if ( empty( $firstname ) || empty( $lastname ) ) {
			return $profile_page_url;
		} else {
			$firstname = sanitize_title( $firstname );
			$lastname  = sanitize_title( $lastname );
			return $profile_page_url . '/' . $firstname . '.' . $lastname;
		}
	}

	public function profile_html( $item = null ) {
		// return '<div class="w4os-avatar-profile">Debug Profile content</div>';
		$profile_url = $this->profile_url( $item );
		$avatarName  = $item->avatarName;

		// if( $avatarName == 'Way Forest' ) {
		// error_log( 'item ' . print_r( $item, true ) );
		// }
		$profileImage = $item->profileImage;
		$img          = ( empty( $profileImage ) ) ? '' : '<img src="' . $profileImage . '" alt="' . $avatarName . '">';

		if ( ! empty( $item->profileFirstImage . $item->profileFirstText ) ) {
			$profileFirstImage = W4OS3::img( $item->profileFirstImage, array( 'alt' => $avatarName ) );
			$reallife          = sprintf(
				'<div class="firstlife" style="clear:both !important;">%s %s</div>',
				$profileFirstImage,
				wpautop( $item->profileFirstText ),
			);
		}

		$data = array(
			__( 'Born', 'w4os' )      => w4os_age( $item->Created ),
			__( 'Last Seen', 'w4os' ) => $this->format_last_seen( $item ),
			__( 'Partner', 'w4os' )   => ( empty( $partner ) ) ? null : trim( $partner->FirstName . ' ' . $partner->LastName ),
			__( 'Wants to', 'w4os' )  => join( ', ', $this->wants( $item ) ),
			__( 'Skills', 'w4os' )    => join( ', ', $this->skills( $item ) ),
			__( 'Languages', 'w4os' ) => $item->profileLanguages,
			__( 'About', 'w4os' )     => empty( $item->profileAboutText ) ? '' : wpautop( $item->profileAboutText ),
			// __( 'Real Life', 'w4os' )   => $reallife,
		);

		$data = array_filter( $data );

		$output[] = '<h2>' . $avatarName . '</h2>';
		$output[] = W4OS3::img(
			$profileImage,
			array(
				'alt'   => $avatarName,
				'class' => 'profile',
			)
		);
		foreach ( $data as $key => $value ) {
			$output[] = '<p><strong>' . $key . '</strong>: ' . $value . '</p>';
		}
		$output = '<div class="w4os-avatar-profile">' . implode( ' ', $output ) . '</div>';
		return $output;
	}

	public static function wants( $item = null, $mask = null, $additionalvalue = null ) {
		if ( empty( $mask ) ) {
			$mask = $item->profileWantToMask ?? null;
		}
		if ( empty( $additionalvalue ) ) {
			$additional = $item->profileWantToText ?? null;
		}

		return w4os_demask(
			$mask,
			array(
				__( 'Build', 'w4os' ),
				__( 'Explore', 'w4os' ),
				__( 'Meet', 'w4os' ),
				__( 'Group', 'w4os' ),
				__( 'Buy', 'w4os' ),
				__( 'Sell', 'w4os' ),
				__( 'Be Hired', 'w4os' ),
				__( 'Hire', 'w4os' ),
			),
			$additionalvalue
		);
	}

	public static function skills( $item = null, $mask = null, $additionalvalue = null ) {
		if ( empty( $mask ) ) {
			$mask = $item->profileWantToMask ?? null;
		}
		if ( empty( $additionalvalue ) ) {
			$additional = $item->profileSkillsText ?? null;
		}

		return w4os_demask(
			$mask,
			array(
				__( 'Textures', 'w4os' ),
				__( 'Architecture', 'w4os' ),
				__( 'Event Planning', 'w4os' ),
				__( 'Modeling', 'w4os' ),
				__( 'Scripting', 'w4os' ),
				__( 'Custom Characters', 'w4os' ),
			),
			$additionalvalue
		);
	}
}
