<?php

define('NULL_KEY', '00000000-0000-0000-0000-000000000000');
define('ZERO_VECTOR', '<0,0,0>');
define('DEFAULT_AVATAR_HEIGHT', '1.7');
define('DEFAULT_AVATAR_PARAMS', '33,61,85,23,58,127,63,85,63,42,0,85,63,36,85,95,153,63,34,0,63,109,88,132,63,136,81,85,103,136,127,0,150,150,150,127,0,0,0,0,0,127,0,0,255,127,114,127,99,63,127,140,127,127,0,0,0,191,0,104,0,0,0,0,0,0,0,0,0,145,216,133,0,127,0,127,170,0,0,127,127,109,85,127,127,63,85,42,150,150,150,150,150,150,150,25,150,150,150,0,127,0,0,144,85,127,132,127,85,0,127,127,127,127,127,127,59,127,85,127,127,106,47,79,127,127,204,2,141,66,0,0,127,127,0,0,0,0,127,0,159,0,0,178,127,36,85,131,127,127,127,153,95,0,140,75,27,127,127,0,150,150,198,0,0,63,30,127,165,209,198,127,127,153,204,51,51,255,255,255,204,0,255,150,150,150,150,150,150,150,150,150,150,0,150,150,150,150,150,0,127,127,150,150,150,150,150,150,150,150,0,0,150,51,132,150,150,150');
define('DEFAULT_AVATAR', "Default Ruth");
define('DEFAULT_HOME', "Welcome");
define('DEFAULT_RESTRICTED_NAMES', array("Default", "Test", "Admin", str_replace(' ', '', get_option('w4os_grid_name'))));
define('ASSET_SERVER_URI', '/assets/asset.php?id=');

$w4osdb = new WPDB(
	get_option('w4os_db_user'),
	get_option('w4os_db_pass'),
	get_option('w4os_db_database'),
	get_option('w4os_db_host')
);

function check_w4os_db_tables() {
	global $w4osdb;
	if(!is_object($w4osdb)) return false;
	$required_tables = array(
		'useraccounts',
		'presence',
		'griduser',
		'regions',
	);
	foreach($required_tables as $table_name) {
		if(strtolower($w4osdb->get_var("SHOW TABLES LIKE '$table_name'")) != $table_name) {
			return false;
			break;
		}
	}
	return true;
}
if (!defined('W4OS_DB_CONNECTED')) define('W4OS_DB_CONNECTED', check_w4os_db_tables());

function w4os_array2table($array, $class="") {
	if(empty($array)) return;
	$result="";
	while (list($key, $value) = each($array)) {
		$result.="<tr><td class=gridvar>" . __($key) . "</td><td class=gridvalue>$value</td></tr>";
	}
	if(!empty($result)) {
		$result="<table class='$class'>$result</table>";
	}
	return $result;
}

// Simple and useless workaround (calling is_user_logged_in too early produces a fatal error)
// function is_user()
// {
// 	return true; // code below doesn't work, we'll see that another day
// 	// if ( is_user_logged_in() == true ) return true;
// 	// if ( get_current_user_id() > 0 ) return true;
// 	// return false;
// }
// add_action('init', 'is_user');

function w4os_notice ($message, $status="") {
  echo "<div class='notice notice-$status'><p>$message</p></div>";
}

function gen_uuid() {
 $uuid = array(
  'time_low'  => 0,
  'time_mid'  => 0,
  'time_hi'  => 0,
  'clock_seq_hi' => 0,
  'clock_seq_low' => 0,
  'node'   => array()
 );

 $uuid['time_low'] = mt_rand(0, 0xffff) + (mt_rand(0, 0xffff) << 16);
 $uuid['time_mid'] = mt_rand(0, 0xffff);
 $uuid['time_hi'] = (4 << 12) | (mt_rand(0, 0x1000));
 $uuid['clock_seq_hi'] = (1 << 7) | (mt_rand(0, 128));
 $uuid['clock_seq_low'] = mt_rand(0, 255);

 for ($i = 0; $i < 6; $i++) {
  $uuid['node'][$i] = mt_rand(0, 255);
 }

 $uuid = sprintf('%08x-%04x-%04x-%02x%02x-%02x%02x%02x%02x%02x%02x',
  $uuid['time_low'],
  $uuid['time_mid'],
  $uuid['time_hi'],
  $uuid['clock_seq_hi'],
  $uuid['clock_seq_low'],
  $uuid['node'][0],
  $uuid['node'][1],
  $uuid['node'][2],
  $uuid['node'][3],
  $uuid['node'][4],
  $uuid['node'][5]
 );

 return $uuid;
}
