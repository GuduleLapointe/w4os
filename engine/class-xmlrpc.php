<?php
/**
 * XML-RPC protocol support
 * 
 * Replace the deprecated php xmlrcp extension with a pure php implementation.
 * 
 * Currently for reference and evaluation only, not yet used in the project.
 * 
 * https://php.watch/versions/8.0/xmlrpc
 * 
**/

# Example with phpxmlrpc/phpxmlrpc composer package
#   composer require phpxmlrpc/phpxmlrpc

use PhpXmlRpc\Client;
use PhpXmlRpc\Request;
use PhpXmlRpc\Value;

class OS_XMLRPC_Client {
    private $endpoint;
    private $timeout;

    public function __construct($endpoint, $timeout = 10) {
        $this->endpoint = $endpoint;
        $this->timeout = $timeout;
    }

    public function call($method, $params = []) {
        $request = OS_XMLRPC::xmlrpc_encode_request($method, $params);
        
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
            throw new Exception("Failed to connect to XML-RPC server");
        }

        $result = OS_XMLRPC::xmlrpc_decode($response);
        if (OS_XMLRPC::xmlrpc_is_fault($result)) {
            throw new Exception($result['faultString'], $result['faultCode']);
        }

        return $result;
    }
}

/**
 * Static class to providing drop-in replacement for the deprecated php xmlrpc extension.
 */
class OS_XMLRPC {
    public static function xmlrpc_encode($value) {
        return new Value($value);
    }

    public static function xmlrpc_decode($xml_response) {
        if (is_string($xml_response)) {
            $response = new PhpXmlRpc\Response($xml_response);
            return $response->value();
        }
        return $xml_response->scalarval();
    }

    public static function xmlrpc_encode_request($method, $params) {
        $xmlrpcParams = array_map(function($param) {
            return new Value($param);
        }, $params);
        
        $request = new Request($method, $xmlrpcParams);
        return $request->serialize();
    }

    public static function xmlrpc_is_fault($response) {
        if ($response instanceof PhpXmlRpc\Response) {
            return $response->faultCode() !== 0;
        }
        return false;
    }

    public static function xmlrpc_server_create() {
        return new PhpXmlRpc\Server();
    }
}

// Usage remains similar:
// $request = OS_XMLRPC::xmlrpc_encode_request('method', ['param1']);
// $response = OS_XMLRPC::xmlrpc_decode($result);
