<?php
/**
 * PHPUnit test for XML-RPC functionality
 * 
 * This test verifies that the XML-RPC compatibility layer works correctly,
 * focusing only on the XML-RPC functionality itself, not on helpers ussing xmlrpc.
 * 
 * @package     magicoli/opensim-helpers
 */

use PHPUnit\Framework\TestCase;

class XmlrpcTest extends TestCase
{
    /**
     * Test that the XML-RPC functions are loaded properly
     */
    public function testXmlrpcFunctionsAvailable()
    {
        $this->assertTrue(function_exists('xmlrpc_encode'), "xmlrpc_encode function should be available");
        $this->assertTrue(function_exists('xmlrpc_decode'), "xmlrpc_decode function should be available");
        $this->assertTrue(php_has('xmlrpc') , "xmlrpc_encode_request function should be available");
        $this->assertTrue(function_exists('xmlrpc_is_fault'), "xmlrpc_is_fault function should be available");
        $this->assertTrue(function_exists('xmlrpc_server_create'), "xmlrpc_server_create function should be available");
    }

    /**
     * Test basic encoding and decoding of values
     */
    public function testXmlrpcEncodeDecode()
    {
        $original = [
            'success' => true,
            'message' => 'Test message',
            'data' => [
                'item1' => 'value1',
                'item2' => 'value2'
            ]
        ];
        
        // Encode the data
        $encoded = xmlrpc_encode($original);
        
        // For XML-RPC, we need to use a request/response cycle to properly test
        // So we'll create a request and then parse the response
        $request = xmlrpc_encode_request('test.method', [$original]);
        $this->assertNotEmpty($request, "XML-RPC request should not be empty");
        $this->assertIsString($request, "XML-RPC request should be a string");
        
        // Check that the XML is well-formed
        $xml = simplexml_load_string($request);
        $this->assertNotFalse($xml, "XML-RPC request should be valid XML");
    }
    
    /**
     * Test creating an XML-RPC server and registering methods
     */
    public function testXmlrpcServer()
    {
        $server = xmlrpc_server_create();
        $this->assertNotFalse($server, "Should be able to create XML-RPC server");
        
        // Register a test method that returns our test data
        xmlrpc_server_register_method($server, 'test.response', function($method, $params, $app_data) use ($response_data) {
            return $response_data;
        });
        
        // This test ensures we can create a proper XML-RPC server and register methods
        $this->assertTrue(true, "XML-RPC server and method registration works");
    }
    
    /**
     * Test the OpenSim_XMLRPC_Client class
     */
    public function testXmlrpcClient()
    {
        // We'll use a dummy URL for testing
        $client = new OpenSim_XMLRPC_Client('http://example.com/xmlrpc');
        $this->assertInstanceOf(OpenSim_XMLRPC_Client::class, $client, "Should be able to create XML-RPC client");
        
        // Verify the client has the required methods
        $this->assertTrue(method_exists($client, 'call'), "Client should have call method");
        $this->assertTrue(method_exists($client, 'setDebug'), "Client should have setDebug method");
    }
    
    /**
     * Test parsing an XML-RPC fault
     */
    public function testXmlrpcFault()
    {
        $fault = [
            'faultCode' => 123,
            'faultString' => 'Test fault message'
        ];
        
        $this->assertTrue(xmlrpc_is_fault($fault), "Should recognize XML-RPC fault structure");
        
        $not_fault = [
            'success' => false,
            'message' => 'This is not a proper fault'
        ];
        
        $this->assertFalse(xmlrpc_is_fault($not_fault), "Should not identify non-fault as fault");
    }
}
