<?php
/**
 * W4OS Engine Bootstrap
 * 
 * Core engine initialization without WordPress dependencies.
 * This file sets up the engine that can be used by both WordPress and helpers.
 */

// Prevent direct access
if (!defined('ABSPATH') && !defined('W4OS_ENGINE')) {
    exit;
}

// Define engine constants
if (!defined('W4OS_ENGINE_PATH')) {
    define('W4OS_ENGINE_PATH', __DIR__);
}

if (!defined('W4OS_ENGINE_VERSION')) {
    define('W4OS_ENGINE_VERSION', '3.0.0');
}

// Engine autoloader
spl_autoload_register(function ($class) {
    if (strpos($class, 'W4OS_Engine_') === 0) {
        $file = W4OS_ENGINE_PATH . '/includes/' . strtolower(str_replace('_', '-', str_replace('W4OS_Engine_', '', $class))) . '.php';
        if (file_exists($file)) {
            require $file;
        }
    }
});

// Load core engine classes
require_once __DIR__ . '/includes/class-database.php';
require_once __DIR__ . '/includes/class-avatar.php';
require_once __DIR__ . '/includes/class-search.php';
require_once __DIR__ . '/includes/class-economy.php';
require_once __DIR__ . '/includes/class-grid.php';
