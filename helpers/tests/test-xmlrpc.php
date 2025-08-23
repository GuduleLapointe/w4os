<?php
/**
 * Test file for XML-RPC compatibility layer
 * 
 * This file tests that the XML-RPC compatibility layer works correctly.
 */

require_once __DIR__ . '/../bootstrap.php';

// Test if xmlrpc functions are available
$functions_available = function_exists('xmlrpc_encode') && 
                       function_exists('xmlrpc_decode') && 
                       php_has('xmlrpc')  && 
                       function_exists('xmlrpc_is_fault');

echo "XML-RPC functions available: " . ($functions_available ? "Yes" : "No") . "\n";

// Test encoding/decoding
$data = [
    'success' => true,
    'message' => 'Test message',
    'data' => ['item1', 'item2']
];

$encoded = xmlrpc_encode($data);
echo "Encoded data: " . (is_string($encoded) ? "Success" : "Failed") . "\n";

// Test creating a server
$server = xmlrpc_server_create();
echo "Create server: " . ($server ? "Success" : "Failed") . "\n";

// Test registering a method
function test_method($method_name, $params, $app_data) {
    return ['success' => true];
}

$register_result = xmlrpc_server_register_method($server, 'test.method', 'test_method');
echo "Register method: " . ($register_result ? "Success" : "Failed") . "\n";

echo "\nXML-RPC compatibility layer test completed\n";
