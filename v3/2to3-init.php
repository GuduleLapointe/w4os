<?php
/**
 * Tranisition initialisation class for v2 to v3.
 *
 * This class loads the classes and functions needed to test v3 features
 * while keeping v2 features available.
 *
 * It will replace both v1/init.php and v2/loader.php when all
 * new v3 features are validated, and all remaining v2 or legacy features
 * are ported to v3.
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

/**
 * Clean up v3 W4OS3 class - comment out to prevent conflicts
 * Original methods moved to proper engine/wordpress structure
 */

// Disable the old v3 W4OS3 class definition
if (!defined('W4OS3_LOADED')) {
    define('W4OS3_LOADED', true);
}
