<?php
/**
 * Bootstrap file for W4OS testing framework
 * Loads WordPress and sets up the testing environment
 */

// Start session FIRST to prevent warnings
if ( session_status() === PHP_SESSION_NONE ) {
	session_start();
}

// Load .env file if it exists for per-site configuration
$env_file = __DIR__ . '/.env';
if (file_exists($env_file)) {
	$env_lines = file($env_file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
	foreach ($env_lines as $line) {
		if (strpos(trim($line), '#') === 0) {
			continue; // Skip comments
		}
		if (strpos($line, '=') !== false) {
			list($key, $value) = explode('=', $line, 2);
			$key = trim($key);
			$value = trim($value, '"\''); // Remove quotes
			putenv("$key=$value");
			$_ENV[$key] = $value;
		}
	}
	echo "Loaded environment configuration from .env\n";
}

// Load WordPress
$wp_load_path = dirname( __FILE__, 5 ) . '/wp-load.php';

if ( ! file_exists( $wp_load_path ) ) {
	die( "Error: Could not find WordPress at {$wp_load_path}\n" );
}

echo "Loading WordPress from: {$wp_load_path}\n";
require_once $wp_load_path;

// Detect branch version: V3 is default, V2 is the exception
global $is_v3_branch, $is_v2_branch, $is_v2_transitional;
$is_v3_branch = defined( 'W4OS_VERSION' ) && version_compare( W4OS_VERSION, '3.0', '>=' );
$is_v2_branch = ! $is_v3_branch;
$is_v2_transitional = $is_v2_branch && defined( 'W4OS_ENABLE_V3' ) && W4OS_ENABLE_V3;

if ( $is_v3_branch ) {
	echo "Detected: V3 branch (version " . W4OS_VERSION . ")\n";
} elseif ( $is_v2_transitional ) {
	echo "Detected: V2 branch with transitional features enabled\n";
} else {
	echo "Detected: V2 branch (legacy mode)\n";
}

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
			return true;
		} else {
			$this->tests_failed++;
			$this->failed_tests[] = $message;
			echo "✗ FAIL: {$message}\n";
			return false;
		}
	}

	public function assert_false( $condition, $message = '' ) {
		return $this->assert_true( ! $condition, $message );
	}

	public function assert_equals( $expected, $actual, $message = '' ) {
		$this->tests_run++;
		if ( $expected === $actual ) {
			$this->tests_passed++;
			echo "✓ PASS: {$message}\n";
			return true;
		} else {
			$this->tests_failed++;
			$error_details = "(expected: " . var_export($expected, true) . ", got: " . var_export($actual, true) . ")";
			$this->failed_tests[] = $message . " " . $error_details;
			echo "✗ FAIL: {$message} {$error_details}\n";
			return false;
		}
	}

	public function assert_not_empty( $value, $message = '' ) {
		$this->tests_run++;
		if ( ! empty( $value ) ) {
			$this->tests_passed++;
			echo "✓ PASS: {$message}\n";
			return true;
		} else {
			$this->tests_failed++;
			$this->failed_tests[] = $message . " (value was empty)";
			echo "✗ FAIL: {$message} (value was empty)\n";
			return false;
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

/**
 * DOM Analysis Helper Functions
 * These functions provide reliable HTML content analysis using DOMDocument
 * instead of unreliable string matching.
 */

/**
 * Parse HTML content into a DOMDocument with error suppression
 * @param string $html_content The HTML content to parse
 * @return DOMDocument|false The parsed document or false on error
 */
function testing_parse_html($html_content) {
	if (empty($html_content)) {
		return false;
	}
	
	$doc = new DOMDocument();
	// Suppress warnings for malformed HTML
	$old_setting = libxml_use_internal_errors(true);
	$success = $doc->loadHTML($html_content, LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);
	libxml_use_internal_errors($old_setting);
	
	return $success ? $doc : false;
}

/**
 * Extract title content from HTML
 * @param string $html_content The HTML content
 * @return string|false The title content or false if not found
 */
function testing_get_html_title($html_content) {
	$doc = testing_parse_html($html_content);
	if (!$doc) {
		return false;
	}
	
	$xpath = new DOMXPath($doc);
	$title_nodes = $xpath->query('//title');
	
	return $title_nodes->length > 0 ? trim($title_nodes->item(0)->textContent) : false;
}

/**
 * Extract main content from HTML, excluding header/footer/nav elements
 * @param string $html_content The HTML content
 * @return string The main content text
 */
function testing_get_main_content($html_content) {
	$doc = testing_parse_html($html_content);
	if (!$doc) {
		return '';
	}
	
	$xpath = new DOMXPath($doc);
	
	// Get body content but exclude header, footer, nav elements
	$body_nodes = $xpath->query('//body//text()[not(ancestor::header) and not(ancestor::footer) and not(ancestor::nav) and not(ancestor::title)]');
	
	$main_content = '';
	foreach ($body_nodes as $node) {
		$main_content .= $node->textContent . ' ';
	}
	
	return trim($main_content);
}

/**
 * Check if text contains a specific string (case-insensitive)
 * @param string $haystack The text to search in
 * @param string $needle The text to search for
 * @return bool True if found, false otherwise
 */
function testing_content_contains($haystack, $needle) {
	return stripos($haystack, $needle) !== false;
}

/**
 * Analyze HTML content for specific elements and text
 * @param string $html_content The HTML content to analyze
 * @param string $search_text The text to search for
 * @return array Analysis results with title_contains, content_contains, title_text, content_length
 */
function testing_analyze_html_content($html_content, $search_text) {
	$title = testing_get_html_title($html_content);
	$content = testing_get_main_content($html_content);
	
	return array(
		'title_contains' => $title ? testing_content_contains($title, $search_text) : false,
		'content_contains' => testing_content_contains($content, $search_text),
		'title_text' => $title ?: '',
		'content_length' => strlen($content),
		'has_content' => !empty($content)
	);
}
