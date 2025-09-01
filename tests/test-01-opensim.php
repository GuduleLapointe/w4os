<?php
/**
 * OpenSimulator Environment Tests
 * Tests OpenSim database and console connectivity using proper W4OS methods
 * 
 * Usage: php test-opensim.php
 */

// Load bootstrap
require_once __DIR__ . '/bootstrap.php';

echo "Testing OpenSimulator environment..." . PHP_EOL;

// Test 1: Get grid login URI using proper W4OS function
echo "\nTesting grid configuration..." . PHP_EOL;

// Test 2: Get main service credentials using appropriate method based on V3 status
echo "\nTesting service credentials..." . PHP_EOL;

// Use global branch detection from bootstrap - V3 is default, V2 is exception
global $is_v3_branch, $is_v2_branch, $is_v2_transitional;
$credentials = array();
$config_drift_detected = false;
$console_failed = false;

// First we fetch the settings
if( $is_v3_branch || $is_v2_transitional ) {
	$login_uri = w4os_grid_login_uri();
	if( !$test->assert_not_empty( $login_uri, 'Grid login URI ' . $login_uri ) ) {
		exit( $test->summary() ? 0 : 1 );
	}

	$credentials = W4OS3::get_credentials( $login_uri );

	$console_enabled = W4OS3::validate_console_creds( $credentials['console'] ?? array() );
} else {
	echo "  Using V2 legacy settings..." . PHP_EOL;

	$login_uri = w4os_get_option( 'w4os_login_uri' );
	if( ! $test->assert_not_empty( $login_uri, 'Grid login URI ' . $login_uri )) {
		exit( $test->summary() ? 0 : 1 );
	}
	$credentials = array(
		'db' => array(
			'host' => w4os_get_option( 'w4os_db_host' ),
			'port' => w4os_get_option( 'w4os_db_port' ),
			'name' => w4os_get_option( 'w4os_db_database' ),
			'user' => w4os_get_option( 'w4os_db_user' ),
			'pass' => w4os_get_option( 'w4os_db_pass' ),
		),
	);
	$console_enabled = false;
}
// $credentials = array();
if( ! $test->assert_not_empty( $credentials, 'Credentials retrieved' )) {
	// $test->assert_true( false, 'No credentials found - cannot proceed with connection tests' );
	exit( $test->summary() ? 0 : 1 );
}

$test->assert_true( true, 'Found credentials' );

// 2. If console is enabled, test connection and test configuration drift
if ( $console_enabled ) {
	echo "\nConsole is enabled - testing console connectivity and settings integrity..." . PHP_EOL;
	if ( empty( $credentials['console']['host'] ) || empty( $credentials['console']['user'] ) ) {
		echo "  ⚠️  Insufficient console credentials - abording tests" . PHP_EOL;
		$test->assert_true( false, 'Insufficient console credentials - cannot proceed with connection tests' );
		exit( $test->summary() ? 0 : 1 );
	}
	$console_info = "{$credentials['console']['host']}:{$credentials['console']['port']} as {$credentials['console']['user']}";

	$rest_args = array(
		'uri'         => $credentials['console']['host'] . ':' . $credentials['console']['port'],
		'ConsoleUser' => $credentials['console']['user'],
		'ConsolePass' => $credentials['console']['pass'],
	);
	
	$rest = new OpenSim_Rest( $rest_args );
	if ( isset( $rest->error ) && is_opensim_rest_error( $rest->error ) ) {
		echo "  ❌ Console connection failed: {$rest->error->getMessage()}" . PHP_EOL;
		$test->assert_true( false, 'Console connection to ' . $console_info . ' failed: ' . $rest->error->getMessage() );
		$console_failed = true;
	} else {
		$responseLines = $rest->sendCommand( 'show info' );
		if ( is_opensim_rest_error( $responseLines ) ) {
			echo "  ❌ Console command failed: {$responseLines->getMessage()}" . PHP_EOL;
			$test->assert_true( false, 'Console command to ' . $console_info . ' failed: ' . $responseLines->getMessage() );
			$console_failed = true;
		} else {
			$console_response = substr( join( ' ', $responseLines ), 0, 50 ) . '...';
			$test->assert_true( true, 'Console connection to ' . $console_info . ' successful (' . $console_response . ')' );
		}
	}

	$live_db_config = W4OS3::get_db_credentials_from_console( $credentials['console'] );
	$live_host = $live_db_config['host'] ?? '';
	$stored_host = $credentials['db']['host'] ?? '';

	if ( $live_host === 'localhost' && ! $test->assert_equals( $live_host, $stored_host, 'Credential integrity: get_credentials should preseve localhost value' ) ) {
		echo "    Live Robust.ini host: '{$live_host}'" . PHP_EOL;
		echo "    Stored W4OS host: '{$stored_host}'" . PHP_EOL;
		echo "    get_credentials() should return stored values without transformation!" . PHP_EOL;
		exit( $test->summary() ? 0 : 1 );
	}

	$drift_result = W4OS3_Settings::check_config_drift();
	
	$drift_detected = isError($drift_result);
	if ( ! $test->assert_false( $drift_detected, 'Checking consistency between live and stored options' ) ) {
		echo "    ⚠️  " . strip_tags($drift_result['message'] ?? 'Configuration drift detected') . PHP_EOL;
		unset($drift_result['live_config']['pass']);
		unset($drift_result['stored_config']['pass']);
		$expected = sprintf(
			'%s:%s/%s as %s',
			$drift_result['live_config']['host'] ?? '',
			$drift_result['live_config']['port'] ?? '',
			$drift_result['live_config']['name'] ?? '',
			$drift_result['live_config']['user'] ?? '',
		);
		$stored = sprintf(
			'%s:%s/%s as %s',
			$drift_result['stored_config']['host'] ?? '',
			$drift_result['stored_config']['port'] ?? '',
			$drift_result['stored_config']['name'] ?? '',
			$drift_result['stored_config']['user'] ?? '',
		);
		$config_drift_detected = true;
		echo "    Expected : $expected" . PHP_EOL;
		echo "    Stored   : $stored" . PHP_EOL;
		exit( $test->summary() ? 0 : 1 );
	}
}

echo PHP_EOL;
echo "Testing Robust database credentials..." . PHP_EOL;
$db_info = sprintf("%s:%s/%s as %s",
	$credentials['db']['host'] ?? null,
	$credentials['db']['port'] ?? null,
	$credentials['db']['name'] ?? null,
	$credentials['db']['user'] ?? null
);
printf( "  Database %s\n", $db_info );

// For debugging, show only non-sensitive parts of credentials
$custom_port_with_localhost = ($credentials['db']['host'] ?? '') === 'localhost' && ($credentials['db']['port'] ?? '3306' ) != '3306';
if( ! $test->assert_false( $custom_port_with_localhost, 'Check if localhost is used with a custom port' ) ) {
	// We don't abort, just warn
	echo "    ⚠️  WARNING: 'localhost' connections use socket, port {$credentials['db']['port']} will be ignored!" . PHP_EOL;
	echo "       If you need to use port {$credentials['db']['port']}, use 127.0.0.1 or host address." . PHP_EOL;
}
	
// Test 4: Database connectivity (only if console works and no drift detected)
echo "\nTesting database connectivity..." . PHP_EOL;

// Simple query to verify connection
$random_number = rand(1000, 9999);

if($is_v3_branch) {
	$test_db = new OSPDO($credentials['db']);
	$db_connected = $test_db && $test_db->is_connected();
	if($db_connected) {
		$test_query = $test_db->get_var( "SELECT $random_number;" );
	}
} else {
	$host_with_port = $credentials['db']['host'] . ( empty( $credentials['db']['port'] ) ? '' : ':' . $credentials['db']['port'] );
	$test_db = new WPDB(
		$credentials['db']['user'],
		$credentials['db']['pass'],
		$credentials['db']['name'],
		$host_with_port
	);
	$db_connected = $test_db && $test_db->check_connection( false );
	if($db_connected) {
		$test_query = (int)$test_db->get_var( "SELECT $random_number;" );
	}
}

if( ! $test->assert_true( $db_connected, 'Database connection to ' . $db_info )) {
	echo "    ❌ Connection failed: " . ($test_db->last_error ?? 'Unknown error') . PHP_EOL;
	exit( $test->summary() ? 0 : 1 );
}

$test->assert_equals( $random_number, $test_query, 'Simple query test' );

// Show summary
$test->summary();
