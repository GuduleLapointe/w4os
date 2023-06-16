<?php if ( ! defined( 'W4OS_PLUGIN' ) ) {
	die;}

if ( get_option( 'w4os_db_user' ) && get_option( 'w4os_db_pass' ) && get_option( 'w4os_db_database' ) && get_option( 'w4os_db_host' ) ) {
	$w4osdb = new WPDB(
		get_option( 'w4os_db_user' ),
		get_option( 'w4os_db_pass' ),
		get_option( 'w4os_db_database' ),
		get_option( 'w4os_db_host' )
	);
} else {
	w4os_admin_notice(
		w4os_give_settings_url( __( 'ROBUST database is not configured. To finish configuration, go to ', 'w4os' ) )
	);
}

function w4os_check_db() {
	if ( defined( 'W4OS_DB_CONNECTED' ) ) {
		return W4OS_DB_CONNECTED;
	}
	global $w4osdb;
	if ( empty( $w4osdb ) ) {
		return false; // Might happen when using wp-cli
	}

	if ( ! empty( $w4osdb ) & ! $w4osdb->check_connection( false ) ) {
		w4os_admin_notice(
			w4os_give_settings_url( __( 'Could not connect to the database server, please verify your credentials on ', 'w4os' ) ),
			'error',
		);
		return false;
	}
	if ( ! $w4osdb->get_var( "SHOW DATABASES LIKE '" . get_option( 'w4os_db_database' ) . "'" ) ) {
		w4os_admin_notice(
			w4os_give_settings_url( __( 'Could not connect to the ROBUST database, please verify database name and/or credentials on ', 'w4os' ) ),
			'error',
		);
		return false;
	}

	if ( ! $w4osdb ) {
		define( 'W4OS_DB_CONNECTED', false );
		return false;
	}

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

	return w4os_check_db_tables( $required_tables, true );
}

function w4os_check_db_tables( $tables, $error = false ) {
	global $w4osdb;
	if ( ! $w4osdb ) {
		return false;
	}
	if ( empty( $tables ) ) {
		return true;
	}
	if ( ! is_array( $tables ) ) {
		$tables = array( $tables );
	}

	$cache_key = sanitize_title( __FUNCTION__ . '-' . join( '-', $tables ) );
	if ( wp_cache_get( $cache_key ) ) {
		return wp_cache_get( $cache_key );
	}

	$missing = array();
	foreach ( $tables as $table ) {
		unset( $actual_name );
		$lower_name = strtolower( $table );
		if ( $w4osdb->get_var( "SHOW TABLES LIKE '$table'" ) == $table ) {
			$actual_name = $table;
		} elseif ( $w4osdb->get_var( "SHOW TABLES LIKE '$lower_name'" ) == $lower_name ) {
			$actual_name = $lower_name;
		}
		if ( empty( $actual_name ) ) {
			$missing[] = $table;
		}
	}

	wp_cache_set( $cache_key, ( 0 === count( $missing ) ) );

	if ( count( $missing ) > 0 ) {
		if ( $error ) {
			w4os_admin_notice(
				w4os_give_settings_url(
					sprintf(
						__( 'Missing tables: %s. The ROBUST database is connected, but some required tables are missing. ', 'w4os' ),
						' <strong><em>' . join( ', ', $missing ) . '</em></strong>',
					),
				),
				'error',
			);
		}
		return false;
	}

	return true;
}

define( 'W4OS_DB_CONNECTED', w4os_check_db() );
