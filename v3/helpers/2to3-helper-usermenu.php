<?php
/**
 * User Menu class
 * 
 * Create a user menu for both classic menu system and Gutenberg block.
 *
 * We do not care about user authentication here, this is managed by another class.
 * Neither do we care about login form, this is managed by another class.
 * 
 * The menu for anonymous users is login url givent by wp_login_url().
 * 
 * For authenticated user, the main menu is the user name and links to profile page W4OS_Avatar::profile_url().
 * Default submenus are
 *  Account : W4OS3::account_url()
 *  Logout : wp_logout_url()
 * More submenus can be added by using the `w4os_avatar_menu` filter.
 * 
 * @package w4os
**/

class W4OS3_UserMenu {
    public function __construct() {
    }

    public function init() {
        add_filter('w4os_avatar_menu', array($this, 'add_default_menu_items'));
        add_action( 'init', array( $this, 'avatar_menu_block_init' ) );
    }

    public function add_default_menu_items($menu) {
        if (is_user_logged_in()) {
            $current_user = wp_get_current_user();
            $menu[] = array(
                'label' => $current_user->display_name,
                'url' => W4OS3_Avatar::profile_url($current_user->ID),
                'icon' => get_avatar($current_user->ID, 24),
                'children' => array(
                    array(
                        'label' => 'Account',
                        'url' => W4OS3::account_url(),
                    ),
                    array(
                        'label' => 'Logout',
                        'url' => wp_logout_url(),
                    ),
                ),
            );
        } else {
            $menu[] = array(
                'label' => 'Login',
                'url' => wp_login_url(),
            );
        }
        return $menu;
    }

    function avatar_menu_block_init() {
        // Skip block registration if Gutenberg is not enabled/merged.
        if ( ! function_exists( 'register_block_type' ) ) {
            return;
        }
        $dir = dirname( __FILE__ );

        $index_js = 'blocks/avatar-menu/index.js';
        wp_register_script(
            'avatar-menu-block-editor',
            plugins_url( $index_js, __FILE__ ),
            [
                'wp-blocks',
                'wp-i18n',
                'wp-element',
                'wp-server-side-render', // Ensure ServerSideRender dependency
            ],
            filemtime( "{$dir}/{$index_js}" )
        );

        $editor_css = 'blocks/avatar-menu/editor.css';
        wp_register_style(
            'avatar-menu-block-editor',
            plugins_url( $editor_css, __FILE__ ),
            [],
            filemtime( "{$dir}/{$editor_css}" )
        );

        $style_css = 'blocks/avatar-menu/style.css';
        wp_register_style(
            'avatar-menu-block',
            plugins_url( $style_css, __FILE__ ),
            [],
            filemtime( "{$dir}/{$style_css}" )
        );

        register_block_type( 'w4os/avatar-menu', [
            'editor_script'   => 'avatar-menu-block-editor',
            'editor_style'    => 'avatar-menu-block-editor',
            'style'           => 'avatar-menu-block',
            'render_callback' => array($this, 'render_block'),
            // Removed 'parent' to manage via allowed_block_types filter
            'parent'          => array('core/navigation'),
        ] );
    }

    // <li data-wp-context="{ &quot;submenuOpenedBy&quot;: { &quot;click&quot;: false, &quot;hover&quot;: false, &quot;focus&quot;: false }, &quot;type&quot;: &quot;submenu&quot; }" data-wp-interactive="core/navigation" data-wp-on--focusout="actions.handleMenuFocusout" data-wp-on--keydown="actions.handleMenuKeydown" data-wp-on-async--mouseenter="actions.openMenuOnHover" data-wp-on-async--mouseleave="actions.closeMenuOnHover" data-wp-watch="callbacks.initMenu" tabindex="-1" class=" wp-block-navigation-item has-child open-on-hover-click wp-block-navigation-submenu">
    // <a class="wp-block-navigation-item__content current-menu-ancestor" href="http://dev.w4os.org/account/">Account</a>
    // <button data-wp-bind--aria-expanded="state.isMenuOpen" data-wp-on-async--click="actions.toggleMenuOnClick" aria-label="Account submenu" class="wp-block-navigation__submenu-icon wp-block-navigation-submenu__toggle" aria-expanded="false">
    // <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 12 12" fill="none" aria-hidden="true" focusable="false">
    // <path d="M1.50002 4L6.00002 8L10.5 4" stroke-width="1.5">
    // </path>
    // </svg>
    // </button>
    // <ul data-wp-on-async--focus="actions.openMenuOnFocus" class="wp-block-navigation__submenu-container has-text-color has-contrast-color has-background has-base-background-color wp-block-navigation-submenu">
    // <li class=" wp-block-navigation-item current-menu-item wp-block-navigation-link">
    // <a class="wp-block-navigation-item__content" href="http://dev.w4os.org/profile/" aria-current="page">
    // <span class="wp-block-navigation-item__label">Profile</span>
    // </a>
    // </li>
    // </ul>
    // </li>    

    function render_block($attributes) {
        $menu = apply_filters('w4os_avatar_menu', array());
        error_log( '$menu = ' . print_r($menu, true));
        // TODO: include $item['icon'] in the output if set
        $block_content = '';
        foreach ($menu as $item) {
            if (isset($item['children']) && is_array($item['children'])) {
                $block_content .= '<!-- wp:navigation-submenu {"label":"' . esc_html($item['label']) . '","url":"' . esc_url($item['url']) . '"} -->';
                foreach ($item['children'] as $child) {
                    $block_content .= '<!-- wp:navigation-link {"label":"' . esc_html($child['label']) . '","url":"' . esc_url($child['url']) . '"} /-->';
                }
                $block_content .= '<!-- /wp:navigation-submenu -->';
            } else {
                $block_content .= '<!-- wp:navigation-link {"label":"' . esc_html($item['label']) . '","url":"' . esc_url($item['url']) . '"} /-->';
            }
        }

        return do_blocks($block_content);
    }

}
