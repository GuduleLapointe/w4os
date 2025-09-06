<?php
/**
 * Offline Messages Helper Tests
 * Tests offline.php functionality across V2, V2-transitional, and V3 branches
 * 
 * Usage: php test-helpers-offline.php
 */

require_once __DIR__ . '/bootstrap.php';

echo "Testing Offline Messages Helper..." . PHP_EOL;

// Test 1: Get credentials and database connection (reusing opensim test pattern)
echo "\nTesting OpenSim database connection..." . PHP_EOL;

global $is_v3_branch, $is_v2_branch, $is_v2_transitional;

echo "\nFetching test avatar..." . PHP_EOL;
$test_avatar_name = getenv('TEST_OFFLINE_AVATAR');
if(!$test->assert_not_empty($test_avatar_name, "TEST_OFFLINE_AVATAR env exists ($test_avatar_name)")) {
    echo "    ⚠️ Skipping offline message tests, to enable, set TEST_OFFLINE_AVATAR env variable" . PHP_EOL;
    exit($test->summary() ? 0 : 1);
}

$login_uri = w4os_grid_login_uri();
if (!$test->assert_not_empty($login_uri, 'Grid login URI ' . $login_uri)) {
    exit($test->summary() ? 0 : 1);
}

$credentials = array();
if ($is_v3_branch || $is_v2_transitional) {
    $credentials = W4OS3::get_credentials($login_uri);
    $robust_db = W4OS3::$robust_db;
    $db_connected = $robust_db && $robust_db->connected;
} else {
    $credentials = array(
        'db' => array(
            'host' => w4os_get_option('w4os_db_host'),
            'port' => w4os_get_option('w4os_db_port'),
            'name' => w4os_get_option('w4os_db_database'),
            'user' => w4os_get_option('w4os_db_user'),
            'pass' => w4os_get_option('w4os_db_pass'),
        ),
    );
    $host_with_port = $credentials['db']['host'] . (empty($credentials['db']['port']) ? '' : ':' . $credentials['db']['port']);
    $robust_db = new WPDB(
        $credentials['db']['user'],
        $credentials['db']['pass'],
        $credentials['db']['name'],
        $host_with_port
    );
    $db_connected = $robust_db && $robust_db->check_connection(false);
}

if (!$test->assert_true($db_connected, 'Database connection established')) {
    echo "    ❌ Cannot proceed without database connection" . PHP_EOL;
    exit($test->summary() ? 0 : 1);
}

// Test 2: Check required tables exist
echo "\nTesting required database tables..." . PHP_EOL;

$required_tables = array(
    'UserAccounts',
    'usersettings',
    'im_offline'
);
foreach ($required_tables as $table) {
    $table_name = $robust_db->get_var("SHOW TABLES LIKE '$table'");
    if(!$test->assert_not_empty($table_name, "$table table exists")) {
        $missing_tables[] = $table;
    }
}
if(!empty($missing_tables)) {
    echo "    ❌ Missing required tables: " . implode(', ', $missing_tables) . PHP_EOL;
    exit($test->summary() ? 0 : 1);
}

$avatar_parts = explode(' ', $test_avatar_name, 2);
if (count($avatar_parts) == 2) {
    $avatar_query = "SELECT PrincipalID, FirstName, LastName FROM UserAccounts WHERE FirstName = %s AND LastName = %s AND active = 1";
    $TEST_OFFLINE_AVATAR = $robust_db->get_row($robust_db->prepare($avatar_query, $avatar_parts[0], $avatar_parts[1]));
}

$avatar_name = $TEST_OFFLINE_AVATAR ? $TEST_OFFLINE_AVATAR->FirstName . ' ' . $TEST_OFFLINE_AVATAR->LastName : 'none';
if (!$test->assert_not_empty($TEST_OFFLINE_AVATAR, 'Test avatar found: ' . $avatar_name)) {
    echo "    ❌ Cannot proceed without test avatar" . PHP_EOL;
    exit($test->summary() ? 0 : 1);
}

echo "DEBUG STOP";
exit($test->summary() ? 0 : 1);


// Test 4: Check avatar email preferences
echo "\nTesting avatar email preferences..." . PHP_EOL;

$settings_query = "SELECT imviaemail, email FROM usersettings WHERE useruuid = %s";
$avatar_settings = $robust_db->get_row($robust_db->prepare($settings_query, $TEST_OFFLINE_AVATAR->PrincipalID));

$has_email_settings = !empty($avatar_settings);
$test->assert_true($has_email_settings, 'Avatar has settings record');

$email_enabled = false;
$email_address = '';
if ($avatar_settings) {
    $email_enabled = $avatar_settings->imviaemail === 'true';
    $email_address = $avatar_settings->email;
    $test->assert_true(true, 'Email forwarding: ' . ($email_enabled ? 'enabled' : 'disabled'));
    $test->assert_true(true, 'Email address: ' . ($email_address ?: 'none'));
}

// Test 5: Test direct offline.php accessibility
echo "\nTesting offline.php direct access..." . PHP_EOL;

// Construct direct URL path
$home_url = get_home_url();
$wp_content_dir = basename(WP_CONTENT_DIR);
$plugin_dir = basename(dirname(__DIR__));
$direct_url = $home_url . '/' . $wp_content_dir . '/plugins/' . $plugin_dir . '/helpers/offline.php';

$test->assert_true(true, 'Direct URL: ' . $direct_url);

// Test with empty request (should fail gracefully)
$direct_response = wp_remote_post($direct_url, array(
    'body' => '',
    'timeout' => 10,
    'sslverify' => false
));

$direct_error = is_wp_error($direct_response) ? $direct_response->get_error_message() : '(none)';
$direct_code = wp_remote_retrieve_response_code($direct_response);
$direct_body = wp_remote_retrieve_body($direct_response);

$test->assert_false(is_wp_error($direct_response), 'Direct access error: ' . $direct_error);
$test->assert_true($direct_code > 0, 'Direct access response code: ' . $direct_code);

// Test 6: Test helper URL accessibility (if function exists)
echo "\nTesting get_helpers_url accessibility..." . PHP_EOL;

if (function_exists('get_helpers_url')) {
    $helper_url = get_helpers_url('offline.php');
    $test->assert_not_empty($helper_url, 'Helper URL: ' . $helper_url);
    
    $helper_response = wp_remote_post($helper_url, array(
        'body' => '',
        'timeout' => 10,
        'sslverify' => false
    ));
    
    $helper_error = is_wp_error($helper_response) ? $helper_response->get_error_message() : '(none)';
    $helper_code = wp_remote_retrieve_response_code($helper_response);
    
    $test->assert_false(is_wp_error($helper_response), 'Helper URL access error: ' . $helper_error);
    $test->assert_true($helper_code > 0, 'Helper URL response code: ' . $helper_code);
} else {
    $test->assert_true(true, 'get_helpers_url() function not available, skipping helper URL test');
}

// Test 7: Test SaveMessage functionality
echo "\nTesting SaveMessage functionality..." . PHP_EOL;

// Generate test message XML
$sender_uuid = '00000000-0000-0000-0000-000000000001'; // System UUID
$message_text = 'Test offline message from automated test suite at ' . date('Y-m-d H:i:s');
$timestamp = time();

$test_xml = '<?xml version="1.0" encoding="utf-8"?>
<GridInstantMessage>
    <toAgentID>' . $TEST_OFFLINE_AVATAR->PrincipalID . '</toAgentID>
    <fromAgentID>' . $sender_uuid . '</fromAgentID>
    <fromAgentName>Test System</fromAgentName>
    <message>' . htmlspecialchars($message_text) . '</message>
    <dialog>0</dialog>
    <offline>1</offline>
    <timestamp>' . $timestamp . '</timestamp>
</GridInstantMessage>';

// Count messages before
$count_before = $robust_db->get_var($robust_db->prepare(
    "SELECT COUNT(*) FROM im_offline WHERE PrincipalID = %s",
    $TEST_OFFLINE_AVATAR->PrincipalID
));

// Test SaveMessage request
$save_response = wp_remote_post($direct_url . '/SaveMessage/', array(
    'body' => $test_xml,
    'headers' => array('Content-Type' => 'application/xml'),
    'timeout' => 10,
    'sslverify' => false
));

$save_error = is_wp_error($save_response) ? $save_response->get_error_message() : '(none)';
$save_code = wp_remote_retrieve_response_code($save_response);
$save_body = wp_remote_retrieve_body($save_response);

$test->assert_false(is_wp_error($save_response), 'SaveMessage request error: ' . $save_error);
$test->assert_equals(200, $save_code, 'SaveMessage response code');

// Check if message was saved
$count_after = $robust_db->get_var($robust_db->prepare(
    "SELECT COUNT(*) FROM im_offline WHERE PrincipalID = %s",
    $TEST_OFFLINE_AVATAR->PrincipalID
));

$message_saved = $count_after > $count_before;
$test->assert_true($message_saved, 'Message saved to database (count: ' . $count_before . ' → ' . $count_after . ')');

// Test 8: Test RetrieveMessages functionality  
echo "\nTesting RetrieveMessages functionality..." . PHP_EOL;

$retrieve_xml = '<?xml version="1.0" encoding="utf-8"?>
<Guid>' . $TEST_OFFLINE_AVATAR->PrincipalID . '</Guid>';

$retrieve_response = wp_remote_post($direct_url . '/RetrieveMessages/', array(
    'body' => $retrieve_xml,
    'headers' => array('Content-Type' => 'application/xml'),
    'timeout' => 10,
    'sslverify' => false
));

$retrieve_error = is_wp_error($retrieve_response) ? $retrieve_response->get_error_message() : '(none)';
$retrieve_code = wp_remote_retrieve_response_code($retrieve_response);
$retrieve_body = wp_remote_retrieve_body($retrieve_response);

$test->assert_false(is_wp_error($retrieve_response), 'RetrieveMessages request error: ' . $retrieve_error);
$test->assert_equals(200, $retrieve_code, 'RetrieveMessages response code');

// Check if messages were retrieved and deleted
$count_final = $robust_db->get_var($robust_db->prepare(
    "SELECT COUNT(*) FROM im_offline WHERE PrincipalID = %s",
    $TEST_OFFLINE_AVATAR->PrincipalID
));

$messages_retrieved = $count_final < $count_after;
if ($message_saved) {
    $test->assert_true($messages_retrieved, 'Messages retrieved and deleted (count: ' . $count_after . ' → ' . $count_final . ')');
} else {
    $test->assert_true(true, 'No messages to retrieve (original save failed)');
}

// Check response contains XML
$contains_xml = strpos($retrieve_body, '<?xml') !== false;
if ($message_saved) {
    $test->assert_true($contains_xml, 'RetrieveMessages response contains XML');
} else {
    $test->assert_true(true, 'Skipping XML content check (no saved messages)');
}

// Test 9: Email functionality (informational only)
echo "\nTesting email functionality..." . PHP_EOL;

if ($email_enabled && !empty($email_address)) {
    $test->assert_true(true, 'Email forwarding configured for test avatar');
    
    // We don't actually test email sending in automated tests
    // but we can verify the configuration looks correct
    $valid_email = filter_var($email_address, FILTER_VALIDATE_EMAIL);
    $test->assert_true($valid_email !== false, 'Avatar email address is valid format');
} else {
    $test->assert_true(true, 'Email forwarding not enabled for test avatar (testing save-only mode)');
}

// Show summary
$test->summary();
