<?php
/**
 * Bootstrap file for W4OS testing framework
 * Loads WordPress and sets up the testing environment
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

	public function get_stats() {
		return array(
			'run' => $this->tests_run,
			'passed' => $this->tests_passed,
			'failed' => $this->tests_failed,
			'failed_tests' => $this->failed_tests
		);
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
			return false;
		} else {
			echo "  Status: ALL PASSED\n";
			return true;
		}
	}
}

// Global test instance
$test = new SimpleTest();
