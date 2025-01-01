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
        // No need to call init, it is called automatically at plugin load.
        $args = func_get_args();

        if (empty ($args)) {
            return;
        }

        if ( W4OS3::is_uuid( $args[0], false ) ) {
            $this->avatar_uuid = $args[0];
            $this->avatar = new W4OS3_Avatar( $this->avatar_uuid );
            $this->thumb = $this->avatar->get_thumb();
            // $this->get_flux_posts();
        }
    }

    function init() {
        add_action( 'init', array( $this, 'register_post_type' ) );
        add_action( 'admin_menu', array( $this, 'register_admin_submenus' ) );
        add_action( 'admin_head', array( $this, 'set_active_submenu' ) );
        add_action( 'save_post_flux_post', array( $this, 'save_post' ), 10, 3 );
        add_action('init', array($this, 'maybe_save_new_flux_post'));
        add_action( 'add_meta_boxes', array( $this, 'add_metaboxes' ) );
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
            'supports'           => array( 'editor' ),
            // 'show_in_rest'       => true,
            'show_in_admin_bar'  => true, // Ensure it shows in the admin bar
            'taxonomies'         => array( 'flux_category' )
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
        $avatar_uuid = get_post_meta( $post->ID, '_avatar_uuid', true );
        $avatar_name = get_post_meta( $post->ID, '_avatar_name', true );
        $avatar = new W4OS3_Avatar( $avatar_uuid );

        $profile_url = $avatar->get_profile_url();

        $avatar_url = $profile_url . '/' . $avatar_uuid;
        $content = sprintf(
            '<p>%s</p>',
            $avatar->profile_link(),
        );
        echo $content;
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

    public function new_flux_form() {
        $content = '';
        $content .= '<form id="new-flux-post-form" method="post">';
        if( $this->avatar_uuid ) {
            $content .= '<input type="hidden" name="avatar_uuid" value="' . $this->avatar_uuid . '">';
        }
        $content .= sprintf(
            '<textarea name="flux-post-content" id="flux-post-content" class="autogrow" placeholder="%s"></textarea>',
            __( 'Your message...', 'w4os' )
        );
        
        $content .= '<script>
            (function() {
                var textarea = document.getElementById("flux-post-content");
                textarea.addEventListener("input", function(){
                    this.style.height = "auto";
                    this.style.height = (this.scrollHeight) + "px";
                });
                textarea.addEventListener("keydown", function(e){
                    if (e.key === "Enter" && !e.shiftKey) {
                        e.preventDefault();
                        this.form.submit();
                    }
                });
            })();
        </script>';
        $content .= '</form>';
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

    public function maybe_save_new_flux_post() {
        if (!empty($_POST['flux-post-content'])) {
            $this->save_new_flux_post();
            // Force reload to avoid resubmission
            wp_redirect( $_SERVER['REQUEST_URI'] );
            die();
        }
    }

    public function save_new_flux_post() {
        if ( ! isset( $_POST['flux-post-content'] ) ) return;
        $content = sanitize_text_field( $_POST['flux-post-content'] );
        $post_id = wp_insert_post( array(
            'post_title' => wp_trim_words( $content, 10, '...' ),
            'post_content' => $content,
            'post_type' => 'flux_post',
            'post_status' => 'publish',
        ));
        if ( ! is_wp_error( $post_id ) ) {
            update_post_meta( $post_id, '_avatar_uuid', sanitize_text_field( $_POST['avatar_uuid'] ) );
            $avatar = new W4OS3_Avatar( $_POST['avatar_uuid'] );
            update_post_meta( $post_id, '_avatar_name', $avatar->get_name($_POST['avatar_uuid']) );
            return 'Flux post created successfully';
        }
        return $post_id->get_error_message();
    }

    public function get_flux_posts() {
        if( ! $this->avatar_uuid ) {
            return;
        }
        $args = array(
            'post_type' => 'flux_post',
            'meta_query' => array(
                array(
                    'key' => '_avatar_uuid',
                    'value' => $this->avatar_uuid,
                )
            )
        );
        $flux_posts = get_posts( $args );
        return $flux_posts;
    }

    public function display_flux() {
        $flux_posts = $this->get_flux_posts();
        $content = '';

        $content .= $this->new_flux_form();

        foreach( $flux_posts as $flux_post ) {
            // $content .= '<div class="flux-post">';
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
                $flux_post->post_content
            );
            // Sow avatar name, post date and post content
            // $content .= 
            // $content .= '<p>' . $this->avatar->AvatarName . '</p>';
            // $content .= '<p>' . $flux_post->post_date . '</p>';
            // $content .= '<p>' . $flux_post->post_content . '</p>';
            // $content .= '</div>';
        }
        if ( ! empty( $content ) ) {
            $content = '<div class="flux">' . $content . '</div>';
        }
        return $content;
    }
}
