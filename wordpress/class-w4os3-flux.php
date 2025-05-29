<?php
/**
 * Flux Posts Class
 *
 * Handles profile flux messages.
 *
 * Provides
 * - "Flux Post" post type for flux content
 * - Additional fields for avatar uuid
 * - Any required save/display filter to make sure the author is the avatar, not a wp user
 * - Social network-like flux display method
 * - Post field for authenticated avatars
 * - No taxonomy for now
 *
 * @package w4os
 * @since 3.0
 */

class W4OS3_Flux extends OpenSim_Flux {

	/**
	 * Constructor
	 */
	function __construct() {
		// No need to call init, it is called automatically at plugin load.
		$args = func_get_args();

		if ( empty( $args ) ) {
			return;
		}

		if ( is_uuid( $args[0], false ) ) {
			$this->avatar_uuid = $args[0];
			$this->avatar      = new W4OS3_Avatar( $this->avatar_uuid );
			$this->thumb       = $this->avatar->get_thumb();
			// $this->get_flux_posts();
		}
	}

	function init() {
		add_action( 'init', array( $this, 'register_post_type' ) );
		add_action( 'admin_menu', array( $this, 'register_admin_submenus' ) );
		add_action( 'admin_head', array( $this, 'set_active_submenu' ) );
		add_action( 'save_post_flux_post', array( $this, 'save_post' ), 10, 3 );
		add_action( 'init', array( $this, 'maybe_save_new_flux_post' ) );
		add_action( 'add_meta_boxes', array( $this, 'add_metaboxes' ) );

		// Manage columns
		add_filter( 'manage_flux_post_posts_columns', array( $this, 'add_author_column' ) );
		add_action( 'manage_flux_post_posts_custom_column', array( $this, 'render_author_column' ), 10, 2 );
		add_filter( 'manage_edit-flux_post_sortable_columns', array( $this, 'make_author_column_sortable' ) );
		add_action( 'pre_get_posts', array( $this, 'orderby_author' ) );
		add_action( 'pre_get_posts', array( $this, 'extend_admin_search' ) );

		// This should not be done here but inside functions requiring it with W4OS3::enqueue_script
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_infinite_scroll_script' ) );

		add_action( 'wp_ajax_load_more_flux_posts', array( $this, 'load_more_flux_posts' ) );
		add_action( 'wp_ajax_nopriv_load_more_flux_posts', array( $this, 'load_more_flux_posts' ) );

		add_filter( 'the_title', array( $this, 'the_title' ), 10, 2 );
		add_filter( 'get_the_title', array( $this, 'the_title' ), 10, 2 );
		add_filter( 'the_content', array( $this, 'the_content' ) );
		add_filter( 'get_the_archive_title', array( $this, 'the_archive_title' ) );
	}

	/**
	 */
	function register_post_type() {
		$labels = array(
			'name'               => __( 'Flux Posts', 'w4os' ),
			'singular_name'      => __( 'Flux Post', 'w4os' ),
			'add_new'            => __( 'Add New', 'w4os' ),
			'add_new_item'       => __( 'Add New Flux Post', 'w4os' ),
			'edit_item'          => __( 'Edit Flux Post', 'w4os' ),
			'new_item'           => __( 'New Flux Post', 'w4os' ),
			'view_item'          => __( 'View Flux Post', 'w4os' ),
			'search_items'       => __( 'Search Flux Posts', 'w4os' ),
			'not_found'          => __( 'No Flux Posts found', 'w4os' ),
			'not_found_in_trash' => __( 'No Flux Posts found in Trash', 'w4os' ),
			'parent_item_colon'  => __( 'Parent Flux Post:', 'w4os' ),
			'menu_name'          => __( 'Flux Posts', 'w4os' ),
		);

		$flux_url_prefix = get_option( 'w4os-settings:flux-prefix', 'flux' );

		$args = array(
			'labels'             => $labels,
			'public'             => true,
			'publicly_queryable' => true,
			'show_ui'            => true,
			'show_in_menu'       => false, // Ensure it does not create a separate main menu item
			'query_var'          => true,
			'rewrite'            => array( 'slug' => $flux_url_prefix ),
			'capability_type'    => 'post',
			'has_archive'        => $flux_url_prefix,
			'hierarchical'       => false,
			'menu_position'      => null,
			'supports'           => array( 'editor' ),
			// 'show_in_rest'       => true,
			'show_in_admin_bar'  => true, // Ensure it shows in the admin bar
			'taxonomies'         => array( 'flux_category' ),
		);

		register_post_type( 'flux_post', $args );
	}

	/**
	 * Add metaboxes
	 */
	function add_metaboxes() {
		add_meta_box(
			'author_avatar',
			__( 'Author', 'w4os' ),
			array( $this, 'render_author_metabox' ),
			'flux_post',
			'side',
			'default'
		);
	}

	/**
	 * Render author metabox
	 */
	function render_author_metabox( $post ) {
		$content = '';
		$args    = array();

		$user_email = get_the_author_meta( 'user_email' );
		if ( ! $user_email ) {
			// Use current wp user email instead
			$user_email = wp_get_current_user()->user_email;
		}
		$args['Email'] = $user_email;
		$args          = array_filter( $args );

		$avatars = W4OS3_Avatar::get_avatars( array( 'Email' => $user_email ) );
		error_log( __FUNCTION__ . ' [DEBUG] avatars: ' . print_r( $avatars, true ) );
		
		// Allow admin to select any avatar
		if ( current_user_can( 'manage_options' ) ) {
			$avatars = array_merge(
				$avatars,
				W4OS3_Avatar::get_avatars()
			);
		}

		$avatar_uuid = get_post_meta( $post->ID, '_avatar_uuid', true );
		$avatar_name = get_post_meta( $post->ID, '_avatar_name', true );

		if ( $avatar_uuid ) {
			// Make sure current value is in the list
			$avatars = wp_parse_args(
				$avatars,
				array(
					$avatar_uuid => $avatar_name,
				)
			);
		} else {
			$avatar_uuid = key( $avatars );
			$avatar_name = current( $avatars );
		}

		// Build a select2 dropdown with avatars
		$content .= sprintf(
			'<select name="avatar_uuid" id="avatar_uuid" class="select2-field" %s>',
			( count( $avatars ) <= 1 ) ? 'disabled' : '',
		);
		// $content .= '<option value="">' . __( 'Select an avatar', 'w4os' ) . '</option>';
		foreach ( $avatars as $uuid => $name ) {
			$content .= sprintf(
				'<option value="%s" %s>%s</option>',
				$uuid,
				( $uuid === $avatar_uuid ) ? ' selected' : '',
				$name,
			);
		}
		$content .= '</select>';

		if ( $avatar_uuid ) {
			$avatar = new W4OS3_Avatar( $avatar_uuid );

			$profile_url = $avatar->get_profile_url();

			$avatar_url = $profile_url . '/' . $avatar_uuid;
			$content   .= sprintf(
				'<p>%s</p>',
				$avatar->profile_link(),
			);
		}

		W4OS3_Settings::enqueue_select2();
		echo $content;
	}

	public function format_author( $post_id ) {
		$avatar_uuid = get_post_meta( $post_id, '_avatar_uuid', true );
		$avatar_name = get_post_meta( $post_id, '_avatar_name', true );
		if ( ! $avatar_uuid ) {
			return 'No author';
		}
		$avatar      = new W4OS3_Avatar( $avatar_uuid );
		$profile_url = $avatar->get_profile_url();
		$content     = sprintf(
			'<a href="%s">%s</a>',
			esc_url( $profile_url ),
			esc_html( $avatar_name ),
		);
		return $content;
	}

	public function register_admin_submenus() {
		add_submenu_page(
			'w4os',
			__( 'Flux', 'w4os' ),
			W4OS_NEW_ICON . ' ' . __( 'Flux', 'w4os' ),
			'manage_options',
			'edit.php?post_type=flux_post',
			null,
			// 1
		);
	}

	/**
	 * Set active submenu for flux posts
	 */
	public function set_active_submenu() {
		global $parent_file, $submenu_file, $current_screen;

		if ( $current_screen->post_type === 'flux_post' ) {
			$parent_file  = 'w4os';
			$submenu_file = 'edit.php?post_type=flux_post';
		}
	}

	public function new_flux_form() {
		$avatar = $this->avatar ?? false;
		if ( $avatar && $avatar->Email == wp_get_current_user()->user_email ) {
			$thatsme = true;
		} else if ( $avatar ) {
			$session_avatar = W4OS3::session_avatar ();
			$thatsme        = $session_avatar ? ( $session_avatar->UUID == $avatar->UUID ) : false;
		}
		if ( ! $thatsme ) {
			return;
		}

		$content  = '';
		$content .= '<form id="new-flux-post-form" method="post">';
		if ( $this->avatar_uuid ) {
			$content .= '<input type="hidden" name="avatar_uuid" value="' . $this->avatar_uuid . '">';
		}
		// Add status message element
		$content .= sprintf(
			'<textarea name="flux-post-content" id="flux-post-content" class="autogrow" placeholder="%s"></textarea>',
			__( 'Your message...', 'w4os' )
		);
		$content .= sprintf(
			'<div id="submit-status" style="display:none;">%s</div>',
			__( 'Spreading your wisdom to the world...', 'w4os' ),
		);

		// Enqueue flux.js to load in the footer
		W4OS3::enqueue_script( 'v3-flux', 'wordpress/js/flux.js', array(), false, true );

		$content .= '</form>';
		return $content;
	}

	public function save_post( $post_id, $post, $update ) {
		if ( 'flux_post' !== $post->post_type ) {
			return;
		}
		$content = trim( wp_strip_all_tags( $post->post_content ) );
		$title   = wp_trim_words( $content, 10, '...' );
		if ( get_post_field( 'post_title', $post_id ) !== $title ) {
			wp_update_post(
				array(
					'ID'         => $post_id,
					'post_title' => $title,
				)
			);
		}
		if ( isset( $_POST['avatar_uuid'] ) ) {
			update_post_meta( $post_id, '_avatar_uuid', sanitize_text_field( $_POST['avatar_uuid'] ) );
			$avatar = new W4OS3_Avatar( $_POST['avatar_uuid'] );
			update_post_meta( $post_id, '_avatar_name', $avatar->get_name( $_POST['avatar_uuid'] ) );
		}
	}

	public function maybe_save_new_flux_post() {
		if ( ! empty( $_POST['flux-post-content'] ) ) {
			$this->save_new_flux_post();
			// Force reload to avoid resubmission
			wp_redirect( $_SERVER['REQUEST_URI'] );
			die();
		}
	}

	public function save_new_flux_post() {
		if ( ! isset( $_POST['flux-post-content'] ) ) {
			return;
		}
		$content = sanitize_text_field( $_POST['flux-post-content'] );
		$post_id = wp_insert_post(
			array(
				'post_title'   => wp_trim_words( $content, 10, '...' ),
				'post_content' => $content,
				'post_type'    => 'flux_post',
				'post_status'  => 'publish',
			)
		);
		if ( ! is_wp_error( $post_id ) ) {
			update_post_meta( $post_id, '_avatar_uuid', sanitize_text_field( $_POST['avatar_uuid'] ) );
			$avatar = new W4OS3_Avatar( $_POST['avatar_uuid'] );
			update_post_meta( $post_id, '_avatar_name', $avatar->get_name( $_POST['avatar_uuid'] ) );
			return 'Flux post created successfully';
		}
		return $post_id->get_error_message();
	}

	/**
	 * Get flux posts with pagination.
	 */
	public function get_flux_posts( $flux_paged = 1, $posts_per_page = 10 ) {
		if ( ! isset( $this->avatar_uuid ) ) {
			return;
		}
		$args       = array(
			'post_type'      => 'flux_post',
			'meta_query'     => array(
				array(
					'key'   => '_avatar_uuid',
					'value' => $this->avatar_uuid,
				),
			),
			'paged'          => $flux_paged,
			'posts_per_page' => $posts_per_page,
		);
		$flux_posts = get_posts( $args );
		return $flux_posts;
	}

	/**
	 * Display flux posts with pagination.
	 */
	public function display_flux() {
		$flux_paged = isset( $_GET['flux_paged'] ) ? intval( $_GET['flux_paged'] ) : 1;
		$flux_posts = $this->get_flux_posts( $flux_paged ) ?? array();
		$content    = '';

		$content      .= $this->new_flux_form();
		$in_world_call = W4OS3::in_world_call();

		$content .= '<div id="flux-posts-container">';
		foreach ( $flux_posts as $flux_post ) {
			// $content .= '<div class="flux-post">';
			if ( $in_world_call ) {
				$message = preg_replace( '/<a (.*?)>/', '<a $1 target="_blank">', $flux_post->post_content );
			} else {
				$message = $flux_post->post_content;
			}
			$content .= sprintf(
				'<div class="flux-post" id="flux-post-%d">
                    <div class=thumb>%s</div>
                    <div class="content">
                        <p class="name">%s</p>
                        <p class="date">%s</p>
                        <p class="message">%s</p>
                    </div>
                </div>',
				$flux_post->ID,
				$this->thumb,
				$this->avatar->AvatarName,
				$flux_post->post_date,
				$message
			);
		}
		$content .= '</div>';

		// Add pagination
		$total_posts = wp_count_posts( 'flux_post' )->publish;
		$total_pages = ceil( $total_posts / 10 );
		if ( $total_pages > 1 ) {
			$content .= '<div class="flux-pagination" style="display: none;"></div>';
		}

		// Add a loading indicator
		$content .= '<div id="flux-loading" style="display: none;">' . __( 'Loading more posts...', 'w4os' ) . '</div>';

		if ( ! empty( $content ) ) {
			$content = '<div class="flux">' . $content . '</div>';
		}
		return $content;
	}

	/**
	 * Enqueue Infinite Scroll JavaScript
	 */
	public function enqueue_infinite_scroll_script() {
		if ( is_page() ) { // Adjust the condition as needed
			// wp_enqueue_script(
			// 'infinite-scroll',
			// plugins_url( 'wordpress/js/flux.js', __FILE__ ),
			// array( 'jquery' ),
			// '1.0',
			// true
			// );
			wp_localize_script(
				'infinite-scroll',
				'flux_params',
				array(
					'ajax_url'       => admin_url( 'admin-ajax.php' ),
					'flux_paged'     => 2,
					'posts_per_page' => 10,
					'avatar_uuid'    => isset( $this->avatar_uuid ) ? $this->avatar_uuid : '',
				)
			);
		}
	}

	/**
	 * AJAX Handler to Load More Flux Posts
	 */
	public function load_more_flux_posts() {
		$paged          = isset( $_POST['paged'] ) ? intval( $_POST['paged'] ) : 1;
		$posts_per_page = isset( $_POST['posts_per_page'] ) ? intval( $_POST['posts_per_page'] ) : 10;
		$avatar_uuid    = isset( $_POST['avatar_uuid'] ) ? sanitize_text_field( $_POST['avatar_uuid'] ) : '';

		if ( ! $avatar_uuid ) {
			wp_send_json_error( 'No avatar UUID provided.' );
		}

		$args = array(
			'post_type'      => 'flux_post',
			'meta_query'     => array(
				array(
					'key'   => '_avatar_uuid',
					'value' => $avatar_uuid,
				),
			),
			'paged'          => $paged,
			'posts_per_page' => $posts_per_page,
		);

		$flux_posts = get_posts( $args );

		if ( empty( $flux_posts ) ) {
			wp_send_json_error( 'No more posts.' );
		}

		ob_start();
		foreach ( $flux_posts as $flux_post ) {
			// ...existing display code...
			if ( W4OS3::in_world_call() ) {
				$message = preg_replace( '/<a (.*?)>/', '<a $1 target="_blank">', $flux_post->post_content );
			} else {
				$message = $flux_post->post_content;
			}
			?>
			<div class="flux-post" id="flux-post-<?php echo esc_attr( $flux_post->ID ); ?>">
				<div class="thumb"><?php echo esc_html( $this->thumb ); ?></div>
				<div class="content">
					<p class="name"><?php echo esc_html( $this->avatar->AvatarName ); ?></p>
					<p class="date"><?php echo esc_html( $flux_post->post_date ); ?></p>
					<p class="message"><?php echo wp_kses_post( $message ); ?></p>
				</div>
			</div>
			<?php
		}
		$content = ob_get_clean();
		wp_send_json_success( $content );
	}

	public function add_author_column( $columns ) {
		$new_columns = array();
		foreach ( $columns as $key => $value ) {
			$new_columns[$key] = $value;
			if ( 'title' === $key ) {
				$new_columns['flux_author'] = __( 'Author', 'w4os' );
			}
		}
		return $new_columns;
	}

	public function render_author_column( $column, $post_id ) {
		if ( 'flux_author' === $column ) {
			echo $this->format_author( $post_id );
		}
	}

	public function make_author_column_sortable( $columns ) {
		$columns['flux_author'] = 'flux_author';
		return $columns;
	}

	public function orderby_author( $query ) {
		if ( ! is_admin() || ! $query->is_main_query() ) {
			return;
		}
		if ( 'flux_author' === $query->get( 'orderby' ) ) {
			$query->set( 'meta_key', '_avatar_name' );
			$query->set( 'orderby', 'meta_value' );
		}
	}

	function extend_admin_search( $query ) {
		if ( 'flux_post' !== $query->get( 'post_type' ) ) {
			return;
		}

		$custom_fields          = array( '_avatar_name' );
		$search_term            = $query->query_vars['s'];
		$query->query_vars['s'] = '';

		if ( $search_term != '' ) {
			$meta_query = array( 'relation' => 'OR' );
			foreach ( $custom_fields as $custom_field ) {
				array_push(
					$meta_query,
					array(
						'key'     => $custom_field,
						'value'   => $search_term,
						'compare' => 'LIKE',
					)
				);
			}
			$query->set( 'meta_query', $meta_query );
		}
	}

	public function the_title( $title, $post_id ) {
		if ( 'flux_post' === get_post_type( $post_id ) && in_the_loop() ) {
			return false;
		}
		return $title;
	}

	public function the_content( $content ) {
		if ( 'flux_post' === get_post_type() && in_the_loop() ) {
			$post_id     = get_the_ID();
			$avatar_uuid = get_post_meta( $post_id, '_avatar_uuid', true );
			if( empty( $avatar_uuid ) ) {
				return $content;
			}
			$avatar	  = new W4OS3_Avatar( $avatar_uuid );
			if ( ! $avatar ) {
				return $content;
			}

			W4OS3::enqueue_style( 'flux-posts-style', 'wordpress/css/flux-posts.css' );
			W4OS3::enqueue_style( 'flux-posts-style', 'wordpress/css/profile.css' );

			$post_date   = get_the_date( '', $post_id ) . ' ' . get_the_time( '', $post_id );
			$timestamp = get_the_time( 'U', $post_id );
			$post_date   = W4OS3::format_date( $timestamp, 'DATE_TIME' );

			$post_meta = sprintf(
				'<p class="flux-meta">
					<span class="flux-author">%s</span>
					<span class="flux-date">%s</span>
				</p>',
				$avatar->profile_link(),
				esc_html( $post_date ?? 'No date' )
			);

			$content = $post_meta . $content;
		}
		return $content;
	}

	public function the_archive_title( $title ) {
		if ( is_post_type_archive( 'flux_post' ) ) {
			$title = __( 'Flux', 'w4os' );
		}
		return $title;
	}
}
