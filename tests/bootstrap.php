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
	echo "Loaded environment configuration from .env" . PHP_EOL;
}

// Load WordPress
$wp_load_path = dirname( __FILE__, 5 ) . '/wp-load.php';

if ( ! file_exists( $wp_load_path ) ) {
	die( "Error: Could not find WordPress at {$wp_load_path}\n" );
}

echo "Loading WordPress from: {$wp_load_path}" . PHP_EOL;
require_once $wp_load_path;

// Detect branch version: V3 is default, V2 is the exception
global $is_v3_branch, $is_v2_branch, $is_v2_transitional;
$is_v3_branch = defined( 'W4OS_VERSION' ) && version_compare( W4OS_VERSION, '3.0', '>=' );
$is_v2_branch = ! $is_v3_branch;
$is_v2_transitional = $is_v2_branch && defined( 'W4OS_ENABLE_V3' ) && W4OS_ENABLE_V3;

if ( $is_v3_branch ) {
	echo "Detected: V3 branch (version " . W4OS_VERSION . ")" . PHP_EOL;
} elseif ( $is_v2_transitional ) {
	echo "Detected: V2 branch with transitional features enabled" . PHP_EOL;
} else {
	echo "Detected: V2 branch (legacy mode)" . PHP_EOL;
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
			echo "✓ PASS: {$message}" . PHP_EOL;
			return true;
		} else {
			$this->tests_failed++;
			$this->failed_tests[] = $message;
			echo "✗ FAIL: {$message}" . PHP_EOL;
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
			echo "✓ PASS: {$message} (" . var_export($expected, true) . ")" . PHP_EOL;
			return true;
		} else {
			$this->tests_failed++;
			$error_details = "(expected: " . var_export($expected, true) . ", got: " . var_export($actual, true) . ")";
			$this->failed_tests[] = $message . " " . $error_details;
			echo "✗ FAIL: {$message} {$error_details}" . PHP_EOL;
			return false;
		}
	}

	public function assert_not_empty( $value, $message = '' ) {
		$this->tests_run++;
		if ( ! empty( $value ) ) {
			$this->tests_passed++;
			echo "✓ PASS: {$message}" . PHP_EOL;
			return true;
		} else {
			$this->tests_failed++;
			$this->failed_tests[] = $message . " (value was empty)";
			echo "✗ FAIL: {$message} (value was empty)" . PHP_EOL;
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
		echo "\n" . str_repeat( '=', 50 ) . PHP_EOL;
		echo "Test Summary:" . PHP_EOL;
		echo "  Total tests: {$this->tests_run}" . PHP_EOL;
		echo "  Passed: {$this->tests_passed}" . PHP_EOL;
		echo "  Failed: {$this->tests_failed}" . PHP_EOL;
		
		if ( $this->tests_failed > 0 ) {
			echo "\nFailed Tests:" . PHP_EOL;
			foreach ( $this->failed_tests as $i => $failed_test ) {
				echo "  " . ($i + 1) . ". {$failed_test}" . PHP_EOL;
			}
			echo "\n  Status: FAILED" . PHP_EOL;
			return false;
		} else {
			echo "  Status: ALL PASSED" . PHP_EOL;
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
 * Extract main content from HTML, excluding navigation, header, footer, sidebar elements
 * Uses multiple strategies to find the actual main content container
 * @param string $html_content The HTML content to extract from
 * @return string The main content text
 */
function testing_get_main_content($html_content) {
	$parsed_html = testing_parse_html($html_content);
	if (!$parsed_html) {
		return '';
	}

	$xpath = new DOMXPath($parsed_html);
	
	// Try multiple main content selectors in order of preference
	$main_selectors = array(
		'//main',                                    // Standard HTML5 main element
		'//div[@id="main-content"]',                // Divi theme
		'//div[@id="content"]',                     // Common theme pattern
		'//div[@class="content"]',                  // Common theme pattern
		'//div[contains(@class, "main-content")]',  // Flexible main-content class
		'//div[contains(@class, "site-content")]',  // WordPress theme pattern
		'//div[contains(@class, "entry-content")]', // Post/page content
		'//article',                                // Article elements
		'//div[@role="main"]'                       // ARIA main role
	);
	
	foreach ($main_selectors as $selector) {
		$main_nodes = $xpath->query($selector);
		if ($main_nodes->length > 0) {
			// Found main content container, extract text while excluding secondary elements
			$main_container = $main_nodes->item(0);
			
			// Remove navigation, header, footer, sidebar elements from the main container
			$exclude_selectors = array(
				'.//nav', './/header', './/footer', 
				'.//*[contains(@class, "sidebar")]', 
				'.//*[contains(@class, "navigation")]',
				'.//*[contains(@class, "nav")]',
				'.//*[contains(@class, "widget")]',
				'.//*[contains(@class, "menu")]'
			);
			
			foreach ($exclude_selectors as $exclude_selector) {
				$exclude_nodes = $xpath->query($exclude_selector, $main_container);
				foreach ($exclude_nodes as $exclude_node) {
					if ($exclude_node->parentNode) {
						$exclude_node->parentNode->removeChild($exclude_node);
					}
				}
			}
			
			return trim($main_container->textContent);
		}
	}
	
	// Fallback: get body content and exclude known secondary elements
	$body_nodes = $xpath->query('//body');
	if ($body_nodes->length === 0) {
		return strip_tags($html_content);
	}

	$body = $body_nodes->item(0);
	
	// Remove common secondary elements from body
	$exclude_selectors = array(
		'.//nav', './/header', './/footer', 
		'.//*[contains(@class, "sidebar")]', 
		'.//*[contains(@class, "navigation")]',
		'.//*[contains(@class, "nav")]',
		'.//*[contains(@class, "widget")]',
		'.//*[contains(@class, "menu")]',
		'.//*[@role="navigation"]',
		'.//*[@role="banner"]',
		'.//*[@role="contentinfo"]'
	);
	
	foreach ($exclude_selectors as $exclude_selector) {
		$exclude_nodes = $xpath->query($exclude_selector, $body);
		foreach ($exclude_nodes as $exclude_node) {
			if ($exclude_node->parentNode) {
				$exclude_node->parentNode->removeChild($exclude_node);
			}
		}
	}
	
	return trim($body->textContent);
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
 * Analyze HTML content for basic page elements
 * Generic function that extracts common HTML elements without any domain-specific logic
 * @param string $html_content The HTML content to analyze
 * @return array Analysis results with basic page elements
 */
function testing_analyze_html_content($html_content) {
    $parsed_html = testing_parse_html($html_content);
    if (!$parsed_html) {
        return array(
            'success' => false,
            'error' => 'Failed to parse HTML'
        );
    }

    $head_title = testing_get_html_title($html_content);
    $main_content = testing_get_main_content($html_content);
    
    // Get page title and all headings
    $xpath = new DOMXPath($parsed_html);
    
    // Find page title (first h1 in the document)
    $page_title = null;
    $headings = $xpath->query('//h1');
    if ($headings->length > 0) {
        $page_title = trim($headings->item(0)->textContent);
    }
    
    // Get all headings for analysis
    $all_headings = array();
    $heading_nodes = $xpath->query('//h1 | //h2 | //h3 | //h4 | //h5 | //h6');
    foreach ($heading_nodes as $heading) {
        $all_headings[] = array(
            'tag' => $heading->tagName,
            'text' => trim($heading->textContent),
            'class' => $heading->getAttribute('class')
        );
    }

    return array(
        'success' => true,
        'head_title' => $head_title,
        'page_title' => $page_title,
        'all_headings' => $all_headings,
        'main_content' => $main_content,
        'content_preview' => substr($main_content, 0, 200),
        'html_length' => strlen($html_content)
    );
}
