#!/usr/bin/env php
<?php
/**
 * Main test runner for W4OS plugin
 * 
 * Usage: ./run-tests.php or php run-tests.php
 */

echo "
" . str_repeat( '=', 50 ) . "
";
echo "W4OS Plugin Test Suite
";
echo str_repeat( '=', 50 ) . "

";

// Array to track all test results
$all_tests = array();
$total_run = 0;
$total_passed = 0;
$total_failed = 0;
$all_failed_tests = array();

// Get list of test files
$test_files = glob( __DIR__ . '/test-*.php' );

if ( empty( $test_files ) ) {
	echo "No test files found matching pattern 'test-*.php'
";
	exit( 1 );
}

// Run each test file as a separate process
foreach ( $test_files as $test_file ) {
	$test_name = basename( $test_file, '.php' );
	echo "Running {$test_name}...
";
	
	// Check if this is a required prerequisite test
	$is_prerequisite = strpos( $test_name, '-required' ) !== false;
	
	// Execute the test file and capture output
	$output = array();
	$return_code = 0;
	exec( "cd " . escapeshellarg( __DIR__ ) . " && php " . escapeshellarg( basename( $test_file ) ) . " 2>&1", $output, $return_code );
	
	// Parse the output to extract test results
	$test_output = implode( "
", $output );
	echo $test_output . "

";
	
	// Parse summary from output
	$tests_run = 0;
	$tests_passed = 0;
	$tests_failed = 0;
	
	if ( preg_match( '/Total tests: (\d+)/', $test_output, $matches ) ) {
		$tests_run = (int) $matches[1];
		$total_run += $tests_run;
	}
	
	if ( preg_match( '/Passed: (\d+)/', $test_output, $matches ) ) {
		$tests_passed = (int) $matches[1];
		$total_passed += $tests_passed;
	}
	
	if ( preg_match( '/Failed: (\d+)/', $test_output, $matches ) ) {
		$tests_failed = (int) $matches[1];
		$total_failed += $tests_failed;
	}
	
	// If return code is non-zero but no test summary found, treat as fatal error
	if ( $return_code !== 0 && $tests_run === 0 ) {
		echo "❌ FATAL ERROR: Test {$test_name} crashed with exit code {$return_code}" . PHP_EOL;
		$tests_run = 1;
		$tests_failed = 1;
		$total_run += 1;
		$total_failed += 1;
		$all_failed_tests[] = "[{$test_name}] Fatal error (exit code {$return_code})";
	}
	
	// Extract failed tests
	if ( preg_match_all( '/\d+\. (.+)/', $test_output, $matches ) ) {
		foreach ( $matches[1] as $failed_test ) {
			$all_failed_tests[] = "[{$test_name}] {$failed_test}";
		}
	}
	
	$all_tests[] = array(
		'name' => $test_name,
		'return_code' => $return_code,
		'tests_run' => $tests_run,
		'tests_passed' => $tests_passed,
		'tests_failed' => $tests_failed
	);
	
	// If this is a prerequisite test and it failed, stop here
	if ( $is_prerequisite && ( $return_code !== 0 || $tests_failed > 0 ) ) {
		echo "❌ PREREQUISITE TEST FAILED: {$test_name}
";
		echo "Stopping test execution - remaining tests will likely fail without prerequisites
";
		break;
	}

	echo "
";
}

// Overall summary
echo str_repeat( '=', 50 ) . "
";
echo "Overall Test Summary:
";

foreach ( $all_tests as $test_result ) {
	$status = $test_result['return_code'] === 0 ? 'PASSED' : 'FAILED';
	echo "  {$test_result['name']}: {$test_result['tests_passed']}/{$test_result['tests_run']} passed ({$status})
";
}

echo "
Total Results:
";
echo "  Total tests: {$total_run}
";
echo "  Passed: {$total_passed}
";
echo "  Failed: {$total_failed}
";

if ( $total_failed > 0 ) {
	echo PHP_EOL . "⚠️  Failed Tests:" . PHP_EOL;
	foreach ( $all_failed_tests as $i => $failed_test ) {
		echo "  " . ($i + 1) . ". {$failed_test}
";
	}
	echo "
  Status: FAILED
";
	exit( 1 );
} else {
	echo "  Status: ALL PASSED
";
}
