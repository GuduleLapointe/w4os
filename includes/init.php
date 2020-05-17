<?php

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
