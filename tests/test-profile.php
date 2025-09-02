<?php
/**
 * Profile page testing framework
 * Tests profile page functionality across V2, V2-transitional, and V3 branches
 */

require_once __DIR__ . '/bootstrap.php';

echo "Testing Profiles..." . PHP_EOL;

if( $is_v3_branch) {
	// This is a mistake, but reflects current usage in v3 branch
	// V3 should not use wp core get_option() directly for plugin options
	$profile_page_option = get_option( 'w4os_profile_page' );
} else {
	$profile_page_option = w4os_get_option( 'w4os_profile_page' );
}
// No need to continue if profile page is not enabled in settings
if(! $test->assert_equals( 'provide', $profile_page_option, 'Profile Page option = ' . var_export( $profile_page_option, true ) )) {
	echo "❌ Profile page not enabled, skipping profile page tests" . PHP_EOL;
	exit( $test->summary() ? 0 : 1 );
}

// First collect some testing data
echo PHP_EOL;
echo "Fetching plugin configuration..." . PHP_EOL;

// Get database connection for later use
global $is_v3_branch, $is_v2_branch, $is_v2_transitional;

$login_uri = w4os_grid_login_uri();
$credentials = array();
if ($is_v3_branch || $is_v2_transitional) {
    $credentials = W4OS3::get_credentials($login_uri);
    $robust_db = W4OS3::$robust_db;
	$db_connected = $robust_db && $robust_db->connected;
} else {
    // For V2 branch, get credentials from options
    $credentials = array(
        'db' => array(
            'host' => w4os_get_option('w4os_db_host'),
            'port' => w4os_get_option('w4os_db_port'),
            'name' => w4os_get_option('w4os_db_database'),
            'user' => w4os_get_option('w4os_db_user'),
            'pass' => w4os_get_option('w4os_db_pass'),
        ),
    );
    
    // Create WPDB connection like in opensim test
    $host_with_port = $credentials['db']['host'] . (empty($credentials['db']['port']) ? '' : ':' . $credentials['db']['port']);
    $robust_db = new WPDB(
        $credentials['db']['user'],
        $credentials['db']['pass'],
        $credentials['db']['name'],
        $host_with_port
    );
}

if(! $test->assert_true( $db_connected, 'Database connected' )) {
	echo "❌ Cannot proceed without a valid database connection" . PHP_EOL;
	exit( $test->summary() ? 0 : 1 );
}

if( $is_v3_branch) {
	echo "Login page option not implemented in V3, using default WP login page" . PHP_EOL;
	$login_page_url = wp_login_url();
} else {
	// # Test login page configuration
	$login_page_option = w4os_get_option( 'w4os_login_page' );
	$test->assert_not_empty( $login_page_option, 'Login Page option = ' . var_export( $login_page_option, true ) );
	// $test->assert_true( true, 'w4os_login_page option = ' . var_export( $login_page_option, true ) );

	# Get the url of the login page
	if($login_page_option === 'default') {
		$login_page_url = wp_login_url();
		$test->assert_not_empty( $login_page_url, 'Login page URL (default) = ' . var_export( $login_page_url, true ) );
	} else {
		$login_page_url = get_permalink( get_page_by_path( $login_page_option ) );
	}
}
$test->assert_not_empty( $login_page_url, 'Login page URL = ' . var_export( $login_page_url, true ) );

# Fetch login page and make sure we get a 200 response. Ignore self-signed cert errors
add_filter( 'https_ssl_verify', '__return_false' );
$response = wp_remote_get( $login_page_url );
# Get error message if any
$error_message = is_wp_error( $response ) ? $response->get_error_message() : '(none)';
$test->assert_equals( 200, wp_remote_retrieve_response_code( $response ), 'Login page HTTP response code');
$test->assert_true( ! is_wp_error( $response ), 'Login page Error message = ' . $error_message );

echo PHP_EOL;
echo "Testing Profile pages..." . PHP_EOL;

# Get profile base URL using appropriate method for V2/V3
$profile_base_url = '';
if( $is_v3_branch || $is_v2_transitional ) {
    $profile_base_url = W4OS3_Avatar::profile_url();
} else {
    // For V2, construct base URL from profile slug option
    $profile_slug = get_option('w4os_profile_slug', 'profile');
    $profile_base_url = get_home_url(null, $profile_slug);
}
if(! $test->assert_not_empty( $profile_base_url, 'Profile base URL = ' . var_export( $profile_base_url, true ) ) ) {
	echo "❌ Cannot proceed without a valid profile base URL" . PHP_EOL;
	exit( $test->summary() ? 0 : 1 );
}

echo PHP_EOL;
echo "Getting the list of avatars..." . PHP_EOL;

# Fetch avatar list first (needed for both branches)
$avatars = null;
if ($db_connected && $robust_db) {
    $avatars = $robust_db->get_results("SELECT PrincipalID, FirstName, LastName FROM UserAccounts WHERE active=1 LIMIT 50");
    echo "Available avatars in database: " . (is_array($avatars) ? count($avatars) : "none") . PHP_EOL;
} else {
    echo "❌ Cannot fetch avatar list - database connection failed" . PHP_EOL;
    if ($robust_db && isset($robust_db->last_error)) {
        echo "    Error: " . $robust_db->last_error . PHP_EOL;
    }
}

# Get test avatar - from env var or pick one randomly
$test_avatar_name = getenv('TEST_AVATAR');
$test_avatar = null;

if ($test_avatar_name) {
    echo "Using .env TEST_AVATAR: $test_avatar_name" . PHP_EOL;
    
    // Find avatar by name in the fetched list
    $found_avatar = null;
    if ($avatars) {
        foreach ($avatars as $avatar) {
            $full_name = $avatar->FirstName . ' ' . $avatar->LastName;
            if ($full_name === $test_avatar_name) {
                $found_avatar = $avatar;
                break;
            }
        }
    }
    
	if( ! $test->assert_not_empty( $found_avatar, 'Test avatar found in database = ' . var_export( $test_avatar_name, true ) )) {
		echo "❌ Cannot proceed without a valid test avatar" . PHP_EOL;
		exit( $test->summary() ? 0 : 1 );
	}

	if ($is_v3_branch && class_exists('W4OS3_Avatar')) {
		$test_avatar = new W4OS3_Avatar($found_avatar->PrincipalID);
	} else {
		$test_avatar = (object)[
			'PrincipalID' => $found_avatar->PrincipalID,
			'FirstName' => $found_avatar->FirstName,
			'LastName' => $found_avatar->LastName
		];
	}
} else {
    echo "Selecting random avatar..." . PHP_EOL;
    
    if ($avatars && count($avatars) > 0) {
        $random_avatar = $avatars[array_rand($avatars)];
        $test_avatar_name = $random_avatar->FirstName . ' ' . $random_avatar->LastName;
        echo "Selected random avatar: $test_avatar_name" . PHP_EOL;
        
        if ($is_v3_branch && class_exists('W4OS3_Avatar')) {
            $test_avatar = new W4OS3_Avatar($random_avatar->PrincipalID);
        } else {
            $test_avatar = (object)[
                'PrincipalID' => $random_avatar->PrincipalID,
                'FirstName' => $random_avatar->FirstName,
                'LastName' => $random_avatar->LastName
            ];
        }
    } else {
        echo "❌ No avatars available for testing" . PHP_EOL;
    }
}

// echo 'DEBUG $test_avatar: ' . var_export($test_avatar, true) . PHP_EOL;
// exit('DEBUG exit');
$test_avatar_details = ($test_avatar ?? null) ? $test_avatar->FirstName . ' ' . $test_avatar->LastName . ' (' . ($test_avatar->PrincipalID ?? $test_avatar->UUID). ')' : '';

if( ! $test->assert_not_empty( $test_avatar, "Avatar for testing: {$test_avatar_details}" )) {
	echo "❌ Cannot proceed without a valid test avatar" . PHP_EOL;
	exit( $test->summary() ? 0 : 1 );
}

# Test base profile page (should show login form)
echo PHP_EOL;
echo "Testing base profile page $profile_base_url..." . PHP_EOL;

$base_response = wp_remote_get($profile_base_url);
$base_error = is_wp_error($base_response) ? $base_response->get_error_message() : '(none)';
$base_code = wp_remote_retrieve_response_code($base_response);
$test->assert_equals(200, $base_code, "Base profile page HTTP response");
$test->assert_true(!is_wp_error($base_response), "Base profile page error = $base_error");

# Check if page contains login form
if ($base_code == 200) {
    $base_body = wp_remote_retrieve_body($base_response);
    $has_login_form = (strpos($base_body, 'w4os-login') !== false || strpos($base_body, 'loginform') !== false);
    $test->assert_true($has_login_form, "Base profile page contains login form");
}

if ($is_v3_branch && method_exists($test_avatar, 'profile_url')) {
	$avatar_profile_url = $test_avatar->profile_url($test_avatar);
	$avatar_display_name = $test_avatar->FirstName . ' ' . $test_avatar->LastName;
} else {
	// Construct profile URL manually for 2.x
	$avatar_slug = strtolower($test_avatar->FirstName . '.' . $test_avatar->LastName);
	$avatar_profile_url = rtrim($profile_base_url, '/') . '/' . $avatar_slug . '/';
	$avatar_display_name = $test_avatar->FirstName . ' ' . $test_avatar->LastName;
}

$test->assert_true(true, "Test avatar: $avatar_display_name");
$test->assert_not_empty($avatar_profile_url, "Avatar profile URL = $avatar_profile_url");

echo PHP_EOL;
echo "Testing valid avatar profile page $avatar_profile_url" . PHP_EOL;

# Test valid avatar profile page
$avatar_response = wp_remote_get($avatar_profile_url);
$avatar_error = is_wp_error($avatar_response) ? $avatar_response->get_error_message() : '(none)';
$avatar_code = wp_remote_retrieve_response_code($avatar_response);

# Check if page contains avatar name
if ($test->assert_equals(200, $avatar_code, "Proper profile HTTP response code")) {
	$avatar_body = wp_remote_retrieve_body($avatar_response);

	# Parse HTML content to analyze page structure
	$analysis = testing_analyze_html_content($avatar_body);
	
	if( $test->assert_true( $analysis['success'], 'HTML parsing' )) {
		# Check if avatar name appears in page title
		$test->assert_true(strpos($analysis['head_title'], $avatar_display_name) !== false, "Avatar name must be in head title (${analysis['head_title']})");

		$in_page_title = strpos($analysis['page_title'], $avatar_display_name);
		if($in_page_title) {
			echo "✓ Notice: Avatar name found in page title";
		} else {
			echo "✗ Notice: Avatar name not in page title";
		}
		echo $analysis['page_title'] . PHP_EOL;
		
		if ($is_v3_branch) {
			$doc = testing_parse_html($avatar_body);
			$xpath = new DOMXPath($doc);
			$profile_headers = $xpath->query('//div[contains(@class, "profile-header")]//h2');
			
			if ($profile_headers->length > 0) {
				$detected_name = trim($profile_headers->item(0)->textContent);
			} else {
				$detected_name = ''; // Not found
			}

			$in_banner = $test->assert_equals($avatar_display_name, $detected_name, "Avatar name in V3 profile banner (${detected_name})");
		} else {
			$in_banner = false; // No banner in V2
		}
		if( $test->assert_true($in_page_title || $in_banner, "Avatar name must appear in page title or banner") ) {
			$test->assert_false($in_page_title && $in_banner, "Avatar name must not be duplicated in title and banner");
		}
	} else {
		echo "   ⚠️  HTML parsing failed: {$analysis['error']}" . PHP_EOL;
	}
} else {
	echo "   ⚠️  Error: $avatar_error" . PHP_EOL;
}

# Test invalid avatar profile page
$invalid_url = rtrim($profile_base_url, '/') . '/invalid.avatar/';


echo PHP_EOL;
echo "Testing proper error handling and display for invalid avatar profile page $invalid_url" . PHP_EOL;

// Primary test using get_headers() - no WordPress/extension dependencies
$opts = array(
	'http' => array(
		'max_redirects'=>1,
		'ignore_errors'=>1
	),
    'ssl' => [
        'verify_peer' => false,
        'verify_peer_name' => false,
    ],
);
stream_context_get_default($opts);
$headers = get_headers($invalid_url, 1);
// get response code from headers
$header_code = null;
if ($headers && is_array($headers)) {
	$status_line = $headers[0];
	if (preg_match('#HTTP/\d+\.\d+\s+(\d+)#', $status_line, $matches)) {
		$header_code = (int)$matches[1];
	}
}
$test->assert_equals(404, $header_code, "Response code for Avatar Not Found page with get_headers()");

// Secondary test using exec(curl) - track curl binary vs PHP lib discrepancy  
$cmd = 'curl -skI ' . escapeshellarg($invalid_url);
exec($cmd, $output, $return_var);
// Parse output to find HTTP status line
$exec_code = null;
foreach ($output as $line) {
	if (preg_match('#HTTP/\d+\.\d+\s+(\d+)#', $line, $matches)) {
		$exec_code = (int)$matches[1];
		break;
	}
}
$test->assert_equals(404, $exec_code, "Response code for Avatar Not Found page with exec(curl)");

# Check current behavior for Avatar Not Found page - get content using same SSL context
$invalid_body = file_get_contents($invalid_url, false, stream_context_get_default());

$not_found_string = 'Avatar not found';

if($test->assert_not_empty($invalid_body, "Response body not empty for Avatar Not Found page")) {
    # Parse HTML content and check for "Avatar not found" message
    $analysis = testing_analyze_html_content($invalid_body);
    
    if ($analysis['success']) {
        $test->assert_true(strpos($analysis['head_title'], $not_found_string) !== false, "Proper head title for Avatar Not Found page (${analysis['head_title']})");
        
        # Check that no valid avatar name pattern appears in main content
        $has_avatar_pattern = preg_match('/\b([A-Z][a-z]+)\s+([A-Z][a-z]+)\b/', $analysis['main_content'], $matches);
        if ($has_avatar_pattern) {
            $potential_name = $matches[1] . ' ' . $matches[2];
            $false_positives = array('Log In', 'Sign Up', 'My Profile', 'Create Avatar', 'Real Life', 'Avatar Not', 'Not Found');
            $is_valid_avatar = !in_array($potential_name, $false_positives);
            $test->assert_false($is_valid_avatar, "No valid avatar name should be detected on Avatar Not Found page (found: '$potential_name')");
        } else {
            $test->assert_true(true, "No avatar name pattern detected on Avatar Not Found page (good)");
        }
    } else {
        echo "   ⚠️  HTML parsing failed: {$analysis['error']}" . PHP_EOL;
    }
} else {
    echo "   ⚠️  Cannot test empty content - skipping head and body content tests" . PHP_EOL;
}

	// Show summary
$test->summary();
