<?php
/**
 * Register all actions and filters for the plugin
 *
 * @link       http://example.com
 * @since      1.0.0
 *
 * @package    W4OS
 * @subpackage W4OS/includes
 */

/**
 * Register all actions and filters for the plugin.
 *
 * Maintain a list of all hooks that are registered throughout
 * the plugin, and register them with the WordPress API. Call the
 * run function to execute the list of actions and filters.
 *
 * @package    W4OS
 * @subpackage W4OS/includes
 * @author     Your Name <email@example.com>
 */
class W4OS3_Avatar {

	/**
	 * The array of actions registered with WordPress.
	 *
	 * @since    1.0.0
	 * @access   protected
	 * @var      array    $actions    The actions registered with WordPress to fire when the plugin loads.
	 */
	protected $actions;

	/**
	 * The array of filters registered with WordPress.
	 *
	 * @since    1.0.0
	 * @access   protected
	 * @var      array    $filters    The filters registered with WordPress to fire when the plugin loads.
	 */
	protected $filters;

	protected $post;

	public $ID;
	public $name;
	public $uuid;

	/**
	 * Initialize the collections used to maintain the actions and filters.
	 *
	 * @param [int|WP_Post|array] avatar post, or post id, or array with known proporties
	 */
	public function __construct( $args = null ) {
		global $w4osdb;

		$this->init_avatar( $args );
	}

	/**
	 * Initialize the filters and actions with WordPress.
	 *
	 * @since    1.0.0
	 */
	public function init() {
		add_action('init', array($this, 'register_post_types'));
        if (W4OS3::get_option('w4os_sync_users')) {
            W4OS3::update_option('w4os_sync_users', false);
            add_action('init', array($this, 'sync_avatars'));
        }
		add_action( 'admin_init', [ __CLASS__, 'register_settings_page' ] );
		add_action( 'admin_menu', [ __CLASS__, 'add_submenus' ] );
        add_action( 'admin_head', array($this, 'remove_avatar_edit_delete_action' ) );

        add_action('wp_ajax_check_name_availability', array($this, 'ajax_check_name_availability'));
        add_action('post_updated', array($this, 'update_password'), 10, 3);
        add_action('save_post_avatar', array($this, 'save_post_action'), 10, 3);
        add_action('wp_trash_post', array($this, 'avatar_deletion_warning'));

        add_filter('rwmb_meta_boxes', array($this, 'metaboxes_avatar'));
        add_filter('rwmb_meta_boxes', array($this, 'metaboxes_userprofile'));
        add_filter('views_edit-avatar', array($this, 'display_synchronization_status'));
        add_filter( 'views_edit-avatar', [ __CLASS__, 'add_settings_button' ] );
        add_filter('post_row_actions', array($this, 'remove_avatar_delete_row_actions'), 10, 2);
	}

	/**
	 * Initialize the avatar object
	 * 
	 * @param [int|WP_Post|array] avatar post, or post id, or array with known proporties
	 * @return void
	 */
	private function init_avatar( $args = null ) {
		if (empty($args) ){
			return;
		}

		if ( is_numeric( $args ) ) {
			$this->ID = $args;
			$post     = get_post( $this->ID );
		} elseif ( is_object( $args ) & ! is_wp_error( $args ) ) {
			$this->ID = $args->ID;
			$post     = $args;
		} else {
			$post = false;
		}
		if ( $post ) {
			// A WP avatar exists for this grid account
			$this->ID        = $post->ID;
			$this->uuid      = get_post_meta( $post->ID, 'avatar_uuid', true );
			$this->name      = get_post_meta( $post->ID, 'avatar_name', true );
			$this->email     = get_post_meta( $post->ID, 'avatar_email', true );
			$this->user      = get_post_meta( $post->ID, 'avatar_owner', true );
			$this->FirstName = strstr( $this->name, ' ', true );
			$this->LastName  = trim( strstr( $this->name, ' ' ) );
			$this->lastseen  = get_post_meta( $post->ID, 'avatar_lastseen', true );
			$this->born      = get_post_meta( $post->ID, 'avatar_born', true );

			$this->get_simulator_data();
			// $this->meta = get_post_meta($post->ID);
		} elseif ( is_array( $args ) ) {
			// No WP avatar for this grid account
			if ( ( isset( $args['FirstName'] ) && isset( $args['LastName'] ) ) ) {
				$this->FirstName = $args['FirstName'];
				$this->LastName  = $args['LastName'];
				$this->name      = "$this->FirstName $this->LastName";
			} elseif ( isset( $args['avatar_name'] ) ) {
				$this->name = $args['avatar_name'];
			}
			$this->user_id = isset( $args['avatar_owner'] ) ? $args['avatar_owner'] : null;
			if ( isset( $args['email'] ) ) {
				$this->email = $args['email'];
				if ( empty( $this->user_id ) ) {
					$owner = get_user_by( 'email', $this->email );
					if ( $owner ) {
						$this->user_id = $owner->ID;
					}
				}
			}
			if ( isset( $args['PrincipalID'] ) ) {
				$this->uuid = $args['PrincipalID'];
			}
			// error_log("args " . print_r($args, true));
		}
	}

	/**
	 * Add submenu for Avatar settings page
	 */
	public static function add_submenus() {
        // W4OS3::add_submenu_page(
        //     'w4os',                         
        //     __( 'Avatar Settings', 'w4os' ),
        //     __( 'Avatar Settings', 'w4os' ),
        //     'manage_options',
        //     'w4os-avatar-settings',
        //     [ 'W4OS3_Settings', 'render_settings_page' ],
        //     3,
        // );
    }

    public static function register_settings_page() {
        if (! W4OS_ENABLE_V3) {
            return;
        }
        // Add v3 settings below
		$option_group = 'w4os_settings_avatar';
		$option_name = 'w4os_settings_avatar'; // Changed option name
		$page = 'w4os-avatar-settings'; // Updated to match menu slug

		register_setting(
			$option_group, // Option group
			$option_name, // Option name
			array(
				'type' => 'array',
				'description' => __( '  Avatars Settings', 'w4os' ),
				'sanitize_callback' => [ __CLASS__, 'sanitize_options' ], // recieves empty args for now
				// 'show_in_rest' => false,
				'default' => array(
					'create_wp_account' => true,
					'multiple_avatars' => false,
				),
			),
		);

		# add_settings_section( string $id, string $title, callable $callback, string $page, array $args = array() )

		$section = "${option_group}_default";
		add_settings_section(
			$section,				// ID
			null,	// Title
			[ 'W4OS3_Settings', 'render_settings_section' ],  // Callback
			$page,				// Page
			array(
				'description' => __( 'Settings for avatars.', 'w4os' ),
			)
		);

		// add_settings_field( string $id, string $title, callable $callback, string $page, string $section = ‘default’, array $args = array() );
		
		add_settings_field(
			'create_wp_account', // id
			__( 'Create WP accounts', 'w4os' ), // title
			[ 'W4OS3_Settings', 'render_settings_field' ], // callback
			$page, // page
			$section, // section
			array(
				'type' => 'checkbox',
				'label' => __( 'Create website accounts for avatars.', 'w4os' ),
				'description' => __( 'This will create a WordPress account for avatars that do not have one. The password will synced between site and OpenSimulator.', 'w4os' ),
				'option_name' => $option_name, // Pass option name
                'label_for' => 'create_wp_account',
			),
		);

		add_settings_field(
			'multiple_avatars',					
			__( 'Restrict Multiple Avatars', 'w4os' ),
			[ 'W4OS3_Settings', 'render_settings_field' ],
			$page, // page
			$section, // section
			array(
				'type' => 'checkbox',
				'label' => __( 'Allow one avatar per website user.', 'w4os' ),
				'description' => __( 'This will restrict users to a single avatar. The option can only be enforced for avatars created through the website.', 'w4os' ),
				'option_name' => $option_name, // Pass option name
                'label_for' => 'multiple_avatars',
			),
		);
    }

	public static function sanitize_options( $input ) {
		return $input;
	}

	function get_simulator_data() {
		if ( ! W4OS_DB_CONNECTED ) {
			return false;
		}
		global $w4osdb;

		if ( ! w4os_empty( $this->uuid ) ) {
			$condition = "PrincipalID = '$this->uuid'";
		} elseif ( ! empty( $this->name ) ) {
			$condition = "FirstName = '$this->FirstName' AND LastName = '$this->LastName'";
			// } else if (! is_email($this->email)) {
			// careful:: in this case, only use if 1 single result
			// $condition = "Email = '$this->email' AND count = 1";
		}
		if ( ! empty( $condition ) ) {
			$matches = $w4osdb->get_results(
				"SELECT * FROM UserAccounts
				LEFT JOIN userprofile ON PrincipalID = userUUID
				LEFT JOIN GridUser ON PrincipalID = UserID
				WHERE active = 1 AND $condition"
			);
			if ( count( $matches ) > 1 ) {
				$message = sprintf(
					__( 'Simulator database contains more than one avatar matching %s. This should never happen and requires an immediate fix.', 'w4os' ),
					"$this->FirstName $this->LastName $this->uuid",
				);
				w4os_transient_admin_notice( $message, 'warning' );
				error_log( $message );
			} elseif ( count( $matches ) == 1 ) {
				$grid_data       = array_shift( $matches );
				$this->uuid      = $grid_data->UserID;
				$this->FirstName = $grid_data->FirstName;
				$this->LastName  = $grid_data->LastName;
				$this->name      = "$this->FirstName $this->LastName";
				$this->email     = $grid_data->Email;
				$this->lastseen  = $grid_data->Login;
				$this->born      = $grid_data->Created;
				$this->image     = ( w4os_empty( $grid_data->profileImage ) ) ? null : $grid_data->profileImage;
				$this->about     = strip_tags( $grid_data->profileAboutText );
			}
		}
	}

	function create_post() {
		// error_log("$this->name - initial " . $this->image);
		$post_id = wp_insert_post(
			array(
				'ID'            => ( isset( $this->ID ) ) ? $this->ID : 0,
				'post_type'     => 'avatar',
				'post_status'   => $this->avatar_status(),
				'post_author'   => ( empty( $this->user_id ) ) ? 0 : $this->user_id,
				'post_title'    => $this->name,
				'post_date_gmt' => date( 'Y-m-d H:i:s', ( ( ! empty( $this->born ) && $this->born > 0 ) ? $this->born : time() ) ),
				'post_content'  => $this->about,
				'meta_input'    => array(
					'avatar_email'     => $this->email,
					'avatar_owner'     => ( empty( $this->user_id ) ) ? null : $this->user_id,
					'avatar_uuid'      => $this->uuid,
					'avatar_born'      => $this->born,
					'avatar_lastseen'  => $this->lastseen,
					'avatar_name'      => $this->name,
					'avatar_firstname' => $this->FirstName,
					'avatar_lastname'  => $this->LastName,
					'avatar_image'     => $this->image,
				),
			)
		);
		// $this->get_simulator_data();
		// error_log("get_simulator_data " . $this->image);
		// $this->sync_single_avatar();
		// error_log("sync_single_avatar " . $this->image);
		if ( ! empty( $this->image ) ) {
			$this->set_thumbnail();
		}
		// error_log("set_thumbnail " . $this->image);

		return $post_id;
	}

	function is_orphan() {
		if ( ! isset( $this->uuid ) ) {
			return true;
		}
		if ( w4os_empty( $this->uuid ) ) {
			return true;
		}

		return false;
	}

	/**
	 * Sync avatar info from OpenSimulator
	 *
	 * @param  object $user_or_id   user object or user id
	 * @param  key    $uuid         if set, create link with avatar and update info
	 *                              if not set, update avatar info if link exists
	 * @return object       [description]
	 */
	function sync_single_avatar() {
		if ( empty( $this->owner ) ) {
			$user = get_user_by( 'email', $this->email );
		} else {
			$user = get_user_by( 'email', $this->owner );
		}

		$user_id = ( $user ) ? $user->ID : null;

		$updates = array(
			'ID'           => $this->ID,
			'post_title'   => $this->name,
			'post_author'  => ( $user_id ) ? $user_id : 0,
			'post_status'  => $this->avatar_status(),
			'post_name'    => sanitize_title( $this->name ),
			'post_content' => $this->about,
			'meta_input'   => array(
				'avatar_email'     => $this->email,
				'avatar_owner'     => $user_id,
				'avatar_uuid'      => $this->uuid,
				'avatar_born'      => $this->born,
				'avatar_name'      => $this->name,
				'avatar_firstname' => $this->FirstName,
				'avatar_lastname'  => $this->LastName,
				'avatar_lastseen'  => $this->lastseen,
				'avatar_image'     => $this->image,
			),
		);

		$result = wp_update_post( $updates, true, false );
		if ( is_wp_error( $result ) ) {
			error_log( "$this->name update failed " . $result->get_error_message() );
			return false;
		}

		$this->set_thumbnail();
	}

	// static function add_in_admin_footer() {
	// $screen = get_current_screen();
	// if( 'edit-avatar' !== $screen->id ) return;
	//
	// add_action( 'in_admin_footer', function(){
	// echo '<p>Goodbye from <strong>in_admin_footer</strong>!</p>';
	// });
	// }

	static function register_post_types() {
		$labels = array(
			'name'                     => esc_html__( 'Avatars', 'w4os' ),
			'singular_name'            => esc_html__( 'Avatar', 'w4os' ),
			'add_new'                  => esc_html__( 'Add New', 'w4os' ),
			'add_new_item'             => esc_html__( 'Add new avatar', 'w4os' ),
			'edit_item'                => esc_html__( 'Edit Avatar', 'w4os' ),
			'new_item'                 => esc_html__( 'New Avatar', 'w4os' ),
			'view_item'                => esc_html__( 'View Avatar', 'w4os' ),
			'view_items'               => esc_html__( 'View Avatars', 'w4os' ),
			'search_items'             => esc_html__( 'Search Avatars', 'w4os' ),
			'not_found'                => esc_html__( 'No avatars found', 'w4os' ),
			'not_found_in_trash'       => esc_html__( 'No avatars found in Trash', 'w4os' ),
			'parent_item_colon'        => esc_html__( 'Parent Avatar:', 'w4os' ),
			'all_items'                => esc_html__( 'All Avatars', 'w4os' ),
			'archives'                 => esc_html__( 'Avatar Archives', 'w4os' ),
			'attributes'               => esc_html__( 'Avatar Attributes', 'w4os' ),
			'insert_into_item'         => esc_html__( 'Insert into avatar', 'w4os' ),
			'uploaded_to_this_item'    => esc_html__( 'Uploaded to this avatar', 'w4os' ),
			'featured_image'           => esc_html__( 'Featured image', 'w4os' ),
			'set_featured_image'       => esc_html__( 'Set featured image', 'w4os' ),
			'remove_featured_image'    => esc_html__( 'Remove featured image', 'w4os' ),
			'use_featured_image'       => esc_html__( 'Use as featured image', 'w4os' ),
			'menu_name'                => esc_html__( 'Avatars', 'w4os' ),
			'filter_items_list'        => esc_html__( 'Filter avatars list', 'w4os' ),
			'filter_by_date'           => esc_html__( 'Filter by date', 'w4os' ),
			'items_list_navigation'    => esc_html__( 'Avatars list navigation', 'w4os' ),
			'items_list'               => esc_html__( 'Avatars list', 'w4os' ),
			'item_published'           => esc_html__( 'Avatar published', 'w4os' ),
			'item_published_privately' => esc_html__( 'Avatar published privately', 'w4os' ),
			'item_reverted_to_draft'   => esc_html__( 'Avatar reverted to draft', 'w4os' ),
			'item_scheduled'           => esc_html__( 'Avatar scheduled', 'w4os' ),
			'item_updated'             => esc_html__( 'Avatar updated', 'w4os' ),
			'text_domain'              => 'w4os',
		);
		$args   = array(
			'label'               => esc_html__( 'Avatars', 'w4os' ),
			'labels'              => $labels,
			'description'         => '',
			'public'              => true,
			'hierarchical'        => false,
			'exclude_from_search' => false,
			'publicly_queryable'  => true,
			'show_ui'             => true,
			'show_in_nav_menus'   => true,
			'show_in_admin_bar'   => true,
			'show_in_rest'        => true,
			'query_var'           => true,
			'can_export'          => true,
			'delete_with_user'    => true,
			'has_archive'         => true,
			'rest_base'           => '',
			'show_in_menu'        => 'w4os',
			'menu_icon'           => 'dashicons-universal-access',
			'capability_type'     => 'post',
			'supports'            => false, // ['author','thumbnail'],
			'taxonomies'          => array(),
			'rewrite'             => array(
				'with_front' => false,
			),
		);

		register_post_type( 'avatar', $args );

		/**
		 * Add avatar-specific publish statuses
		 */
		register_post_status(
			'model',
			array(
				'label'                     => _x( 'Model', 'avatar' ),
				'public'                    => false,
				'post_type'                 => 'avatar',
				'exclude_from_search'       => true,
				'show_in_admin_all_list'    => false, // actuelly means "count in section 'all' of status list"
				'show_in_admin_status_list' => true,
				'label_count'               => _n_noop( 'Model <span class="count">(%s)</span>', 'Models <span class="count">(%s)</span>' ),
			)
		);
		register_post_status(
			'service',
			array(
				'label'                     => _x( 'Service Account', 'avatar' ),
				'public'                    => false,
				'post_type'                 => 'avatar',
				'exclude_from_search'       => true,
				'show_in_admin_all_list'    => false,
				'show_in_admin_status_list' => true,
				'label_count'               => _n_noop( 'Service Account <span class="count">(%s)</span>', 'Service Accounts <span class="count">(%s)</span>' ),
			)
		);
		register_post_status(
			'banned',
			array(
				'label'                     => _x( 'Banned Account', 'avatar' ),
				'public'                    => false,
				'post_type'                 => 'avatar',
				'exclude_from_search'       => true,
				'show_in_admin_all_list'    => false,
				'show_in_admin_status_list' => true,
				'label_count'               => _n_noop( 'Banned Account <span class="count">(%s)</span>', 'Banned Accounts <span class="count">(%s)</span>' ),
			)
		);

		/**
		 * Remove avatar delete capabilities for very add_role
		 * (this has to be done on OpenSimulator side, or the avatars will be created again)
		 */

		global $wp_roles;
		$remove_caps = array(
			'delete_avatar',
			'delete_avatars',
		);

		$roles          = $wp_roles->roles;
		$editable_roles = apply_filters( 'editable_roles', $roles );

		foreach ( $roles as $role_slug => $role_array ) {
			$role = get_role( $role_slug );

			foreach ( $remove_caps as $cap ) {
				$role->remove_cap( $cap );
			}
		}
	}

	static function metaboxes_avatar( $meta_boxes ) {

		$prefix               = 'avatar_';
		$meta_boxes['avatar'] = array(
			'title'      => __( 'Profile', 'w4os' ),
			'id'         => 'avatar-profile-fields',
			'post_types' => array( 'avatar' ),
			'context'    => 'after_title',
			'style'      => 'seamless',
			'fields'     => array(
				'name'  => array(
					// 'name'     => __( 'Avatar Name', 'w4os' ),
					// 'id'       => $prefix . 'name',
					// 'type'     => 'hidden',
					// 'std'   => 'saved ' . self::current_avatar_name(),
					// 'callback' => __CLASS__ . '::current_avatar_name',
					// ],
					// 'saved_name' => [
						// 'name'     => __( 'Avatar Name', 'w4os' ),
						'id' => $prefix . 'name',
					'type'   => 'custom_html',
					'std'    => '<h1>' . self::current_avatar_name() . '</h1>',
					// 'admin_columns' => [
					// 'position'   => 'replace title',
					// 'sort'       => true,
					// 'searchable' => true,
					// 'filterable' => true,
					// ],
					// 'callback' => __CLASS__ . '::current_avatar_name',
				),
				array(
					'name'          => __( 'WordPress User', 'w4os' ),
					'id'            => $prefix . 'owner',
					'type'          => 'user',
					'field_type'    => 'select_advanced',
					'columns'       => 4,
					'std'           => wp_get_current_user()->ID,
					'placeholder'   => __( 'Select a user', 'w4os' ),
					'admin_columns' => array(
						'position'   => 'after title',
						'sort'       => true,
						'searchable' => true,
						'filterable' => true,
					),
				),
				'email' => array(
					'name'          => __( 'E-mail', 'w4os' ),
					'id'            => $prefix . 'email',
					'type'          => 'email',
					'std'           => wp_get_current_user()->user_email,
					'admin_columns' => array(
						'position'   => 'after avatar_owner',
						'sort'       => true,
						'searchable' => true,
					),
					'columns'       => 4,
					// 'readonly' => (!W4OS3::is_new_post()),
					'desc'          => __( 'Optional. If set, the avatar will be linked to any matching WP user account.', 'w4os' ),
					'hidden'        => array(
						'when'     => array( array( 'avatar_owner', '!=', '' ) ),
						'relation' => 'or',
					),
				),
				array(
					'name'     => __( 'Create WP user', 'w4os' ),
					'id'       => $prefix . 'create_wp_user',
					'type'     => 'switch',
					'style'    => 'rounded',
					'columns'  => 2,
					'desc'     => __( '(not implemented)', 'w4os' ),
					'disabled' => true,
					'readonly' => true,
					'visible'  => array(
						'when'     => array( array( 'avatar_email', '!=', '' ), array( 'avatar_owner', '=', '' ) ),
						'relation' => 'and',
					),
				),
				array(
					'name'    => ( W4OS3::is_new_post() ) ? __( 'Password', 'w4os' ) : __( 'Change password', 'w4os' ),
					'id'      => $prefix . 'password',
					'type'    => 'password',
					'columns' => 4,
						// 'required' => W4OS3::is_new_post() &! current_user_can('administrator'),
				),
				array(
					'name'    => __( 'Confirm password', 'w4os' ),
					'id'      => $prefix . 'confirm_password',
					'type'    => 'password',
					'columns' => 4,
						// 'required' => W4OS3::is_new_post() &! current_user_can('administrator'),
				),
				array(
					'name'     => __( 'Same password as WP user', 'w4os' ),
					'id'       => $prefix . 'use_wp_password',
					'type'     => 'switch',
					'style'    => 'rounded',
					'std'      => false,
					'desc'     => __( '(not implemented)', 'w4os' ),
					'disabled' => true,
					'readonly' => true,
					'columns'  => 4,
					'visible'  => array(
						'when'     => array(
							array( 'avatar_owner', '!=', '' ),
							array( 'create_wp_user', '=', true ),
						),
						'relation' => 'or',
					),
				),
			),
		);
		if ( W4OS3::is_new_post() ) {
			$meta_boxes['avatar']['fields']                                       = array_merge(
				$meta_boxes['avatar']['fields'],
				array(
					'model' => array(
						'name'    => __( 'Model', 'w4os' ),
						'id'      => $prefix . 'model',
						'type'    => 'image_select',
						'options' => self::w4os_get_models_options(),
					),
				)
			);
			  $meta_boxes['avatar']['fields']['name']                             = array(
				  'name'        => __( 'Avatar Name', 'w4os' ),
				  'id'          => $prefix . 'name',
				  'type'        => 'text',
				  // 'disabled' => (!W4OS3::is_new_post()),
				  'readonly'    => ( ! W4OS3::is_new_post() ),
				  'required'    => true,
				  // Translators: Avatar name placeholder, only latin, unaccended characters, first letter uppercase, no spaces
				  'placeholder' => __( 'Firstname', 'w4os' ) . ' ' . __( 'Lastname', 'w4os' ),
				  'required'    => true,
				  // 'columns'     => 6,
				  'std'         => self::generate_name(),
				  'desc'        => ( W4OS3::is_new_post() ) ? __( 'The avatar name is permanent, it can\'t be changed later.', 'w4os' ) : '',
			  );
			  $meta_boxes['avatar']['validation']['rules'][ $prefix . 'name' ]    = array(
				  // 'maxlength' => 64,
				  'pattern' => W4OS_PATTERN_NAME, // Must have 9 digits
				  'remote'  => admin_url( 'admin-ajax.php?action=check_name_availability' ), // remote ajax validation
			  );
			  $meta_boxes['avatar']['validation']['messages'][ $prefix . 'name' ] = array(
				  'remote'  => 'This name is not available.',
				  'pattern' => __( 'Please provide first and last name, only letters and numbers, separated by a space.', 'w4os' ),
			  );

		} else {
			$meta_boxes['avatar']['fields']['uuid'] = array(
				'name'          => __( 'UUID', 'w4os' ),
				'id'            => $prefix . 'uuid',
				'type'          => 'text',
				'placeholder'   => __( 'Wil be set by the server', 'w4os' ),
				'std'           => self::current_avatar_uuid(),
				'disabled'      => true,
				'readonly'      => true,
				'save_field'    => false,
				'admin_columns' => array(
					'position'   => 'before date',
					// 'sort'     => true,
					'searchable' => true,
				),
				// 'save_field' => false,
				// 'visible'     => [
				// 'when'     => [['avatar_uuid', '!=', '']],
				// 'relation' => 'or',
				// ],
			);

			$meta_boxes['avatar']['fields']['lastseen'] = array(
				'name'          => __( 'Last seen', 'w4os' ),
				'id'            => $prefix . 'lastseen',
				'type'          => 'datetime',
				'timestamp'     => true,
				// 'disabled'      => true,
				'readonly'      => true,
				// 'save_field' => false,
				'admin_columns' => array(
					'position' => 'before date',
					'sort'     => true,
				),
				// 'visible'       => [
				// 'when'     => [['avatar_uuid', '!=', '']],
				// 'relation' => 'or',
				// ],
			);

			$meta_boxes['avatar']['fields']['born'] = array(
				'name'          => __( 'Born', 'w4os' ),
				'id'            => $prefix . 'born',
				'type'          => 'datetime',
				'timestamp'     => true,
				// 'disabled'      => true,
				'readonly'      => true,
				// 'save_field' => false,
				'admin_columns' => array(
					'position' => 'replace date',
					'sort'     => true,
				),
				// 'visible'       => [
				// 'when'     => [['avatar_uuid', '!=', '']],
				// 'relation' => 'or',
				// ],
			);

		}

		return $meta_boxes;
	}

	static function metaboxes_userprofile( $meta_boxes ) {
		$prefix = 'opensimulator_';

		$meta_boxes[] = array(
			'title'  => __( 'OpenSimulator', 'w4os' ),
			'id'     => 'opensimulator',
			'type'   => 'user',
			'fields' => array(
				array(
					'name'              => __( 'Avatars', 'w4os' ),
					'id'                => $prefix . 'avatars',
					'type'              => 'post',
					'post_type'         => array( 'avatar' ),
					'field_type'        => 'select_advanced',
					'multiple'          => true,
					'readonly'          => true,
					'disabled'          => true,
					'field_save'        => false,
					'desc'              => __( 'Go to avatar profile to manage your avatar', 'w4os' ),
					'admin_columns'     => array(
						'position'   => 'after email',
						'searchable' => true,
						'sort'       => true,
					),
					'sanitize_callback' => 'W4OS3_Avatar::validate_user_avatars',
				),
			),
		);

		return $meta_boxes;
	}

	function get_uuid() {
		return get_post_meta( $this->post, 'avatar_uuid', true );
	}

	static function password_match( $password, $confirm_password = null, $user = null ) {
		if ( $password == $confirm_password ) {
			return true;
		}

		w4os_notice( __( "Passwords don't match.", 'w4os' ), 'error' );
		return false;
	}

	static function update_password( $post_ID, $post_after, $post_before ) {
		if ( $post_after->post_type !== 'avatar' ) {
			return;
		}
		if ( empty( $_POST['avatar_password'] ) ) {
			return;
		}
		$avatar = new W4OS3_Avatar( $post_after );
		if ( w4os_empty( $avatar->uuid ) ) {
			return;
		}
		global $w4osdb;

		if ( ! self::password_match( $_POST['avatar_password'], $_POST['avatar_confirm_password'] ) ) {
			return false;
		}

		$password = stripcslashes( $_POST['avatar_password'] );
		$salt     = md5( w4os_gen_uuid() );
		$hash     = md5( md5( $password ) . ':' . $salt );

		$new = array(
			'UUID'         => $avatar->uuid,
			'passwordHash' => $hash,
			'passwordSalt' => $salt,
		);
		$w4osdb->replace( 'auth', $new );

		if ( is_wp_error( $new ) ) {
			error_log( $new->get_error_message() );
			wp_redirect( $_POST['referredby'] );
			die();
		}
	}

	static function save_post_action( $post_ID, $post, $update ) {
		if ( ! $update ) {
			return;
		}
		if ( W4OS3::is_new_post() ) {
			return; // triggered when opened new post page, empty
		}
		if ( $post->post_type != 'avatar' ) {
			return;
		}
		if ( isset( $_REQUEST['action'] ) && $_REQUEST['action'] == 'trash' && ( $_REQUEST['post'] == $post->ID || in_array( $post->ID, $_REQUEST['post'] ) ) ) {
			return;
		}

		remove_action( 'save_post_avatar', __CLASS__ . '::' . __FUNCTION__ );

		$avatar = new self( $post->ID );

		if ( empty( $avatar->name ) ) {
			$avatar->name = sanitize_text_field( isset( $_POST['avatar_name'] ) ? $_POST['avatar_name'] : null );
		}
		if ( empty( $avatar->name ) ) {
			$avatar->name = get_post_meta( $post->ID, 'avatar_name', true );
		}

		$avatar->owner = ( isset( $_POST['avatar_owner'] ) ) ? sanitize_text_field( $_POST['avatar_owner'] ) : null;
		$avatar->owner = ( intval( $avatar->owner ) == (int) $avatar->owner ) ? $avatar->owner : null;
		if ( ! empty( $avatar->owner ) ) {
			$avatar->email = get_user_by( 'id', $avatar->owner )->user_email;
		} else {
			$avatar->email = ( isset( $_POST['avatar_email'] ) ) ? sanitize_email( $_POST['avatar_email'] ) : null;
		}

		$uuid = $avatar->uuid;
		if ( w4os_empty( $uuid ) ) {
			$uuid = self::get_uuid_by_name( $avatar->name );
		}
		if ( w4os_empty( $uuid ) && $avatar->name != 'TEMPORARY UNDEFINED' ) {
			$uuid = $avatar->create();
			if ( ! w4os_empty( $uuid ) && ! empty( $avatar->name ) ) {
			} else {
				if ( isset( $_POST['referredby'] ) ) {
					wp_redirect( $_POST['referredby'] );
					die();
				} else {
					error_log(
						"Could not create $avatar->name and redirect failed<pre>"
						. "\nuuid " . print_r( $uuid, true )
						. "\navatar " . print_r( $avatar, true )
						. "\ndata " . print_r( $data, true )
						. "\npostarr " . print_r( $postarr, true )
						. "\nREQUEST " . print_r( $_REQUEST, true )
						. '</pre>'
					);
				}
			}
		}
		$avatar->uuid = $uuid;

		$avatar->get_simulator_data();
		$avatar->sync_single_avatar();

		$avatar->set_thumbnail();
		return;
	}

	function create( $avatar = null, $data = array(), $postarr = array() ) {
		if ( ! W4OS_DB_CONNECTED ) {
			return;
		}
		if ( ! is_object( $avatar ) ) {
			$avatar = $this;
		}
		if ( ! is_object( $avatar ) ) {
			return;
		}
		if ( $_REQUEST['action'] == 'trash' && ( $_REQUEST['post'] == $avatar->ID || in_array( $avatar->ID, $_REQUEST['post'] ) ) ) {
			return;
		}

		global $w4osdb;
		$errors = false;

		$uuid = self::get_uuid_by_name( $avatar->name );
		if ( $uuid ) {
			w4os_notice( __( 'This user already has an avatar.', 'w4os' ), 'fail' );
			error_log( 'This user already has an avatar' );
			return $uuid;
		}

		if ( ! self::check_name_availability( $avatar->name ) ) {
			w4os_notice( __( 'This name is not available.', 'w4os' ), 'error' );
			return false;
		}

		$parts     = explode( ' ', $avatar->name );
		$FirstName = $parts[0];
		$LastName  = $parts[1];

		$model = esc_attr( empty( $postarr['avatar_model'] ) ? W4OS_DEFAULT_AVATAR : $postarr['avatar_model'] );

		if ( isset( $postarr['avatar_password'] ) && $postarr['avatar_password'] != $postarr['avatar_confirm_password'] ) {
			w4os_notice( __( "Passwords don't match.", 'w4os' ), 'error' );
			return false;

			// TODO: also check if same pwd as user if use_wp_password is set
			// $owner = get_user($postarr['avatar_owner']);
			// if ( ! wp_check_password($params['w4os_password_1'], $owner->user_pass, $owner->ID)) {
			// $errors=true; w4os_notice(__("The password does not match.", 'w4os'), 'error') ;
			// return false;
			// }
		}
		$password = ( isset( $postarr['avatar_password'] ) ) ? stripcslashes( $postarr['avatar_password'] ) : null;

		$newavatar_uuid = w4os_gen_uuid();
		$check_uuid     = $w4osdb->get_var( "SELECT PrincipalID FROM UserAccounts WHERE PrincipalID = '$newavatar_uuid'" );
		if ( $check_uuid ) {
			w4os_notice( __( 'This should never happen! Generated a random UUID that already existed. Sorry. Try again.', 'w4os' ), 'fail' );
			return false;
		}

		$salt         = md5( w4os_gen_uuid() );
		$hash         = md5( md5( $password ) . ':' . $salt );
		$created      = time();
		$HomeRegionID = $w4osdb->get_var( "SELECT UUID FROM regions WHERE regionName = '" . W4OS_DEFAULT_HOME . "'" );
		if ( empty( $HomeRegionID ) ) {
			$HomeRegionID = '00000000-0000-0000-0000-000000000000';
		}

		if ( ! empty( $avatar->owner ) ) {
			$owner         = get_user_by( 'id', $avatar->owner );
			$avatar->email = $owner->user_email;
		} else {
			$avatar->email = sanitize_email( $postarr['avatar_email'] );
		}

		$result = $w4osdb->insert(
			'UserAccounts',
			array(
				'PrincipalID' => $newavatar_uuid,
				'ScopeID'     => W4OS_NULL_KEY,
				'FirstName'   => $FirstName,
				'LastName'    => $LastName,
				'Email'       => $avatar->email,
				'ServiceURLs' => 'HomeURI= InventoryServerURI= AssetServerURI=',
				'Created'     => $created,
			)
		);
		if ( ! $result ) {
			w4os_notice( __( 'Error while creating user', 'w4os' ), 'fail' );
		} else {
			$result = $w4osdb->insert(
				'auth',
				array(
					'UUID'         => $newavatar_uuid,
					'passwordHash' => $hash,
					'passwordSalt' => $salt,
					'webLoginKey'  => W4OS_NULL_KEY,
				)
			);
		}
		if ( ! $result ) {
			w4os_notice( __( 'Error while setting password', 'w4os' ), 'fail' );
		} else {
			$result = $w4osdb->insert(
				'GridUser',
				array(
					'UserID'       => $newavatar_uuid,
					'HomeRegionID' => $HomeRegionID,
					'HomePosition' => '<128,128,21>',
					'LastRegionID' => $HomeRegionID,
					'LastPosition' => '<128,128,21>',
				)
			);
		}
		if ( ! $result ) {
			w4os_notice( __( 'Error while setting home region', 'w4os' ), 'fail' );
		} else {
			$model_firstname = strstr( $model, ' ', true );
			$model_lastname  = trim( strstr( $model, ' ' ) );
			$model_uuid      = $w4osdb->get_var( "SELECT PrincipalID FROM UserAccounts WHERE FirstName = '$model_firstname' AND LastName = '$model_lastname'" );
			if ( w4os_empty( $model_uuid ) ) {
				error_log(
					sprintf(
						'%s could not find model %s',
						__CLASS__ . '->' . __FUNCTION__ . '()',
						"$model_firstname $model_lastname"
					)
				);
			}

			$inventory_uuid = w4os_gen_uuid();
			$result         = $w4osdb->insert(
				'inventoryfolders',
				array(
					'folderName'     => 'My Inventory',
					'type'           => 8,
					'version'        => 1,
					'folderID'       => $inventory_uuid,
					'agentID'        => $newavatar_uuid,
					'parentFolderID' => W4OS_NULL_KEY,
				)
			);
		}
		if ( ! $result ) {
			w4os_notice( __( 'Error while creating user inventory', 'w4os' ), 'fail' );
		} else {
			$bodyparts_uuid       = w4os_gen_uuid();
			$bodyparts_model_uuid = w4os_gen_uuid();
			$currentoutfit_uuid   = w4os_gen_uuid();
			$folders              = array(
				array( 'Textures', 0, 1, w4os_gen_uuid(), $inventory_uuid ),
				array( 'Sounds', 1, 1, w4os_gen_uuid(), $inventory_uuid ),
				array( 'Calling Cards', 2, 2, w4os_gen_uuid(), $inventory_uuid ),
				array( 'Landmarks', 3, 1, w4os_gen_uuid(), $inventory_uuid ),
				array( 'Photo Album', 15, 1, w4os_gen_uuid(), $inventory_uuid ),
				array( 'Clothing', 5, 3, w4os_gen_uuid(), $inventory_uuid ),
				array( 'Objects', 6, 1, w4os_gen_uuid(), $inventory_uuid ),
				array( 'Notecards', 7, 1, w4os_gen_uuid(), $inventory_uuid ),
				array( 'Scripts', 10, 1, w4os_gen_uuid(), $inventory_uuid ),
				array( 'Body Parts', 13, 5, $bodyparts_uuid, $inventory_uuid ),
				array( 'Trash', 14, 1, w4os_gen_uuid(), $inventory_uuid ),
				array( 'Animations', 20, 1, w4os_gen_uuid(), $inventory_uuid ),
				array( 'Gestures', 21, 1, w4os_gen_uuid(), $inventory_uuid ),
				array( 'Lost And Found', 16, 1, w4os_gen_uuid(), $inventory_uuid ),
				array( 'Current Outfit', 46, 1, $currentoutfit_uuid, $inventory_uuid ),
				// array('My Outfits', 48, 1, $myoutfits_uuid, $inventory_uuid ),
				// array("$model_firstname $model_lastname", 47, 1, $myoutfits_model_uuid, $myoutfits_uuid ),
				// array('Friends', 2, 2, w4os_gen_uuid(), $inventory_uuid ),
				// array('Favorites', 23, w4os_gen_uuid(), $inventory_uuid ),
				// array('All', 2, 1, w4os_gen_uuid(), $inventory_uuid ),
			);
			if ( ! w4os_empty( $model_uuid ) ) {
				$folders[] = array( "$model_firstname $model_lastname outfit", -1, 1, $bodyparts_model_uuid, $bodyparts_uuid );
			}
			foreach ( $folders as $folder ) {
				$name     = $folder[0];
				$type     = $folder[1];
				$version  = $folder[2];
				$folderid = $folder[3];
				$parentid = $folder[4];
				if ( $result ) {
					$result = $w4osdb->insert(
						'inventoryfolders',
						array(
							'folderName'     => $name,
							'type'           => $type,
							'version'        => $version,
							'folderID'       => $folderid,
							'agentID'        => $newavatar_uuid,
							'parentFolderID' => $parentid,
						)
					);
				}
				if ( ! $result ) {
					w4os_notice( __( "Error while adding folder $folder", 'w4os' ), 'fail' );
					break;
				}
			}
		}

		// if ( $result ) {
		// $result = $w4osdb->get_results("SELECT folderName,type,version FROM inventoryfolders WHERE agentID = '$model_uuid' AND type != 8");
		// if($result) {
		// foreach($result as $row) {
		// $result = $w4osdb->insert (
		// 'inventoryfolders', array (
		// 'folderName' => $row->folderName,
		// 'type' => $row->type,
		// 'version' => $row->version,
		// 'folderID' => w4os_gen_uuid(),
		// 'agentID' => $newavatar_uuid,
		// 'parentFolderID' => $inventory_uuid,
		// )
		// );
		// if( ! $result ) break;
		// }
		// }
		// }

		if ( $result & ! w4os_empty( $model_uuid ) ) {
			// TODO: add default ruth values if no model found

			$model = $w4osdb->get_results( "SELECT Name, Value FROM Avatars WHERE PrincipalID = '$model_uuid'" );
			// w4os_notice(print_r($result, true), 'code');
			// foreach($result as $row) {
			// w4os_notice(print_r($row, true), 'code');
			// w4os_notice($row->Name . " = " . $row->Value);
			// }

			// foreach($avatars_prefs as $var => $val) {
			if ( $model ) {
				foreach ( $model as $row ) {
					unset( $newitem );
					unset( $newitems );
					unset( $newvalues );
					$Name  = $row->Name;
					$Value = $row->Value;
					if ( strpos( $Name, 'Wearable' ) !== false ) {
						// Must add a copy of item in inventory
						$uuids           = explode( ':', $Value );
						$item            = $uuids[0];
						$asset           = $uuids[1];
						$destinventoryid = $w4osdb->get_var( "SELECT inventoryID FROM inventoryitems WHERE assetID='$asset' AND avatarID='$newavatar_uuid'" );
						if ( ! $destinventoryid ) {
							$newitem                = $w4osdb->get_row( "SELECT * FROM inventoryitems WHERE assetID='$asset' AND avatarID='$model_uuid'", ARRAY_A );
							$destinventoryid        = w4os_gen_uuid();
							$newitem['inventoryID'] = $destinventoryid;
							$newitems[]             = $newitem;
							$Value                  = "$destinventoryid:$asset";
						}
					} elseif ( strpos( $Name, '_ap_' ) !== false ) {
						$items = explode( ',', $Value );
						foreach ( $items as $item ) {
							$asset           = $w4osdb->get_var( "SELECT assetID FROM inventoryitems WHERE inventoryID='$item'" );
							$destinventoryid = $w4osdb->get_var( "SELECT inventoryID FROM inventoryitems WHERE assetID='$asset' AND avatarID='$newavatar_uuid'" );
							if ( ! $destinventoryid ) {
								$newitem                = $w4osdb->get_row( "SELECT * FROM inventoryitems WHERE assetID='$asset' AND avatarID='$model_uuid'", ARRAY_A );
								$destinventoryid        = w4os_gen_uuid();
								$newitem['inventoryID'] = $destinventoryid;
								// $Value = $destinventoryid;
								$newitems[]  = $newitem;
								$newvalues[] = $destinventoryid;
							}
						}
						if ( $newvalues ) {
							$Value = implode( ',', $newvalues );
						}
					}
					if ( ! empty( $newitems ) ) {
						foreach ( $newitems as $newitem ) {
							// $destinventoryid = w4os_gen_uuid();
							// w4os_notice("Creating inventory item '$Name' for $firstname");
							$newitem['parentFolderID'] = $bodyparts_model_uuid;
							$newitem['avatarID']       = $newavatar_uuid;
							$result                    = $w4osdb->insert( 'inventoryitems', $newitem );
							if ( ! $result ) {
								w4os_notice( __( 'Error while adding inventory item', 'w4os' ), 'fail' );
							}
							// w4os_notice(print_r($newitem, true), 'code');
							// echo "<pre>" . print_r($newitem, true) . "</pre>"; exit;

							// Adding aliases in "Current Outfit" folder to avoid FireStorm error message
							$outfit_link                   = $newitem;
							$outfit_link['assetType']      = 24;
							$outfit_link['assetID']        = $newitem['inventoryID'];
							$outfit_link['inventoryID']    = w4os_gen_uuid();
							$outfit_link['parentFolderID'] = $currentoutfit_uuid;
							$result                        = $w4osdb->insert( 'inventoryitems', $outfit_link );
							if ( ! $result ) {
								w4os_notice( __( 'Error while adding inventory outfit link', 'w4os' ), 'fail' );
							}
						}
						// } else {
						// w4os_notice("Not creating inventory item '$Name' for $firstname");
					}
					$result = $w4osdb->insert(
						'Avatars',
						array(
							'PrincipalID' => $newavatar_uuid,
							'Name'        => $Name,
							'Value'       => $Value,
						)
					);
					if ( ! $result ) {
						w4os_notice( __( 'Error while adding avatar', 'w4os' ), 'fail' );
					}
				}
			} else {
				error_log(
					sprintf(
						'%s could find model %s\'s inventory items',
						__FUNCTION__,
						"$model_firstname $model_lastname"
					)
				);
			}
		}

		if ( ! $result ) {
			// TODO: delete sql rows created during the process

			$message = sprintf( __( 'Errors occurred while creating avatar %s', 'w4os' ), "$FirstName $LastName" );
			w4os_notice( sprintf( $message, "$FirstName $LastName" ), 'fail' );
			error_log( sprintf( $message, "$FirstName $LastName" ) );
			return false;
		}

		// $avatar->uuid = $newavatar_uuid;
		if ( $avatar->ID ) {
			update_post_meta( $avatar->ID, 'avatar_uuid', $newavatar_uuid );
		}

		return $newavatar_uuid;
		// w4os_notice(sprintf( __('Avatar %s created successfully.', 'w4os' ), "$FirstName $LastName" ), 'success' );
	}


	/**
	 * example row action link for avatar post type
	 */
	// static function add_row_action_links($actions, $post) {
	// if( 'avatar' == $post->post_type )
	// $actions['google_link'] = sprintf(
	// '<a href="%s" class="google_link" target="_blank">%s</a>',
	// 'http://google.com/search?q=' . $post->post_title,
	// sprintf(__('Search %s on Google', 'w4os'), $post->post_title),
	// );
	//
	// return $actions;
	// }

	static function sanitize_name( $value, $field = array(), $oldvalue = null, $object_id = null ) {
		// return $value;
		$return = sanitize_text_field( $value );
		$return = remove_accents( $return );

		$return = substr( preg_replace( '/(' . W4OS_PATTERN_NAME . ')[^[:alnum:]]*/', '$1', $return ), 0, 64 );
		if ( $value != $return & ! empty( $field['name'] ) ) {
			w4os_notice(
				sprintf(
					__( '%1$s contains invalid characters, replaced "%2$s" by "%3$s"', 'w4os' ),
					$field['name'],
					wp_specialchars_decode( strip_tags( stripslashes( $value ) ) ),
					esc_attr( $return ),
				),
				'warning'
			);
		}
		return $return;
	}

	static function w4os_get_profile_picture() {
		$options = array(
			w4os_get_asset_url(),
		);
		return $options;
	}

	static function w4os_get_models_options() {
		global $w4osdb;
		$results = array();

		$models    = $w4osdb->get_results(
			"SELECT FirstName, LastName, profileImage, profileAboutText
	    FROM UserAccounts LEFT JOIN userprofile ON PrincipalID = userUUID
	    WHERE active = true
	    AND (FirstName = '" . get_option( 'w4os_model_firstname' ) . "'
	    OR LastName = '" . get_option( 'w4os_model_lastname' ) . "')
	    ORDER BY FirstName, LastName"
		);
		$results[] = w4os_get_asset_url( W4OS_NOTFOUND_PROFILEPIC );
		if ( $models ) {
			foreach ( $models as $model ) {
				$model_name             = $model->FirstName . ' ' . $model->LastName;
				$model_imgid            = ( w4os_empty( $model->profileImage ) ) ? W4OS_NOTFOUND_PROFILEPIC : $model->profileImage;
				$model_img_url          = w4os_get_asset_url( $model_imgid );
				$results[ $model_name ] = $model_img_url;
			}
		}
		return $results;
	}

	static function check_name_availability( $avatar_name ) {
		if ( ! preg_match( '/^' . W4OS_PATTERN_NAME . '$/', $avatar_name ) ) {
			return false;
		}

		// Check if name restricted
		$parts = explode( ' ', $avatar_name );
		foreach ( $parts as $part ) {
			if ( in_array( strtolower( $part ), array_map( 'strtolower', W4OS_DEFAULT_RESTRICTED_NAMES ) ) ) {
				return false;
			}
		}

		// Check if there is another avatar with this name in WordPress
		$wp_avatar = self::get_wpavatar_by_name( $avatar_name );
		if ( $wp_avatar ) {
			return false;
		}

		// check if there avatar exist in simulator
		$uuid = self::get_uuid_by_name( $avatar_name );
		if ( $uuid ) {
			return false;       }

		return true;
	}

	static function get_wpavatar_by_name( $avatar_name ) {
		$post_id  = false;
		$args     = array(
			'post_type'  => 'avatar',
			'order_by'   => 'ID',
			'meta_query' => array(
				array(
					'key'   => 'avatar_name',
					'value' => esc_sql( $avatar_name ),
				),
			),
		);
		$my_query = new WP_Query( $args );
		if ( $my_query->have_posts() ) {
			$post_id = $my_query->post->ID;
		}
		wp_reset_postdata();

		return $post_id;
	}

	static function get_uuid_by_name( $avatar_name ) {
		if ( ! W4OS_DB_CONNECTED ) {
			return false;
		}
		if ( empty( $avatar_name ) ) {
			return false;
		}
		if ( ! preg_match( '/^' . W4OS_PATTERN_NAME . '$/', $avatar_name ) ) {
			return false;
		}

		global $w4osdb;
		$parts     = explode( ' ', $avatar_name );
		$FirstName = $parts[0];
		$LastName  = $parts[1];

		$check_uuid = $w4osdb->get_var(
			sprintf(
				"SELECT PrincipalID FROM UserAccounts
			WHERE (FirstName = '%s' AND LastName = '%s')
			",
				esc_sql( $FirstName ),
				esc_sql( $LastName ),
			)
		);

		if ( $check_uuid ) {
			return $check_uuid;
		} else {
			return false;
		}
	}

	static function ajax_check_name_availability() {
		$avatar_name = esc_attr( $_GET['avatar_name'] );

		if ( self::check_name_availability( $avatar_name ) ) {
			echo 'true';
		} else {
			echo 'false';
		}
		die;
	}

	static function current_avatar_name() {
		global $post;
		if ( ! empty( $_REQUEST['post'] ) ) {
			if ( is_array( $_REQUEST['post'] ) ) {
				return;
			}
			$post_id = esc_attr( $_REQUEST['post'] );
			$post    = get_post( $post_id );
		}
		if ( $post ) {
			return $post->post_title;
		}
	}

	static function current_avatar_uuid() {
		global $post;
		if ( ! empty( $_REQUEST['post'] ) ) {
			if ( is_array( $_REQUEST['post'] ) ) {
				return;
			}
			$post_id = esc_attr( $_REQUEST['post'] );
			$post    = get_post( $post_id );
		}
		if ( $post ) {
			return get_post_meta( $post->ID, 'avatar_uuid', true );
		}
	}

	static function generate_name() {

		// Try WP User name first
		$user = wp_get_current_user();
		if ( $user ) {
			$name = self::sanitize_name(
				( empty( $user->display_name ) )
				? "$user->first_name $user->last_name"
				: $user->display_name
			);
			if ( self::check_name_availability( $name ) ) {
				return $name;
			}
		}

		// Fallback to random name with Faker library
		$faker = \Faker\Factory::create();
		for ( $i = 0; $i < 10; $i++ ) {
			// Limit attempts, we don't want to run forever, even if it's unlikely
			$name = self::sanitize_name( $faker->name );
			if ( self::check_name_availability( $name ) ) {
				return $name;
			}
		}

		// If still no name found, this is wrong, return false or error.
		return false;
	}

	static function count() {
		// TODO: count broken assets
		// SELECT inventoryname, inventoryID, assetID, a.id FROM inventoryitems LEFT JOIN assets AS a ON id = assetID WHERE a.id IS NULL;

		if ( ! W4OS_DB_CONNECTED ) {
			return;
		}
		global $wpdb, $w4osdb;
		if ( ! isset( $wpdb ) ) {
			return false;
		}
		if ( ! isset( $w4osdb ) ) {
			return false;
		}

		$count['wp_users']      = count_users()['total_users'];
		$count['grid_accounts'] = 0;    // Deprecated in 3.0
		$count['wp_linked']     = 0;            // Deprecated in 3.0
		$count['wp_only']       = null;           // Deprecated in 3.0
		$count['grid_only']     = null;     // Deprecated in 3.0
		$count['sync']          = 0;                     // Deprecated in 3.0?

		$count['models'] = $w4osdb->get_var(
			sprintf(
				"SELECT count(*) FROM UserAccounts
			WHERE FirstName = '%s'
			OR LastName = '%s'
			",
				get_option( 'w4os_model_firstname' ),
				get_option( 'w4os_model_lastname' ),
			)
		);

		$count['tech'] = $w4osdb->get_var(
			sprintf(
				"SELECT count(*) FROM UserAccounts
			WHERE (Email IS NULL OR Email = '')
			AND FirstName != '%s'
			AND LastName != '%s'
			",
				esc_sql( get_option( 'w4os_model_firstname' ) ),
				esc_sql( get_option( 'w4os_model_lastname' ) ),
			)
		);

		/**
		 * Will be deprecated in 3.0
		 *
		 * @var [type]
		 */
		$accounts = self::get_avatars_ids_and_uuids();
		foreach ( $accounts as $key => $account ) {
			if ( ! isset( $account['w4os_uuid'] ) ) {
				$account['w4os_uuid'] = null;
			}
			if ( ! w4os_empty( $account['w4os_uuid'] ) ) {
				$count['wp_linked']++;
			}
			if ( ! isset( $account['PrincipalID'] ) ) {
				$account['PrincipalID'] = null;
			}

			if ( ! w4os_empty( $account['PrincipalID'] ) ) {
				$count['grid_accounts']++;
				if ( $account['PrincipalID'] == $account['w4os_uuid'] || ! empty( $account['ID'] ) ) {
					$count['sync']++;
				} else {
					// error_log("grid only " . print_r($account, true));
					$count['grid_only'] += 1;
				}
			} else {
				// error_log($account['avatar_name'] . ' no PrincipalID ' . print_r($account, true));
				$account['PrincipalID'] = null;
				// if(isset($account['w4os_uuid']) &! w4os_empty($account['w4os_uuid'])) {
				if ( ! empty( $account['ID'] ) ) {
					$count['wp_only']++;
				} else {
					$count['grid_only'] += 1;
				}
				// } else {
				// $count['grid_only'] += 1;
				// }
			}
		}
		// End deprecated

		return $count;
	}

	static function get_avatars_ids_and_uuids() {
		if ( ! W4OS_DB_CONNECTED ) {
			return;
		}
		global $wpdb, $w4osdb;
		if ( ! isset( $wpdb ) ) {
			return false;
		}
		if ( ! isset( $w4osdb ) ) {
			return false;
		}

		$GridAccounts = $w4osdb->get_results(
			sprintf(
				"SELECT CONCAT(FirstName, ' ', LastName) as avatar_name, PrincipalID, Email as email FROM UserAccounts
			WHERE active = 1
			AND (FirstName != 'GRID' OR LastName != 'SERVICES')
			AND FirstName != ''
			AND LastName != ''
			",
				esc_sql( get_option( 'w4os_model_firstname' ) ),
				esc_sql( get_option( 'w4os_model_lastname' ) ),
			),
			OBJECT_K
		);
		// AND Email is not NULL AND Email != ''

		foreach ( $GridAccounts as $key => $row ) {
			// if(empty($row->email)) continue;
			// $GridAccounts[$row->email] = (array)$row;
			$accounts[ $key ] = (array) $row;
		}

		$avatars = $wpdb->get_results(
			"SELECT post_title as avatar_name, mail.meta_value as email, ID, user.meta_value as user_id, uuid.meta_value AS w4os_uuid
			FROM $wpdb->posts
			LEFT JOIN $wpdb->postmeta as mail ON ID = mail.post_id AND mail.meta_key = 'avatar_email'
			LEFT JOIN $wpdb->postmeta as user ON ID = user.post_id AND user.meta_key = 'avatar_owner'
			LEFT JOIN $wpdb->postmeta as uuid ON ID = uuid.post_id AND uuid.meta_key = 'avatar_uuid'
			WHERE post_type = 'avatar' AND ( post_status='publish' OR post_status='model' OR post_status='bot' OR post_status='service' ) ",
			OBJECT_K
		);

		foreach ( $avatars as $key => $row ) {
			if ( empty( $key ) ) {
				continue;
			}
			// $WPGridAccounts[$row->email] = (array)$row;
			if ( empty( $accounts[ $key ] ) ) {
				$accounts[ $key ] = (array) $row;
			} else {
				$accounts[ $key ] = array_merge( $accounts[ $key ], (array) $row );
			}
		}

		return $accounts;
	}

	/**
	 * Make sure avatar posts match OpenSimulator accounts. Executed on a regular
	 * basis by scheduled actions, and can be triggered manually with "Synchronize
	 * users now button" to get most recent changes immediately.
	 */
	static function sync_avatars() {
		if ( ! W4OS_DB_CONNECTED ) {
			return;
		}
		global $wpdb, $w4osdb;
		if ( ! isset( $wpdb ) ) {
			return false;
		}
		if ( ! isset( $w4osdb ) ) {
			return false;
		}

		W4OS3::update_option( 'w4os_sync_users', null );

		$accounts      = self::get_avatars_ids_and_uuids();
		$messages      = array();
		$users_created = array();
		$users_updated = array();
		foreach ( $accounts as $key => $account ) {
			// $user = @get_user_by('ID', $account['user_id']);
			// First cleanup NULL_KEY and other empty UUIDs
			if ( ! isset( $account['PrincipalID'] ) || w4os_empty( $account['PrincipalID'] ) ) {
				$account['PrincipalID'] = null;
			}
			if ( ! isset( $account['w4os_uuid'] ) || w4os_empty( $account['w4os_uuid'] ) ) {
				$account['w4os_uuid'] = null;
			}

			if ( isset( $account['ID'] ) ) {
				$avatar = new W4OS3_Avatar( $account['ID'] );
			} else {
				$avatar = new W4OS3_Avatar( $account );
			}

			if ( $avatar->is_orphan() || empty( $account['PrincipalID'] ) ) {
				/**
				 * Orphan: wp avatar without opensim account, delete it
				 */

				$message = "$avatar->name does not exist anymore on the grid";
				$result  = wp_delete_post( $avatar->ID, true );
				if ( is_wp_error( $result ) ) {
					error_log( "$message, but delete post failed " . $result > get_error_message() );
				} else {
					error_log( "$message, it has been deleted from WordPress." );
				}

				continue;
			}

			if ( $account['PrincipalID'] == $account['w4os_uuid'] ) {

				/**
				 * Already linked, silently resync
				 */
				$avatar->get_simulator_data();
				$avatar->sync_single_avatar();

			} elseif ( isset( $account['user_id'] ) & ! empty( $account['user_id'] ) ) {

				/**
				* TODO: wrong reference, but an avatar exists with same name as this WP post, update reference
				*/

				error_log(
					"$avatar->name wrong reference, but an avatar exists in WP, we should fix wrong reference (not implemented)"
					// . "avatar " . print_r($avatar, true)
					// . "account " . print_r($account, true)
				);
				// $result = W4OS_Avatar::sync_single_avatar($account['user_id'], $account['PrincipalID']);
				// if(W4OS_Avatar::sync_single_avatar($account['user_id'], $account['PrincipalID']))
				// $users_updated[] = sprintf('<a href=%s>%s %s</a>', get_edit_user_link($newid), $account['FirstName'], $account['LastName']);
				// else
				// $errors[] = '<p class=error>' .  sprintf(__('Error while updating %s %s (%s) %s', 'w4os'), $account['FirstName'], $account['LastName'], $account['email'], $result) . '</p>';

			} else {

				/**
				* Widow: opensim accoun without related wp avatar, create one
				*/

				$avatar->get_simulator_data();

				if ( $avatar->create_post() ) {
					// Succeed
				} else {
					error_log( "could not create avatar post for $avatar->name" );
				}
			}

			/**
			* TODO: create user if none and wp_create_users is enabled
			*/

			// $user = get_user_by('email', $account['email']);
			// if($user) {
			// assign to existing user
			// error_log("assign $avatar->name to $user->display_name");
			// } else if (W4OS::get_option('w4os_settings:create_wp_users', 'nothing')) {
			// create user if create_wp_users is true
			// error_log("TODO: create user $avatar->name");
			// $newid = wp_insert_user(array(
			// 'user_login' => w4os_create_user_login($account['FirstName'], $account['LastName'], $account['email']),
			// 'user_pass' => wp_generate_password(),
			// 'user_email' => $account['email'],
			// 'first_name' => $account['FirstName'],
			// 'last_name' => $account['LastName'],
			// 'role' => 'grid_user',
			// 'display_name' => trim($account['FirstName'] . ' ' . $account['LastName']),
			// ));
			// if(is_wp_error( $newid )) {
			// $errors[] = $newid->get_error_message();
			// } else if(W4OS_Avatar::sync_single_avatar($newid, $account['PrincipalID'])) {
			// $users_created[] = sprintf('<a href=%s>%s %s</a>', get_edit_user_link($newid), $account['FirstName'], $account['LastName']);
			// } else {
			// $errors[] = '<p class=error>' .  sprintf(__('Error while updating newly created user %s for %s %s (%s) %s', 'w4os'), $newid, $account['FirstName'], $account['LastName'], $account['email'], $result) . '</p>';
			// }
			// }

		}

		if ( ! empty( $users_updated ) ) {
			$messages[] = sprintf(
				_n(
					'%d reference updated',
					'%d references updated',
					count( $users_updated ),
					'w4os',
				),
				count( $users_updated )
			) . ': ' . join( ', ', $users_updated );
		}
		if ( ! empty( $users_created ) ) {
			$messages[] = '<p>' . sprintf(
				_n(
					'%d new WordPress account created',
					'%d new WordPress accounts created',
					count( $users_created ),
					'w4os',
				),
				count( $users_created )
			) . ': ' . join( ', ', $users_created );
		}
		if ( ! empty( $users_dereferenced ) ) {
			$messages[] = sprintf(
				_n(
					'%d broken reference removed',
					'%d broken references removed',
					count( $users_dereferenced ),
					'w4os',
				),
				count( $users_dereferenced )
			);
		}

		if ( ! empty( $errors ) ) {
			$messages[] = '<p class=sync-errors><ul><li>' . join( '</li><li>', $errors ) . '</p>';
		}

		/**
		 * Resync avatars shown in WP user profiles
		 */
		$users = get_users();
		foreach ( $users as $user ) {
			$user_avatars = self::get_user_avatars( $user->ID );
			delete_user_meta( $user->ID, 'opensimulator_avatars' );
			foreach ( $user_avatars as $user_avatar ) {
				add_user_meta( $user->ID, 'opensimulator_avatars', $user_avatar );
			}
		}

		if ( ! empty( $messages ) ) {
			return '<div class=messages><p>' . join( '</p><p>', $messages ) . '</div>';
		}
	}

	static function display_synchronization_status( $views = null ) {
        // Add main tabs
        self::add_main_tabs();

        // ...existing code...

        // Check the current main tab
        $current_main_tab = isset( $_GET['main_tab'] ) ? $_GET['main_tab'] : 'list';

        if ( $current_main_tab === 'settings' ) {
            // Render the settings page content
            W4OS3_Settings::render_settings_page();
            return $views;
        }

		$count = self::count();

		$messages = array();
		if ( $count['grid_only'] > 0 ) {
			$messages[] = sprintf(
				_n(
					'%d grid account has no linked WP account. Syncing will create a new WP account.',
					'%d grid accounts have no linked WP account. Syncing will create new WP accounts.',
					$count['grid_only'],
					'w4os'
				),
				$count['grid_only']
			);
		}
		if ( $count['wp_only'] > 0 ) {
			$messages[] = sprintf(
				_n(
					'%d WordPress avatar has no related account in OpenSimulator database (corrupt reference or deleted). Syncing accounts will delete it from WordPress database too.',
					'%d WordPress avatars have no related account in OpenSimulator database corrupt reference or deleted). Syncing accounts will remove these from WordPress database too.',
					$count['wp_only'],
					'w4os'
				),
				$count['wp_only']
			);
		}
		// if($count['tech'] > 0) {
		// $messages[] = sprintf(_n(
		// "%d grid avatar (other than models) has no email address, it is handled as a service account and is not displayed in avatars list.",
		// "%d grid avatars (other than models) have no email address, they are handled as service accounts and are not displayed in avatars list.",
		// $count['tech'],
		// 'w4os'
		// ), $count['tech'])
		// . ' '
		// . sprintf(
		// __('Users avatars need an email address for %s and %s to work properly.', 'w4os'),
		// '<em>OpenSimulator</em>',
		// '<em>w4os</em>',
		// );
		// }
		if ( $count['grid_only'] + $count['wp_only'] > 0 ) {
			echo '<p class=description>
			<form method="post" action="options.php" autocomplete="off">
			<input type="hidden" input-hidden" id="w4os_sync_users" name="w4os_sync_users" value="1">';
			settings_fields( 'w4os_status' );
			submit_button( __( 'Synchronize users now', 'w4os' ) );
			_e( 'Synchronization is made at plugin activation and is handled automatically afterwards, but in certain circumstances it may be necessary to initiate it manually to get an immediate result, especially if users have been added or deleted directly from the grid administration console.', 'w4os' );
			echo '</form></p>';
		}
		if ( ! empty( $sync_result ) ) {
			echo '<p class=info>' . $sync_result . '</p>';
		}
		// include(plugin_dir_path( dirname( __FILE__ ) ) . 'admin/partials/table-header-avatar.php');

		foreach ( $messages as $message ) {
			echo '<p>' . $message . '</p>';
		}
		return $views;
	}

	function is_model() {
		return ( $this->FirstName == get_option( 'w4os_model_firstname' ) || $this->FirstName == get_option( 'w4os_model_firstname' ) );
	}

	function is_service() {
		return ( ! $this->is_model() && empty( $this->email ) );
	}

	function avatar_status() {
		if ( $this->is_model() ) {
			return 'model';
		}
		if ( $this->is_service() ) {
			return 'service';
		}
		return 'publish';
	}

	static function remove_avatar_delete_row_actions( $actions, $post ) {
		if ( $post->post_type === 'avatar' ) {
			unset( $actions['clone'] );
			unset( $actions['trash'] );
		}
		return $actions;
	}

	static function avatar_deletion_warning( $post_id ) {
		if ( get_post_type( $post_id ) === 'avatar' ) {
				w4os_transient_admin_notice( __( 'Avatars can not be deleted from WordPress.', 'w4os' ), 'error' );
			// wp_die('The post you were trying to delete is protected.');
		}
	}

	static function get_user_avatars( $user_id ) {
		$avatars = array();
		if ( $user_id <= 0 ) {
			return $avatars;
		}

		$args = array(
			'post_type'  => 'avatar',
			'orderby'    => 'publish_date',
			'order'      => 'ASC',
			'meta_query' => array(
				array(
					'key'   => 'avatar_owner',
					'value' => $user_id,
				),
			),
		);
		$loop = new WP_Query( $args );
		while ( $loop->have_posts() ) {
			$loop->the_post();
			$avatars[] = get_the_ID();
		}

		wp_reset_query();
		wp_reset_postdata();
		return $avatars;
	}

	static function validate_user_avatars( $value, $field = array(), $oldvalue = null, $user_id = null ) {
		if ( $field['id'] != 'opensimulator_avatars' ) {
			return $value;
		}
		if ( empty( $user_id ) ) {
			return array();
		}

		return self::get_user_avatars( $user_id );
	}

	static function remove_avatar_edit_delete_action() {
		$current_screen = get_current_screen();

		// Hides the "Move to Trash" link on the post edit page.
		if ( 'post' === $current_screen->base &&
		'avatar' === $current_screen->post_type ) :
			?>
			<style>#delete-action { display: none; }</style>
			<?php
		endif;
	}

	static function get_post_by_name( $post_name, $args = array() ) {
		$args = array_merge(
			array(
				'posts_per_page' => 1,

				'name'           => trim( $post_name ),
				'orderby'        => 'ID',
				'order'          => 'ASC',
			),
			$args
		);

		$query = new WP_Query( $args );
		if ( $query->have_posts() ) {
			return $query->post->ID;
		}

		return false;
	}

	function set_thumbnail( $image_uuid = W4OS_NULL_KEY ) {

		if ( w4os_empty( $image_uuid ) ) {
			$image_uuid = $this->image;
		}
		if ( w4os_empty( $image_uuid ) ) {
			return;
		}

		if ( ! isset( $this->owner ) ) {
			$this->owner = get_post( $this->ID )->post_author;
		}
		$owner = $this->owner;

		// Avoid duplicates in the same session
		$cache_key = sanitize_title( __CLASS__ . '-' . __FUNCTION__ . " $owner $this->ID $image_uuid" );
		if ( wp_cache_get( $cache_key ) ) {
			return;
		}

		// First try profile folder structure, fallback to wp standard folders
		$upload_dir = w4os_upload_dir( 'profiles/' . $this->ID );
		if ( ! wp_mkdir_p( $upload_dir ) ) {
			$upload_dir = wp_upload_dir()['path'];
		}

		$asset_url  = w4os_get_asset_url( $image_uuid );
		$image_name = basename( $asset_url );

		// $unique_file_name = wp_unique_filename( $upload_dir, $image_name ); // Generate unique name
		// $filename         = basename( $unique_file_name ); // Create image file name
		$unique_file_name = $image_name;
		$filename         = $image_name;
		$post_name        = $image_uuid;
		$file             = $upload_dir . '/' . $filename;

		$attachment_id = self::get_post_by_name( $image_uuid, array( 'post_type' => 'attachment' ) );

		if ( ! $attachment_id ) {
			$image_data = file_get_contents( $asset_url ); // Get image data

			// Create the image  file on the server
			file_put_contents( $file, $image_data );

			// Check image file type
			$wp_filetype = wp_check_filetype( $filename, null );

			// Set attachment data
			$attachment = array(
				'post_author'    => $owner,
				'post_mime_type' => $wp_filetype['type'],
				'post_title'     => sanitize_text_field( $this->name ),
				'post_name'      => $post_name,
				'post_content'   => '',
				'post_status'    => 'inherit',
			);

			// Create the attachment
			$attachment_id = wp_insert_attachment( $attachment, $file, $this->ID );
		}

		// Include image.php
		require_once ABSPATH . 'wp-admin/includes/image.php';

		// Define attachment metadata
		$attach_data = wp_generate_attachment_metadata( $attachment_id, $file );

		// Assign metadata to attachment
		wp_update_attachment_metadata( $attachment_id, $attach_data );

		// And finally assign featured image to post
		$success = set_post_thumbnail( $this->ID, $attachment_id );

		wp_cache_set( $cache_key, $success );
		return;
	}

    public static function add_settings_button( $views ) {
        // Remove the existing Settings link
        unset( $views['settings'] );

        // Determine the current tab
        $current_tab = isset( $_GET['tab'] ) ? $_GET['tab'] : 'list';

        // Base URL for avatar post type
        $base_url = admin_url( 'edit.php?post_type=avatar' );

        // Add List tab
        $views['list'] = '<a href="' . esc_url( $base_url ) . '" class="' . ( $current_tab === 'list' ? 'current' : '' ) . '">' . __( 'List', 'w4os' ) . '</a>';

        // Add Settings tab
        $views['settings'] = '<a href="' . esc_url( add_query_arg( 'tab', 'settings', $base_url ) ) . '" class="' . ( $current_tab === 'settings' ? 'current' : '' ) . '">' . __( 'Settings', 'w4os' ) . '</a>';

        return $views;
    }

    public static function add_main_tabs() {
        // Determine the current main tab
        $current_main_tab = isset( $_GET['main_tab'] ) ? $_GET['main_tab'] : 'list';

        // Base URL for avatar post type
        $base_url = admin_url( 'edit.php?post_type=avatar' );
		$tabs = array(
			'list'     => array(
				'title' => __( 'List', 'w4os' ),
				'url'   => $base_url,
			),
			'settings' => array(
				'title' => __( 'Settings', 'w4os' ),
				'url'   => add_query_arg( 'main_tab', 'settings', $base_url ),
			),
		);
        echo '<h2 class="nav-tab-wrapper">';
		foreach ( $tabs as $tab => $data ) {
			$class = ( $current_main_tab === $tab ) ? 'nav-tab-active' : '';
			echo '<a href="' . esc_url( $data['url'] ) . '" class="nav-tab ' . $class . '">' . esc_html( $data['title'] ) . '</a>';
		}
        echo '</h2>';
    }
}
