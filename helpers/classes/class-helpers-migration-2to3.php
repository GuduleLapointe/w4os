<?php
/**
 * Helpers Constants to Engine Settings Migration
 * 
 * Migrates PHP constants to the new Engine Settings format.
 * This file handles the conversion of legacy PHP constants (from config.php)
 * to the standardized OpenSim INI format used by Engine_Settings.
 */

if (!defined('ABSPATH') && !defined('OPENSIM_ENGINE')) {
    exit;
}

class Helpers_Migration_2to3 {
    
    private static $constants_mapping = [
        'engine' => [
            // Parameters not handled by OpenSim, usable regardless of framework
            'Helpers' => [
                'GridLogoURL' => 'OPENSIM_GRID_LOGO_URL',
                'OSHelpersDir' => 'OS_HELPERS_DIR',
            ],
            'Search' => [
                'SearchDB' => ['SEARCH_DB', 'ROBUST_DB', 'OPENSIM_DB', 'transform' => 'db_credentials'],
                'SearchEventsTable' => 'SEARCH_TABLE_EVENTS',
                'SearchRegionTable' => 'SEARCH_REGION_TABLE',
                'HypeventsUrl' => 'HYPEVENTS_URL',
            ],
            'OfflineMessages' => [
                'OfflineDB' => ['OFFLINE_DB', 'transform' => 'db_credentials'],
                'OfflineMessageTable' => 'OFFLINE_MESSAGE_TBL',
                'MuteDB' => ['MUTE_DB', 'ROBUST_DB', 'OPENSIM_DB', 'transform' => 'db_credentials'],
                'MuteListTable' => 'MUTE_LIST_TBL',
                'SenderEmail' => 'OPENSIM_MAIL_SENDER',
            ],
            'Economy' => [
                'CurrencyMoneyTable' => 'CURRENCY_MONEY_TBL',
                'CurrencyTransactionTable' => 'CURRENCY_TRANSACTION_TBL',
                'CurrencyHelperPath' => 'CURRENCY_HELPER_PATH',
                'GloebitConversionThreshold' => 'GLOEBIT_CONVERSION_THRESHOLD',
                'GloebitConversionTable' => 'GLOEBIT_CONVERSION_TABLE',
                'PodexErrorMessage' => 'PODEX_ERROR_MESSAGE',
                'PodexRedirectUrl' => 'PODEX_REDIRECT_URL',
            ],
        ],

        'robust' => [
            'Const' => [
                'BaseHostname' => ['OPENSIM_LOGIN_URI', 'transform' => 'uri_to_hostname'],
                'BaseURL' => ['OPENSIM_LOGIN_URI', 'transform' => 'uri_to_base_url'],
                'PublicPort' => ['OPENSIM_LOGIN_URI', 'transform' => 'extract_public_port'],
            ],
            'LoginService' => [
                'Currency' => 'CURRENCY_NAME',

                // {DSTZone} {} Affects only Daylight Saving Time rules
                // Default to "America/Los_Angeles;Pacific Standard Time" 
                // Set to "none" if OPENSIM_USE_UTC_TIME is set and false
                // ;;   "none"     no DST
                // ;;   "local"    use the server's only timezone to calculate DST.  This is previous OpenSimulator behaviour.
                // ;;   "America/Los_Angeles;Pacific Standard Time" use these timezone names to look up Daylight savings.
                'DSTZone' => ['OPENSIM_USE_UTC_TIME', 'transform' => 'get_dst_zone'],
            ],
    
            'GridInfoService' => [
                'gridname' => 'OPENSIM_GRID_NAME',
                'login' => 'OPENSIM_LOGIN_URI',
                'economy' => 'CURRENCY_HELPER_URL',
            ],
            
            'DatabaseService' => [
                'ConnectionString' => ['ROBUST_DB', 'OPENSIM_DB', 'transform' => 'db_credentials'],
            ],

            'Network' => [
                'ConsoleUser' => ['ROBUST_CONSOLE', 'transform' => 'extract_console_user'],
                'ConsolePass' => ['ROBUST_CONSOLE', 'transform' => 'extract_console_pass'],
                'ConsolePort' => ['ROBUST_CONSOLE', 'transform' => 'extract_console_port'],
            ],

            'AssetService' => [
                'ConnectionString' => ['ASSETS_DB', 'ROBUST_DB', 'OPENSIM_DB', 'transform' => 'db_credentials'],
            ],
            'UserProfilesService' => [
                'ConnectionString' => ['PROFILE_DB', 'ROBUST_DB', 'OPENSIM_DB', 'transform' => 'db_credentials'],
            ],            
        ],

        'opensim' => [
            'DatabaseService' => [
                'ConnectionString' => ['OPENSIM_DB', 'ROBUST_DB', 'transform' => 'db_credentials'],
            ],

            'Search' => [
                // 'Module' => 'OpenSimSearch', // Fixed value if search URL is not empty, for user info only
            ],
    
            'DataSnapshot' => [
                // 'index_sims' => 'to be implemented',
                'gridname' => 'OPENSIM_GRID_NAME',
                'DATA_SRV_*' => 'DATA_SRV_*', // Find any matching constant, preserve name
            ],

            'Economy' => [
                'economymodule' => ['CURRENCY_PROVIDER', 'transform' => 'currency_provider_to_module'],
                'economy' => 'CURRENCY_HELPER_URL',
                // 'PriceUpload' => 0, // Fixed value
                // 'PriceGroupCreate' => 0, // Fixed value
            ],

            'Gloebit' => [
                'Enabled' => ['CURRENCY_PROVIDER', 'transform' => 'is_gloebit_enabled'],
                // 'GLBSpecificStorageProvider' => ['transform' => 'get_storage_module_economy'], // To be implemented
                'GLBSpecificConnectionString' => ['CURRENCY_DB', 'transform' => 'db_credentials'],
                'GLBOwnerEmail' => 'OPENSIM_MAIL_SENDER',
            ],

            // 'Messaging' => [
            // ],
        ],

        'moneyserver' => [
            'MySql' => [
                'hostname' => ['CURRENCY_DB.host', 'CURRENCY_DB_HOST'],
                'database' => ['CURRENCY_DB.name', 'CURRENCY_DB_NAME'],
                'username' => ['CURRENCY_DB.user', 'CURRENCY_DB_USER'],
                'password' => ['CURRENCY_DB.pass', 'CURRENCY_DB_PASS'],
                'port' => ['CURRENCY_DB.port', 'CURRENCY_DB_PORT'],
            ],
            'MoneyServer' => [
                // - not used with Gloebit
                // TODO: check if Podex uses MoneyServer

                'BankerAvatar' => 'CURRENCY_BANKER_AVATAR', // TODO: check if both variants are legit

                'Enabled' => ['CURRENCY_USE_MONEYSERVER', 'transform' => 'boolean_to_string'],
                'ScriptKey' => 'CURRENCY_SCRIPT_KEY', // TODO: check if both variants are legit
                'MoneyScriptAccessKey' => 'CURRENCY_SCRIPT_KEY', // TODO: check if both variants are legit
                'Rate' => 'CURRENCY_RATE',
                'RatePer' => 'CURRENCY_RATE_PER',
            ],
        ],
    ];
    
    /**
     * Transform a value according to the specified transformation
     */
    protected static function transform_value($value, $transform, $all_values = array(), $constant_config = null) {
        switch ($transform) {
            case 'boolean_to_string':
                return self::normalize_boolean($value);
                
            case 'preserve_array':
                // Keep arrays as-is for JSON encoding
                return is_array($value) ? $value : $value;
                
            case 'ensure_trailing_slash':
                return $value ? rtrim($value, '/') . '/' : $value;
                
            case 'add_search_path':
                return $value ? rtrim($value, '/') . '/search/' : $value;
                
            case 'add_offline_path':
                return $value ? rtrim($value, '/') . '/offline/' : $value;
                
            case 'extract_console_user':
                return is_array($value) && isset($value['ConsoleUser']) ? $value['ConsoleUser'] : null;
                
            case 'extract_console_pass':
                return is_array($value) && isset($value['ConsolePass']) ? $value['ConsolePass'] : null;
                
            case 'extract_console_port':
                return is_array($value) && isset($value['ConsolePort']) ? $value['ConsolePort'] : null;
                
            case 'sanitize_id':
                return sanitize_id($value ?? '');
                
            case 'currency_provider_to_module':
                switch (strtolower($value ?? '')) {
                    case 'gloebit':
                        return 'Gloebit';
                    case 'podex':
                        return 'DTLNSLMoneyModule';
                    case 'moneyserver':
                    case 'opensim':
                    case '':
                        return 'DTLNSLMoneyModule';
                    default:
                        return $value;
                }
                
            case 'is_gloebit_enabled':
                return (strtolower($value ?? '') === 'gloebit') ? 'true' : 'false';
                
            case 'sandbox_to_environment':
                return ($value === true || $value === 'true') ? 'sandbox' : 'production';
                
            case 'static_mysql_provider':
                return 'OpenSim.Data.MySQL.dll';
                
            case 'uri_to_hostname':
                if (empty($value)) return null;
                $parsed = parse_url($value);
                return isset($parsed['host']) ? $parsed['host'] : null;
                
            case 'uri_to_base_url':
                if (empty($value)) return null;
                $parsed = parse_url($value);
                if (!isset($parsed['host'])) return null;
                $scheme = isset($parsed['scheme']) ? $parsed['scheme'] : 'http';
                return $scheme . '://' . $parsed['host'];
                
            case 'extract_public_port':
                if (empty($value)) return '8002';
                $parsed = parse_url($value);
                return isset($parsed['port']) ? $parsed['port'] : '8002';
                
            case 'sanitize_login_uri':
                if (empty($value)) return null;
                // Use OpenSim class if available
                if (class_exists('OpenSim')) {
                    return OpenSim::sanitize_uri($value);
                }
                // Fallback sanitization
                $value = (preg_match('/^https?:\/\//', $value)) ? $value : 'http://' . $value;
                $parts = parse_url($value);
                if (!$parts) return null;
                $parts = array_merge([
                    'scheme' => 'http',
                    'port' => 8002,
                ], $parts);
                return $parts['scheme'] . '://' . $parts['host'] . ':' . $parts['port'];
                
            case 'db_credentials':
                if (!is_array($constant_config)) {
                    $constant_config = array($constant_config);
                }

                foreach ($constant_config as $key => $constant_name) {
                    if ($key === 'transform') {
                        continue; // Skip the transform key
                    }
                    
                    if (!is_string($constant_name) || empty($constant_name)) {
                        continue;
                    }
                    
                    $value = $all_values[$constant_name] ?? null;
                    
                    if (is_array($value)) {
                        error_log("$constant_name is array: " . print_r($value, true));
                        // Already an array, normalize it to db_credentials format
                        $db_array = array(
                            'type' => $value['type'] ?? null,
                            'host' => $value['host'] ?? null,
                            'name' => $value['name'] ?? $value['database'] ?? null,
                            'user' => $value['user'] ?? $value['username'] ?? null,
                            'pass' => $value['pass'] ?? $value['password'] ?? null,
                            'port' => isset($value['port']) ? intval($value['port']) : null,
                        );
                        if (!empty($db_array['host']) && !empty($db_array['name'])) {
                            error_log("Returning array from $constant_name: " . print_r($db_array, true));
                            return $db_array;
                        }
                    } else {
                        // Look for individual constants with same prefix
                        $host = $all_values[$constant_name . '_HOST'] ?? null;
                        $name = $all_values[$constant_name . '_NAME'] ?? null;
                        
                        if (!empty($host) && !empty($name)) {
                            $user = $all_values[$constant_name . '_USER'] ?? null;
                            $pass = $all_values[$constant_name . '_PASS'] ?? null;
                            $port = $all_values[$constant_name . '_PORT'] ?? null;
                            
                            // Build array in db_credentials format
                            $db_array = array(
                                'host' => $host,
                                'name' => $name,
                                'user' => $user,
                                'pass' => $pass,
                                'port' => $port,
                            );
                            return $db_array;
                        }
                    }
                }
                
                error_log("No valid database credentials found in " . print_r($constant_config, true));
                return null;
                
            case 'get_dst_zone':
                // If OPENSIM_USE_UTC_TIME is false, return "none"
                if ($value === false || $value === 'false') {
                    return 'none';
                }
                // Default DST zone
                return 'America/Los_Angeles;Pacific Standard Time';
                
            default:
                return $value;
        }
    }
    
    /**
     * Normalize boolean values from various formats
     */
    protected static function normalize_boolean($value) {
        if (is_bool($value)) {
            return $value ? 'true' : 'false';
        }
        
        $value = strtolower(trim($value ?? ''));
        if (in_array($value, array('true', '1', 'yes', 'on', 'enabled'))) {
            return 'true';
        } elseif (in_array($value, array('false', '0', 'no', 'off', 'disabled', ''))) {
            return 'false';
        }
        
        return $value; // Return as-is if not clearly boolean
    }

    /**
     * Find constant value using precedence rules
     * 
     * @param mixed $constant_config Configuration for the constant (string or array)
     * @param array $all_values All available PHP constants
     * @return mixed Found value or null
     */
    protected static function find_constant_value_with_precedence($constant_config, $all_values) {
        // Handle simple string constant name
        if (is_string($constant_config)) {
            if (isset($all_values[$constant_config])) {
                $value = $all_values[$constant_config];
                return $value;
            }
            return null;
        }
        
        // Handle array configuration
        if (!is_array($constant_config)) {
            return null;
        }
        
        // Handle special case for transforms that don't need source constants
        if (count($constant_config) === 1 && isset($constant_config['transform'])) {
            return 'TRANSFORM_ONLY';
        }

        if( isset($constant_config['transform']) && $constant_config['transform'] == 'db_credentials' ) {
            // Special case for db_credentials - allow transform even if no constants found
            return 'TRANSFORM_ONLY';
        }

        // Go through precedence order - get just the constant names (not transform)
        foreach ($constant_config as $key => $constant_name) {
            // Skip 'transform' key
            if ($key === 'transform') {
                continue;
            }
            
            if (isset($all_values[$constant_name])) {
                $value = $all_values[$constant_name];
                return $value;
            }
        }
        
        return null;
    }
    
    /**
     * Migrate PHP constants to Engine Settings using comprehensive mapping
     * 
     * @param array $constants Optional array of constants to migrate. If empty, gets current constants.
     * @return array Migration results
     */
    public static function migrate_constants($constants = null) {
        $results = [
            'migrated' => [],
            'skipped' => [],
            'errors' => []
        ];
        
        // Get all PHP constants if not provided
        if ($constants === null) {
            $all_constants = get_defined_constants(true);
            // Flatten all constants from all categories
            $all_values = array();
            foreach ($all_constants as $category => $constants_array) {
                if (is_array($constants_array)) {
                    $all_values = array_merge($all_values, $constants_array);
                }
            }
        } else {
            $all_values = $constants;
        }
        
        if (empty($all_values)) {
            $results['errors'][] = 'No constants found to migrate';
            return $results;
        }
        
        // Process each INI file and its sections
        foreach (self::$constants_mapping as $ini_file => $file_sections) {
            $instance = basename($ini_file, '.ini');
            
            foreach ($file_sections as $section => $section_mapping) {
                
                foreach ($section_mapping as $ini_key => $constant_config) {
                    try {
                        
                        // Find the value using precedence rules
                        $value = self::find_constant_value_with_precedence($constant_config, $all_values);
                        
                        if ($value === null) {
                            $results['skipped'][] = $ini_key . ' (no matching constant found)';
                            continue;
                        }
                        
                        // Handle transforms
                        if (is_array($constant_config) && isset($constant_config['transform'])) {
                            if ($value === 'TRANSFORM_ONLY') {
                                // Transform-only case, don't need source value
                                $value = self::transform_value(null, $constant_config['transform'], $all_values, $constant_config);
                            } else {
                                $value = self::transform_value($value, $constant_config['transform'], $all_values, $constant_config);
                            }
                        }
                        
                        // Skip if value is still null after transformation
                        if ($value === null) {
                            $results['skipped'][] = $ini_key . ' (null after transformation)';
                            continue;
                        }
                        
                        // Save to Engine Settings
                        $setting_key = $instance . '.' . $section . '.' . $ini_key;
                        $success = Engine_Settings::set($setting_key, $value, false); // Don't save individually
                        
                        if ($success) {
                            $results['migrated'][] = $setting_key;
                        } else {
                            $results['errors'][] = 'Failed to set ' . $setting_key;
                            error_log("Failed to set: $setting_key");
                        }
                        
                    } catch (Exception $e) {
                        $results['errors'][] = 'Error processing ' . $ini_key . ': ' . $e->getMessage();
                        error_log("Exception processing $ini_key: " . $e->getMessage());
                    }
                }
            }
        }
        
        // Save all instances if no errors occurred
        if (empty($results['errors'])) {
            Engine_Settings::save();
        }
        
        return $results;
    }
}
