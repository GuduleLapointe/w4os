<?php
/**
 * XML-RPC Client Class
 * 
 * This file provides a custom XML-RPC client class for OpenSim projects.
 * The actual XML-RPC function replacements are in vendor/phpxmlrpc/phpxmlrpc/lib/xmlrpc.inc
 * 
 * @see https://github.com/gggeek/phpxmlrpc
 * @see https://php.watch/versions/8.0/xmlrpc
 */

// bootstrap.php sets autoload before this file is included
// // Set up autoloader for composer if it's not already loaded
// $composerAutoloadFiles = [
//     // Standard vendor paths
//     __DIR__ . '/../../../vendor/autoload.php',
//     __DIR__ . '/../../../../vendor/autoload.php',
//     // Fallback paths
//     __DIR__ . '/../vendor/autoload.php',
//     __DIR__ . '/vendor/autoload.php',
// ];

// foreach ($composerAutoloadFiles as $file) {
//     if (file_exists($file)) {
//         require_once $file;
//         break;
//     }
// }

/**
 * Client class for XML-RPC requests
 * 
 * A convenient wrapper for making XML-RPC calls to OpenSim services.
 */
class OpenSim_XMLRPC_Client {
    private $endpoint;
    private $timeout;
    private $debug = false;

    /**
     * Create a new XML-RPC client
     * 
     * @param string $endpoint The URL endpoint for the XML-RPC service
     * @param int $timeout Connection timeout in seconds
     * @param bool $debug Whether to enable debug logging
     */
    public function __construct($endpoint, $timeout = 10, $debug = false) {
        $this->endpoint = $endpoint;
        $this->timeout = $timeout;
        $this->debug = $debug;
    }

    /**
     * Call an XML-RPC method
     * 
     * @param string $method The method name to call
     * @param array $params The parameters to pass
     * @return mixed The response from the server
     * @throws \Exception When connection fails or server returns a fault
     */
    public function call($method, $params = []) {
        $request = xmlrpc_encode_request($method, $params);
        
        if ($this->debug) {
            error_log("[DEBUG] XML-RPC Request to {$this->endpoint}: " . $method);
        }
        
        $context = stream_context_create([
            'http' => [
                'method'  => "POST",
                'header'  => "Content-Type: text/xml",
                'timeout' => $this->timeout,
                'content' => $request
            ]
        ]);

        $response = file_get_contents($this->endpoint, false, $context);
        if ($response === false) {
            throw new \Exception("Failed to connect to XML-RPC server at {$this->endpoint}");
        }

        $result = xmlrpc_decode($response);
        
        if ($this->debug) {
            error_log("[DEBUG] XML-RPC Response: " . print_r($result, true));
        }
        
        if (xmlrpc_is_fault($result)) {
            throw new \Exception($result['faultString'], $result['faultCode']);
        }

        return $result;
    }
    
    /**
     * Enable or disable debug logging
     * 
     * @param bool $debug Whether to enable debug logging
     */
    public function setDebug($debug) {
        $this->debug = $debug;
    }
}
