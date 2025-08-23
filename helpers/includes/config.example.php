<?php
/**
 * config.example.php
 *
 * Helpers configuration
 * Rename this file as "config.php" before editing.
 *
 * @package		magicoli/opensim-helpers
 * @author 		Gudule Lapointe <gudule@speculoos.world>
 * @link 			https://github.com/magicoli/opensim-helpers
 * @license		AGPLv3
 */

/**
 * Grid info
 */
define( 'OPENSIM_GRID_NAME', 'Your Grid' );
define( 'OPENSIM_LOGIN_URI', 'http://yourgrid.org:8002' );
define( 'OPENSIM_MAIL_SENDER', "no-reply@{$_SERVER['SERVER_NAME']}" );
// define('OPENSIM_GRID_LOGO_URL', "http://yougrid.org/logo.png");

define( 'HYPEVENTS_URL', preg_replace( ':/$:', '', 'https://2do.directory/events' ) );

/**
 * Main database.
 * For grids, use Robust database credentials.
 * For standalone simulators, use OpenSim database credentials.
 *
 * Access to OpenSim database is required
 *   - for search including classifieds
 *   - for offline messages processing
 *   - for economy
 * It is not required if only search is needed, without classifieds (e.g. to for
 * a multi-grid search engine). In this case search will only provide results
 * for places, land for sale and events.
 */

// Main database is now set with ROBUST_DB constant. Use of OPENSIM_DB_* constants will be deprecated.
define( 'ROBUST_DB', array (
	'host' => 'localhost',
	'port' => 3306,
	'name' => 'opensim',
	'user' => 'opensim',
	'pass' => '',
) );

/**
 * Robust console settings.
 * 
 * It will allow more interactions with the Robust server in the future.
 * e.g. access to some region information without requiring a direct connection
 * to each simulator.
 */
define( 'ROBUST_CONSOLE', array(
	// 'ConsoleUser' => 'user',
	// 'ConsolePass' => 'password',
	// 'ConsolePort' => 8004,
) );

/**
 * OpenSim DB refers here to the main database, i.e. Robust DB in the case
 * of a grid, or OpenSim db for a standalone simulator. It is only the historical
 * name of the settings and can be left as aliases of Robust DB credentials.
 * Transition is in progress to better differentiate the Robust/Standalone
 * main database from potential simulators/regions specific databases.
 */
define( 'OPENSIM_DB', true ); // Set to false for search only, see below
define( 'OPENSIM_DB_HOST', ROBUST_DB['host'] );
define( 'OPENSIM_DB_PORT', ROBUST_DB['port'] );
define( 'OPENSIM_DB_NAME', ROBUST_DB['name'] );
define( 'OPENSIM_DB_USER', ROBUST_DB['user'] );
define( 'OPENSIM_DB_PASS', ROBUST_DB['pass'] );

/**
 * Search database credentials and settings.
 * Needed if you enable search in OpenSim server.
 *
 * A dedicated database is:
 *   - strongly recommended if the search engine is shared by several grids
 *   - recommended and more efficient for large and/or hypergrid-enabled grids
 *   - optional for closed grids and standalone simulators
 * These are recommendations, the Robust/Main database can safely be used
 * in all cases.
 */

define( 'SEARCH_DB', array(
	'host' => OPENSIM_DB_HOST,
	'port' => null, // Leave null for default port
	'name' => OPENSIM_DB_NAME,
	'user' => OPENSIM_DB_USER,
	'pass' => OPENSIM_DB_PASS,
) );
define( 'SEARCH_DB_HOST', SEARCH_DB['host'] );
define( 'SEARCH_DB_PORT', SEARCH_DB['port'] );
define( 'SEARCH_DB_NAME', SEARCH_DB['name'] );
define( 'SEARCH_DB_USER', SEARCH_DB['user'] );
define( 'SEARCH_DB_PASS', SEARCH_DB['pass'] );

define( 'SEARCH_TABLE_EVENTS', 'events' );

/**
 * Other registrars to forward hosts registrations (deprecated)
 *
 * This method is not needed since OpenSim server (0.9.x) which allow
 * specifying multiple registrars, but could be used in the future to implement
 * peer to peer information sharing.
 *
 * @var array
 */
define(
	'SEARCH_REGISTRARS',
	array(
	// 'http://yourgrid.org/helpers/register.php',
	// 'http://2do.directory/helpers/register.php',
	// 'http://metaverseink.com/cgi-bin/register.py',
	)
);

/**
 * Currency database credentials and settings.
 * Needed if currency is enabled on OpenSim server.
 * A dedicated database is recommended, but not mandatory.
 */
define( 'CURRENCY_DB', array(
	'host' => OPENSIM_DB_HOST,
	'port' => null, // Leave null for default port
	'name' => OPENSIM_DB_NAME,
	'user' => OPENSIM_DB_USER,
	'pass' => OPENSIM_DB_PASS,
) );
define( 'CURRENCY_DB_HOST', CURRENCY_DB['host'] );
define( 'CURRENCY_DB_PORT', CURRENCY_DB['port'] );
define( 'CURRENCY_DB_NAME', CURRENCY_DB['name'] );
define( 'CURRENCY_DB_USER', CURRENCY_DB['user'] );
define( 'CURRENCY_DB_PASS', CURRENCY_DB['pass'] );

define( 'CURRENCY_MONEY_TBL', 'balances' );
define( 'CURRENCY_TRANSACTION_TBL', 'transactions' );

/**
 * Money Server settings.
 */
define( 'CURRENCY_USE_MONEYSERVER', false );
define( 'CURRENCY_SCRIPT_KEY', '123456789' );
define( 'CURRENCY_RATE', 10 ); // amount in dollar...
define( 'CURRENCY_RATE_PER', 1000 ); // ... for this amount in virtual currency
define( 'CURRENCY_PROVIDER', null ); // NULL, 'podex' or 'gloebit'
define( 'CURRENCY_HELPER_URL', 'http://yourgrid.org/helpers/' );
// if (!defined('CURRENCY_HELPER_PATH')) define('CURRENCY_HELPER_PATH', dirname(__DIR__));

/**
 * Timezone settings. Leave commented if included in a larger project. You
 * dont want to mess up with timezone randomly in the middle of a process!
 */
define( 'OPENSIM_USE_UTC_TIME', true );
if ( OPENSIM_USE_UTC_TIME ) {
	date_default_timezone_set( 'UTC' );
}

/**
 * OffLine messages DB credentials.
 *
 * @var [type]
 */
define( 'OFFLINE_DB', array(
	'host' => OPENSIM_DB_HOST,
	'port' => null, // Leave null for default port
	'name' => OPENSIM_DB_NAME,
	'user' => OPENSIM_DB_USER,
	'pass' => OPENSIM_DB_PASS,
) );
define( 'OFFLINE_DB_HOST', OFFLINE_DB['host'] );
define( 'OFFLINE_DB_PORT', OFFLINE_DB['port'] );
define( 'OFFLINE_DB_NAME', OFFLINE_DB['name'] );
define( 'OFFLINE_DB_USER', OFFLINE_DB['user'] );
define( 'OFFLINE_DB_PASS', OFFLINE_DB['pass'] );

define( 'OFFLINE_MESSAGE_TBL', 'im_offline' ); // Same DB as Offline Module V2?

/**
 * Mute list database.
 * mute is now handled by OpenSim server (0.9.x), so we shouldn't need this
 */
// define('MUTE_DB_HOST', OPENSIM_DB_HOST);
// define('MUTE_DB_NAME', OPENSIM_DB_NAME);
// define('MUTE_DB_USER', OPENSIM_DB_USER);
// define('MUTE_DB_PASS', OPENSIM_DB_PASS);
// define('MUTE_LIST_TBL', 'mute_list');

/**
 * Additional custom config
 * (e.g. define custom values for addons here)
 */
// define('MY_CONSTANT_NAME', 'my value');

/**
 * DO NOT MAKE CHANGES BELOW THIS
 * Add your custom values above.
 */
require_once 'databases.php';
require_once dirname(dirname(__DIR__)) . '/engine/includes/functions.php';

$currency_addon = dirname( __DIR__ ) . '/addons/' . CURRENCY_PROVIDER . '.php';
if ( file_exists( $currency_addon ) ) {
	require_once $currency_addon;
}
