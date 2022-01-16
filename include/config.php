<?php
//
// Configration file for non Web Interface
//
//
//

// Please set this hepler script URL and directory
if (!defined('ENV_HELPER_URL'))  define('ENV_HELPER_URL', (!empty(W4OS_GRID_INFO['economy'])) ? W4OS_GRID_INFO['economy'] : get_home_url(NULL, '/economy/'));
if (!defined('ENV_HELPER_PATH')) define('ENV_HELPER_PATH', dirname(__DIR__));

//////////////////////////////////////////////////////////////////////////////////i
// Valiables for OpenSim

// Please set MySQL DB access information
define('OPENSIM_DB_HOST', get_option('w4os_db_host'));
define('OPENSIM_DB_NAME', get_option('w4os_db_database'));
define('OPENSIM_DB_USER', get_option('w4os_db_user'));
define('OPENSIM_DB_PASS', get_option('w4os_db_pass'));

define('OPENSIM_DB_MYSQLI', true);		// if you use MySQLi interface, please set true


// Money Server Access Key
// Please set same key with MoneyScriptAccessKey in MoneyServer.ini
define('CURRENCY_SCRIPT_KEY', get_option('w4os_money_script_access_key', '123456789'));


// Group Module Access Keys
// Please set same keys with at [Groups] section in OpenSim.ini (case of Aurora-Sim, it is Groups.ini)
define('XMLGROUP_RKEY', '1234');	// Read Key
define('XMLGROUP_WKEY', '1234');	// Write key

$logo = get_theme_mod( 'custom_logo' );
$image = wp_get_attachment_image_src( $logo , 'full' );
$image_url = $image[0];
define('WEBSITE_LOGO_URL', $image_url);

//define('CURRENCY_MODULE', 'Gloebit');

//
// Forward regions registrations to other compatible registrars
//
$otherRegistrars=array(
	// 'http://metaverseink.com/cgi-bin/register.py',
);


//////////////////////////////////////////////////////////////////////////////////
// You need not change the below usually.

define('USE_CURRENCY_SERVER', 1);
define('USE_UTC_TIME',		  1);

define('SYSURL', ENV_HELPER_URL);
$GLOBALS['xmlrpc_internalencoding'] = 'UTF-8';

if (USE_UTC_TIME) date_default_timezone_set('UTC');


// Currency DB
define('CURRENCY_DB_HOST',          get_option('w4os_economy_db_host'));
define('CURRENCY_DB_NAME',          get_option('w4os_economy_db_database'));
define('CURRENCY_DB_USER',          get_option('w4os_economy_db_user'));
define('CURRENCY_DB_PASS',          get_option('w4os_economy_db_pass'));
define('CURRENCY_DB_MYSQLI',        OPENSIM_DB_MYSQLI);
define('CURRENCY_MONEY_TBL',        'balances');
define('CURRENCY_TRANSACTION_TBL',  'transactions');


// OffLine Message DB
define('OFFLINE_DB_HOST',     		OPENSIM_DB_HOST);
define('OFFLINE_DB_NAME',     		OPENSIM_DB_NAME);
define('OFFLINE_DB_USER',     		OPENSIM_DB_USER);
define('OFFLINE_DB_PASS',     		OPENSIM_DB_PASS);
define('OFFLINE_DB_MYSQLI',    		OPENSIM_DB_MYSQLI);
define('OFFLINE_MESSAGE_TBL', 		'offline_message');


// MuteList DB
define('MUTE_DB_HOST',              OPENSIM_DB_HOST);
define('MUTE_DB_NAME',              OPENSIM_DB_NAME);
define('MUTE_DB_USER',              OPENSIM_DB_USER);
define('MUTE_DB_PASS',              OPENSIM_DB_PASS);
define('MUTE_DB_MYSQLI',            OPENSIM_DB_MYSQLI);
define('MUTE_LIST_TBL',             'mute_list');



////////////////////////////////////////////////////////////
// External other Modules

// XML Group.  see also xmlgroups_config.php
define('XMLGROUP_ACTIVE_TBL',		'osagent');
define('XMLGROUP_LIST_TBL',			'osgroup');
define('XMLGROUP_INVITE_TBL',		'osgroupinvite');
define('XMLGROUP_MEMBERSHIP_TBL',	'osgroupmembership');
define('XMLGROUP_NOTICE_TBL',		'osgroupnotice');
define('XMLGROUP_ROLE_MEMBER_TBL',	'osgrouprolemembership');
define('XMLGROUP_ROLE_TBL',			'osrole');


// Avatar Profile. see also profile_config.php
define('PROFILE_CLASSIFIEDS_TBL',	'classifieds');
define('PROFILE_USERNOTES_TBL',		'usernotes');
define('PROFILE_USERPICKS_TBL',		'userpicks');
define('PROFILE_USERPROFILE_TBL',	'userprofile');
define('PROFILE_USERSETTINGS_TBL',	'usersettings');


// Search the In World. see also search_config.php
define('SEARCH_ALLPARCELS_TBL',		'allparcels');
define('SEARCH_EVENTS_TBL',			'events');
define('SEARCH_HOSTSREGISTER_TBL',	'hostsregister');
define('SEARCH_OBJECTS_TBL',		'objects');
define('SEARCH_PARCELS_TBL',		'parcels');
define('SEARCH_PARCELSALES_TBL',	'parcelsales');
define('SEARCH_POPULARPLACES_TBL',	'popularplaces');
define('SEARCH_REGIONS_TBL',		'search_regions');
define('SEARCH_CLASSIFIEDS_TBL',	PROFILE_CLASSIFIEDS_TBL);

switch(get_option('w4os_currency_provider')) {
  case 'gloebit':
  define('GLOEBIT_SANDBOX', get_option('w4os_gloebit_sandbox', false));
  // TODO: fetch conversion table from Gloebit website.
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

define('ENV_CONFIG_PARSED', true);
