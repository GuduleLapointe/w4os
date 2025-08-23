<?php
/**
 * Prerequisites Test - Must pass before running other tests
 * Tests fundamental requirements: DB connectivity and grid online status
 */

require_once __DIR__ . '/OpenSimHelpersTestCase.php';

class PrerequisitesTest extends OpenSimHelpersTestCase
{
    public function testDatabaseConnectivity()
    {
        $connection_string = Engine_Settings::get('robust.DatabaseService.ConnectionString');
        
        if (empty($connection_string)) {
            $this->fail('CRITICAL: Robust database connection string not configured');
        }
        
        // Test database connectivity using OpenSim::db()
        try {
            $db = OpenSim::db();
            $connected = $db->is_connected();
            
            if (!$connected) {
                $this->fail('CRITICAL: Cannot connect to Robust database');
            }
            
            $this->assertTrue($connected, 'Database connection successful');
        } catch (Exception $e) {
            $this->fail('CRITICAL: Database connection failed: ' . $e->getMessage());
        }
    }
    
    public function testGridOnlineStatus()
    {
        $login_uri = Engine_Settings::get('robust.GridInfoService.login');
        
        if (empty($login_uri)) {
            $this->fail('CRITICAL: Grid login URI not configured');
        }
        
        // Add http:// prefix if missing
        if (!preg_match('#^https?://#', $login_uri)) {
            $login_uri = 'http://' . $login_uri;
        }
        
        // Clean the URI
        // grid login URI can only be used to directly communicate with the grid API,
        // (which provides get_grid_info endpoint), not for user front-end pages.
        $grid_login_uri = rtrim($login_uri, '/');
        $grid_info_url = $grid_login_uri . '/get_grid_info';
        
        echo "\nTesting grid at: $grid_info_url";
        
        // Test get_grid_info endpoint
        $context = stream_context_create([
            'http' => [
                'timeout' => 10,
                'method' => 'GET'
            ]
        ]);

        $response = @file_get_contents($grid_info_url, false, $context);
        
        if ($response === false) {
            $this->fail('CRITICAL: Grid is not responding at ' . $grid_info_url);
        }
        
        // Parse XML response
        $xml = @simplexml_load_string($response);
        
        if ($xml === false) {
            $this->fail('CRITICAL: Grid returned invalid XML response');
        }
        
        $grid_name = (string)$xml->gridname ?? '';
        
        if (empty($grid_name)) {
            $this->fail('CRITICAL: Grid response missing gridname');
        }
        
        $this->assertTrue(true, 'Grid is online');
    }
}
