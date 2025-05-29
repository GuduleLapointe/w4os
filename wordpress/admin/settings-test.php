<?php
/**
 * Engine Settings Test Page
 * 
 * Temporary admin page to test the Engine_Settings functionality
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

class W4OS_Settings_Test_Page {
    
    public function __construct() {
        add_action('admin_menu', array($this, 'add_admin_menu'), 99); // High priority to ensure it loads after main menu
        add_action('admin_init', array($this, 'handle_form_submission'));
    }
    
    public function add_admin_menu() {
        $tool_icon = '<span class="dashicons dashicons-admin-tools"></span>';
        add_submenu_page(
            'w4os',                    // Parent menu slug
            'Engine Settings Test',     // Page title
            $tool_icon . ' Settings Test',    // Menu title with icon
            'manage_options',          // Capability
            'w4os-settings-test',      // Menu slug
            array($this, 'admin_page'), // Callback
        );
    }
    
    public function handle_form_submission() {
        if (!isset($_POST['w4os_settings_test_nonce']) || 
            !wp_verify_nonce($_POST['w4os_settings_test_nonce'], 'w4os_settings_test')) {
            return;
        }
        
        if (!current_user_can('manage_options')) {
            return;
        }
        
        // Handle different actions
        $action = $_POST['action'] ?? '';
        
        switch ($action) {
            case 'save_settings':
                $this->save_test_settings();
                break;
            case 'clear_settings':
                $this->clear_test_settings();
                break;
            case 'import_config_array':
                $this->import_config_array();
                break;
            case 'import_ini_file':
                $this->import_ini_file();
                break;
            case 'migrate_constants':
                $this->migrate_from_constants();
                break;
            case 'fix_arrays':
                $this->fix_array_values();
                break;
        }
    }
    
    private function save_test_settings() {
        $messages = array();
        
        // Test saving individual settings
        if (isset($_POST['database_host'])) {
            $success = Engine_Settings::set('DatabaseService.host', sanitize_text_field($_POST['database_host']));
            $messages[] = $success ? 'Database host saved' : 'Failed to save database host';
        }
        
        if (isset($_POST['database_name'])) {
            $success = Engine_Settings::set('DatabaseService.name', sanitize_text_field($_POST['database_name']));
            $messages[] = $success ? 'Database name saved' : 'Failed to save database name';
        }
        
        if (isset($_POST['console_user'])) {
            $success = Engine_Settings::set('Network.ConsoleUser', sanitize_text_field($_POST['console_user']));
            $messages[] = $success ? 'Console user saved' : 'Failed to save console user';
        }
        
        if (isset($_POST['grid_name'])) {
            $success = Engine_Settings::set('GridInfoService.gridname', sanitize_text_field($_POST['grid_name']));
            $messages[] = $success ? 'Grid name saved' : 'Failed to save grid name';
        }
        
        // Test saving a section
        if (isset($_POST['test_section'])) {
            $test_data = array(
                'test_key1' => 'Test Value 1',
                'test_key2' => 42,
                'test_key3' => true,
                'test_timestamp' => time(),
            );
            $success = Engine_Settings::set_section('TestSection', $test_data);
            $messages[] = $success ? 'Test section saved' : 'Failed to save test section';
        }
        
        foreach ($messages as $message) {
            add_settings_error('w4os_settings_test', 'save_result', $message, 'updated');
        }
    }
    
    private function clear_test_settings() {
        // Clear all settings for testing purposes
        $success = Engine_Settings::clear_all();
        
        if ($success) {
            add_settings_error('w4os_settings_test', 'clear_result', 'All settings cleared successfully', 'updated');
        } else {
            add_settings_error('w4os_settings_test', 'clear_result', 'Failed to clear settings', 'error');
        }
    }
    
    private function import_config_array() {
        // Sample OpenSim config for testing (based on your example)
        $sample_config = array(
            'Const' => array(
                'BaseURL' => 'http://dev.w4os.org',
                'PublicPort' => 8402,
                'PrivatePort' => 8403,
            ),
            'DatabaseService' => array(
                'ConnectionString' => 'Data Source=localhost;Database=w4osdemo_robust;User ID=opensim;Password=testpass;Old Guids=true;'
            ),
            'Network' => array(
                'ConsoleUser' => 'testuser',
                'ConsolePass' => 'testpass',
                'ConsolePort' => 8404,
            ),
            'GridInfoService' => array(
                'login' => 'http://dev.w4os.org:8402/',
                'economy' => 'http://dev.w4os.org/economy/',
                'gridname' => 'W4OS Test Grid',
            ),
            'LoginService' => array(
                'Currency' => 'WO$',
            ),
        );
        
        $success = Engine_Settings::import_from_opensim($sample_config);
        $message = $success ? 'Sample OpenSim config array imported successfully' : 'Failed to import OpenSim config array';
        add_settings_error('w4os_settings_test', 'import_result', $message, $success ? 'updated' : 'error');
    }
    
    private function import_ini_file() {
        // Path to the example .ini file
        $ini_file_path = W4OS_PLUGIN_DIR . 'tmp/example.engine.ini';
        
        if (!file_exists($ini_file_path)) {
            $message = 'Example INI file not found at: ' . $ini_file_path;
            add_settings_error('w4os_settings_test', 'import_result', $message, 'error');
            return;
        }
        
        // Check if OpenSim_Ini class is available
        if (!class_exists('OpenSim_Ini')) {
            $message = 'OpenSim_Ini class not found. Please check that helper classes are loaded.';
            add_settings_error('w4os_settings_test', 'import_result', $message, 'error');
            return;
        }
        
        try {
            $success = Engine_Settings::import_from_ini_file($ini_file_path);
            
            if ($success) {
                // Check if the imported settings contain missing constant markers
                $all_settings = Engine_Settings::all();
                $missing_constants = array();
                
                array_walk_recursive($all_settings, function($value, $key) use (&$missing_constants) {
                    if (is_string($value) && preg_match_all('/\[MISSING_CONSTANT:([^]]+)\]/', $value, $matches)) {
                        foreach ($matches[1] as $constant) {
                            $missing_constants[] = $constant;
                        }
                    }
                });
                
                if (!empty($missing_constants)) {
                    $missing_list = array_unique($missing_constants);
                    $message = 'INI file imported with warnings. Missing constants: ' . implode(', ', $missing_list) . 
                               '. Check settings for [MISSING_CONSTANT:...] markers.';
                    add_settings_error('w4os_settings_test', 'import_result', $message, 'notice-warning');
                } else {
                    $message = 'OpenSim INI file imported successfully from: ' . basename($ini_file_path);
                    add_settings_error('w4os_settings_test', 'import_result', $message, 'updated');
                }
            } else {
                $message = 'Failed to import OpenSim INI file. Check error logs for details.';
                add_settings_error('w4os_settings_test', 'import_result', $message, 'error');
            }
            
        } catch (Exception $e) {
            $message = 'Exception during INI import: ' . $e->getMessage();
            add_settings_error('w4os_settings_test', 'import_result', $message, 'error');
        }
    }
    
    private function migrate_from_constants() {
        // Get current PHP constants for migration
        $constants = Engine_Settings::get_current_constants();
        
        if (empty($constants)) {
            $message = 'No OpenSim/helpers constants found to migrate. Constants should start with: OPENSIM_, ROBUST_, CURRENCY_, SEARCH_, OFFLINE_, GLOEBIT_, PODEX_, HYPEVENTS_, EVENTS_';
            add_settings_error('w4os_settings_test', 'migrate_result', $message, 'notice-warning');
            return;
        }
        
        try {
            $success = Engine_Settings::migrate_from_constants($constants);
            
            if ($success) {
                $constant_count = count($constants);
                $message = "Successfully migrated {$constant_count} PHP constants to INI format. Constants found: " . implode(', ', array_keys($constants));
                add_settings_error('w4os_settings_test', 'migrate_result', $message, 'updated');
            } else {
                $message = 'Failed to migrate PHP constants to INI format. Check error logs for details.';
                add_settings_error('w4os_settings_test', 'migrate_result', $message, 'error');
            }
            
        } catch (Exception $e) {
            $message = 'Exception during constants migration: ' . $e->getMessage();
            add_settings_error('w4os_settings_test', 'migrate_result', $message, 'error');
        }
    }
    
    private function fix_array_values() {
        try {
            $success = Engine_Settings::fix_array_values();
            
            if ($success) {
                $message = 'Successfully fixed array values that were saved as "Array" strings';
                add_settings_error('w4os_settings_test', 'fix_result', $message, 'updated');
            } else {
                $message = 'No array values needed fixing, or operation failed';
                add_settings_error('w4os_settings_test', 'fix_result', $message, 'notice-info');
            }
            
        } catch (Exception $e) {
            $message = 'Exception during array fix: ' . $e->getMessage();
            add_settings_error('w4os_settings_test', 'fix_result', $message, 'error');
        }
    }
    
    public function admin_page() {
        ?>
        <div class="wrap">
            <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
            
            <?php settings_errors('w4os_settings_test'); ?>
            
            <div class="notice notice-info">
                <p><strong>This is a temporary test page for the Engine Settings system.</strong></p>
                <p>Settings file location: <code><?php echo esc_html(Engine_Settings::get_file_path()); ?></code></p>
                <p>Configuration status: 
                    <span class="<?php echo Engine_Settings::is_configured() ? 'text-success' : 'text-warning'; ?>">
                        <?php echo Engine_Settings::is_configured() ? 'Configured' : 'Not configured'; ?>
                    </span>
                </p>
            </div>
            
            <div class="w4os-settings-layout">
                <div class="w4os-main-content">
            
            <!-- Test Form -->
            <form method="post">
                <?php wp_nonce_field('w4os_settings_test', 'w4os_settings_test_nonce'); ?>
                
                <div class="card">
                    <h2>Test Individual Settings</h2>
                    <div class="inside">
                        <table class="form-table">
                            <tr>
                                <th><label for="database_host">Database Host</label></th>
                                <td>
                                    <input type="text" id="database_host" name="database_host" 
                                           value="<?php echo esc_attr(Engine_Settings::get('DatabaseService.host', 'localhost')); ?>" 
                                           class="regular-text" />
                                </td>
                            </tr>
                            <tr>
                                <th><label for="database_name">Database Name</label></th>
                                <td>
                                    <input type="text" id="database_name" name="database_name" 
                                           value="<?php echo esc_attr(Engine_Settings::get('DatabaseService.name', '')); ?>" 
                                           class="regular-text" />
                                </td>
                            </tr>
                            <tr>
                                <th><label for="console_user">Console User</label></th>
                                <td>
                                    <input type="text" id="console_user" name="console_user" 
                                           value="<?php echo esc_attr(Engine_Settings::get('Network.ConsoleUser', '')); ?>" 
                                           class="regular-text" />
                                </td>
                            </tr>
                            <tr>
                                <th><label for="grid_name">Grid Name</label></th>
                                <td>
                                    <input type="text" id="grid_name" name="grid_name" 
                                           value="<?php echo esc_attr(Engine_Settings::get('GridInfoService.gridname', '')); ?>" 
                                           class="regular-text" />
                                </td>
                            </tr>
                        </table>
                        
                        <p>
                            <input type="hidden" name="action" value="save_settings" />
                            <input type="submit" class="button-primary" value="Save Test Settings" />
                        </p>
                    </div>
                </div>
            </form>
            
            <!-- Test Section Save -->
            <form method="post">
                <?php wp_nonce_field('w4os_settings_test', 'w4os_settings_test_nonce'); ?>
                <div class="card">
                    <h2>Test Section Save</h2>
                    <div class="inside">
                        <p>This will save a test section with various data types (string, number, boolean, timestamp).</p>
                        <p>
                            <input type="hidden" name="action" value="save_settings" />
                            <input type="hidden" name="test_section" value="1" />
                            <input type="submit" class="button" value="Save Test Section" />
                        </p>
                    </div>
                </div>
            </form>
            
            <!-- Import OpenSim Config -->
            <form method="post">
                <?php wp_nonce_field('w4os_settings_test', 'w4os_settings_test_nonce'); ?>
                <div class="card">
                    <h2>Test OpenSim Config Import</h2>
                    <div class="inside">
                        <p>Test importing OpenSim configuration in different formats:</p>
                        
                        <p>
                            <input type="hidden" name="action" value="import_config_array" />
                            <input type="submit" class="button" value="Import Sample Config Array" />
                            <small> - Tests importing from a PHP array (simulated config)</small>
                        </p>
                    </div>
                </div>
            </form>
            
            <!-- Import Real INI File -->
            <form method="post">
                <?php wp_nonce_field('w4os_settings_test', 'w4os_settings_test_nonce'); ?>
                <div class="card">
                    <h2>Test Real INI File Import</h2>
                    <div class="inside">
                        <p>Import from <code>tmp/example.engine.ini</code> file:</p>
                        <p>File status: 
                            <?php 
                            $ini_file_path = W4OS_PLUGIN_DIR . 'tmp/example.engine.ini';
                            if (file_exists($ini_file_path)) {
                                echo '<span style="color: green;">✅ File exists</span>';
                                echo '<br><small>File size: ' . filesize($ini_file_path) . ' bytes</small>';
                                echo '<br><small>Last modified: ' . date('Y-m-d H:i:s', filemtime($ini_file_path)) . '</small>';
                            } else {
                                echo '<span style="color: red;">❌ File not found at: ' . esc_html($ini_file_path) . '</span>';
                            }
                            ?>
                        </p>
                        <p>
                            <input type="hidden" name="action" value="import_ini_file" />
                            <input type="submit" class="button" value="Import Real INI File" 
                                   <?php echo file_exists($ini_file_path) ? '' : 'disabled'; ?> />
                        </p>
                    </div>
                </div>
            </form>
            
            <!-- Migrate PHP Constants -->
            <form method="post">
                <?php wp_nonce_field('w4os_settings_test', 'w4os_settings_test_nonce'); ?>
                <div class="card">
                    <h2>Test PHP Constants Migration</h2>
                    <div class="inside">
                        <p>Migrate existing PHP constants to INI format:</p>
                        <?php 
                        $current_constants = Engine_Settings::get_current_constants();
                        if (!empty($current_constants)) {
                            echo '<p>Found ' . count($current_constants) . ' constants to migrate:</p>';
                            echo '<div style="background: #f0f0f0; padding: 10px; max-height: 200px; overflow-y: auto; margin: 10px 0;">';
                            foreach ($current_constants as $name => $value) {
                                echo '<strong>' . esc_html($name) . '</strong> = ';
                                if (is_array($value)) {
                                    echo '<em>Array (' . count($value) . ' items)</em>';
                                } elseif (is_bool($value)) {
                                    echo $value ? 'true' : 'false';
                                } elseif (is_string($value) && strlen($value) > 50) {
                                    echo esc_html(substr($value, 0, 47)) . '...';
                                } else {
                                    echo esc_html($value);
                                }
                                echo '<br>';
                            }
                            echo '</div>';
                        } else {
                            echo '<p><em>No OpenSim/helpers constants found in current environment.</em></p>';
                            echo '<p><small>Constants should start with: OPENSIM_, ROBUST_, CURRENCY_, SEARCH_, OFFLINE_, GLOEBIT_, PODEX_, HYPEVENTS_, EVENTS_</small></p>';
                        }
                        ?>
                        <p>
                            <input type="hidden" name="action" value="migrate_constants" />
                            <input type="submit" class="button" value="Migrate PHP Constants" 
                                   <?php echo !empty($current_constants) ? '' : 'disabled'; ?> />
                        </p>
                    </div>
                </div>
            </form>
            
            <!-- Fix Array Values -->
            <form method="post">
                <?php wp_nonce_field('w4os_settings_test', 'w4os_settings_test_nonce'); ?>
                <div class="card">
                    <h2>Fix Array Values</h2>
                    <div class="inside">
                        <p>Fix any array values that were incorrectly saved as "Array" strings:</p>
                        <p><small>This will fix GLOEBIT_CONVERSION_TABLE and other arrays that show as "Array" instead of proper JSON.</small></p>
                        <p>
                            <input type="hidden" name="action" value="fix_arrays" />
                            <input type="submit" class="button" value="Fix Array Values" />
                        </p>
                    </div>
                </div>
            </form>
            
            </div> <!-- End main content -->
            
            <div class="w4os-sidebar">
                <!-- Current Settings Display -->
                <div class="card current-settings">
                    <h2>Current Settings</h2>
                    <div class="inside">
                        <?php
                        $all_settings = Engine_Settings::all();
                        if (empty($all_settings)) {
                            echo '<p><em>No settings found. The configuration file will be created when you save settings.</em></p>';
                        } else {
                            echo '<pre style="background: #f0f0f0; padding: 10px; overflow-x: auto;">';
                            echo esc_html(print_r($all_settings, true));
                            echo '</pre>';
                        }
                        ?>
                    </div>
                </div>
            </div> <!-- End sidebar -->
            
            </div> <!-- End layout wrapper -->
            
            <!-- Clear Settings -->
            <form method="post">
                <?php wp_nonce_field('w4os_settings_test', 'w4os_settings_test_nonce'); ?>
                <div class="card">
                    <h2>Clear All Settings</h2>
                    <div class="inside">
                        <p><strong>Warning:</strong> This will clear ALL engine settings, not just test data.</p>
                        <p>
                            <input type="hidden" name="action" value="clear_settings" />
                            <input type="submit" class="button button-secondary" value="Clear All Settings" 
                                   onclick="return confirm('Are you sure you want to clear ALL settings? This cannot be undone.')" />
                        </p>
                    </div>
                </div>
            </form>
<!-- File System Info -->
                <div class="card">
                    <h2>File System Information</h2>
                    <!-- <div class="inside"> -->
                        <table class="widefat" style="border: none; margin: 5px;">
                            <tr>
                                <th>Config Directory</th>
                                <td><code><?php echo esc_html(Engine_Settings::get_config_dir()); ?></code></td>
                                <td><?php echo is_dir(Engine_Settings::get_config_dir()) ? '✅ Exists' : '❌ Missing'; ?></td>
                            </tr>
                            <tr>
                                <th>Settings File</th>
                                <td><code><?php echo esc_html(Engine_Settings::get_file_path()); ?></code></td>
                                <td><?php echo file_exists(Engine_Settings::get_file_path()) ? '✅ Exists' : '❌ Not created yet'; ?></td>
                            </tr>
                            <tr>
                                <th>.htaccess Protection</th>
                                <td><code><?php echo esc_html(Engine_Settings::get_config_dir() . '/.htaccess'); ?></code></td>
                                <td><?php echo file_exists(Engine_Settings::get_config_dir() . '/.htaccess') ? '✅ Protected' : '❌ Not protected'; ?></td>
                            </tr>
                            <tr>
                                <th>Directory Permissions</th>
                                <td colspan="2">
                                    <?php 
                                    if (is_dir(Engine_Settings::get_config_dir())) {
                                        $perms = substr(sprintf('%o', fileperms(Engine_Settings::get_config_dir())), -4);
                                        echo "0{$perms} ";
                                        echo ($perms === '0700') ? '✅ Secure (owner only)' : '⚠️ Consider setting to 0700';
                                    } else {
                                        echo 'Directory not created yet';
                                    }
                                    ?>
                                </td>
                            </tr>
                            <tr>
                                <th>OpenSim_Ini Class</th>
                                <td colspan="2">
                                    <?php 
                                    if (class_exists('OpenSim_Ini')) {
                                        echo '✅ Available';
                                        $ini_class_file = dirname(W4OS_PLUGIN_DIR) . '/helpers/classes/class-ini.php';
                                        if (file_exists($ini_class_file)) {
                                            echo '<br><small>Loaded from: ' . esc_html($ini_class_file) . '</small>';
                                        }
                                    } else {
                                        echo '❌ Not available';
                                        $expected_path = dirname(W4OS_PLUGIN_DIR) . '/helpers/classes/class-ini.php';
                                        echo '<br><small>Expected at: ' . esc_html($expected_path) . '</small>';
                                        echo '<br><small>Exists: ' . (file_exists($expected_path) ? 'Yes' : 'No') . '</small>';
                                    }
                                    ?>
                                </td>
                            </tr>
                            <tr>
                                <th>OpenSim_Error Class</th>
                                <td colspan="2">
                                    <?php 
                                    echo class_exists('OpenSim_Error') ? '✅ Available' : '❌ Not available';
                                    ?>
                                </td>
                            </tr>
                        </table>
                    <!-- </div> -->
                </div>
            </div>
        
        <style>
        .text-success { color: #46b450; font-weight: bold; }
        .text-warning { color: #ffb900; font-weight: bold; }
        .card { margin: 20px 0; max-width: 100%; padding: 0; }
        .card h2 { margin-top: 0; padding: 10px 15px; background: #f1f1f1; margin-bottom: 0; }
        .card .inside { padding: 0 15px; }
        
        /* Override WordPress admin width constraints */
        .wrap {
            max-width: none !important;
        }
        
        .form-table {
            max-width: none !important;
        }
        
        .form-table th,
        .form-table td {
            width: auto !important;
        }
        
        .regular-text {
            width: 100% !important;
            max-width: none !important;
        }
        
        /* Two-column layout */
        .w4os-settings-layout {
            display: flex;
            gap: 20px;
            margin-top: 20px;
            width: 100%;
        }
        
        .w4os-main-content {
            flex: 1;
            min-width: 0; /* Allows flex item to shrink */
        }
        
        .w4os-sidebar {
            flex: 1;
            min-width: 0;
        }
        
        .w4os-sidebar .current-settings {
            position: sticky;
            top: 32px; /* Account for WP admin bar */
        }
        
        .w4os-sidebar pre {
            white-space: pre-wrap;
            word-wrap: break-word;
        }
        
        /* Make cards use full width */
        .w4os-main-content .card,
        .w4os-sidebar .card {
            width: 100%;
            box-sizing: border-box;
        }
        
        /* Responsive: stack on smaller screens */
        @media (max-width: 1024px) {
            .w4os-settings-layout {
                flex-direction: column;
            }
            
            .w4os-sidebar .card {
                position: static;
            }
        }
        </style>
        <?php
    }
}

// Initialize the test page
new W4OS_Settings_Test_Page();
