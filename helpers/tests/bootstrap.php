<?php
/**
 * PHPUnit Bootstrap for OpenSim Helpers Tests
 */

// Define test environment
define('PHPUNIT_RUNNING', true);

// Do not define OPENSIM_ENGINE, OPENSIM_HELPERS, or OPENSIM_HELPERS_PATH here
// They are define in the main bootstrap loaded in the next line
// define('OPENSIM_ENGINE', true);
// define('OPENSIM_HELPERS', true);
// define('OPENSIM_HELPERS_PATH', dirname(__DIR__));

// Load the helpers bootstrap
require_once dirname(__DIR__) . '/bootstrap.php';

// Ensure test environment is properly configured
if (!Engine_Settings::configured()) {
    echo "WARNING: Engine not configured. Some tests may fail.\n";
    echo "Configure OpenSim settings before running tests.\n";
}
