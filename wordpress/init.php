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
    
    // If still not found, create a minimal stub to prevent fatal errors
    if (!class_exists('W4OS3')) {
        class W4OS3 {
            public static function encrypt($data) {
                // Fallback encryption - base64 encode for now
                return base64_encode($data);
            }
            
            public static function decrypt($data) {
                // Fallback decryption - base64 decode
                return base64_decode($data);
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
    W4OS_WordPress::getInstance();
}
