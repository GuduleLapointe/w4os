<?php if ( ! defined( 'W4OS_PLUGIN' ) ) {
	die;}

if ( get_option( 'w4os_db_user' ) && get_option( 'w4os_db_pass' ) && get_option( 'w4os_db_database' ) && get_option( 'w4os_db_host' ) ) {
	$w4osdb = new WPDB(
		get_option( 'w4os_db_user' ),
		get_option( 'w4os_db_pass' ),
		get_option( 'w4os_db_database' ),
		get_option( 'w4os_db_host' )
	);
	// if(isset($w4osdb->error)) {
	// w4os_admin_notice( $w4osdb->error->get_error_message(), 'error' );
	// }
} else {
	w4os_admin_notice(
		sprintf(
			__( 'ROBUST database is not configured. To finish configuration, go to %s.', 'w4os' ),
			w4os_settings_link(),
		),
	);
}

function w4os_check_db( $cred = array() ) {
	if ( empty( $cred ) ) {
		if ( defined( 'W4OS_DB_CONNECTED' ) ) {
			return W4OS_DB_CONNECTED;
		}
		global $w4osdb;
		if ( empty( $w4osdb ) ) {
			return false; // Might happen when using wp-cli
		}
		$checkdb = $w4osdb;
	} else {
		$cred    = array_merge(
			array(
				'user'     => null,
				'pass'     => null,
				'database' => null,
				'host'     => null,
				'port'     => null,
			),
			$cred
		);
		$checkdb = new WPDB(
			$cred['user'],
			$cred['pass'],
			$cred['database'],
			$cred['host'] . ( empty( $cred['port'] ) ? '' : ':' . $cred['port'] ),
		);

	}

	if ( ! empty( $checkdb ) & ! $checkdb->check_connection( false ) ) {
		w4os_admin_notice(
			sprintf(
				__( 'Could not connect to the database server, please verify your credentials on %s.', 'w4os' ),
				w4os_settings_link(),
			),
			'error',
		);
		return false;
	}
	if ( ! $checkdb->get_var( "SHOW DATABASES LIKE '" . get_option( 'w4os_db_database' ) . "'" ) ) {
		w4os_admin_notice(
			sprintf(
				__( 'Could not connect to the ROBUST database, please verify database name and/or credentials on %s.', 'w4os' ),
				w4os_settings_link(),
			),
			'error',
		);
		return false;
	}

	if ( ! $checkdb ) {
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
				sprintf(
					__( 'Missing tables: %1$s. The ROBUST database is connected, but some required tables are missing. Check database settings on %2$s.', 'w4os' ),
					' <strong><em>' . join( ', ', $missing ) . '</em></strong>',
					w4os_settings_link(),
				),
				'error',
			);
		}
		return false;
	}

	return true;
}

define( 'W4OS_DB_CONNECTED', w4os_check_db() );
