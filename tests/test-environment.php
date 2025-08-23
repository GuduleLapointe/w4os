<?php
/**
 * WordPress Environment Tests
 * Tests basic WordPress functionality and configuration
 * 
 * Usage: php test-environment.php
 */

// Load bootstrap
require_once __DIR__ . '/bootstrap.php';

echo "Testing WordPress environment...\n";

// Test 1: WordPress basics
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

// Test 4: Plugin environment
echo "\nTesting plugin environment...\n";
$active_plugins = get_option( 'active_plugins', array() );
$test->assert_true( is_array( $active_plugins ), 'Active plugins list is available' );

echo "  Active plugins (" . count( $active_plugins ) . "):\n";
foreach ( $active_plugins as $plugin ) {
	echo "    - {$plugin}\n";
}

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
echo "\nTesting database tables...\n";
$tables = $wpdb->get_results( "SHOW TABLES" );
$table_count = count( $tables );
$test->assert_true( $table_count > 0, 'Database tables exist' );
echo "  Found {$table_count} database tables\n";

// Show summary
$test->summary();