<?php
/**
 * OpenSimulator Engine Bootstrap
 * 
 * Core OpenSimulator communication engine without framework dependencies.
 * This file sets up the engine that can be used by WordPress, API, or standalone.
 * 
 * The engine is totally independant from whichever library or projects includs it.
 * So nothing in engine can depend or rely on a parent project like WordPress or Helpers.
 */

// Prevent direct access
if (!defined('ABSPATH') && !defined('OPENSIM_ENGINE')) {
    exit;
}

define('ENGINE_PATH', __DIR__); // Alias for OPENSIM_ENGINE_PATH

// Define engine constants
if (!defined('OPENSIM_ENGINE_PATH')) {
    define('OPENSIM_ENGINE_PATH', __DIR__);
}

if (!defined('OPENSIM_ENGINE_VERSION')) {
    define('OPENSIM_ENGINE_VERSION', '1.0.0');
}

// Load Composer autoloader for engine dependencies (currently Laminas)
// require_once OPENSIM_ENGINE_PATH . '/vendor/autoload.php';

// Engine autoloader (remove debug logs for production)
spl_autoload_register(function ($class) {
    // Original v3 autoloader for OpenSim_ classes
    if (strpos($class, 'OpenSim_') === 0) {
        $file = OPENSIM_ENGINE_PATH . '/class-' . strtolower(str_replace('_', '-', str_replace('OpenSim_', '', $class))) . '.php';
        if (file_exists($file)) {
            require $file;
            return;
        }
    }

    // Imported autoloader to implement PSR-4 style autoloading for Engine_ classes
    // we should keep this one, but make sure it handles initial OpenSim_ classes too

    if (! strpos($class, 'Engine_') === 0 && ! strpos($class, 'TalentsGallery\\Engine\\') === 0) {
        return;
    }

    $class_tag = strtolower(str_replace(
        ['Engine_', '_'], ['', '-'], 
        basename(str_replace('\\', DIRECTORY_SEPARATOR, $class)
        )
    ));

    foreach (['class', 'models/class', 'trait'] as $prefix) {
        $file_path = ENGINE_PATH . DIRECTORY_SEPARATOR . "$prefix-$class_tag.php";
        if (file_exists($file_path)) {
            // error_log("[NOTICE] Loading $prefix $class from $file_path");  
            require_once $file_path;
            return; // Stop searching once the file is found
        }
    }
});

// Load ONLY essential dependencies that are always needed
require_once OPENSIM_ENGINE_PATH . '/opensim-rest/class-rest.php';
require_once OPENSIM_ENGINE_PATH . '/includes/functions.php';

// The following is only enabled in v3 branch, keep it commented out for now

// # This doesn't work and requires more investigation. Commented and kept for future reference.
// # In the meantime, we keep our dependency to php-xmlrpc extension
// // Load XML-RPC compatibility layer if the extension is not available
// // if (!function_exists('xmlrpc_encode') || !function_exists('xmlrpc_server_create')) { 
// //     require_once OPENSIM_ENGINE_PATH . '/includes/library-xmlrpc.php';
// // }

// // Load ONLY core classes that are always used
// // require_once OPENSIM_ENGINE_PATH . '/class-ini.php';
// require_once OPENSIM_ENGINE_PATH . '/class-engine-exceptions.php';
// require_once OPENSIM_ENGINE_PATH . '/class-engine-settings.php';
// // require_once OPENSIM_ENGINE_PATH . '/class-installation-wizard.php';
// require_once OPENSIM_ENGINE_PATH . '/class-opensim.php';
// // require_once OPENSIM_ENGINE_PATH . '/class-service.php';
// require_once OPENSIM_ENGINE_PATH . '/class-database.php';

// // Temporary fix: Load helper classes for OpenSim compatibility
// // TODO: if they are needed by engine, they should be moved to the engine directory
// // $helpers_path = dirname(OPENSIM_ENGINE_PATH);
// // if (file_exists($helpers_path . '/classes/class-error.php')) {
// //     require_once $helpers_path . '/classes/class-error.php';
// // }

// // Initialize settings system
// Engine_Settings::init();

// // Engine initialization complete
// if (!defined('OPENSIM_ENGINE_LOADED')) {
//     define('OPENSIM_ENGINE_LOADED', true);
// }

// // Optional: Test the settings system if in debug mode
// if (defined('OPENSIM_ENGINE_DEBUG') && OPENSIM_ENGINE_DEBUG) {
//     error_log('OpenSim Engine: Settings system initialized at ' . Engine_Settings::get_config_dir());
// }

// // All other classes now loaded via autoloader:
// // - OpenSim_Avatar
// // - OpenSim_Region  
// // - OpenSim_Search
// // - OpenSim_Economy
// // - OpenSim_Grid
// // - OpenSim_Installation_Wizard
// // - OpenSim_Form_Field
// // - OpenSim_Ini
// // - OpenSim_Service
