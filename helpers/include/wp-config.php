<?php if ( ! defined( 'W4OS_PLUGIN' ) ) die();
/*
 * wp-config.php
 *
 * Helpers configuration for W4OS plugin.
 * Not for use with standalone helpers.
 *
 * Part of "flexible_helpers_scripts" collection
 *   https://github.com/GuduleLapointe/flexible_helper_scripts
 *   by Gudule Lapointe <gudule@speculoos.world>
 */

//////////////////////////////////////////////////////////////////////////////////i
// Valiables for OpenSim
/**
 * Grid info
 */

define('OPENSIM_GRID_NAME', get_option('w4os_grid_name', 'OpenSimulator'));
define('OPENSIM_LOGIN_URI', W4OS_GRID_LOGIN_URI);
define("OPENSIM_MAIL_SENDER", get_option('w4os_offline_sender', 'no-reply@' . $_SERVER['SERVER_NAME']));

// $logo = get_theme_mod( 'custom_logo' );
// $image = wp_get_attachment_image_src( $logo , 'full' );
// $image_url = $image[0];
// define('OPENSIM_GRID_LOGO_URL', $image_url);

/**
 * Main database. For grids, use Robust database credentials. For standalone
 * simulators, use OpenSim database credentials.
 */
define('OPENSIM_DB_HOST', get_option('w4os_db_host'));
define('OPENSIM_DB_NAME', get_option('w4os_db_database'));
define('OPENSIM_DB_USER', get_option('w4os_db_user'));
define('OPENSIM_DB_PASS', get_option('w4os_db_pass'));

/**
 * Currency database. Used both with out without currency server
 */
define('CURRENCY_DB_HOST', get_option('w4os_economy_db_host'));
define('CURRENCY_DB_NAME', get_option('w4os_economy_db_database'));
define('CURRENCY_DB_USER', get_option('w4os_economy_db_user'));
define('CURRENCY_DB_PASS', get_option('w4os_economy_db_pass'));
define('CURRENCY_MONEY_TBL',     'balances');
define('CURRENCY_TRANSACTION_TBL', 'transactions');

/**
 * Money Server settings.
 */
define('CURRENCY_USE_MONEYSERVER', true);
define('CURRENCY_SCRIPT_KEY', get_option('w4os_money_script_access_key', '123456789'));
define('CURRENCY_RATE', get_option('w4os_currency_rate', 10)); // amount in dollar...
define('CURRENCY_RATE_PER', get_option('w4os_currency_rate_per', 1000)); // ... for this amount in virtuall currency
define('CURRENCY_PROVIDER', get_option('w4os_currency_provider'));
if (!defined('CURRENCY_HELPER_URL'))  define('CURRENCY_HELPER_URL', (!empty(W4OS_GRID_INFO['economy'])) ? W4OS_GRID_INFO['economy'] : get_home_url(NULL, '/economy/'));
// if (!defined('CURRENCY_HELPER_PATH')) define('CURRENCY_HELPER_PATH', dirname(__DIR__));
switch(CURRENCY_PROVIDER) {
  case 'gloebit':
  define('GLOEBIT_SANDBOX', get_option('w4os_gloebit_sandbox', false));
  // TODO: grab conversion table from Gloebit website.
  define('GLOEBIT_CONVERSION_TABLE', array(
    400 => 199,
    1050 => 499,
    2150 => 999,
    4500 => 1999,
    11500 => 4999,
  ));
  define('GLOEBIT_CONVERSION_THRESHOLD', 1.2);
  break;
}

/**
 * Search database credentials and settings.
 * - Shared search engine (same engine used by several grids or simulator): use
 *   a dedicated database.
 * - Hypegrid-enabled or large grids: it's better (but not mandatory) to setup a
 *   separate database, to optimize performances and maintenance.
 * - Closed grids or closed simulators: you can safely use OpenSim database.
 */
define('SEARCH_DB_HOST', get_option('w4os_search_db_host', OPENSIM_DB_HOST));
define('SEARCH_DB_NAME', get_option('w4os_search_db_database', OPENSIM_DB_NAME));
define('SEARCH_DB_USER', get_option('w4os_search_db_user', OPENSIM_DB_USER));
define('SEARCH_DB_PASS', get_option('w4os_search_db_pass', OPENSIM_DB_PASS));
// Other registrars to Forward hosts registrations to
define('SEARCH_REGISTRARS', array(
	// 'http://metaverseink.com/cgi-bin/register.py',
));


/**
 * Timezone settings. Leave commented if included in a larger project. You
 * dont want to mess up with timezone randomly in the middle of a process!
 */
// define('OPENSIM_USE_UTC_TIME', false);
// if (OPENSIM_USE_UTC_TIME) date_default_timezone_set('UTC');

/**
 * OffLine messages DB credentials.
 * @var [type]
 */
define('OFFLINE_DB_HOST', OPENSIM_DB_HOST);
define('OFFLINE_DB_NAME', OPENSIM_DB_NAME);
define('OFFLINE_DB_USER', OPENSIM_DB_USER);
define('OFFLINE_DB_PASS', OPENSIM_DB_PASS);
define('OFFLINE_DB_MYSQLI', true);
define('OFFLINE_MESSAGE_TBL', 'im_offline'); // Same DB as Offline Module V2?

// MuteList DB
define('MUTE_DB_HOST',           OPENSIM_DB_HOST);
define('MUTE_DB_NAME',           OPENSIM_DB_NAME);
define('MUTE_DB_USER',           OPENSIM_DB_USER);
define('MUTE_DB_PASS',           OPENSIM_DB_PASS);
define('MUTE_DB_MYSQLI',         true);
define('MUTE_LIST_TBL',          'mute_list');

define('HYPEVENTS_URL', preg_replace(':/$:', '', get_option('w4os_hypevents_url', 'https://2do.pm/events')));

define('NULL_KEY', '00000000-0000-0000-0000-000000000000');

define('TPLINK_LOCAL', 1);
define('TPLINK_HG', 2);
define('TPLINK_V3HG', 4);
define('TPLINK_HOP', 8);
define('TPLINK_TXT', 16);
define('TPLINK_APPTP', 32);
define('TPLINK_MAP', 64);
define('TPLINK_DEFAULT', TPLINK_V3HG);

define('TPLINK', pow(2,8)-1);

define('TPLINK_NAMING', array(
  'TPLINK_LOCAL' => 'local',
  'TPLINK_HG' => 'HG', // inside viewer
  'TPLINK_V3HG' => 'V3HG', // from website
  'TPLINK_HOP' => 'hop',
  'TPLINK_TXT' => 'text',
  'TPLINK_APPTP' => 'teleport',
  'TPLINK_MAP' => 'map',
  'TPLINK_DEFAULT' => 'DEFAULT',
));

require_once('classes-db.php');
require_once('functions.php');
