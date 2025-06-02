<?php
/**
 * OpenSimulator Engine Bootstrap
 * 
 * Core OpenSimulator communication engine without framework dependencies.
 * This file sets up the engine that can be used by WordPress, API, or standalone.
 */

// Prevent direct access
if (!defined('ABSPATH') && !defined('OPENSIM_ENGINE')) {
    exit;
}

// Define engine constants
if (!defined('OPENSIM_ENGINE_PATH')) {
    define('OPENSIM_ENGINE_PATH', __DIR__);
}

if (!defined('OPENSIM_ENGINE_VERSION')) {
    define('OPENSIM_ENGINE_VERSION', '1.0.0');
}

// Engine autoloader
spl_autoload_register(function ($class) {
    if (strpos($class, 'OpenSim_') === 0) {
        $file = OPENSIM_ENGINE_PATH . '/class-' . strtolower(str_replace('_', '-', str_replace('OpenSim_', '', $class))) . '.php';
        if (file_exists($file)) {
            require $file;
        }
    }
});

require_once OPENSIM_ENGINE_PATH . '/includes/functions.php';

// Load core Engine classes
require_once OPENSIM_ENGINE_PATH . '/class-engine-exceptions.php';
require_once OPENSIM_ENGINE_PATH . '/class-engine-settings.php';

// Load OpenSimulator classes
require_once OPENSIM_ENGINE_PATH . '/class-opensim.php';
require_once OPENSIM_ENGINE_PATH . '/class-service.php';
require_once OPENSIM_ENGINE_PATH . '/class-database.php';

// Temporary fix: Load helper classes for OpenSim compatibility
// TODO: if they are needed by engine, they should be moved to the engine directory
$helpers_path = dirname(OPENSIM_ENGINE_PATH) . '/helpers';
if (file_exists($helpers_path . '/classes/class-ini.php')) {
    require_once $helpers_path . '/classes/class-ini.php';
}
if (file_exists($helpers_path . '/classes/class-error.php')) {
    require_once $helpers_path . '/classes/class-error.php';
}

require_once OPENSIM_ENGINE_PATH . '/class-avatar.php';
require_once OPENSIM_ENGINE_PATH . '/class-region.php';
require_once OPENSIM_ENGINE_PATH . '/class-search.php';
require_once OPENSIM_ENGINE_PATH . '/class-economy.php';
require_once OPENSIM_ENGINE_PATH . '/class-grid.php';

// Initialize settings system
Engine_Settings::init();

// Engine initialization complete
if (!defined('OPENSIM_ENGINE_LOADED')) {
    define('OPENSIM_ENGINE_LOADED', true);
}

// Optional: Test the settings system if in debug mode
if (defined('OPENSIM_ENGINE_DEBUG') && OPENSIM_ENGINE_DEBUG) {
    error_log('OpenSim Engine: Settings system initialized at ' . Engine_Settings::get_config_dir());
}
