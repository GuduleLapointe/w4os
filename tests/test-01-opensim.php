<?php
/**
 * OpenSimulator Environment Tests
 * Tests OpenSim database and console connectivity using proper W4OS methods
 * 
 * Usage: php test-opensim.php
 */

// Load bootstrap
require_once __DIR__ . '/bootstrap.php';

echo "Testing OpenSimulator environment...\n";

// Test 1: Get grid login URI using proper W4OS function
echo "\nTesting grid configuration...\n";
$login_uri = w4os_grid_login_uri();
$test->assert_not_empty( $login_uri, 'Grid login URI ' . $login_uri );

// Test 2: Get main service credentials using appropriate method based on V3 status
echo "\nTesting service credentials...\n";

// Check if V3 is enabled and properly initialized
$v3_enabled = defined( 'W4OS_ENABLE_V3' ) && W4OS_ENABLE_V3;
$credentials = array();

if ( $v3_enabled ) {
	// Try V3 credentials method
	$login_uri = get_option( 'w4os_login_uri' );
	
	// Force credential sync from console for V3 testing
	// This ensures we get the current ConnectionString from Robust.ini
	echo "  Forcing V3 credential sync from console...\n";
	$current_creds = W4OS3::get_credentials( $login_uri );
	if ( ! empty( $current_creds ) ) {
		W4OS3::update_credentials( $login_uri, $current_creds );
		// Get refreshed credentials after console sync
		$credentials = W4OS3::get_credentials( $login_uri );
	} else {
		$credentials = $current_creds;
	}
}

// If V3 failed or is disabled, fall back to legacy credentials
if ( empty( $credentials ) ) {
	$credentials = array(
		'db' => array(
			'host' => get_option( 'w4os_db_host' ),
			'port' => get_option( 'w4os_db_port', '3306' ),
			'name' => get_option( 'w4os_db_database' ),
			'user' => get_option( 'w4os_db_user' ),
			'pass' => get_option( 'w4os_db_pass' ),
		),
		'console' => array(
			'host' => null,
			'port' => null,
			'user' => null,
			'pass' => null,
		)
	);
}

// Check if we have at least database credentials
$has_db_creds = !empty( $credentials['db']['host'] ) && 
                !empty( $credentials['db']['user'] ) && 
                !empty( $credentials['db']['pass'] ) && 
                !empty( $credentials['db']['name'] );

// Display credential details (excluding password)
if ( $has_db_creds ) {
	$method = $v3_enabled ? 'V3' : 'legacy';
	echo "  Credentials retrieved using {$method} method:\n";
	printf( "  Database:" . PHP_EOL
		. "\thost\t: %s" . PHP_EOL 
		. "\tport\t: %s" . PHP_EOL
		. "\tname\t: %s" . PHP_EOL
		. "\tuser\t: %s" . PHP_EOL
		. "\tpass\t: %s" . PHP_EOL,
		$credentials['db']['host'],
		$credentials['db']['port'] ?? '3306',
		$credentials['db']['name'],
		$credentials['db']['user'],
		str_repeat( '*', strlen( $credentials['db']['pass'] ) )
	);
	if ( !empty( $credentials['console']['host'] ) ) {
		printf( "  Console:" . PHP_EOL
			. "\thost\t: %s" . PHP_EOL 
			. "\tport\t: %s" . PHP_EOL
			. "\tuser\t: %s" . PHP_EOL
			. "\tpass\t: %s" . PHP_EOL,
			$credentials['console']['host'],
			$credentials['console']['port'] ?? 'nil',
			$credentials['console']['user'],
			str_repeat( '*', strlen( $credentials['console']['pass'] ) )
		);
	} else {
		echo "    Console: not configured\n";
	}
}

$test->assert_true( $has_db_creds, 'Service credentials retrieved' );

if ( ! $has_db_creds ) {
	echo "  No credentials found - skipping connection tests\n";
	$test->summary();
	exit( $test->summary() ? 0 : 1 );
}

// Test 3: Database connectivity (required)
echo "\nTesting database connectivity...\n";
if ( ! empty( $credentials['db']['host'] ) && ! empty( $credentials['db']['user'] ) && 
     ! empty( $credentials['db']['pass'] ) && ! empty( $credentials['db']['name'] ) ) {
	
	$port = $credentials['db']['port'] ?? 3306;
	$db_info = "{$credentials['db']['host']}:{$port}/{$credentials['db']['name']} as {$credentials['db']['user']}";
	
	// Debug: Show exactly what parameters we're using
	echo "    host:\t{$credentials['db']['host']}\n";
	echo "    user:\t{$credentials['db']['user']}\n";
	echo "    database:\t{$credentials['db']['name']}\n";
	echo "    port:\t{$port}\n";
	
	// Use the same connection method as the plugin (WPDB with host:port format)
	$host_with_port = $credentials['db']['host'] . ( empty( $credentials['db']['port'] ) ? '' : ':' . $credentials['db']['port'] );
	echo "  Using WPDB connection to: {$host_with_port}\n";
	
	// Important note: When host is 'localhost', MySQL client ignores port and uses socket connection
	// For proper port testing, use 127.0.0.1 instead of localhost
	if ( $credentials['db']['host'] === 'localhost' && $credentials['db']['port'] !== '3306' ) {
		echo "  ⚠️  WARNING: 'localhost' connections use socket, port {$credentials['db']['port']} will be ignored!\n";
		echo "      If you need to use port {$credentials['db']['port']}, change host to '127.0.0.1'\n";
	}
	
	// Create WPDB instance exactly like the plugin does
	$test_db = new WPDB(
		$credentials['db']['user'],
		$credentials['db']['pass'],
		$credentials['db']['name'],
		$host_with_port
	);
	
	// Also check the actual plugin's database connection for comparison
	global $w4osdb;
	
	// First check if the connection was established
	if ( $test_db && $test_db->check_connection( false ) ) {
		// Additional test: try to actually query the database to verify it's really connected
		$test_query = $test_db->get_var( "SELECT 1" );
		if ( $test_query === '1' ) {
			$test->assert_true( true, 'Database connection to ' . $db_info . ' successful (using WPDB)' );
			
			// Additional warning if localhost is used with non-standard port
			if ( $credentials['db']['host'] === 'localhost' && $credentials['db']['port'] !== '3306' ) {
				$test->assert_true( false, 'Configuration error: with localhost, custom port is ignored by mysql, use 127.0.0.1 or default port' );
			}
		} else {
			$test->assert_true( false, 'Database connection to ' . $db_info . ' established but query failed' );
		}
	} else {
		$error_msg = $test_db->last_error ?? 'Connection check failed';
		$test->assert_true( false, 'Database connection to ' . $db_info . ' failed: ' . $error_msg );
	}
} else {
	$test->assert_true( false, 'Database credentials incomplete' );
}

// Test 4: Console connectivity (optional - only test if credentials are provided)
echo "\nTesting console connectivity...\n";
if ( ! empty( $credentials['console']['host'] ) && ! empty( $credentials['console']['port'] ) && 
     ! empty( $credentials['console']['user'] ) && ! empty( $credentials['console']['pass'] ) ) {
	
	$console_info = "{$credentials['console']['host']}:{$credentials['console']['port']} as {$credentials['console']['user']}";
	
	$rest_args = array(
		'uri'         => $credentials['console']['host'] . ':' . $credentials['console']['port'],
		'ConsoleUser' => $credentials['console']['user'],
		'ConsolePass' => $credentials['console']['pass'],
	);
	
	$rest = new OpenSim_Rest( $rest_args );
	if ( isset( $rest->error ) && is_opensim_rest_error( $rest->error ) ) {
		$test->assert_true( false, 'Console connection to ' . $console_info . ' failed: ' . $rest->error->getMessage() );
	} else {
		$responseLines = $rest->sendCommand( 'show info' );
		if ( is_opensim_rest_error( $responseLines ) ) {
			$test->assert_true( false, 'Console command to ' . $console_info . ' failed: ' . $responseLines->getMessage() );
		} else {
			$console_response = substr( join( ' ', $responseLines ), 0, 50 ) . '...';
			$test->assert_true( true, 'Console connection to ' . $console_info . ' successful (' . $console_response . ')' );
		}
	}
} else {
	echo "  Console credentials not provided - skipping console test\n";
}

// Show summary
$test->summary();
