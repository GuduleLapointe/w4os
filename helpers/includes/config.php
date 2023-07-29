<?php if ( ! defined( 'W4OS_PLUGIN' ) ) {
	die();}
/*
 * config.php
 *
 * Helpers configuration for W4OS plugin.
 * Not for use with standalone helpers.
 *
 * Part of "flexible_helpers_scripts" collection
 *   https://github.com/GuduleLapointe/flexible_helper_scripts
 *   by Gudule Lapointe <gudule@speculoos.world>
 */

/**
 * Grid info
 */
define( 'OPENSIM_GRID_NAME', get_option( 'w4os_grid_name', 'OpenSimulator' ) );
define( 'OPENSIM_LOGIN_URI', W4OS_GRID_LOGIN_URI );
define( 'OPENSIM_MAIL_SENDER', get_option( 'w4os_offline_sender', 'no-reply@' . $_SERVER['SERVER_NAME'] ) );
// define('OPENSIM_GRID_LOGO_URL', (get_theme_mod( 'custom_logo' )) ? wp_get_attachment_image_src( get_theme_mod( 'custom_logo' ) , 'full' )[0] : '');

define( 'HYPEVENTS_URL', preg_replace( ':/$:', '', get_option( 'w4os_hypevents_url', 'https://2do.pm/events' ) ) );

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
define( 'OPENSIM_DB_HOST', get_option( 'w4os_db_host' ) );
define( 'OPENSIM_DB_NAME', get_option( 'w4os_db_database' ) );
define( 'OPENSIM_DB_USER', get_option( 'w4os_db_user' ) );
define( 'OPENSIM_DB_PASS', get_option( 'w4os_db_pass' ) );
define( 'OPENSIM_DB', ( ! empty( OPENSIM_DB_HOST ) & ! empty( OPENSIM_DB_NAME ) & ! empty( OPENSIM_DB_USER ) & ! empty( OPENSIM_DB_PASS ) ) ? true : false );

/**
 * Search database credentials and settings.
 * Needed if you enable search in OpenSim server.
 *
 * A dedicated database is:
 *   - strongly recommended if the search engine is shared by several grids
 *   - recommended and more efficient for large and/or hypergrid-enabled grids
 *   - optional for closed grids and standalone simulators
 * These are recommendations, the Robust database can safely be used instead.
 */
define( 'SEARCH_DB_HOST', get_option( 'w4os_search_db_host', OPENSIM_DB_HOST ) );
define( 'SEARCH_DB_NAME', get_option( 'w4os_search_db_database', OPENSIM_DB_NAME ) );
define( 'SEARCH_DB_USER', get_option( 'w4os_search_db_user', OPENSIM_DB_USER ) );
define( 'SEARCH_DB_PASS', get_option( 'w4os_search_db_pass', OPENSIM_DB_PASS ) );

/**
 * Other registrars to forward hosts registrations.
 *
 * This method is not needed as with current OpenSim server (0.9.x) which allow
 * specifying multiple registrars, but could be used in the future to implement
 * peer to peer information sharing.
 *
 * @var array
 */
define(
	'SEARCH_REGISTRARS',
	array(
	// 'http://2do.directory/helpers/register.php',
	// 'http://metaverseink.com/cgi-bin/register.py',
	)
);

/**
 * Currency database credentials and settings.
 * Needed if currency is enabled on OpenSim server.
 * A dedicated database is recommended, but not mandatory.
 */
define( 'CURRENCY_DB_HOST', get_option( 'w4os_economy_db_host' ) );
define( 'CURRENCY_DB_NAME', get_option( 'w4os_economy_db_database' ) );
define( 'CURRENCY_DB_USER', get_option( 'w4os_economy_db_user' ) );
define( 'CURRENCY_DB_PASS', get_option( 'w4os_economy_db_pass' ) );
define( 'CURRENCY_MONEY_TBL', 'balances' );
define( 'CURRENCY_TRANSACTION_TBL', 'transactions' );

/**
 * Money Server settings.
 */
define( 'CURRENCY_USE_MONEYSERVER', true );
define( 'CURRENCY_SCRIPT_KEY', get_option( 'w4os_money_script_access_key', '123456789' ) );
$currency_rate = (float) get_option( 'w4os_currency_rate', 10 );
define( 'CURRENCY_RATE', ( $currency_rate <= 0 ? 10 : $currency_rate ) ); // amount in dollar...
$currency_per = (int) get_option( 'w4os_currency_rate_per', 1000 );
define( 'CURRENCY_RATE_PER', ( $currency_per <= 0 ? 1000 : $currency_per ) ); // ... for this amount in virtuall currency
define( 'CURRENCY_PROVIDER', get_option( 'w4os_currency_provider' ) );
if ( ! defined( 'CURRENCY_HELPER_URL' ) ) {
	define( 'CURRENCY_HELPER_URL', ( ! empty( W4OS_GRID_INFO['economy'] ) ) ? W4OS_GRID_INFO['economy'] : get_home_url( null, '/economy/' ) );
}
switch ( CURRENCY_PROVIDER ) {
	case 'podex':
		if ( ! empty( get_option( 'w4os_podex_error_message' ) ) ) {
			define( 'PODEX_ERROR_MESSAGE', get_option( 'w4os_podex_error_message' ) );
		}
		if ( ! empty( get_option( 'w4os_podex_redirect_url' ) ) ) {
			define( 'PODEX_REDIRECT_URL', get_option( 'w4os_podex_redirect_url' ) );
		}
		break;

}

// if (!defined('CURRENCY_HELPER_PATH')) define('CURRENCY_HELPER_PATH', dirname(__DIR__));

/**
 * Timezone settings. Leave commented if included in a larger project. You
 * dont want to mess up with timezone randomly in the middle of a process!
 */
// define('OPENSIM_USE_UTC_TIME', false);
// if (OPENSIM_USE_UTC_TIME) date_default_timezone_set('UTC');

/**
 * OffLine messages DB credentials.
 *
 * @var [type]
 */
define( 'OFFLINE_DB_HOST', OPENSIM_DB_HOST );
define( 'OFFLINE_DB_NAME', OPENSIM_DB_NAME );
define( 'OFFLINE_DB_USER', OPENSIM_DB_USER );
define( 'OFFLINE_DB_PASS', OPENSIM_DB_PASS );
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
require_once 'functions.php';

$currency_addon = dirname( __DIR__ ) . '/addons/' . CURRENCY_PROVIDER . '.php';
if ( file_exists( $currency_addon ) ) {
	require_once $currency_addon;
}
