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
    }

    /**
     * Register post type
     */
    function register_post_type() {
        register_post_type( 'flux_post', array(
            'labels' => array(
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
            ),
            'public' => true,
            'has_archive' => true,
            'rewrite' => array( 'slug' => 'flux' ),
            'supports' => array( 'title', 'editor', 'author', 'thumbnail' ),
            'taxonomies' => array(),
            'menu_position' => 5,
            'menu_icon' => 'dashicons-format-status',
        ) );
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

}

