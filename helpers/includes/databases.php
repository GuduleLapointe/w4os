<?php
/**
 * Database initialization for WordPress context
 *
 * @package     magicoli/opensim-helpers
 * @author      Gudule Lapointe <gudule@speculoos.world>
 * @link        https://github.com/magicoli/opensim-helpers
 * @license     AGPLv3
 */

// OSPDO class definition moved to engine/includes/class-opensim-database.php
// This file now only handles Helpers database initialization

// Initialize OpenSimulator database connection if constants are defined
if ( defined( 'OPENSIM_DB' ) && OPENSIM_DB === true ) {
	$OpenSimDB = new OSPDO( 'mysql:host=' . OPENSIM_DB_HOST . ';dbname=' . OPENSIM_DB_NAME, OPENSIM_DB_USER, OPENSIM_DB_PASS );
}
