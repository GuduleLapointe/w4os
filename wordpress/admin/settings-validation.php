<?php
/**
 * WordPress Options vs Engine Settings Validation Test Page
 * 
 * This page compares values between the old WordPress options system
 * and the new Engine Settings system to ensure they return identical values.
 */

if (!defined('ABSPATH')) {
    exit;
}

class W4OS_Settings_Validation_Page {
    
    public function __construct() {
        add_action('admin_menu', [$this, 'add_admin_menu'], 99);
    }
    
    public function add_admin_menu() {
        add_submenu_page(
            'w4os',
            'Settings Validation',
            'Settings Validation',
            'manage_options',
            'w4os-settings-validation',
            [$this, 'admin_page']
        );
    }
    
    public function admin_page() {
        // Run migration if requested
        if (isset($_POST['run_migration']) && check_admin_referer('w4os_validation', 'w4os_validation_nonce')) {
            $migration_results = W4OS3_Migration_2to3::migrate_wordpress_options();
            echo '<div class="notice notice-success"><p>Migration completed. See results below.</p></div>';
        }
        
        // Get all available WordPress options
        $wp_options = W4OS3_Migration_2to3::get_available_options();
        $mapped_keys = W4OS3_Migration_2to3::get_mapped_ini_keys();
        
        ?>
        <div class="wrap">
            <h1>Settings Validation: WordPress Options vs Engine Settings</h1>
            
            <div class="notice notice-info">
                <p><strong>Purpose:</strong> This page compares values between the old WordPress options system and the new Engine Settings system to ensure they return identical values.</p>
            </div>
            
            <!-- Migration Section -->
            <div class="card" style="max-width: auto;">
                <h2>Step 1: Run Migration</h2>
                <p>First, migrate WordPress options to Engine Settings:</p>
                <form method="post">
                    <?php wp_nonce_field('w4os_validation', 'w4os_validation_nonce'); ?>
                    <input type="submit" name="run_migration" class="button button-primary" value="Run Migration">
                </form>
                
                <?php if (isset($migration_results)): ?>
                    <h3>Migration Results</h3>
                    <div style="background: #f1f1f1; padding: 10px; margin: 10px 0;">
                        <strong>Migrated (<?php echo count($migration_results['migrated']); ?>):</strong><br>
                        <?php foreach ($migration_results['migrated'] as $item): ?>
                            ✓ <?php echo esc_html($item); ?><br>
                        <?php endforeach; ?>
                        
                        <?php if (!empty($migration_results['skipped'])): ?>
                            <br><strong>Skipped (<?php echo count($migration_results['skipped']); ?>):</strong><br>
                            <?php foreach ($migration_results['skipped'] as $item): ?>
                                - <?php echo esc_html($item); ?><br>
                            <?php endforeach; ?>
                        <?php endif; ?>
                        
                        <?php if (!empty($migration_results['errors'])): ?>
                            <br><strong>Errors (<?php echo count($migration_results['errors']); ?>):</strong><br>
                            <?php foreach ($migration_results['errors'] as $error): ?>
                                ❌ <?php echo esc_html($error); ?><br>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
            </div>
            
            <!-- Validation Section -->
            <div class="card">
                <h2>Step 2: Value Comparison</h2>
                <p>Compare values between WordPress options and Engine Settings:</p>
                
                <?php $this->display_comparison_table($wp_options, $mapped_keys); ?>
            </div>
            
            <!-- Test Specific Values -->
            <div class="card">
                <h2>Step 3: Test Specific Values</h2>
                <p>Test some commonly used values to ensure they match:</p>
                
                <?php $this->test_common_values(); ?>
            </div>
        </div>
        
        <style>
        .card { max-width: 100%; }
        .comparison-table { width: auto; border-collapse: collapse; margin: 20px 0; }
        .comparison-table th, .comparison-table td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        .comparison-table th { background-color: #f2f2f2; }
        .value-match { background-color: #d4edda; }
        .value-mismatch { background-color: #f8d7da; }
        .value-missing, .value-similar { background-color: #fff3cd; }
        </style>
        <?php
    }
    
    private function display_comparison_table($wp_options, $mapped_keys) {
        echo '<table class="comparison-table">';
        echo '<thead><tr>
        <th>INI Key</th>
        <th>Status</th>
        <th>WordPress Option</th>
        <th>Old Value</th>
        <th>New Value</th>
        </tr></thead>';
        echo '<tbody>';
        
        foreach ($mapped_keys as $ini_key) {
            $mapping = W4OS3_Migration_2to3::get_mapping_for_key($ini_key);
            if (!$mapping) continue;
            
            // Get old value using precedence
            $old_value = $this->get_old_value_with_precedence($mapping, $wp_options);
            
            // Get new value from Engine Settings
            $new_value = null;
            if (class_exists('Engine_Settings')) {
                try {
                    $new_value = Engine_Settings::get($ini_key);
                } catch (Exception $e) {
                    $new_value = 'ERROR: ' . $e->getMessage();
                }
            }
            
            // Compare values
            $status = $this->compare_values($old_value, $new_value);
            $css_class = $status['class'];
            
            echo "<tr class='{$css_class}'>";
            echo "<td><strong>{$ini_key}</strong></td>";
            echo "<td>{$status['message']}</td>";
            echo "<td>" . $this->format_mapping_display($mapping) . "</td>";
            echo "<td>" . $this->format_value_display($old_value) . "</td>";
            echo "<td>" . $this->format_value_display($new_value) . "</td>";
            echo "</tr>";
        }
        
        echo '</tbody></table>';
    }
    
    private function get_old_value_with_precedence($mapping, $wp_options) {
        // Remove transform key if present
        $option_names = array_filter($mapping, function($key) {
            return $key !== 'transform';
        }, ARRAY_FILTER_USE_KEY);
        
        foreach ($option_names as $option_name) {
            if (strpos($option_name, '.') !== false) {
                // Handle nested options like 'w4os_settings.economy_url'
                list($base_option, $sub_key) = explode('.', $option_name, 2);
                if (isset($wp_options[$base_option]) && is_array($wp_options[$base_option])) {
                    if (isset($wp_options[$base_option][$sub_key])) {
                        return $wp_options[$base_option][$sub_key];
                    }
                }
            } else {
                // Handle direct options
                if (isset($wp_options[$option_name])) {
                    $value = $wp_options[$option_name];
                    
                    // Special handling for database credentials - show full credential array
                    if (stripos($option_name, 'connectionstring') !== false || 
                        stripos($option_name, 'db_') !== false) {
                        
                        // Check w4os-credentials first (highest priority)
                        if (isset($wp_options['w4os-credentials']) && is_array($wp_options['w4os-credentials'])) {
                            foreach ($wp_options['w4os-credentials'] as $uri => $encrypted_creds) {
                                if (class_exists('W4OS3') && method_exists('W4OS3', 'decrypt')) {
                                    $decrypted = W4OS3::decrypt($encrypted_creds);
                                    if ($decrypted && is_array($decrypted)) {
                                        // Check if use_default is set and true
                                        if (isset($decrypted['use_default']) && $decrypted['use_default']) {
                                            continue; // Skip this one, use_default means empty
                                        }
                                        return $decrypted; // Return first valid credentials
                                    }
                                }
                            }
                        }
                        
                        // Check w4os-settings connections (medium priority)
                        if (isset($wp_options['w4os-settings']['connections']) && is_array($wp_options['w4os-settings']['connections'])) {
                            foreach ($wp_options['w4os-settings']['connections'] as $service => $service_config) {
                                if (isset($service_config['db']) && is_array($service_config['db'])) {
                                    $db_config = $service_config['db'];
                                    // Check if use_default is set and true
                                    if (isset($db_config['use_default']) && $db_config['use_default']) {
                                        continue; // Skip this one, use_default means empty
                                    }
                                    return $db_config; // Return first valid db config
                                }
                            }
                        }
                        
                        // Fallback to building from individual values
                        $db_creds = [];
                        if (isset($wp_options['w4os_db_host'])) $db_creds['host'] = $wp_options['w4os_db_host'];
                        if (isset($wp_options['w4os_db_database'])) $db_creds['name'] = $wp_options['w4os_db_database'];
                        if (isset($wp_options['w4os_db_user'])) $db_creds['user'] = $wp_options['w4os_db_user'];
                        if (isset($wp_options['w4os_db_pass'])) $db_creds['pass'] = $wp_options['w4os_db_pass'];
                        if (isset($wp_options['w4os_db_port'])) $db_creds['port'] = $wp_options['w4os_db_port'];
                        
                        if (!empty($db_creds)) {
                            return $db_creds;
                        }
                    }
                    
                    return $value;
                }
            }
        }
        
        return null;
    }
    
    private function compare_values($old_value, $new_value) {
        if(is_bool($old_value) || is_bool($new_value)) {
            return ($old_value === $new_value) ? 
                ['class' => 'value-match', 'message' => '✓ Match'] :
                ['class' => 'value-mismatch', 'message' => '❌ Boolean mismatch'];
        }

        if (empty($old_value) && empty($new_value)) {
            return ['class' => 'value-match', 'message' => '✓ Match'];
        }
        
        if (empty($old_value)) {
            return ['class' => 'value-missing', 'message' => 'New value'];
        }
        
        if (empty($new_value)) {
            return ['class' => 'value-mismatch', 'message' => '❌ New empty'];
        }
        
        // Special handling for connection strings - decrypt and parse both values
        if (is_string($new_value) && strpos($new_value, 'Data Source=') !== false) {
            // New value is a connection string, parse it to array
            $new_parsed = OSPDO::connectionstring_to_array_to_array($new_value);
            
            // Old value might be encrypted credentials, try to decrypt and compare
            if (is_string($old_value) && class_exists('W4OS3')) {
                try {
                    $old_decrypted = W4OS3::decrypt($old_value);
                    if ($old_decrypted && is_array($old_decrypted)) {
                        return $this->compare_db_credentials($old_decrypted, $new_parsed);
                    }
                } catch (Exception $e) {
                    // Fall through to normal comparison
                }
            }
            
            // If old value is already an array (from w4os-credentials), compare directly
            if (is_array($old_value)) {
                return $this->compare_db_credentials($old_value, $new_parsed);
            }
        }
        
        // If both are arrays, compare as credential arrays
        if (is_array($old_value) && is_array($new_value)) {
            return $this->compare_db_credentials($old_value, $new_value);
        }
        
        // Convert for comparison
        $old_str = is_array($old_value) ? json_encode($old_value) : (string)$old_value;
        $new_str = is_array($new_value) ? json_encode($new_value) : (string)$new_value;
        
        if ($old_str === $new_str) {
            return ['class' => 'value-match', 'message' => '✓ Match'];
        } else if(strtolower($old_str) === strtolower($new_str)) {
            return ['class' => 'value-similar', 'message' => '✓ Similar'];  
        }
        
        return ['class' => 'value-mismatch', 'message' => '❌ Mismatch'];
    }
    
    private function compare_db_credentials($old_creds, $new_creds) {
        // If old credentials have use_default set to true, treat as empty
        if (isset($old_creds['use_default']) && $old_creds['use_default']) {
            return ['class' => 'value-missing', 'message' => 'Old uses default (empty)'];
        }
        
        // Normalize both credential arrays to same format
        $old_normalized = $this->normalize_db_credentials($old_creds);
        $new_normalized = $this->normalize_db_credentials($new_creds);
        
        // Compare normalized arrays
        if ($old_normalized === $new_normalized) {
            return ['class' => 'value-match', 'message' => '✓ Credentials match'];
        }
        
        // Check if they're similar (same connection, different format)
        $old_key = $old_normalized['host'] . ':' . $old_normalized['name'];
        $new_key = $new_normalized['host'] . ':' . $new_normalized['name'];
        
        if ($old_key === $new_key && 
            $old_normalized['user'] === $new_normalized['user'] &&
            $old_normalized['pass'] === $new_normalized['pass']) {
            return ['class' => 'value-similar', 'message' => '✓ Same credentials, different format'];
        }
        
        return ['class' => 'value-mismatch', 'message' => '❌ Credentials differ'];
    }
    
    private function normalize_db_credentials($creds) {
        if (empty($creds) || !is_array($creds)) {
            return [];
        }
        
        $normalized = [
            'host' => '',
            'port' => '3306',
            'name' => '',
            'user' => '',
            'pass' => ''
        ];
        
        // Handle different key formats
        foreach ($creds as $key => $value) {
            switch (strtolower($key)) {
                case 'data source':
                case 'host':
                    if (strpos($value, ':') !== false) {
                        list($host, $port) = explode(':', $value, 2);
                        $normalized['host'] = $host;
                        $normalized['port'] = $port;
                    } else {
                        $normalized['host'] = $value;
                    }
                    break;
                case 'database':
                case 'name':
                    $normalized['name'] = $value;
                    break;
                case 'user id':
                case 'user':
                    $normalized['user'] = $value;
                    break;
                case 'password':
                case 'pass':
                    $normalized['pass'] = $value;
                    break;
                case 'port':
                    $normalized['port'] = $value;
                    break;
            }
        }
        
        return $normalized;
    }
    
    private function format_value_display($value) {
        if ($value === null) {
            return '<em>null</em>';
        }
        
        if (is_bool($value)) {
            return $value ? 'true' : 'false';
        }
        
        if (is_array($value)) {
            return '<pre>' . esc_html(json_encode($value, JSON_PRETTY_PRINT)) . '</pre>';
        }
        
        $str = (string)$value;
        if (strlen($str) > 100) {
            return esc_html(substr($str, 0, 97)) . '...';
        }
        
        return esc_html($str);
    }
    
    private function format_mapping_display($mapping) {
        $option_names = array_filter($mapping, function($key) {
            return $key !== 'transform';
        }, ARRAY_FILTER_USE_KEY);
        
        $display = implode('<br>→ ', array_values($option_names));
        
        if (isset($mapping['transform'])) {
            $display .= '<br><em>Transform: ' . $mapping['transform'] . '</em>';
        }
        
        return $display;
    }
    
    private function test_common_values() {
        $test_cases = [
            'W4OS.ModelFirstName' => 'w4os_model_firstname',
            'GridInfoService.gridname' => 'w4os_grid_name',
            'SearchService.SearchURL' => 'w4os_search_url',
            'MoneyServer.ScriptKey' => 'w4os_money_script_access_key',
            'DatabaseService.ConnectionString' => null, // Computed value
        ];
        
        echo '<table class="comparison-table">';
        echo '<thead><tr>
        <th>Test Case</th>
        <th>Match?</th>
        <th>Old Method</th>
        <th>New Method</th>
        </tr></thead>';
        echo '<tbody>';
        
        foreach ($test_cases as $ini_key => $wp_option) {
            // Old method
            $old_value = $wp_option ? get_option($wp_option) : 'N/A (computed)';
            
            // New method
            $new_value = 'N/A';
            if (class_exists('Engine_Settings')) {
                try {
                    $new_value = Engine_Settings::get($ini_key);
                } catch (Exception $e) {
                    $new_value = 'ERROR: ' . $e->getMessage();
                }
            }
            
            $match = ($old_value === $new_value) ? '✓' : '❌';
            $css_class = ($old_value === $new_value) ? 'value-match' : 'value-mismatch';
            
            echo "<tr class='{$css_class}'>";
            echo "<td><strong>{$ini_key}</strong></td>";
            echo "<td><nobr>{$match}</nobr></td>";
            echo "<td>" . $this->format_value_display($old_value) . "</td>";
            echo "<td>" . $this->format_value_display($new_value) . "</td>";
            echo "</tr>";
        }
        
        echo '</tbody></table>';
    }
}

// Initialize the validation page
new W4OS_Settings_Validation_Page();
