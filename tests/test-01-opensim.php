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

// Test 2: Get main service credentials using W4OS3::get_credentials()
echo "\nTesting service credentials...\n";
$credentials = W4OS3::get_credentials( $login_uri );
$test->assert_not_empty( $credentials, 'Service credentials retrieved' );

if ( empty( $credentials ) ) {
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
	
	@$db_conn = new mysqli( 
		$credentials['db']['host'], 
		$credentials['db']['user'], 
		$credentials['db']['pass'], 
		$credentials['db']['name'], 
		$port 
	);
	
	if ( $db_conn && ! $db_conn->connect_error ) {
		$test->assert_true( true, 'Database connection to ' . $db_info . ' successful' );
		$db_conn->close();
	} else {
		$error_msg = $db_conn->connect_error ?? 'Unknown DB connection error';
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
