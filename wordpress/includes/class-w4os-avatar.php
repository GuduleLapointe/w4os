<?php
/**
 * WordPress Avatar Class
 * 
 * WordPress-specific avatar functionality that extends the engine Avatar class.
 * Handles WordPress integration, URL generation, and display methods.
 */

if (!defined('ABSPATH')) {
    exit('Direct access not allowed');
}

class W4OS_Avatar extends Avatar {
    public static $slug;
    public static $profile_page_url;
    public $profile_url;
    private $is_profile_page = false;
    
    public function __construct($args = null) {
        // Initialize WordPress-specific properties
        self::$slug = get_option('w4os_profile_slug', 'profile');
        self::$profile_page_url = get_home_url(null, self::$slug);
        
        // Call parent constructor
        parent::__construct($args);
        
        if (!empty($args)) {
            $this->profile_url = $this->get_profile_url();
        }
    }

    /**
     * Initialize WordPress hooks and filters
     */
    public function init() {
        add_filter('w4os_settings', array($this, 'register_w4os_settings'), 10, 3);
        add_action('init', array($this, 'add_rewrite_rules'));
        add_filter('query_vars', array($this, 'add_profile_query_vars'));
        add_action('template_include', array($this, 'template_include'));
        add_filter('the_title', array($this, 'the_title'));
        add_filter('pre_get_document_title', array($this, 'document_title'));
        add_filter('body_class', array($this, 'body_class'));
        add_filter('post_class', array($this, 'post_class'));
    }

    /**
     * Get WordPress option specific to avatars
     */
    static function get_option($option, $default = false) {
        $settings = W4OS3::get_option('w4os-avatars:settings', array());
        if (isset($settings[$option])) {
            $value = $settings[$option];
        } else {
            $value = $default;
        }
        return $value;
    }

    /**
     * Template include filter to setup profile page
     */
    public function template_include($template) {
        $this->setup_profile();
        return $template;
    }

    /**
     * Set page title for profile page
     */
    public function setup_profile() {
        global $wp_query;

        $pagename = W4OS3::get_localized_post_slug();

        if ($pagename === self::$slug) {
            $this->is_profile_page = true;
        } else {
            return;
        }

        $query_firstname = get_query_var('profile_firstname');
        $query_lastname = get_query_var('profile_lastname');
        $query_name = get_query_var('name');

        if (!empty($query_name) && preg_match('/\./', $query_name)) {
            $parts = explode('@', $query_name);
            if (count($parts) > 1) {
                $grid = $parts[1];
                $query_name = $parts[0];
                // Handle external grid
            }
            $parts = explode('.', $query_name);
            $query_firstname = $parts[0];
            $query_lastname = $parts[1];
        }

        if (empty($query_firstname) || empty($query_lastname)) {
            if (is_user_logged_in()) {
                $uuid = w4os_profile_sync(wp_get_current_user());
                if ($uuid) {
                    $page_title = __('My Profile', 'w4os');
                } else {
                    $page_title = __('Create My Avatar', 'w4os');
                }
            } else {
                $page_title = __('Log in', 'w4os');
            }
        } else {
            $avatar = new W4OS_Avatar("$query_firstname.$query_lastname");
            if ($avatar->UUID) {
                $page_title = $avatar->AvatarName;
            } else {
                $not_found = true;
                $page_title = __('Avatar not found', 'w4os');
            }
        }
        
        $this->profile = $avatar ?? false;
        $this->page_title = $page_title;
        $this->head_title = $page_title . ' – ' . get_bloginfo('name');
    }

    /**
     * Add rewrite rules for the profile page
     */
    public function add_rewrite_rules() {
        $target = 'index.php?pagename=' . self::$slug . '&profile_firstname=$matches[1]&profile_lastname=$matches[2]&profile_args=$matches[3]';
        
        add_rewrite_rule(
            '^' . self::$slug . '/(.+?)\.(.+?)(\?.*)?$',
            $target,
            'top'
        );
    }

    public function add_profile_query_vars($vars) {
        $vars[] = 'profile_firstname';
        $vars[] = 'profile_lastname';
        $vars[] = 'profile_args';
        return $vars;
    }

    /**
     * WordPress-specific profile URL generation
     */
    public function get_profile_url() {
        return self::profile_url($this->data);
    }

    public static function profile_url($item = null) {
        if (!empty($item->externalProfileURL)) {
            return $item->externalProfileURL;
        }
        
        $slug = get_option('w4os_profile_slug', 'profile');
        $profile_page_url = get_home_url(null, $slug);
        
        if (empty($item) && !empty($_GET['name'])) {
            $parts = explode('@', $_GET['name']);
            $name_parts = explode('.', $parts[0]);
            $firstname = $name_parts[0];
            $lastname = $name_parts[1];
            $grid = $parts[1] ?? null;
            
            if (!empty($grid)) {
                $grid_info = W4OS3::grid_info($grid);
                if ($grid_info && isset($grid_info['web_profile_url'])) {
                    $profile_page_url = $grid_info['web_profile_url'];
                    if ($profile_page_url) {
                        $profile_page_url = add_query_arg(array('name' => "$firstname.$lastname"), $profile_page_url);
                        $profile_page_url = remove_query_arg('session_id', $profile_page_url);
                        return $profile_page_url;
                    }
                }
                return false;
            }
        } elseif (is_object($item)) {
            $firstname = $item->FirstName;
            $lastname = $item->LastName;
        }

        if (empty($firstname) || empty($lastname)) {
            return $profile_page_url;
        } else {
            $firstname = sanitize_title($firstname);
            $lastname = sanitize_title($lastname);
            return $profile_page_url . '/' . $firstname . '.' . $lastname;
        }
    }

    /**
     * Generate profile link with optional picture
     */
    public function profile_link($include_picture = false) {
        W4OS3::enqueue_style('w4os-profile', 'v3/css/profile.css');

        $profile_url = $this->get_profile_url();
        $avatarName = $this->AvatarName;
        $profileImage = $this->profileImage;
        
        $img = ($include_picture) ? W4OS3::img($profileImage, array('alt' => $avatarName, 'class' => 'profile')) : '';
        
        return sprintf(
            '<a href="%s" title="%s">%s%s</a>',
            $profile_url,
            __('View profile page', 'w4os'),
            $img,
            $avatarName
        );
    }

    /**
     * Generate profile picture HTML
     */
    public function profile_picture($echo = false) {
        $avatarName = $this->AvatarName ?? '';
        $html = OpenSim::empty($this->profileImage) ? '' : W4OS3::img(
            $this->profileImage,
            array(
                'alt' => $avatarName,
                'class' => 'profile',
            )
        );

        if ($echo) {
            echo $html;
        } else {
            return $html;
        }
    }

    /**
     * WordPress title filter
     */
    public function the_title($title) {
        if (!$this->is_profile_page) {
            return $title;
        }

        if ($this->is_profile_page && in_the_loop() && is_main_query()) {
            if (self::get_option('hide_profile_title', true)) {
                return null;
            }
            if ($this->page_title) {
                return $this->page_title;
            }
        }

        return $title;
    }

    public function document_title($title) {
        if (!$this->is_profile_page) {
            return $title;
        }
        if ($this->head_title) {
            return $this->head_title;
        }
        return $title;
    }

    public function body_class($classes) {
        if ($this->is_profile_page) {
            $classes[] = 'profile-page';
        }
        return $classes;
    }

    public function post_class($classes) {
        if ($this->is_profile_page) {
            $classes[] = 'profile-post';
        }
        return $classes;
    }

    /**
     * Get user avatar by WordPress user ID
     */
    static function get_user_avatar($user_id = null) {
        if (empty($user_id)) {
            $user = wp_get_current_user();
        } else {
            $user = get_user_by('ID', $user_id);
        }

        $avatars = self::get_avatars_by_email($user->user_email);
        if (empty($avatars)) {
            return false;
        }
        
        $key = key($avatars);
        $avatar = new W4OS_Avatar($key);

        return $avatar;
    }

    // Override parent method to use WordPress-specific database connection
    public static function get_avatars($args = array(), $format = OBJECT) {
        global $w4osdb;
        if (empty($w4osdb)) {
            return false;
        }

        if (!isset($args['active'])) {
            $args['active'] = true;
        }

        $conditions = array();
        foreach ($args as $arg => $value) {
            switch ($arg) {
                case 'Email':
                    $conditions[] = $w4osdb->prepare('Email = %s', $value);
                    break;
                case 'active':
                    $conditions[] = 'active = ' . ($value ? 'true' : 'false');
                    break;
            }
        }

        $avatars = array();
        $sql = 'SELECT PrincipalID, FirstName, LastName FROM UserAccounts';
        if (!empty($conditions)) {
            $sql .= ' WHERE ' . implode(' AND ', $conditions);
        }

        $result = $w4osdb->get_results($sql, $format);
        if (is_array($result)) {
            foreach ($result as $avatar) {
                $avatars[$avatar->PrincipalID] = trim("$avatar->FirstName $avatar->LastName");
            }
        }
        return $avatars;
    }
}

// Backward compatibility alias
class W4OS3_Avatar extends W4OS_Avatar {
    // Alias for existing code that uses W4OS3_Avatar
}
