<?php
/**
 * WordPress Environment Tests
 * Tests basic WordPress functionality and configuration
 * 
 * Usage: php test-environment.php
 */

// Load bootstrap
require_once __DIR__ . '/bootstrap.php';

echo "Testing WordPress environment..." . PHP_EOL;

// Test 1: WordPress basics
$test->assert_true( function_exists( 'get_option' ), 'WordPress functions are available' );
$test->assert_true( defined( 'ABSPATH' ), 'WordPress ABSPATH is defined' );

// Test 2: Database connection
echo "\nTesting database connection..." . PHP_EOL;
global $wpdb;
$test->assert_true( is_object( $wpdb ), 'WordPress database object exists' );

$db_test = $wpdb->get_var( "SELECT 1" );
$test->assert_equals( '1', $db_test, 'Database query works' );

// Test 3: WordPress site info
echo "\nTesting WordPress site configuration..." . PHP_EOL;
$site_url = get_option( 'siteurl' );
$home_url = get_option( 'home' );
$wp_version = get_bloginfo( 'version' );

$test->assert_not_empty( $site_url, 'Site URL ' . $site_url );
$test->assert_not_empty( $home_url, 'Home URL ' . $home_url );
$test->assert_not_empty( $wp_version, 'WordPress version ' . $wp_version );

// Test 4: Plugin environment
echo "\nTesting plugin environment..." . PHP_EOL;
$active_plugins = get_option( 'active_plugins', array() );
$test->assert_true( is_array( $active_plugins ), 'Active plugins list available (' . count( $active_plugins ) . ' plugins)' );

// Test 5: Check for W4OS in active plugins
$w4os_active = false;
foreach ( $active_plugins as $plugin ) {
	if ( strpos( $plugin, 'w4os' ) !== false ) {
		$w4os_active = true;
		break;
	}
}
$test->assert_true( $w4os_active, 'W4OS plugin is in active plugins list' );

// Test 6: Database tables
echo "\nTesting WordPress database tables..." . PHP_EOL;
$tables = $wpdb->get_results( "SHOW TABLES" );
$table_count = count( $tables );
$test->assert_true( $table_count > 0, $table_count . ' tables found)' );

// Show summary
$test->summary();
