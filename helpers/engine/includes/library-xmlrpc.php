<?php
/**
 * XML-RPC Library Functions
 * 
 * Replace the deprecated php xmlrpc extension with phpxmlrpc/phpxmlrpc library.
 * This file provides drop-in replacements for all standard xmlrpc_* functions.
 * 
 * @see https://github.com/gggeek/phpxmlrpc
 * @see https://php.watch/versions/8.0/xmlrpc
 */

// Check if the XML RPC is already available, if so, don't override the functions
if (function_exists('xmlrpc_encode') && !defined('OS_XMLRPC_FORCE_REPLACE')) {
    return;
}

// Check that the PhpXmlRpc library is available
if (!class_exists('\\PhpXmlRpc\\Value')) {
    error_log('[ERROR] PhpXmlRpc library not found. Please install it with: composer require phpxmlrpc/phpxmlrpc');
    return;
}

use PhpXmlRpc\Value;
use PhpXmlRpc\Request;
use PhpXmlRpc\Response;
use PhpXmlRpc\Server;
use PhpXmlRpc\Encoder;

/**
 * Create a new xmlrpc server
 * 
 * @return Server
 */
function xmlrpc_server_create() {
    return new Server();
}

/**
 * Destroy an XML-RPC server
 * 
 * @param Server $server The server instance to destroy
 * @return bool Always returns TRUE
 */
function xmlrpc_server_destroy($server) {
    // PhpXmlRpc doesn't require explicit destruction
    return true;
}

/**
 * Register a PHP function to handle an XML-RPC method
 *
 * @param Server $server The XML-RPC server
 * @param string $method The XML-RPC method name
 * @param string $function The PHP function name
 * @return bool Returns TRUE on success or FALSE on failure
 */
function xmlrpc_server_register_method($server, $method, $function) {
    if (!($server instanceof Server)) {
        return false;
    }
    
    $server->addHandler($method, $function);
    return true;
}

/**
 * Parse XML and return a PHP value
 *
 * @param string $xml XML to parse
 * @param string $encoding Character encoding (unused in this implementation)
 * @return mixed PHP value
 */
function xmlrpc_decode($xml, $encoding = "iso-8859-1") {
    $encoder = new Encoder();
    
    // Handle string input (XML data)
    if (is_string($xml)) {
        // Check if it's actually XML content
        if (preg_match('/<\?xml|<methodResponse|<methodCall|<value/', $xml)) {
            try {
                // Try to decode it as XML-RPC response
                $resp = new Response($xml);
                if ($resp->faultCode() === 0) {
                    return $encoder->decode($resp->value());
                } else {
                    return [
                        'faultCode' => $resp->faultCode(),
                        'faultString' => $resp->faultString()
                    ];
                }
            } catch (\Exception $e) {
                // If direct response decoding fails, try using decodeXml
                try {
                    $result = $encoder->decodeXml($xml);
                    if ($result instanceof \PhpXmlRpc\Value) {
                        return $encoder->decode($result);
                    } elseif ($result instanceof \PhpXmlRpc\Response) {
                        if ($result->faultCode() === 0) {
                            return $encoder->decode($result->value());
                        } else {
                            return [
                                'faultCode' => $result->faultCode(),
                                'faultString' => $result->faultString()
                            ];
                        }
                    }
                    return $result;
                } catch (\Exception $e2) {
                    // If all XML parsing fails, return the original string
                    error_log("XML-RPC decoding error: " . $e2->getMessage());
                    return $xml;
                }
            }
        } else {
            // Not valid XML, just return the string
            return $xml;
        }
    }
    
    // For PhpXmlRpc\Value objects
    if ($xml instanceof \PhpXmlRpc\Value) {
        return $encoder->decode($xml);
    }
    
    // For other values, just return as is
    return $xml;
}

/**
 * Return an XML-RPC value as a PHP value
 *
 * @param mixed $value The value to encode
 * @return string XML-RPC representation
 */
function xmlrpc_encode($value) {
    $encoder = new Encoder();
    return $encoder->encode($value);
}

/**
 * Generate XML for an XML-RPC method request
 *
 * @param string $method The XML-RPC method name
 * @param array $params The parameters for the method
 * @param array $output_options (unused in this implementation)
 * @return string XML representation of the request
 */
function xmlrpc_encode_request($method, $params, $output_options = []) {
    $encoder = new Encoder();
    $paramValues = [];
    
    foreach ((array)$params as $param) {
        $paramValues[] = $encoder->encode($param);
    }
    
    $request = new Request($method, $paramValues);
    return $request->serialize();
}

/**
 * Determines if an array value represents an XML-RPC fault
 *
 * @param array $arg The XML-RPC value
 * @return bool TRUE if the argument is an XML-RPC fault
 */
function xmlrpc_is_fault($arg) {
    return (is_array($arg) && isset($arg['faultCode']) && isset($arg['faultString']));
}

/**
 * Calls a method on an XML-RPC server
 *
 * @param Server $server The XML-RPC server
 * @param string $request The XML request
 * @param mixed $user_data User data
 * @param array $output_options (unused in this implementation)
 * @return mixed The response
 */
function xmlrpc_server_call_method($server, $request, $user_data, $output_options = []) {
    if (!($server instanceof Server)) {
        return false;
    }
    
    // Process the request and get the response as a string
    $response = $server->service($request, true);
    
    // Output the response directly
    echo $response;
    
    return true;
}

/**
 * Add introspection documentation for an XML-RPC method
 * (Not fully implemented, as it's not used in the currency.php)
 */
function xmlrpc_server_register_introspection_callback($server, $function) {
    // Not fully implemented as it's not used in the OpenSim code
    return true;
}
