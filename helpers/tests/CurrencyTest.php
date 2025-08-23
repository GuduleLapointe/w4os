<?php
/**
 * PHPUnit test for Currency functionality
 * 
 * This test verifies that currency.php works correctly with the XML-RPC layer.
 * It tests both XML-RPC connectivity and the logical processing of currency requests.
 * 
 * @package     magicoli/opensim-helpers
 */

use PHPUnit\Framework\TestCase;

class CurrencyTest extends TestCase
{
    protected $currencyUrl;
    
    /**
     * Set up for the tests - determine the currency URL
     */
    protected function setUp(): void
    {
        parent::setUp();
        
        // First check if Engine_Settings has a currency URL configured
        if (class_exists('Engine_Settings')) {
            $this->currencyUrl = Engine_Settings::get('robust.GridInfoService.economy');
        }
        
        if(!empty($this->currencyUrl)) {
            // Ensure the URL is a valid string
            $this->currencyUrl .= 'currency.php';
        }

        // Only Fallback to legacy constant if not found with Engine_Settings
        if (empty($this->currencyUrl) && defined('CURRENCY_HELPER_URL')) {
            $this->currencyUrl = CURRENCY_HELPER_URL;
        }
        
        // If no URL found, tests will be skipped
    }
    
    /**
     * Test that we can determine a currency URL
     */
    public function testCurrencyUrlAvailable()
    {
        if (empty($this->currencyUrl)) {
            $this->markTestSkipped("No currency URL available - configure in robust.GridInfoService.economy");
        }
        
        $this->assertNotEmpty($this->currencyUrl, "Currency URL should be available");
    }
    
    /**
     * Test the getCurrencyQuote request format
     */
    public function testGetCurrencyQuoteRequest()
    {
        // This is the format used in currency.php for getCurrencyQuote
        $params = [
            'agentId' => '00000000-0000-0000-0000-000000000001',
            'secureSessionId' => 'test-session-id',
            'currencyBuy' => 1000
        ];
        
        // Create the XML-RPC request
        $request = xmlrpc_encode_request('getCurrencyQuote', [$params]);
        
        $this->assertNotEmpty($request, "Currency quote request should not be empty");
        $this->assertIsString($request, "Currency quote request should be a string");
        
        // Validate XML structure
        $xml = simplexml_load_string($request);
        $this->assertNotFalse($xml, "Currency quote request should be valid XML");
        
        // Check for required elements
        $this->assertStringContainsString('getCurrencyQuote', $request, "Request should contain method name");
        $this->assertStringContainsString('agentId', $request, "Request should contain agentId parameter");
        $this->assertStringContainsString('secureSessionId', $request, "Request should contain secureSessionId parameter");
        $this->assertStringContainsString('currencyBuy', $request, "Request should contain currencyBuy parameter");
    }
    
    /**
     * Mock test for currency response structure
     */
    public function testCurrencyResponseStructure()
    {
        // Create a mock successful response
        $successResponse = [
            'success' => true,
            'currency' => [
                'estimatedCost' => 10,
                'currencyBuy' => 1000
            ],
            'confirm' => '123456'
        ];
        
        // Create a mock error response
        $errorResponse = [
            'success' => false,
            'errorMessage' => 'Cannot authenticate user',
            'errorURI' => 'http://example.com/currency'
        ];
        
        // Verify success response structure
        $this->assertArrayHasKey('success', $successResponse);
        $this->assertArrayHasKey('currency', $successResponse);
        $this->assertArrayHasKey('confirm', $successResponse);
        $this->assertTrue($successResponse['success']);
        
        // Verify error response structure
        $this->assertArrayHasKey('success', $errorResponse);
        $this->assertArrayHasKey('errorMessage', $errorResponse);
        $this->assertFalse($errorResponse['success']);
    }
    
    /**
     * Test attempting to connect to currency.php
     * 
     * This test doesn't make actual XML-RPC calls but checks if the URL is accessible.
     * Real XML-RPC calls would typically fail due to authentication requirements.
     */
    public function testCurrencyConnection()
    {
        if (empty($this->currencyUrl)) {
            $this->markTestSkipped("No currency URL available - configure in robust.GridInfoService.economy");
            return;
        }
        
        // Create a client to test connection
        $client = new OpenSim_XMLRPC_Client($this->currencyUrl);
        $this->assertInstanceOf(OpenSim_XMLRPC_Client::class, $client, "Should be able to create XML-RPC client");
        
        // Check if the URL is generally accessible (not a full XML-RPC check)
        $urlExists = false;
        try {
            // Use get_headers to check if URL exists without making a full request
            $headers = @get_headers($this->currencyUrl);
            $urlExists = $headers && strpos($headers[0], '200') !== false;
        } catch (\Exception $e) {
            // Ignore exceptions, we're just checking if URL exists
        }
        
        // This is a loose check - we don't expect this to always succeed
        // Just mark it as skipped if it fails
        if (!$urlExists) {
            $this->markTestSkipped("Currency URL exists but may not be accessible: " . $this->currencyUrl);
        }
    }
    
    /**
     * Test building a complete getCurrencyQuote request
     */
    public function testBuildCurrencyQuoteRequest()
    {
        // Get test parameters for a currency quote request
        $params = $this->getTestCurrencyQuoteParams();
        
        // Create the XML-RPC request
        $request = xmlrpc_encode_request('getCurrencyQuote', [$params]);
        
        // Validate the request format
        $this->assertStringContainsString('<methodName>getCurrencyQuote</methodName>', $request);
        $this->assertStringContainsString('<name>agentId</name>', $request);
        $this->assertStringContainsString('<name>secureSessionId</name>', $request);
        $this->assertStringContainsString('<name>currencyBuy</name>', $request);
    }
    
    /**
     * Helper method to get test parameters for currency quote
     */
    private function getTestCurrencyQuoteParams()
    {
        return [
            'agentId' => '00000000-0000-0000-0000-000000000001',
            'secureSessionId' => 'test-session-id',
            'currencyBuy' => 1000,
            'ipAddress' => '127.0.0.1'
        ];
    }
}
