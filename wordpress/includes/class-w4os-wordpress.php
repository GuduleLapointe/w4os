<?php
/**
 * W4OS WordPress Integration Class
 * 
 * WordPress-specific W4OS functionality that bridges OpenSimulator engine
 * with WordPress features like options, hooks, enqueue scripts, etc.
 */

if (!defined('ABSPATH')) {
    exit;
}

class W4OS_WordPress
{
    private static $instance = null;
    private static $opensim_engine = null;
    
    public static function getInstance()
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    public function __construct()
    {
        // Initialize OpenSimulator engine
        if (class_exists('OpenSim')) {
            self::$opensim_engine = OpenSim::getInstance();
        }
    }
    
    /**
     * Initialize WordPress integration
     */
    public function init()
    {
        // WordPress-specific initialization
        add_action('init', [$this, 'setup_rewrite_rules']);
        add_action('wp_enqueue_scripts', [$this, 'enqueue_frontend_assets']);
        add_action('admin_enqueue_scripts', [$this, 'enqueue_admin_assets']);
    }
    
    /**
     * WordPress-specific enqueue script wrapper
     * Simplified script enqueuing with w4os prefixing
     */
    public static function enqueue_script($handle, $src, $deps = [], $ver = false, $in_footer = false)
    {
        $handle = preg_match('/^w4os-/', $handle) ? $handle : 'w4os-' . $handle;
        $ver = empty($ver) ? (defined('W4OS_VERSION') ? W4OS_VERSION : '1.0.0') : $ver;
        $src = preg_match('/^http/', $src) ? $src : (defined('W4OS_PLUGIN_DIR_URL') ? W4OS_PLUGIN_DIR_URL : plugin_dir_url(dirname(dirname(__DIR__)))) . $src;
        
        if (function_exists('wp_enqueue_script')) {
            wp_enqueue_script($handle, $src, $deps, $ver, $in_footer);
        }
    }
    
    /**
     * WordPress-specific enqueue style wrapper
     * Simplified stylesheet enqueuing with w4os prefixing
     */
    public static function enqueue_style($handle, $src, $deps = [], $ver = false, $media = 'all')
    {
        $handle = preg_match('/^w4os-/', $handle) ? $handle : 'w4os-' . $handle;
        $ver = empty($ver) ? (defined('W4OS_VERSION') ? W4OS_VERSION : '1.0.0') : $ver;
        $src = preg_match('/^http/', $src) ? $src : (defined('W4OS_PLUGIN_DIR_URL') ? W4OS_PLUGIN_DIR_URL : plugin_dir_url(dirname(dirname(__DIR__)))) . $src;
        
        if (function_exists('wp_enqueue_style')) {
            wp_enqueue_style($handle, $src, $deps, $ver, $media);
        }
    }
    
    /**
     * Get WordPress option with namespace support
     */
    public static function get_option($option, $default = false)
    {
        if (preg_match('/:/', $option)) {
            $option_group = strstr($option, ':', true);
            $option = trim(strstr($option, ':'), ':');
        } else {
            $option_group = 'w4os-settings';
        }
        
        $options = get_option($option_group, []);
        if (isset($options[$option])) {
            return $options[$option];
        }
        
        // Fallback to v2 settings
        if ($option_group === 'w4os-settings') {
            $options = get_option('w4os_settings', []);
            if (isset($options[$option])) {
                return $options[$option];
            }
        }
        
        return $default;
    }
    
    /**
     * Update WordPress option with namespace support
     */
    public static function update_option($option, $value, $autoload = null)
    {
        if (preg_match('/:/', $option)) {
            $option_group = strstr($option, ':', true);
            $option = trim(strstr($option, ':'), ':');
        } else {
            $option_group = 'w4os-settings';
        }
        
        $options = get_option($option_group, []);
        $options[$option] = $value;
        
        return update_option($option_group, $options, $autoload);
    }
    
    /**
     * Get account URL - WordPress specific
     */
    public static function account_url()
    {
        $account_slug = get_option('w4os_account_url', 'account');
        $page = get_page_by_path($account_slug);
        $account_url = ($page) ? get_permalink($page->ID) : false;
        
        if (empty($account_url)) {
            return get_edit_user_link();
        }
        
        return $account_url;
    }
    
    /**
     * Add submenu page with w4os prefix
     */
    public static function add_submenu_page($parent_slug, $page_title, $menu_title, $capability, $menu_slug, $callback = '', $position = null)
    {
        $parent_slug = 'w4os';
        $prefix = $parent_slug . '-';
        
        if (!preg_match('/^' . $prefix . '/', $menu_slug)) {
            $menu_slug = $prefix . $menu_slug;
        }
        
        return add_submenu_page(
            $parent_slug,
            $page_title,
            $menu_title,
            $capability,
            $menu_slug,
            $callback,
            $position
        );
    }
    
    /**
     * Check if current page is new post
     */
    public static function is_new_post($args = null)
    {
        global $pagenow;
        
        if (!is_admin()) {
            return false;
        }
        
        return in_array($pagenow, ['post-new.php']);
    }
    
    /**
     * WordPress date formatting wrapper
     */
    public static function format_date($timestamp, $format = 'MEDIUM', $timetype_str = 'NONE')
    {
        switch ($format) {
            case 'MEDIUM':
                $format = get_option('date_format');
                return date_i18n($format, $timestamp);
                
            case 'LONG':
            case 'DATE_TIME':
                return sprintf(
                    __('%s at %s', 'w4os'),
                    date_i18n(get_option('date_format'), $timestamp),
                    date_i18n(get_option('time_format'), $timestamp)
                );
                
            default:
                $format = get_option('date_format');
                return date_i18n($format, $timestamp);
        }
    }
    
    /**
     * Generate image tag for UUID using WordPress functions
     */
    public static function img($img_uuid, $atts = [])
    {
        if (!self::$opensim_engine || self::$opensim_engine::is_null_key($img_uuid)) {
            return '';
        }
        
        if (!self::$opensim_engine::is_uuid($img_uuid)) {
            return '';
        }
        
        // Use WordPress function if available
        if (function_exists('w4os_get_asset_url')) {
            $asset_url = w4os_get_asset_url($img_uuid);
            if (empty($asset_url)) {
                return '';
            }
            
            $class = $atts['class'] ?? '';
            $class = is_array($class) ? implode(' ', $class) : $class;
            $width = isset($atts['width']) ? 'width="' . esc_attr($atts['width']) . '"' : '';
            $height = isset($atts['height']) ? 'height="' . esc_attr($atts['height']) . '"' : '';
            $attributes = trim($width . ' ' . $height);
            $alt = esc_attr($atts['alt'] ?? '');
            
            return sprintf(
                '<img src="%s" class="%s" alt="%s" %s>',
                esc_url($asset_url),
                esc_attr($class),
                $alt,
                $attributes
            );
        }
        
        return '';
    }
    
    /**
     * Bridge to OpenSim engine methods
     */
    public static function __callStatic($method, $args)
    {
        if (self::$opensim_engine && method_exists(self::$opensim_engine, $method)) {
            return call_user_func_array([self::$opensim_engine, $method], $args);
        }
        
        return null;
    }
    
    /**
     * Setup WordPress rewrite rules
     */
    public function setup_rewrite_rules()
    {
        // WordPress-specific rewrite rules will be added here
    }
    
    /**
     * Enqueue frontend assets
     */
    public function enqueue_frontend_assets()
    {
        // Frontend assets will be enqueued here
    }
    
    /**
     * Enqueue admin assets
     */
    public function enqueue_admin_assets()
    {
        // Admin assets will be enqueued here
    }
}
