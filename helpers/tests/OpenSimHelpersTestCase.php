<?php
/**
 * Base Test Case for OpenSim Helpers Tests
 */

use PHPUnit\Framework\TestCase;

abstract class OpenSimHelpersTestCase extends TestCase
{
    protected static $helpersBaseUrl;
    protected static $gatekeeperUrl;
    
    public static function setUpBeforeClass(): void
    {
        // Get helpers base URL using the proper method
        self::$helpersBaseUrl = Helpers::url();
        
        // Get grid login URI for grid connectivity tests
        $login_uri = Engine_Settings::get('robust.GridInfoService.login');
        
        if (empty($login_uri)) {
            throw new Exception('CRITICAL: Grid login URI not configured');
        }
        
        // Add http:// prefix if missing
        if (!preg_match('#^https?://#', $login_uri)) {
            $login_uri = 'http://' . $login_uri;
        }
        
        self::$gatekeeperUrl = $login_uri;
    }
    
    /**
     * Send HTTP request to helpers script
     */
    protected function sendRequest($script, $data = '', $method = 'POST', $headers = [])
    {
        $url = Helpers::url($script);
        
        $context_options = [
            'http' => [
                'method' => $method,
                'timeout' => 30,
                'ignore_errors' => true,
            ]
        ];
        
        if (!empty($data)) {
            $context_options['http']['content'] = $data;
        }
        
        if (!empty($headers)) {
            $context_options['http']['header'] = implode("\r\n", $headers);
        }
        
        $context = stream_context_create($context_options);
        $response = file_get_contents($url, false, $context);
        
        // Get response headers
        $response_headers = [];
        if (isset($http_response_header)) {
            $response_headers = $http_response_header;
        }
        
        return [
            'body' => $response,
            'headers' => $response_headers,
            'url' => $url
        ];
    }
    
    /**
     * Send XMLRPC request
     */
    protected function sendXmlRpcRequest($script, $method, $params)
    {
        $xml = xmlrpc_encode_request($method, $params);
        
        $response = $this->sendRequest($script, $xml, 'POST', [
            'Content-Type: text/xml',
            'Content-Length: ' . strlen($xml)
        ]);
        
        return $response;
    }
    
    /**
     * Parse XMLRPC response
     */
    protected function parseXmlRpcResponse($response_body)
    {
        if (empty($response_body)) {
            return null;
        }
        
        // Handle malformed responses
        if (!preg_match('/^<\?xml/', trim($response_body))) {
            return null;
        }
        
        return xmlrpc_decode($response_body);
    }
    
    /**
     * Assert response is valid XMLRPC
     */
    protected function assertValidXmlRpcResponse($response, $message = '')
    {
        $this->assertNotEmpty($response['body'], 'Response body should not be empty' . ($message ? ': ' . $message : ''));
        
        $decoded = $this->parseXmlRpcResponse($response['body']);
        $this->assertNotNull($decoded, 'Response should be valid XMLRPC' . ($message ? ': ' . $message : ''));
        
        return $decoded;
    }
    
    /**
     * Assert string contains substring (PHPUnit compatibility)
     */
    protected function assertStringContains($needle, $haystack, $message = '')
    {
        $this->assertNotFalse(strpos($haystack, $needle), $message ?: "Failed asserting that '$haystack' contains '$needle'");
    }
    
    /**
     * Assert string does not contain substring (PHPUnit compatibility)
     */
    protected function assertStringNotContains($needle, $haystack, $message = '')
    {
        $this->assertFalse(strpos($haystack, $needle), $message ?: "Failed asserting that '$haystack' does not contain '$needle'");
    }
    
    /**
     * Assert response indicates success
     */
    protected function assertSuccessfulResponse($decoded, $message = '')
    {
        $this->assertIsArray($decoded, 'Response should be an array' . ($message ? ': ' . $message : ''));
        $this->assertArrayHasKey('success', $decoded, 'Response should have success field' . ($message ? ': ' . $message : ''));
        $this->assertTrue($decoded['success'], 'Response should indicate success' . ($message ? ': ' . $message : ''));
    }
    
    /**
     * Assert response indicates failure
     */
    protected function assertFailedResponse($decoded, $message = '')
    {
        $this->assertIsArray($decoded, 'Response should be an array' . ($message ? ': ' . $message : ''));
        $this->assertArrayHasKey('success', $decoded, 'Response should have success field' . ($message ? ': ' . $message : ''));
        $this->assertFalse($decoded['success'], 'Response should indicate failure' . ($message ? ': ' . $message : ''));
    }
    
    /**
     * Generate test UUID
     */
    protected function generateTestUuid()
    {
        return sprintf(
            '%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
            mt_rand(0, 0xffff), mt_rand(0, 0xffff),
            mt_rand(0, 0xffff),
            mt_rand(0, 0x0fff) | 0x4000,
            mt_rand(0, 0x3fff) | 0x8000,
            mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff)
        );
    }
    
    /**
     * Skip test if service not configured
     */
    protected function skipIfNotConfigured($config_key, $service_name)
    {
        if (!Engine_Settings::get($config_key)) {
            $this->markTestSkipped("$service_name not configured (missing $config_key)");
        }
    }
}
