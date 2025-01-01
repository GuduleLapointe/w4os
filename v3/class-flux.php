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

class W4OS3_Flux {
    
    /**
     * Constructor
     */
    function __construct() {
    }

    function init() {
        add_action( 'init', array( $this, 'register_post_type' ) );
        add_action( 'admin_menu', array( $this, 'register_admin_submenus' ) );
        add_action( 'admin_head', array( $this, 'set_active_submenu' ) );
        add_action( 'save_post_flux_post', array( $this, 'save_post' ), 10, 3 );
    }

    /**
     * Register post type
     */
    function register_post_type() {
        $labels = array(
            'name' => __( 'Flux Posts', 'w4os' ),
            'singular_name' => __( 'Flux Post', 'w4os' ),
            'add_new' => __( 'Add New', 'w4os' ),
            'add_new_item' => __( 'Add New Flux Post', 'w4os' ),
            'edit_item' => __( 'Edit Flux Post', 'w4os' ),
            'new_item' => __( 'New Flux Post', 'w4os' ),
            'view_item' => __( 'View Flux Post', 'w4os' ),
            'search_items' => __( 'Search Flux Posts', 'w4os' ),
            'not_found' => __( 'No Flux Posts found', 'w4os' ),
            'not_found_in_trash' => __( 'No Flux Posts found in Trash', 'w4os' ),
            'parent_item_colon' => __( 'Parent Flux Post:', 'w4os' ),
            'menu_name' => __( 'Flux Posts', 'w4os' ),
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
            'supports'           => array( 'editor', 'author' ),
            // 'show_in_rest'       => true,
            'show_in_admin_bar'  => true, // Ensure it shows in the admin bar
            'taxonomies'         => array( 'flux_category' )
        );

        register_post_type( 'flux_post', $args );
    }

    public function register_admin_submenus() {
        add_submenu_page(
            'w4os',
            __( 'Flux', 'w4os' ),
            '(dev) ' . __( 'Flux', 'w4os' ),
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
            $parent_file = 'w4os';
            $submenu_file = 'edit.php?post_type=flux_post';
        }
    }

    /**
     * Display flux
     */
    function display_flux( $content ) {
        if ( ! is_singular( 'flux_post' ) ) return $content;
        // $avatar_uuid = get_post_meta( get_the_ID(), '_avatar_uuid', true );
        // $avatar = get_avatar( $avatar_uuid, 96 );
        // $content = $avatar . $content;
        return $content;
    }

    public function save_post( $post_id, $post, $update ) {
        if ( 'flux_post' !== $post->post_type ) return;
        $content = trim( wp_strip_all_tags( $post->post_content ) );
        $title   = wp_trim_words( $content, 10, '...' );
        if ( get_post_field( 'post_title', $post_id ) !== $title ) {
            wp_update_post( array( 'ID' => $post_id, 'post_title' => $title ) );
        }
    }
}
