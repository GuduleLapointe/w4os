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
define('W4OS_PLUGIN_DIR', plugin_dir_path(__DIR__));
define('W4OS_PLUGIN_URL', plugin_dir_url(__DIR__));
define('W4OS_VERSION', '2.9.5-beta-9');
define('W4OS_SLUG', 'w4os');

// Enable all features (remove beta toggles)
if(!defined('W4OS_ENABLE_V3')) {
    define('W4OS_ENABLE_V3', true);
}

// Load engine first
require_once W4OS_PLUGIN_DIR . 'engine/bootstrap.php';

// Load backward compatibility layer
require_once W4OS_PLUGIN_DIR . 'compatibility.php';

// Load v3 transitional files FIRST (contains W4OS3 class and latest features)
if (file_exists(W4OS_PLUGIN_DIR . 'v3/2to3-init.php')) {
    require_once W4OS_PLUGIN_DIR . 'v3/2to3-init.php';
}

// Ensure W4OS3 class is available (critical for credential handling)
if (!class_exists('W4OS3')) {
    // Try to load W4OS3 class from various possible locations
    $possible_locations = [
        W4OS_PLUGIN_DIR . 'v3/includes/class-w4os3.php',
        W4OS_PLUGIN_DIR . 'v3/class-w4os3.php',
        W4OS_PLUGIN_DIR . 'v2/includes/class-w4os3.php',
        W4OS_PLUGIN_DIR . 'includes/class-w4os3.php',
        W4OS_PLUGIN_DIR . 'class-w4os3.php'
    ];
    
    foreach ($possible_locations as $file) {
        if (file_exists($file)) {
            require_once $file;
            break;
        }
    }
    
    // If still not found, create a bridge to OpenSimulator engine
    if (!class_exists('W4OS3')) {
        class W4OS3 {
            public static $robust_db;
            public static $assets_db;
            public static $profile_db;
            
            // Bridge to OpenSimulator engine methods
            public static function sanitize_uri($uri) {
                if (class_exists('OpenSim')) {
                    return OpenSim::sanitize_uri($uri);
                }
                // Fallback implementation
                if (empty($uri)) return null;
                $uri = (preg_match('/^https?:\/\//', $uri)) ? $uri : 'http://' . $uri;
                $parts = parse_url($uri);
                if (!$parts) return null;
                $parts = array_merge(['scheme' => 'http', 'port' => 8002], $parts);
                return $parts['scheme'] . '://' . $parts['host'] . ':' . $parts['port'];
            }
            
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
            
            // WordPress-specific methods for compatibility
            public static function enqueue_script($handle, $src, $deps = [], $ver = false, $in_footer = false) {
                if (class_exists('W4OS_WordPress')) {
                    return W4OS_WordPress::enqueue_script($handle, $src, $deps, $ver, $in_footer);
                }
                if (function_exists('wp_enqueue_script')) {
                    $handle = preg_match('/^w4os-/', $handle) ? $handle : 'w4os-' . $handle;
                    wp_enqueue_script($handle, $src, $deps, $ver, $in_footer);
                }
            }
            
            public static function enqueue_style($handle, $src, $deps = [], $ver = false, $media = 'all') {
                if (class_exists('W4OS_WordPress')) {
                    return W4OS_WordPress::enqueue_style($handle, $src, $deps, $ver, $media);
                }
                if (function_exists('wp_enqueue_style')) {
                    $handle = preg_match('/^w4os-/', $handle) ? $handle : 'w4os-' . $handle;
                    wp_enqueue_style($handle, $src, $deps, $ver, $media);
                }
            }
            
            public static function account_url() {
                if (class_exists('W4OS_WordPress')) {
                    return W4OS_WordPress::account_url();
                }
                $account_slug = get_option('w4os_account_url', 'account');
                $page = get_page_by_path($account_slug);
                return ($page) ? get_permalink($page->ID) : get_edit_user_link();
            }
        }
    }
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

// Load WordPress-specific classes and functions (new organized structure)
if (file_exists(__DIR__ . '/includes/class-w4os-wordpress.php')) {
    require_once __DIR__ . '/includes/class-w4os-wordpress.php';
    require_once __DIR__ . '/includes/admin-functions.php';
    require_once __DIR__ . '/includes/public-functions.php';
    
    // Initialize WordPress integration
    W4OS::getInstance();
}
