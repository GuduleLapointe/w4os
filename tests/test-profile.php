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

$login_page_option = get_option( 'w4os_login_page' );
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

# Get the profile page URL
$profile_page_option = get_option( 'w4os_profile_page' );
$test->assert_not_empty( $profile_page_option, 'Profile Page option = ' . var_export( $profile_page_option, true ) );

// if($profile_page_option === 'provide') {
// 	$profile_page_slug = w4os_get_option('')

// Show summary
$test->summary();
