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
    /**
     * Mapping of menu item IDs to their label callback functions.
     *
     * @var array
     */
    protected $placeholders = null;

    /**
     * Mapping of menu item slugs to their visibility settings.
     *
     * @var array
     */
    protected $menu_visibility = [];

    public function __construct() {
        // Initialize label callbacks mapping if needed
    }

    public function init() {
        add_filter('w4os_avatar_menu', array($this, 'w4os_avatar_menu'));
        add_action( 'init', array( $this, 'avatar_menu_block_init' ) );

        add_action( 'admin_init', array( $this, 'add_meta_boxes' ) );

        // Add filter to modify menu items before rendering
        add_filter('wp_get_nav_menu_items', array($this, 'wp_get_nav_menu_items'), 10, 3);
        add_filter('wp_get_nav_menu_items', array($this, 'replace_menu_item_labels'), 15, 3); // Added this line
    }

    public function wp_nav_menu($nav_menu, $args) {
        if( is_null($this->placeholders) ) {
            $this->save_placeholders();
        }

        return $nav_menu;
    }

    public function wp_get_nav_menu_items($items, $menu, $args) {
        $this->save_placeholders();
        foreach ($items as $key => $item) {
            if( isset ( $this->placeholders[$item->title] ) ) {
                $original = $item->title;
                $items[$key]->title = $this->placeholders[$item->title];
                $processed[$original] = $items[$key]->title;
            }
        }
        return $items;
    }

    public function add_meta_boxes() {
        add_meta_box(
            'w4os-avatar-menu',
            __('Avatar Menu', 'w4os'),
            array($this, 'add_meta_boxes_callback'),
            'nav-menus',
            'side',
            'default'
        );
    }

    /**
     * Save placeholders.
     * 
     * Crawl the menu and save placeholders for dynamic labels.
     * 
     * @param array $menu The menu items.
     * @return void
     */
    private function save_placeholders( $menu = null ) {
        if ( is_null($menu) ) {
            $menu = apply_filters('w4os_avatar_menu', array());
        }

        foreach ($menu as $key => $item) {
            $item['id'] = empty($item['id']) ? $key : $item['id'];
            if( isset ( $item['label'] ) && is_callable($item['label']) ) {
                $callback = $item['label'];
                $label = call_user_func($callback, true);
                $placeholder = '%' . sanitize_title($item['id']) . '%';
                $this->placeholders[$placeholder] = call_user_func($callback);
            }
            if (!empty($item['children']) && is_array($item['children'])) {
                $this->save_placeholders($item['children']);
            }
            if (isset($item['which_users'])) {
                $this->menu_visibility[$item['id']] = $item['which_users'];
            }
        }
    }
    
    public function add_meta_boxes_callback() {
        $menu = apply_filters('w4os_avatar_menu', array());
        W4OS3::enqueue_script( 'usermenu-classic-menu', 'wordpress/includes/js/usermenu-classic-menu.js' );
        $this->menu = $menu;
        $i = -1;
        ?>
        <div id="w4os-avatar-menu" class="posttypediv">
            <div id="tabs-panel-w4os-avatar-menu" class="tabs-panel tabs-panel-active">
                <ul id="w4os-avatar-menu-checklist" class="categorychecklist form-no-clear">
                    <?php $this->render_metabox_items($menu, $i); ?>
                </ul>
            </div>
            <p class="button-controls">
                <span class="avatar-menu-select-all">
                   <input type="checkbox" id="avatar-menu-select-all" /> 
                   <label for="avatar-menu-select-all">Select All</label>
                </span> 
                <span class="add-to-menu">
                    <input type="submit" class="button-secondary submit-add-to-menu right" value="Add to Menu" name="add-post-type-menu-item" id="submit-w4os-avatar-menu">
                    <span class="spinner"></span>
                </span>
            </p>
        </div>
        <?php
    }

    /**
     * Recursively renders menu items and their children.
     *
     * @param array $menu The array of menu items.
     * @param int &$i Reference to the index counter.
     */
    private function render_metabox_items($menu, &$i) {
        foreach ($menu as $item_id => $item) {
            $item['id'] = empty($item['id']) ? $item_id : $item['id'];
            // Determine if the label is dynamic by checking if it's callable before execution
            if ( is_callable($item['label']) ) {
                $callback = $item['label'];
                // get admin label from callback
                $label = call_user_func($callback, true);
                $menu_title = '%' . sanitize_title($item_id) . '%';
            } else if ( is_string($item['label']) ) {
                $menu_title = esc_attr($item['label']);
                $label = $item['label'];
            } else {
                error_log( 'Invalid menu item label: ' . print_r($item, true) );
                continue;
            }

            $index = $i--;
            echo sprintf(
                '<li>
                    <label class="menu-item-title">
                        <input type="checkbox" class="menu-item-checkbox" name="menu-item[%1$d][menu-item-object-id]" value="-1">
                        %2$s
                    </label>
                    <input type="hidden" class="menu-item-type" name="menu-item[%1$d][menu-item-type]" value="custom">
                    <input type="hidden" class="menu-item-title" name="menu-item[%1$d][menu-item-title]" value="%3$s">
                    <input type="hidden" class="menu-item-url" name="menu-item[%1$d][menu-item-url]" value="%4$s">
                </li>',
                $index,
                print_r( $label, true),
                $menu_title,
                esc_url($item['url'])
            );

            if (!empty($item['children']) && is_array($item['children'])) {
                echo '<li class="children"><ul>';
                $this->render_metabox_items($item['children'], $i);
                echo '</ul></li>';
            }
        }
    }

    private function menu_visible( $slug ) {
        if( empty ( $this->menu_visibility ) ) {
            return true;
        }
        if( empty( $slug ) ) {
            return true;
        }
        // Check if the menu item's slug exists in the visibility mapping
        $which_users = $this->menu_visibility[$slug] ?? null;
        if ( ! empty( $which_users ) ) {

            if ($which_users === 'logged_in' && ! is_user_logged_in()) {
                return false;
            }

            if ($which_users === 'logged_out' && is_user_logged_in()) {
                return false;
            }
        }

        return true;
    }
    /**
     * Replace placeholders in menu item titles with dynamic content and enforce visibility.
     *
     * @param array    $items The menu items.
     * @param stdClass $args  The wp_nav_menu() arguments.
     * @return array Modified menu items.
     */
    public function replace_menu_item_labels($items, $args) {
        foreach ($items as $key => $item) {
            $slug = preg_replace( '/-[0-9]*$/', '', $item->post_name );
            // Check if the menu item's slug exists in the visibility mapping
            if (isset($this->menu_visibility[$slug])) {
                $which_users = $this->menu_visibility[$slug];

                if ($which_users === 'logged_in' && ! is_user_logged_in()) {
                    unset($items[$key]);
                    continue;
                }

                if ($which_users === 'logged_out' && is_user_logged_in()) {
                    unset($items[$key]);
                    continue;
                }
            }

            if (isset($this->placeholders[$item->title])) {
                $original = $item->title;
                $items[$key]->title = $this->placeholders[$item->title];
                // Optional: Log or handle processed items
                $processed[$original] = $items[$key]->title;
            }
        }
        return $items;
    }

    /**
     * Profile Menu title callback.
     *
     * @param bool $admin_label Whether to return the admin label.
     * @return string The menu title.
     */
    public function profile_menu_title( $admin_label = false ) {
        if (is_user_logged_in() && ! $admin_label ) {
            $current_user = wp_get_current_user();
            return $current_user->display_name;
        }
        return __('Avatar profile', 'w4os');
    }

    /**
     * Modify the avatar menu by defining menu items and their visibility.
     *
     * @param array $menu The existing menu items.
     * @return array Modified menu items.
     */
    public function w4os_avatar_menu($menu) {
        $prefix = 'avatar-';

        $current_user = wp_get_current_user();

        // Define Profile Menu
        $menu[$prefix . 'profile'] = array(
            'label' => [ $this, 'profile_menu_title' ], // Callback for dynamic label
            'admin-label' => __('Avatar Profile', 'w4os'), // For admin menu settings only
            'url' => W4OS3_Avatar::profile_url($current_user->ID),
            'icon' => get_avatar($current_user->ID, 24),
            'which_users' => 'logged_in', // Visibility setting
            'children' => array(
                $prefix . 'account' => array(
                    'label' => __('Account settings', 'w4os'),
                    'url' => W4OS3::account_url(),
                    'which_users' => 'logged_in', // Visibility setting
                ),
                $prefix . 'logout' => array(
                    'label' => __('Logout', 'w4os'),
                    'url' => wp_logout_url(),
                    'which_users' => 'logged_in', // Visibility setting
                ),
            ),
        );

        // Define Login Menu
        $menu['login'] = array(
            'label' => __('Login', 'w4os'),
            'url' => wp_login_url(),
            'which_users' => 'logged_out', // Visibility setting
        );
        $menu['register'] = array(
            'label' => __('Register', 'w4os'),
            'url' => wp_registration_url(),
            'which_users' => 'logged_out', // Visibility setting
        );

        // Populate the visibility mapping
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

    function render_block($attributes) {
        // Make sure placeholders and visibility settings are up to date
        $this->save_placeholders();
        $menu = apply_filters('w4os_avatar_menu', array());
        // TODO: include $item['icon'] in the output if set
        $block_content = '';
        foreach ($menu as $slug => $item) {
            if( ! $this->menu_visible( $slug ) ) {
                continue;
            }
            if (isset($item['children']) && is_array($item['children'])) {
                if( is_callable($item['label']) ) {
                    $callback = $item['label'];
                    $label = call_user_func( $callback );
                } else {
                    $label = $item['label'];
                }
                $block_content .= '<!-- wp:navigation-submenu {"label":"' . esc_html($label) . '","url":"' . esc_url($item['url']) . '"} -->';
                foreach ($item['children'] as $child) {
                    if( is_callable($child['label']) ) {
                        $callback = $child['label'];
                        $label = call_user_func( $callback );
                    } else {
                        $label = $child['label'];
                    }
                    $block_content .= '<!-- wp:navigation-link {"label":"' . esc_html($label) . '","url":"' . esc_url($child['url']) . '"} /-->';
                }
                $block_content .= '<!-- /wp:navigation-submenu -->';
            } else {
                $block_content .= '<!-- wp:navigation-link {"label":"' . esc_html($item['label']) . '","url":"' . esc_url($item['url']) . '"} /-->';
            }
        }

        return do_blocks($block_content);
    }

}
