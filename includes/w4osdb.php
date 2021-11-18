<?php if ( ! defined( 'W4OS_PLUGIN' ) ) die;

if(get_option('w4os_db_user') && get_option('w4os_db_pass') && get_option('w4os_db_database') && get_option('w4os_db_host')) {
  $w4osdb = new WPDB(
    get_option('w4os_db_user'),
    get_option('w4os_db_pass'),
    get_option('w4os_db_database'),
    get_option('w4os_db_host')
  );
} else {
  w4os_admin_notice(
    w4os_give_settings_url( __('ROBUST database is not configured. To finish configuration, go to ', 'w4os') )
  );
}

function w4os_check_db_tables() {
	if(defined('W4OS_DB_CONNECTED')) return W4OS_DB_CONNECTED;
	global $w4osdb;
  if(empty($w4osdb)) return false; // Might happen when using wp-cli

  if(! empty($w4osdb) &! $w4osdb->check_connection(false)) {
    w4os_admin_notice(
      w4os_give_settings_url( __('Could not connect to the database server, please verify your credentials on ', 'w4os') ),
      'error',
    );
    return false;
  }
  if(!$w4osdb->get_var("SHOW DATABASES LIKE '" . get_option('w4os_db_database') . "'")) {
    w4os_admin_notice(
      w4os_give_settings_url( __('Could not connect to the ROBUST database, please verify database name and/or credentials on ', 'w4os') ),
      'error',
    );
    return false;
  }

	if(!is_object($w4osdb)) return false;
	$required_tables = array(
		// 'AgentPrefs',
		// 'assets',
		// 'auth',
		'Avatars',
		// 'Friends',
		'GridUser',
		'inventoryfolders',
		'inventoryitems',
		// 'migrations',
		// 'MuteList',
		'Presence',
		'regions',
		// 'tokens',
		'UserAccounts',
	);
  $missing_tables=array();
	foreach($required_tables as $table_name) {
		unset($actual_name);
		$lower_name = strtolower($table_name);
		if($w4osdb->get_var("SHOW TABLES LIKE '$table_name'") == $table_name) $actual_name = $table_name;
		else if($w4osdb->get_var("SHOW TABLES LIKE '$lower_name'") == $lower_name) $actual_name = $lower_name;
		if(isset($actual_name)) {
			if (!defined($table_name)) define($table_name, $actual_name);
			continue;
		}
    $missing_tables[] = $table_name;
	}
  if(count($missing_tables) > 0) {
    w4os_admin_notice(
      w4os_give_settings_url(
        sprintf(
          __("Missing tables: %s. The ROBUST database is connected, but it does not seem valid. ", 'w4os'),
          ' <strong><em>' . join(', ', $missing_tables) . '</em></strong>',
        ),
      ),
      'error',
    );
    return false;
  }
	return true;
}
if (!defined('W4OS_DB_CONNECTED')) define('W4OS_DB_CONNECTED', w4os_check_db_tables());
