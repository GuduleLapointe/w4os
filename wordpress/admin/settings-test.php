<?php
/**
 * Engine Settings Test Page
 * 
 * Temporary test page to test the Engine_Settings functionality
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
            case 'migrate_wordpress_options':
                $this->migrate_wordpress_options();
                break;
            case 'test_constants_migration':
                $this->test_constants_migration();
                break;
            case 'perform_constants_migration':
                $this->perform_constants_migration();
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
        $ini_file_path = W4OS_PLUGIN_DIR . 'tmp/example.source.ini';
        
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
            // Create an instance of OpenSim_Ini with the file path
            $ini = new OpenSim_Ini($ini_file_path);
            if($ini) {
                $ini_config = $ini->get_config();
            } else {
                throw new Exception('Failed to load INI file');
            }
            if( ! $ini_config ?? false) {
                throw new Exception('Failed to parse INI file');    
            }
        } catch (Exception $e) {
            $message = 'Error parsing INI file: ' . $e->getMessage();
            add_settings_error('w4os_settings_test', 'import_result', $message, 'error');
            return;
        }
        
        error_log(__METHOD__ . '() [DEBUG] Parsed INI file: ' . print_r($ini_config ?? [ 'empty' ], true));

        // try {
        //     $success = Engine_Settings::import_from_ini_file($ini_file_path);
            
        //     if ($success) {
        //         // Check if the imported settings contain missing constant markers
        //         $all_settings = Engine_Settings::all();
        //         $missing_constants = array();
                
        //         array_walk_recursive($all_settings, function($value, $key) use (&$missing_constants) {
        //             if (is_string($value) && preg_match_all('/\[MISSING_CONSTANT:([^]]+)\]/', $value, $matches)) {
        //                 foreach ($matches[1] as $constant) {
        //                     $missing_constants[] = $constant;
        //                 }
        //             }
        //         });
                
        //         if (!empty($missing_constants)) {
        //             $missing_list = array_unique($missing_constants);
        //             $message = 'INI file imported with warnings. Missing constants: ' . implode(', ', $missing_list) . 
        //                        '. Check settings for [MISSING_CONSTANT:...] markers.';
        //             add_settings_error('w4os_settings_test', 'import_result', $message, 'notice-warning');
        //         } else {
        //             $message = 'OpenSim INI file imported successfully from: ' . basename($ini_file_path);
        //             add_settings_error('w4os_settings_test', 'import_result', $message, 'updated');
        //         }
        //     } else {
        //         $message = 'Failed to import OpenSim INI file. Check error logs for details.';
        //         add_settings_error('w4os_settings_test', 'import_result', $message, 'error');
        //     }
            
        // } catch (Exception $e) {
        //     $message = 'Exception during INI import: ' . $e->getMessage();
        //     add_settings_error('w4os_settings_test', 'import_result', $message, 'error');
        // }
    }
    
    private function migrate_wordpress_options() {
        try {
            $result = W4OS_Migration_2to3::migrate_wordpress_options();
            
            if (!empty($result['migrated']) || !empty($result['skipped'])) {
                $message = 'WordPress options migration completed!';
                if (!empty($result['migrated'])) {
                    $message .= ' Migrated: ' . count($result['migrated']) . ' options.';
                }
                if (!empty($result['skipped'])) {
                    $message .= ' Skipped: ' . count($result['skipped']) . ' options.';
                }
                if (!empty($result['errors'])) {
                    $message .= ' Errors: ' . count($result['errors']) . ' options.';
                }
                add_settings_error('w4os_settings_test', 'migrate_wp_result', $message, 'updated');
                
                // Show details
                if (!empty($result['migrated'])) {
                    $details = 'Migrated options: ' . implode(', ', array_slice($result['migrated'], 0, 10));
                    if (count($result['migrated']) > 10) {
                        $details .= ' and ' . (count($result['migrated']) - 10) . ' more...';
                    }
                    add_settings_error('w4os_settings_test', 'migrate_wp_details', $details, 'notice-info');
                }
            } else {
                $message = 'No WordPress options found to migrate. Make sure you have w4os_* options in your database.';
                add_settings_error('w4os_settings_test', 'migrate_wp_result', $message, 'notice-warning');
            }
            
        } catch (Exception $e) {
            $message = 'Exception during WordPress options migration: ' . $e->getMessage();
            add_settings_error('w4os_settings_test', 'migrate_wp_result', $message, 'error');
        }
    }

    /**
     * Test constants migration (debug only - shows what would be migrated)
     */
    private function test_constants_migration() {
        if (!class_exists('Helpers_Migration_2to3')) {
            require_once OPENSIM_ENGINE_PATH . '/helpers/includes/helpers-migration-v2to3.php';
        }
        
        // Get constants that were added after plugin loading started (no filtering)
        $internal_constants = $this->get_internal_constants();
        
        // Get the mapping to see which constants would be processed
        $mapping = $this->get_constants_mapping_for_debug();
        $mapped_constants = $this->extract_all_constant_names_from_mapping($mapping);
        
        echo '<div class="notice notice-info"><p>Found ' . count($internal_constants) . ' new constants. ' . count($mapped_constants) . ' are in the migration mapping.</p></div>';
        
        // Group constants by status
        $will_migrate = array();
        $not_in_mapping = array();
        
        foreach ($internal_constants as $name => $value) {
            if (in_array($name, $mapped_constants)) {
                $will_migrate[$name] = $value;
            } else {
                $not_in_mapping[$name] = $value;
            }
        }
        
        // Show constants that will be migrated
        echo '<h3 style="color: green;">✓ Constants that WILL be migrated (' . count($will_migrate) . ')</h3>';
        if (!empty($will_migrate)) {
            echo '<div class="table-container" style="background: #f0f8f0; border-left: 4px solid #28a745; padding: 10px; margin: 10px 0;">';
            echo '<table class="widefat striped">';
            echo '<thead><tr><th>Constant Name</th><th>Current Value</th><th>Type</th></tr></thead><tbody>';
            foreach ($will_migrate as $name => $value) {
                $display_value = $this->format_value_for_display($value);
                $type = gettype($value);
                echo "<tr><td><strong>{$name}</strong></td><td>{$display_value}</td><td>{$type}</td></tr>";
            }
            echo '</tbody></table></div>';
        }
        
        // Show constants that won't be migrated
        echo '<h3 style="color: #888;">⚪ Constants NOT in migration mapping (' . count($not_in_mapping) . ')</h3>';

        echo '<p>Make sure to check if any of the constants below are important for your application and should be added to the mapping.
        In theory, most of them would be system or framework constants and can be safely ignored.</p>';
        echo '<div style="background: #f8f8f8; border-left: 4px solid #888; padding: 10px; margin: 10px 0;">';
        
        if (!empty($not_in_mapping)) {
            // Apply filtering to remove unwanted constants from the display
            $filtered_constants = $this->filter_unprocessed_constants($not_in_mapping);
            
            echo '<p><strong>User/Project constants (' . count($filtered_constants) . '):</strong></p>';
            if (!empty($filtered_constants)) {
                echo '<div class="table-container">';
                echo '<table class="widefat striped">';
                echo '<thead><tr><th>Constant Name</th><th>Current Value</th><th>Type</th></tr></thead><tbody>';
                
                foreach ($filtered_constants as $name => $value) {
                    $display_value = $this->format_value_for_display($value);
                    $type = gettype($value);
                    echo "<tr><td>{$name}</td><td>{$display_value}</td><td>{$type}</td></tr>";
                }
                echo '</tbody></table>';
                echo '</div>'; // Close table container
            } else {
                echo '<p><em>All user constants are already in the migration mapping or filtered out!</em></p>';
            }
            
            // Show filtered constants count
            $filtered_count = count($not_in_mapping) - count($filtered_constants);
            if ($filtered_count > 0) {
                echo '<p><small>(' . $filtered_count . ' constants filtered out: W4OS_* constants, duplicates, or individual DB credentials)</small></p>';
            }
        } else {
            echo '<p><em>All new constants are in the migration mapping! It\'s a little bit worrying, there must be at least a few system or framework constants here.</em></p>';
        }
        
        echo '</div>';
    }

    /**
     * Actually perform constants migration
     */
    private function perform_constants_migration() {
        try {
            $result = Helpers_Migration_2to3::migrate_constants();
            
            if (!empty($result['migrated']) || !empty($result['skipped'])) {
                $message = 'Constants migration completed!';
                if (!empty($result['migrated'])) {
                    $message .= ' Migrated: ' . count($result['migrated']) . ' constants.';
                }
                if (!empty($result['skipped'])) {
                    $message .= ' Skipped: ' . count($result['skipped']) . ' constants.';
                }
                if (!empty($result['errors'])) {
                    $message .= ' Errors: ' . count($result['errors']) . ' constants.';
                }
                add_settings_error('w4os_settings_test', 'migrate_result', $message, 'updated');
                
                // Show details
                if (!empty($result['migrated'])) {
                    $details = 'Migrated: ' . implode(', ', array_slice($result['migrated'], 0, 10));
                    if (count($result['migrated']) > 10) {
                        $details .= ' and ' . (count($result['migrated']) - 10) . ' more...';
                    }
                    add_settings_error('w4os_settings_test', 'migrate_details', $details, 'notice-info');
                }
                
                // Show any errors
                if (!empty($result['errors'])) {
                    foreach ($result['errors'] as $error) {
                        add_settings_error('w4os_settings_test', 'migrate_error', 'Error: ' . $error, 'error');
                    }
                }
            } else {
                $message = 'No constants found to migrate or migration failed.';
                add_settings_error('w4os_settings_test', 'migrate_result', $message, 'notice-warning');
            }
            
        } catch (Exception $e) {
            $message = 'Exception during constants migration: ' . $e->getMessage();
            add_settings_error('w4os_settings_test', 'migrate_result', $message, 'error');
        }
    }

    /**
     * Get constants mapping for debug purposes
     */
    private function get_constants_mapping_for_debug() {
        // Access the mapping from Helpers_Migration_2to3
        $reflection = new ReflectionClass('Helpers_Migration_2to3');
        $property = $reflection->getProperty('constants_mapping');
        $property->setAccessible(true);
        return $property->getValue();
    }
    
    /**
     * Extract all constant names referenced in the mapping
     */
    private function extract_all_constant_names_from_mapping($mapping) {
        $constant_names = array();
        
        foreach ($mapping as $ini_file => $file_sections) {
            foreach ($file_sections as $section => $section_mapping) {
                foreach ($section_mapping as $ini_key => $constant_config) {
                    if (is_string($constant_config)) {
                        $constant_names[] = $constant_config;
                    } elseif (is_array($constant_config)) {
                        foreach ($constant_config as $key => $constant_name) {
                            // Skip 'transform' key and only add string values
                            if ($key !== 'transform' && is_string($constant_name)) {
                                $constant_names[] = $constant_name;
                            }
                        }
                        
                        // For db_credentials transforms, also add individual constants
                        if (isset($constant_config['transform']) && $constant_config['transform'] === 'db_credentials') {
                            foreach ($constant_config as $key => $base_constant) {
                                if ($key !== 'transform' && is_string($base_constant)) {
                                    // Add individual DB credential constants
                                    $constant_names[] = $base_constant . '_HOST';
                                    $constant_names[] = $base_constant . '_NAME';
                                    $constant_names[] = $base_constant . '_USER';
                                    $constant_names[] = $base_constant . '_PASS';
                                    $constant_names[] = $base_constant . '_PORT';
                                }
                            }
                        }
                    }
                }
            }
        }
        
        return array_unique($constant_names);
    }

    /**
     * Format value for display in debug table
     */
    private function format_value_for_display($value) {
        if (is_null($value)) {
            return '<em style="color: #999;">null</em>';
        } elseif (is_bool($value)) {
            return $value ? '<span style="color: green;">true</span>' : '<span style="color: red;">false</span>';
        } elseif (is_array($value)) {
            $json = json_encode($value, JSON_UNESCAPED_SLASHES);
            if (strlen($json) > 100) {
                return '<span style="color: #0073aa;" title="' . htmlspecialchars($json) . '">[Array with ' . count($value) . ' items]</span>';
            }
            return '<span style="color: #0073aa;">' . htmlspecialchars($json) . '</span>';
        } elseif (is_string($value)) {
            if (strlen($value) > 80) {
                return '<span title="' . htmlspecialchars($value) . '">' . htmlspecialchars(substr($value, 0, 77)) . '...</span>';
            }
            return htmlspecialchars($value);
        } else {
            return htmlspecialchars((string)$value);
        }
    }

    /**
     * Get constants that were added after plugin loading started
     * Returns the raw list of new constants (no filtering applied)
     */
    private function get_internal_constants() {
        // Get baseline constants captured when plugin started loading
        $baseline_constants = isset($GLOBALS['migration_preprocess_constants']) 
            ? $GLOBALS['migration_preprocess_constants'] 
            : array();
        
        // Get current constants (all user constants)
        $current_constants = get_defined_constants(true);
        $current_user_constants = isset($current_constants['user']) ? $current_constants['user'] : array();
        
        if (empty($baseline_constants)) {
            error_log('W4OS: No baseline constants found in $GLOBALS');
            return $current_user_constants; // Return all user constants if no baseline
        }
        
        // Flatten baseline constants into a simple array
        $baseline_flat = array();
        foreach ($baseline_constants as $category => $category_constants) {
            $baseline_flat = array_merge($baseline_flat, $category_constants);
        }
        
        // Use array_diff to get only constants that weren't in the baseline
        $new_constants = array_diff_key($current_user_constants, $baseline_flat);
        
        return $new_constants;
    }
    
    /**
     * Filter out constants we don't want to show in unprocessed list
     */
    private function filter_unprocessed_constants($constants) {
        $filtered = array();
        $processed_constants = $this->get_processed_constants();
        
        foreach ($constants as $name => $value) {
            // Skip W4OS_* constants (handled by WordPress migration)
            if (strpos($name, 'W4OS_') === 0) {
                continue;
            }
            
            // Skip individual DB credentials if the main DB constant is processed
            if ($this->is_individual_db_credential($name, $processed_constants)) {
                continue;
            }
            
            // Skip if already in processed list
            if (in_array($name, $processed_constants)) {
                continue;
            }
            
            $filtered[$name] = $value;
        }
        
        return $filtered;
    }
    
    /**
     * Check if a constant is an individual DB credential that should be ignored
     */
    private function is_individual_db_credential($name, $processed_constants) {
        $db_suffixes = ['_DB_HOST', '_DB_NAME', '_DB_USER', '_DB_PASS', '_DB_PORT'];
        
        foreach ($db_suffixes as $suffix) {
            if (strpos($name, $suffix) !== false) {
                $base_name = str_replace($suffix, '_DB', $name);
                if (in_array($base_name, $processed_constants)) {
                    return true;
                }
            }
        }
        
        return false;
    }
    
    /**
     * Get list of constants that are processed by migration mapping
     */
    private function get_processed_constants() {
        $mapping = $this->get_constants_mapping_for_debug();
        return $this->extract_all_constant_names_from_mapping($mapping);
    }
    public function admin_page() {
        ?>
        <div class="wrap">
            <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
            
            <?php settings_errors('w4os_settings_test'); ?>
            
            <div class="notice notice-info">
                <p><strong>This is a temporary test page for the Engine Settings system.</strong></p>
                <p>Settings file location: <code><?php echo esc_html(Engine_Settings::get_config_dir()); ?></code></p>
                <p>Configuration status: 
                    <span class="<?php echo Engine_Settings::is_configured() ? 'text-success' : 'text-warning'; ?>">
                        <?php echo Engine_Settings::is_configured() ? 'Configured' : 'Not configured'; ?>
                    </span>
                </p>
            </div>
            
            <div class="w4os-settings-layout">
                <div class="w4os-main-content">

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

            <!-- Import Real INI File -->
            <form method="post">
                <?php wp_nonce_field('w4os_settings_test', 'w4os_settings_test_nonce'); ?>
                <div class="card">
                    <h2>Test Real INI File Import</h2>
                    <div class="inside">
                        <p>Import from <code>tmp/example.source.ini</code> file:</p>
                        <p>File status: 
                            <?php 
                            $ini_file_path = W4OS_PLUGIN_DIR . 'tmp/example.source.ini';
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

            <!-- Test PHP Constants Migration -->
            <form method="post">
                <?php wp_nonce_field('w4os_settings_test', 'w4os_settings_test_nonce'); ?>
                <div class="card">
                    <h2>Test PHP Constants Migration</h2>
                    <div class="inside">
                        <p>Debug view showing all available constants and which ones will be migrated:</p>
                        <?php $this->test_constants_migration(); ?>
                        <div style="margin-top: 15px; padding-top: 15px; border-top: 1px solid #ddd;">
                            <p>
                                <input type="hidden" name="action" value="test_constants_migration" />
                                <input type="submit" class="button" value="Refresh Constants Debug" />
                            </p>
                            <p>
                                <input type="hidden" name="action" value="perform_constants_migration" />
                                <input type="submit" class="button-primary" value="Perform Constants Migration" 
                                       onclick="return confirm('Are you sure you want to migrate constants? This will create/update INI files.')" />
                            </p>
                        </div>
                    </div>
                </div>
            </form>

            <!-- Migrate WordPress Options -->
            <form method="post">
                <?php wp_nonce_field('w4os_settings_test', 'w4os_settings_test_nonce'); ?>
                <div class="card">
                    <h2>Migrate WordPress Options</h2>
                    <div class="inside">
                        <p>Migrate WordPress options (w4os_* settings) to Engine Settings format.</p>
                        <p><strong>Available WordPress Options (<?php echo count(W4OS_Migration_2to3::get_available_options()); ?> found):</strong></p>
                        <?php
                        $available_options = W4OS_Migration_2to3::get_available_options();
                        if (!empty($available_options)) {
                            echo "<div style='max-height: 300px; overflow-y: auto; border: 1px solid #ddd; padding: 10px; background: #f9f9f9;'>";
                            echo "<ul style='margin: 0;'>";
                            foreach ($available_options as $option => $value) {
                                $display_value = $value;
                                if (is_array($value)) {
                                    $display_value = 'Array (' . count($value) . ' items)';
                                } elseif (is_bool($value)) {
                                    $display_value = $value ? 'true' : 'false';
                                } elseif (strlen($value) > 80) {
                                    $display_value = substr($value, 0, 77) . '...';
                                }
                                echo "<li><code>" . esc_html($option) . "</code> = <strong>" . esc_html($display_value) . "</strong></li>";
                            }
                            echo "</ul>";
                            echo "</div>";
                        } else {
                            echo "<p><em>No w4os_* WordPress options found in database.</em></p>";
                        }
                        ?>
                        <p>
                        <input type="hidden" name="action" value="migrate_wordpress_options">
                        <input type="submit" value="Migrate WordPress Options" class="button" 
                               <?php echo empty($available_options) ? 'disabled' : ''; ?>>
                        </p>
                    </div>
                </div>
            </form>

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
                                <td><code><?php echo esc_html(Engine_Settings::get_config_dir()); ?></code></td>
                                <td><?php echo file_exists(Engine_Settings::get_config_dir()) ? '✅ Exists' : '❌ Not created yet'; ?></td>
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
            max-width: 100% !important;
        }
        .table-container {
            display: inline-block;
            overflow-y: auto;
            max-width: 100%;
            max-height: 40em;
            padding: 0 0 10px !important;
        }
        .table-container:hover {
            max-width: none;
        }
        .table-container th {
            position: sticky;
            top: 0;
            background: #f1f1f1;
        } 
        table,
        .form-table {
            /* max-width: 100% !important; */
            /* max-height: 40em;
            overflow-y: auto; */
        }
        
        .form-table th,
        .form-table td {
            width: auto !important;
        }
        
        .regular-text {
            width: 100% !important;
            max-width: 100% !important;
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
