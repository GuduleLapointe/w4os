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

// Load core OpenSimulator classes
require_once OPENSIM_ENGINE_PATH . '/class-opensim.php';
require_once OPENSIM_ENGINE_PATH . '/class-database.php';
require_once OPENSIM_ENGINE_PATH . '/class-avatar.php';
require_once OPENSIM_ENGINE_PATH . '/class-search.php';
require_once OPENSIM_ENGINE_PATH . '/class-economy.php';
require_once OPENSIM_ENGINE_PATH . '/class-grid.php';
