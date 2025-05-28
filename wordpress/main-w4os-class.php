<?php
/**
 * WordPress Integration Initialization
 * 
 * Loads all WordPress-specific functionality including:
 * - Admin pages and menus
 * - Settings management  
 * - WordPress hooks and filters
 * - Public-facing features
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Define plugin constants
define('W4OS_VERSION', '2.9.5-beta-9');
define('W4OS_PLUGIN_DIR', plugin_dir_path(__DIR__));
define('W4OS_PLUGIN_URL', plugin_dir_url(__DIR__));
define('W4OS_SLUG', basename( W4OS_PLUGIN_DIR ) );

// Enable all features (remove beta toggles)
if(!defined('W4OS_ENABLE_V3')) {
    define('W4OS_ENABLE_V3', true);
}

// Load engine first
require_once W4OS_PLUGIN_DIR . 'engine/bootstrap.php';

// Load v3 transitional files FIRST (contains latest features not yet migrated here)
require_once W4OS_PLUGIN_DIR . 'v3/2to3-init.php';
    
// Use bridge implementation to OpenSimulator engine
class W4OS3 {
    public static $robust_db;
    public static $assets_db;
    public static $profile_db;
    
    // Moved in engine OpenSim class
    // public static function sanitize_uri($uri) {
    //     if (class_exists('OpenSim')) {
    //         return OpenSim::sanitize_uri($uri);
    //     }
    //     // Fallback implementation
    //     if (empty($uri)) return null;
    //     $uri = (preg_match('/^https?:\/\//', $uri)) ? $uri : 'http://' . $uri;
    //     $parts = parse_url($uri);
    //     if (!$parts) return null;
    //     $parts = array_merge(['scheme' => 'http', 'port' => 8002], $parts);
    //     return $parts['scheme'] . '://' . $parts['host'] . ':' . $parts['port'];
    // }
    
    public static function encrypt($data, $key = null) {
        if (class_exists('OpenSim') && $key) {
            return OpenSim::encrypt($data, $key);
        }
        // Fallback encryption - base64 encode for now
        return base64_encode(json_encode($data));
    }
    
    public static function decrypt($data, $key = null) {
        if (class_exists('OpenSim') && $key) {
            return OpenSim::decrypt($data, $key);
        }
        // Fallback decryption - base64 decode
        $decoded = base64_decode($data);
        $json = json_decode($decoded, true);
        return $json !== null ? $json : $decoded;
    }
    
    public static function is_uuid($uuid, $accept_null = true) {
        if (class_exists('OpenSim')) {
            return OpenSim::is_uuid($uuid, $accept_null);
        }
        // Fallback implementation
        if (!is_string($uuid)) return false;
        return preg_match('/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/', $uuid);
    }
    
    public static function fast_xml($url) {
        if (class_exists('OpenSim')) {
            return OpenSim::fast_xml($url);
        }
        return null;
    }
    
    public static function grid_info($gateway_uri = null, $force = false) {
        if (class_exists('OpenSim')) {
            if (!$gateway_uri) {
                $gateway_uri = get_option('w4os_login_uri', 'localhost:8002');
            }
            return OpenSim::grid_info($gateway_uri, $force);
        }
        // Fallback implementation
        if (!$gateway_uri) {
            $gateway_uri = get_option('w4os_login_uri', 'localhost:8002');
        }
        return ['gridname' => 'Unknown Grid', 'gridnick' => 'unknown'];
    }
    
    // WordPress-specific enqueue methods
    public static function enqueue_script($handle, $src, $deps = [], $ver = false, $in_footer = false) {
        $handle = preg_match('/^w4os-/', $handle) ? $handle : 'w4os-' . $handle;
        $ver = empty($ver) ? (defined('W4OS_VERSION') ? W4OS_VERSION : '1.0.0') : $ver;
        $src = preg_match('/^http/', $src) ? $src : (defined('W4OS_PLUGIN_DIR_URL') ? W4OS_PLUGIN_DIR_URL : plugin_dir_url(dirname(__DIR__))) . $src;
        
        if (function_exists('wp_enqueue_script')) {
            wp_enqueue_script($handle, $src, $deps, $ver, $in_footer);
        }
    }
    
    public static function enqueue_style($handle, $src, $deps = [], $ver = false, $media = 'all') {
        $handle = preg_match('/^w4os-/', $handle) ? $handle : 'w4os-' . $handle;
        $ver = empty($ver) ? (defined('W4OS_VERSION') ? W4OS_VERSION : '1.0.0') : $ver;
        $src = preg_match('/^http/', $src) ? $src : (defined('W4OS_PLUGIN_DIR_URL') ? W4OS_PLUGIN_DIR_URL : plugin_dir_url(dirname(__DIR__))) . $src;
        
        if (function_exists('wp_enqueue_style')) {
            wp_enqueue_style($handle, $src, $deps, $ver, $media);
        }
    }
    
    public static function account_url() {
        $account_slug = get_option('w4os_account_url', 'account');
        $page = get_page_by_path($account_slug);
        return ($page) ? get_permalink($page->ID) : get_edit_user_link();
    }
    
    // Additional methods that might be needed by v1 functions
    public static function empty($var) {
        if (class_exists('OpenSim')) {
            return OpenSim::empty($var);
        }
        if (!$var) return true;
        if (empty($var)) return true;
        $null_keys = ['00000000-0000-0000-0000-000000000000', '00000000-0000-0000-0000-000000000001'];
        if (in_array($var, $null_keys)) return true;
        return false;
    }
    
    public static function date($timestamp = null, $format = null, $timezone = null) {
        $args = func_get_args();
        if (empty($args)) {
            $timestamp = time();
            $format = get_option('date_format');
        } elseif (is_numeric($args[0])) {
            $timestamp = $args[0];
            $format = $args[1] ?? get_option('date_format');
        } else {
            $format = $args[0] ?? get_option('date_format');
            $timestamp = $args[1] ?? time();
        }
        $timezone = $args[2] ?? null;
        if (empty($timestamp)) return;
        if (empty($format)) $format = get_option('date_format');
        return wp_date($format, $timestamp, $timezone);
    }
    
    // WordPress-specific methods moved from class-w4os.php
    public static function get_option($option, $default = false) {
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

    public static function update_option($option, $value, $autoload = null) {
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
    
    public static function add_submenu_page($parent_slug, $page_title, $menu_title, $capability, $menu_slug, $callback = '', $position = null) {
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
    
    public static function is_new_post($args = null) {
        global $pagenow;
        
        if (!is_admin()) {
            return false;
        }
        
        return in_array($pagenow, ['post-new.php']);
    }
    
    public static function format_date($timestamp, $format = 'MEDIUM', $timetype_str = 'NONE') {
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
    
    public static function img($img_uuid, $atts = []) {
        if (class_exists('OpenSim')) {
            if (OpenSim::is_null_key($img_uuid) || !OpenSim::is_uuid($img_uuid)) {
                return '';
            }
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
    
    public static function get_localized_post_id($post_id = null, $default = true) {
        if (empty($post_id)) {
            $post_id = get_the_id();
        }
        
        // Check for WPML
        if (function_exists('icl_object_id')) {
            $default_language = apply_filters('wpml_default_language', null);
            if ($default) {
                $localized_post_id = icl_object_id($post_id, 'post', false, $default_language);
            } else {
                $localized_post_id = icl_object_id($post_id, 'post', false);
                $localized_post_id = (empty($localized_post_id)) ? icl_object_id($post_id, 'post', false, $default_language) : $localized_post_id;
            }
            
            return empty($localized_post_id) ? $post_id : $localized_post_id;
        }
        
        // Check for Polylang
        if (function_exists('pll_get_post')) {
            global $polylang;
            $languages = $polylang->model->get_languages_list();
            
            if ($default) {
                $default_language = $polylang->default_lang;
            } else {
                $default_language = get_locale();
            }
            
            $localized_post_id = $post_id;
            
            if (isset($languages[$default_language]) && $languages[$default_language]['slug'] !== get_locale()) {
                $translations = $polylang->model->post->get_translations($post_id);
                
                if (isset($translations[$default_language])) {
                    $localized_post_id = $translations[$default_language];
                }
            }
            
            return $localized_post_id;
        }
        
        return $post_id;
    }
    
    public static function get_localized_post_slug($post_id = null) {
        $localized_post_id = self::get_localized_post_id($post_id);
        $original = get_post($localized_post_id);
        $post_name = isset($original->post_name) ? $original->post_name : null;
        return $post_name;
    }
    
    public static function sprintf_safe($format, ...$args) {
        try {
            $result = sprintf($format, ...$args);
            restore_error_handler();
            return $result;
        } catch (Throwable $e) {
            error_log("Error W4OS3::sprintf_safe( $format, " . join(', ', $args) . '): ' . $e->getMessage());
            restore_error_handler();
            return $format;
        }
    }
}

// Load WordPress-specific classes (consolidating into W4OS3)
// Only load the model class now that methods are consolidated into W4OS3
if (file_exists(__DIR__ . '/includes/class-w4os3-model.php')) {
    require_once __DIR__ . '/includes/class-w4os3-model.php';
}

// Load all current WordPress functionality in correct order
// Legacy v1 init (contains core WordPress integration)
require_once W4OS_PLUGIN_DIR . 'v1/init.php';

// v2 loader (contains additional features)
require_once W4OS_PLUGIN_DIR . 'v2/loader.php';

// Load admin functionality
if (is_admin()) {
    require_once W4OS_PLUGIN_DIR . 'v1/admin/admin-init.php';
}
