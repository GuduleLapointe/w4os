<?php
/**
 * Simple test runner for W4OS plugin - no PHPUnit required
 * 
 * Usage: php tests/run-tests.php
 */

// Load WordPress
$wp_load_path = dirname( __FILE__, 5 ) . '/wp-load.php';

if ( ! file_exists( $wp_load_path ) ) {
	die( "Error: Could not find WordPress at {$wp_load_path}\n" );
}

echo "Loading WordPress from: {$wp_load_path}\n";
require_once $wp_load_path;

// Simple test framework
class SimpleTest {
	private $tests_run = 0;
	private $tests_passed = 0;
	private $tests_failed = 0;
	private $failed_tests = array();

	public function assert_true( $condition, $message = '' ) {
		$this->tests_run++;
		if ( $condition ) {
			$this->tests_passed++;
			echo "✓ PASS: {$message}\n";
		} else {
			$this->tests_failed++;
			$this->failed_tests[] = $message;
			echo "✗ FAIL: {$message}\n";
		}
	}

	public function assert_equals( $expected, $actual, $message = '' ) {
		$this->tests_run++;
		if ( $expected === $actual ) {
			$this->tests_passed++;
			echo "✓ PASS: {$message}\n";
		} else {
			$this->tests_failed++;
			$error_details = "(expected: " . var_export($expected, true) . ", got: " . var_export($actual, true) . ")";
			$this->failed_tests[] = $message . " " . $error_details;
			echo "✗ FAIL: {$message} {$error_details}\n";
		}
	}

	public function assert_not_empty( $value, $message = '' ) {
		$this->tests_run++;
		if ( ! empty( $value ) ) {
			$this->tests_passed++;
			echo "✓ PASS: {$message}\n";
		} else {
			$this->tests_failed++;
			$this->failed_tests[] = $message . " (value was empty)";
			echo "✗ FAIL: {$message} (value was empty)\n";
		}
	}

	public function summary() {
		echo "\n" . str_repeat( '=', 50 ) . "\n";
		echo "Test Summary:\n";
		echo "  Total tests: {$this->tests_run}\n";
		echo "  Passed: {$this->tests_passed}\n";
		echo "  Failed: {$this->tests_failed}\n";
		
		if ( $this->tests_failed > 0 ) {
			echo "\nFailed Tests:\n";
			foreach ( $this->failed_tests as $i => $failed_test ) {
				echo "  " . ($i + 1) . ". {$failed_test}\n";
			}
			echo "\n  Status: FAILED\n";
			exit( 1 );
		} else {
			echo "  Status: ALL PASSED\n";
		}
	}
}

$test = new SimpleTest();

echo "\n" . str_repeat( '=', 50 ) . "\n";
echo "W4OS Plugin Test Suite\n";
echo str_repeat( '=', 50 ) . "\n\n";

// Test 1: WordPress basics
echo "Testing WordPress environment...\n";
$test->assert_true( function_exists( 'get_option' ), 'WordPress functions are available' );
$test->assert_true( defined( 'ABSPATH' ), 'WordPress ABSPATH is defined' );

// Test 2: Database connection
echo "\nTesting database connection...\n";
global $wpdb;
$test->assert_true( is_object( $wpdb ), 'WordPress database object exists' );

$db_test = $wpdb->get_var( "SELECT 1" );
$test->assert_equals( '1', $db_test, 'Database query works' );

// Test 3: WordPress site info
echo "\nTesting WordPress site configuration...\n";
$site_url = get_option( 'siteurl' );
$home_url = get_option( 'home' );
$wp_version = get_bloginfo( 'version' );

$test->assert_not_empty( $site_url, 'Site URL is set' );
$test->assert_not_empty( $home_url, 'Home URL is set' );
$test->assert_not_empty( $wp_version, 'WordPress version is available' );

echo "  Site URL: {$site_url}\n";
echo "  Home URL: {$home_url}\n";
echo "  WP Version: {$wp_version}\n";

// Test 4: W4OS plugin
echo "\nTesting W4OS plugin...\n";

// Check if W4OS plugin file exists
$plugin_file = dirname( dirname( __FILE__ ) ) . '/w4os.php';
$test->assert_true( file_exists( $plugin_file ), 'W4OS plugin file exists' );

// Check if W4OS is loaded (look for any W4OS-specific functions or constants)
$w4os_loaded = function_exists( 'w4os_init' ) || 
               defined( 'W4OS_PLUGIN' ) || 
               defined( 'W4OS_VERSION' ) ||
               class_exists( 'W4OS' );

$test->assert_true( $w4os_loaded, 'W4OS plugin appears to be loaded' );

// Test 5: Active plugins
echo "\nTesting plugin environment...\n";
$active_plugins = get_option( 'active_plugins', array() );
$test->assert_true( is_array( $active_plugins ), 'Active plugins list is available' );

echo "  Active plugins (" . count( $active_plugins ) . "):\n";
foreach ( $active_plugins as $plugin ) {
	echo "    - {$plugin}\n";
}

// Test 6: Check for W4OS in active plugins
$w4os_active = false;
foreach ( $active_plugins as $plugin ) {
	if ( strpos( $plugin, 'w4os' ) !== false ) {
		$w4os_active = true;
		break;
	}
}
$test->assert_true( $w4os_active, 'W4OS plugin is in active plugins list' );

// Test 7: Database tables
echo "\nTesting database tables...\n";
$tables = $wpdb->get_results( "SHOW TABLES" );
$table_count = count( $tables );
$test->assert_true( $table_count > 0, 'Database tables exist' );
echo "  Found {$table_count} database tables\n";

// Look for W4OS-specific tables (if any)
$w4os_tables = array();
foreach ( $tables as $table_obj ) {
	$table_name = array_values( (array) $table_obj )[0];
	if ( strpos( $table_name, 'w4os' ) !== false ) {
		$w4os_tables[] = $table_name;
	}
}

if ( ! empty( $w4os_tables ) ) {
	echo "  W4OS tables found:\n";
	foreach ( $w4os_tables as $table ) {
		echo "    - {$table}\n";
	}
} else {
	echo "  No W4OS-specific tables found (this may be normal)\n";
}

$test->summary();
