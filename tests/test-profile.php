<?php
/**
 * OpenSimulator Environment Tests
 * Tests OpenSim database and console connectivity using proper W4OS methods
 * 
 * Usage: php test-opensim.php
 */

// I need a new test suite for profile page. Use the same minimalistic clear structure as in tests/test-01-opensim.php (but I think we don't need to exit on failure this time).

// Boostrap:
// - make bootstrap read .env file to allow per-site values

// Testing values:
// - Use plugin functions to get base profile url (W4OS3_Avatar::profile_url()) (might be the same as login page or not)
// - use wp function to get the login page it should be either the url of the page set by w4os_login_page option, either wp core login page or login page set by another plugin if set to "default"
// - if TEST_AVATAR is set, use plugin methods to get its id, otherwise use the plugin methods to list avatars and pick one randomly and get its id and name
// - Use plugin functions to get the picked avatar profile url W4OS3_Avatar::profile_url()

// Tests:
// - get the login page (or profile page alone): it should return 200 OK and contain the login form, e.g:
// # https://dev.w4os.org/profile/
// HTTP/1.1 200 OK
// <title>Log in – W4OS</title>
// <div class="login w4os-login ">
// <form name="w4os-loginform" id="w4os-loginform" action="https://dev.w4os.org/wp-login.php" method="post">
// <h2 class="wp-block-site-title">

// - get the picked avatar profile page (usually /profile/firstname.lastname/), it should returrn 200 OK and contain the avatar profile, e.g.:

// # https://dev.w4os.org/profile/way.forest/
// HTTP/1.1 200 OK
// <title>Way Forest – W4OS</title>
// <h2>Way Forest</h2>December 9, 2021 (1,362 days old)</div>
// <h2 class="wp-block-site-title">

// - get a wrong avatar profile page, it should return 404 and probably the website 404 page (so no specific check on content, mileage might vary too much), e.g.
// # https://dev.w4os.org/profile/wrong.name/
// HTTP/1.1 404 Not Found

// My issue is that I don't get the same results on both website, the first thing to do is to make a test suite, so we can easily track the results and start investigate the cause itself.


// Tests are intended mostly for dev environments, make sure to disable certifiicate check to avoid rejects with self-signed certificates.
// Load bootstrap
require_once __DIR__ . '/bootstrap.php';

echo "Testing Profiles..." . PHP_EOL;

// First collect some testing data
echo PHP_EOL;
echo "Fetching Login configuration..." . PHP_EOL;

// Get database connection for later use
global $is_v3_branch, $is_v2_branch, $is_v2_transitional;

$credentials = array();
if ($is_v3_branch || $is_v2_transitional) {
    $login_uri = w4os_grid_login_uri();
    $credentials = W4OS3::get_credentials($login_uri);
    $robust_db = W4OS3::$robust_db;
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

$db_connected = ($is_v3_branch || $is_v2_transitional) ? 
    ($robust_db && $robust_db->ready) : 
    ($robust_db && $robust_db->check_connection(false));

echo "Database connection: " . ($db_connected ? "OK" : "FAILED") . PHP_EOL;

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
$test->assert_not_empty( $login_page_url, 'Login page URL = ' . var_export( $login_page_url, true ) );

# Fetch login page and make sure we get a 200 response. Ignore self-signed cert errors
add_filter( 'https_ssl_verify', '__return_false' );
$response = wp_remote_get( $login_page_url );
# Get error message if any
$error_message = is_wp_error( $response ) ? $response->get_error_message() : '(none)';
$test->assert_equals( 200, wp_remote_retrieve_response_code( $response ), 'Login page HTTP response code = ' . wp_remote_retrieve_response_code( $response ) );
$test->assert_true( ! is_wp_error( $response ), 'Login page Error message = ' . $error_message );

echo PHP_EOL;
echo "Testing Profile page..." . PHP_EOL;

echo PHP_EOL;
echo "Testing Profile page..." . PHP_EOL;

# Get the profile page URL configuration
$profile_page_option = w4os_get_option( 'w4os_profile_page' );
$test->assert_not_empty( $profile_page_option, 'Profile Page option = ' . var_export( $profile_page_option, true ) );

# Get profile base URL using W4OS3 method
$profile_base_url = '';
if (class_exists('W4OS3_Avatar')) {
    $profile_base_url = W4OS3_Avatar::profile_url();
} elseif (function_exists('w4os_profile_url')) {
    $profile_base_url = w4os_profile_url();
}
$test->assert_not_empty( $profile_base_url, 'Profile base URL = ' . var_export( $profile_base_url, true ) );

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

$test_avatar_details = ($test_avatar ?? null) ? $test_avatar->FirstName . ' ' . $test_avatar->LastName . ' (' . $test_avatar->PrincipalID . ')' : '';

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
$test->assert_equals(200, $base_code, "Base profile page HTTP response code = $base_code");
$test->assert_true(!is_wp_error($base_response), "Base profile page error = $base_error");

# Check if page contains login form
if ($base_code == 200) {
    $base_body = wp_remote_retrieve_body($base_response);
    $has_login_form = (strpos($base_body, 'w4os-login') !== false || strpos($base_body, 'loginform') !== false);
    $test->assert_true($has_login_form, "Base profile page contains login form");
}

if ($is_v3_branch && method_exists($test_avatar, 'profile_url')) {
	$avatar_profile_url = $test_avatar->profile_url();
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
$test->assert_equals(200, $avatar_code, "Avatar profile HTTP response code = $avatar_code");
$test->assert_true(!is_wp_error($avatar_response), "Avatar profile error = $avatar_error");

# Check if page contains avatar name
if ($avatar_code == 200) {
	$avatar_body = wp_remote_retrieve_body($avatar_response);
	$has_avatar_name = (strpos($avatar_body, $avatar_display_name) !== false);
	$test->assert_true($has_avatar_name, "Profile page contains avatar name '$avatar_display_name'");
	
	# Check for profile-specific elements
	$has_title = (strpos($avatar_body, "<title>") !== false && strpos($avatar_body, $avatar_display_name) !== false);
	$test->assert_true($has_title, "Profile page has correct title with avatar name");
}


# Test invalid avatar profile page
$invalid_url = rtrim($profile_base_url, '/') . '/invalid.avatar/';


echo PHP_EOL;
echo "Testing proper error handling and display for invalid avatar profile page $invalid_url" . PHP_EOL;
$invalid_response = wp_remote_get($invalid_url);
$error_message = is_wp_error($invalid_response) ? $invalid_response->get_error_message() : '(none)';
$invalid_code = wp_remote_retrieve_response_code($invalid_response);

$test->assert_equals(404, $invalid_code, "'404 Not Found' returned for invalid profile page");
echo "   Error message: $error_message" . PHP_EOL;

# Check current behavior for invalid profile page
$invalid_body = wp_remote_retrieve_body($invalid_response);

$not_found_string = 'Avatar not found';

# Test that title shows not found string (this should pass in current state)
$doc = new DOMDocument();
@$doc->loadHTML($invalid_body, LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);
$xpath = new DOMXPath($doc);
$title_nodes = $xpath->query('//title');
$title_content = $title_nodes->length > 0 ? $title_nodes->item(0)->textContent : '';
$has_title_not_found = (strpos($title_content, $not_found_string) !== false);
$test->assert_true($has_title_not_found, "$not_found_string title in invalid profile page properties");

# Test current buggy behavior: body content should include the not found string message
# Extract main content area excluding header, footer, nav elements
$main_content = '';

# Get body content but exclude header, footer, nav elements
$body_nodes = $xpath->query('//body//text()[not(ancestor::header) and not(ancestor::footer) and not(ancestor::nav) and not(ancestor::title)]');
foreach ($body_nodes as $node) {
    $main_content .= $node->textContent . ' ';
}

$has_body_not_found = (strpos($main_content, $not_found_string) !== false);
$test->assert_true($has_body_not_found, "$not_found_string appears in invalid profile page main content");

	// Show summary
$test->summary();
