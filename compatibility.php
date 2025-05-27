<?php
/**
 * Backward Compatibility Layer
 * 
 * Ensures any external references to old file locations continue to work
 * during the transition period.
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Create compatibility aliases for moved classes
if (!class_exists('OpenSim_Database')) {
    class_alias('OpenSim_Database', 'W4OS_Database');
}

if (!class_exists('OpenSim_Avatar')) {
    class_alias('OpenSim_Avatar', 'W4OS_Avatar');
}

if (!class_exists('OpenSim_Search')) {
    class_alias('OpenSim_Search', 'W4OS_Search');
}

if (!class_exists('OpenSim_Economy')) {
    class_alias('OpenSim_Economy', 'W4OS_Economy');
}

if (!class_exists('OpenSim_Grid')) {
    class_alias('OpenSim_Grid', 'W4OS_Grid');
}

// Maintain function compatibility
// (Functions will be added here as they are moved)
