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
if (!class_exists('W4OS_Engine_Database')) {
    class_alias('W4OS_Engine_Database', 'W4OS_Database');
}

if (!class_exists('W4OS_Engine_Avatar')) {
    class_alias('W4OS_Engine_Avatar', 'W4OS_Avatar');
}

if (!class_exists('W4OS_Engine_Search')) {
    class_alias('W4OS_Engine_Search', 'W4OS_Search');
}

if (!class_exists('W4OS_Engine_Economy')) {
    class_alias('W4OS_Engine_Economy', 'W4OS_Economy');
}

if (!class_exists('W4OS_Engine_Grid')) {
    class_alias('W4OS_Engine_Grid', 'W4OS_Grid');
}

// Maintain function compatibility
// (Functions will be added here as they are moved)