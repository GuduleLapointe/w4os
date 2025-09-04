<?php
/**
 * W4OS Dependencies Test
 * 
 * Tests PHP version, WordPress version, and required/recommended PHP extensions
 */

// Load bootstrap
require_once __DIR__ . '/bootstrap.php';

echo "Testing system dependencies..." . PHP_EOL;

// Test PHP version
$required_php = '7.4';
$current_php = PHP_VERSION;
if(!$test->assert_true(
	version_compare( $current_php, $required_php, '>=' ),
	"PHP version >= $required_php (got: $current_php)"
)) {
    echo "   ❌  Cannot proceed on an unsupported PHP version" . PHP_EOL;
    exit( $test->summary() ? 0 : 1 );
}

// Test WordPress version
$required_wp = '5.0';
$current_wp = get_bloginfo( 'version' );
if(!$test->assert_true(
	version_compare( $current_wp, $required_wp, '>=' ),
	"WordPress version >= $required_wp (got: $current_wp)"
)) {
    echo "   ❌  Cannot proceed on an unsupported WordPress version" . PHP_EOL;
    exit( $test->summary() ? 0 : 1 );
}

// Required PHP extensions
$required_extensions = [
	'curl' => 'HTTP requests to OpenSimulator servers',
	'json' => 'Configuration files and API responses',
	'simplexml' => 'Parsing XML responses from OpenSimulator',
	'openssl' => 'Encryption/decryption of sensitive data',
	'pdo' => 'Database connections',
	'mysqli' => 'WordPress database compatibility'
];

foreach ( $required_extensions as $extension => $purpose ) {
	$available = php_has( $extension );
	$test->assert_true( $available, "Required extension '$extension' available ($purpose)" );
	if ( ! $available ) {
		echo "   ⚠️  Missing required extension: $extension - $purpose" . PHP_EOL;
        $required_errors = true;
	}
}
if($required_errors ?? false) {
    echo "   ❌  One or more required PHP extensions are missing. Please install them to ensure proper functionality." . PHP_EOL;
    exit( $test->summary() ? 0 : 1 );
}

// Recommended PHP extensions
$recommended_extensions = [
	'xmlrpc' => 'Parsing XML responses from OpenSimulator in Web search, Popular places, Grid info...',
	'imagick' => 'Profile images and web assets server',
	'mbstring' => 'String manipulation and encoding',
	'intl' => 'Internationalization features',
	'gd' => 'Image processing fallback (if imagick not available)'
];

foreach ( $recommended_extensions as $extension => $purpose ) {
	$available = php_has( $extension );
    $test->assert_true( $available, "Recommended extension '$extension' available ($purpose)" );
}

$test->summary();
