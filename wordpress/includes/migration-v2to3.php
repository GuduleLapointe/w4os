<?php
/**
 * WordPress Options to Engine Settings Migration
 * 
 * Migrates WordPress options to the new Engine Settings format.
 * This file handles the conversion of legacy WordPress get_option() calls
 * to the standardized OpenSim INI format used by Engine_Settings.
 */

if (!defined('ABSPATH') && !defined('OPENSIM_ENGINE')) {
    exit;
}

class W4OS_Options_Migrator {
    
    /**
     * Complete mapping of INI sections to WordPress options with precedence rules
     * 
     * Precedence order based on actual code usage:
     * 1. Direct get_option() calls - highest priority
     * 2. w4os_get_option() calls with specific option groups  
     * 3. avatar_get_option() calls (translates to w4os-avatars.settings.*)
     * 4. W4OS3::get_credentials() for database/console credentials
     */
    private static $ini_mapping = [
        'w4os.ini' => [
            'W4OS' => [
                'ModelFirstName' => ['w4os_model_firstname'],
                'ModelLastName' => ['w4os_model_lastname'],
                'ProfileSlug' => ['w4os_profile_slug'],
                'ProfilePage' => ['w4os_profile_page'],
                'AssetsSlug' => ['w4os_assets_slug'],
                'LoginPage' => ['w4os_login_page'],
                'ShowConfigurationInstructions' => ['w4os_configuration_instructions', 'transform' => 'boolean_to_string'],
                'PodexRedirectUrl' => ['w4os_podex_redirect_url'],
                'EnableRegistration' => ['w4os_enable_registration', 'transform' => 'boolean_to_string'],
                'ExcludeModels' => ['w4os_exclude_models', 'transform' => 'boolean_to_string'],
                'ExcludeNomail' => ['w4os_exclude_nomail', 'transform' => 'boolean_to_string'],
                'ExcludeTests' => ['w4os_exclude_tests', 'transform' => 'boolean_to_string'],
                'UserlistReplaceName' => ['w4os_userlist_replace_name', 'transform' => 'boolean_to_string'],
                'ProvideAssetServer' => ['w4os_provide_asset_server', 'transform' => 'boolean_to_string'],
                'RewriteRules' => ['w4os_rewrite_rules', 'transform' => 'boolean_to_string'],
                'RewriteVersion' => ['w4os_rewrite_version'],
                'LegacyImported' => ['w4os_legacy_imported', 'transform' => 'boolean_to_string'],
                'LegacyNoticeDismissed' => ['w4os_legacy_notice_dismissed', 'transform' => 'boolean_to_string'],
                'TosPageId' => ['w4os_tos_page_id'],
                'EconomySlug' => ['w4os_economy_slug'],
                'HelpersSlug' => ['w4os_helpers_slug'],
                'ProvideGuide' => ['w4os-guide.provide', 'transform' => 'boolean_to_string'],
                'ProvideSearch' => ['w4os_provide_search', 'transform' => 'boolean_to_string'],
                'GuideSource' => ['w4os-guide.source'],
                'GuideUrl' => ['w4os-guide.url'],
                'ProfileEditPage' => ['w4os_profile_edit_page'],
                'ProfilePageCustom' => ['w4os_profile_page_custom'],
            ],
        ],
        'helpers.ini' => [
            'Helpers' => [
                'OpensimMailSender' => ['w4os_offline_sender'],
                'HypeventsUrl' => ['w4os_hypevents_url'],
                'OfflineHelperUri' => ['w4os_offline_helper_uri'],
                'EconomyHelperUri' => ['w4os_economy_helper_uri'],
                'SearchRegisterUri' => ['w4os_search_register'],
                'InternalAssetServerUri' => ['w4os_internal_asset_server_uri'],
                'ExternalAssetServerUri' => ['w4os_external_asset_server_uri'],
                'AssetServerUri' => ['w4os_asset_server_uri'],
            ],
        ],

        'robust.ini' => [
            'LoginService' => [
                'SearchURL' => ['w4os_grid_info.search'], // Web Search URL
                // 'Currency' => 'G$', // To be implemented
                'DestinationGuide' => ['w4os-guide.url'],
            ],
    
            'GridInfoService' => [
                'gridname' => ['w4os_grid_name'],
                'login' => ['w4os_login_uri'],
                'economy' => ['w4os_economy_helper_uri'],
                'search' => ['w4os_grid_info.search'], // Web Search URL
                'OfflineMessageURL' => ['w4os_offline_helper_uri'],
            ],
            
            'DatabaseService' => [
                'ConnectionString' => ['transform' => 'get_db_credentials_robust'],
            ],
        ],

        'opensim.ini' => [
            'Search' => [
                // 'Module' => 'OpenSimSearch', // Fixed value if search URL is not empty, for user info only
                'SearchURL' => ['w4os_search_url'], // In-world Search URL
            ],
    
            'DataSnapshot' => [
                // 'index_sims' => 'to be implemented',
                'gridname' => ['w4os_grid_name'],
                'DATA_SRV_W4os' => [':w4os_search_register'],
                // 'DATA_SRV_2do' => 'http://2do.directory/helpers/register.php', // Fixed value
            ],

            'Economy' => [
                'economymodule' => ['w4os_currency_provider', 'transform' => 'currency_provider_to_module'],
                'economy' => ['w4os_economy_helper_uri'],
                'SellEnabled' => ['w4os_provide_economy_helpers', 'transform' => 'boolean_to_string'],
                // 'PriceUpload' => 0, // Fixed value
                // 'PriceGroupCreate' => 0, // Fixed value
            ],

            'Gloebit' => [
                'Enabled' => ['w4os_currency_provider', 'transform' => 'is_gloebit_enabled'],
                'GLBSpecificStorageProvider' => ['transform' => 'get_storage_module_economy'],
                'GLBSpecificConnectionString' => ['transform' => 'get_db_credentials_economy'],
            ],

            'Messaging' => [
                'OfflineMessageModule' => 'OfflineMessageModule', // if w4os_provide_offline_messages is true
                'Enabled' => ['w4os_provide_offline_messages', 'transform' => 'boolean_to_string'],
                'OfflineMessageURL' => ['w4os_offline_helper_uri'],
            ],
        ],

        'moneyserver.ini' => [
            'MoneyServer' => [
                // - not used with Gloebit
                // - default if w4os_provide_economy_helpers is true and w4os_currency_provider is empty)
                // TODO: check if Podex uses MoneyServer
                'Enabled' => ['w4os_provide_economy_helpers', 'transform' => 'boolean_to_string'],
                'ScriptKey' => ['w4os_money_script_access_key'],
                'Rate' => ['w4os_currency_rate'],
                'RatePer' => ['w4os_currency_rate_per'],
            ],
            'MySql' => [
                'hostname' => ['w4os_economy_db_host'],
                'database' => ['w4os_economy_db_database'],
                'username' => ['w4os_economy_db_user'],
                'password' => ['w4os_economy_db_pass'],
                'port' => ['w4os_economy_db_port', 'default' => 3306],
            ],
        ],
    ];
    
    /**
     * All discovered w4os WordPress options from example.options
     * This is the complete list based on actual usage in the codebase
     */
    private static $all_w4os_options = [
        'w4os_search_url',
        'w4os_grid_name',
        'w4os_login_uri',
        'w4os_popular_places_max',
        'w4os_web_search_max',
        'w4os_profile_slug',
        'w4os_offline_sender',
        'w4os_hypevents_url',
        'w4os_db_host',
        'w4os_db_database',
        'w4os_db_user',
        'w4os_db_pass',
        'w4os_search_db_host',
        'w4os_search_db_database',
        'w4os_search_db_user',
        'w4os_search_db_pass',
        'w4os_economy_db_host',
        'w4os_economy_db_database',
        'w4os_economy_db_user',
        'w4os_economy_db_pass',
        'w4os_money_script_access_key',
        'w4os_currency_rate',
        'w4os_currency_rate_per',
        'w4os_currency_provider',
        'w4os_podex_error_message',
        'w4os_podex_redirect_url',
        'w4os_model_firstname',
        'w4os_model_lastname',
        'w4os_configuration_instructions',
        'w4os_assets_slug',
        'w4os_login_page',
        'w4os_provide_economy_helpers',
        'w4os_provide_offline_messages',
        'w4os_provide_search',
        'w4os_search_register',
        'w4os_sync_users',
        'w4os_enable_registration',
        'w4os_profile_page',
        'w4os_userlist_replace_name',
        'w4os_economy_helper_uri',
        'w4os_offline_helper_uri',
        'w4os_profile_edit_page',
        'w4os_profile_edit_page_custom',
        'w4os_profile_edit_page_provide',
        'w4os_profile_edit_page_standard',
        'w4os_profile_page_custom',
        'w4os_profile_page_provide',
        'w4os_profile_page_standard',
        'w4os_asset_server_uri',
        'w4os_assets_permalink',
        'w4os-avatar',
        'w4os-avatars',
        'w4os-credentials',
        'w4os_credentials',
        'w4os_db_port',
        'w4os_db_use_default',
        'w4os-economy',
        'w4os_economy_db_port',
        'w4os_economy_slug',
        'w4os_economy_use_default_db',
        'w4os_economy_use_robust_db',
        'w4os-enable-v3-beta',
        'w4os_enable_v3_beta',
        'w4os_exclude',
        'w4os_exclude_hypergrid',
        'w4os_exclude_models',
        'w4os_exclude_nomail',
        'w4os_exclude_tests',
        'w4os_external_asset_server_uri',
        'w4os_flush_rewrite_rules',
        'w4os_grid_info',
        'w4os-guide',
        'w4os_helpers_slug',
        'w4os_internal_asset_server_uri',
        'w4os_legacy_imported',
        'w4os_legacy_notice_dismissed',
        'w4os_model_info',
        'w4os_model_uuid',
        'w4os-models',
        'w4os-offline',
        'w4os_provide',
        'w4os_provide_asset_server',
        'w4os-region',
        'w4os-regions',
        'w4os-region-settings',
        'w4os_rewrite_rules',
        'w4os_rewrite_version',
        'w4os-search',
        'w4os_search_db_port',
        'w4os_search_db_use_default',
        'w4os_search_use_default_db',
        'w4os_search_use_robust_db',
        'w4os-settings',
        'w4os_settings',
        'w4os_settings_avatar',
        'w4os_settings_avIE',
        'w4os_settings_region',
        'w4os_settings_region_default',
        'w4os_tos_page_id',
        'w4os_upated',
        'w4os_updated',
        // WordPress core options that might be needed
        'gmt_offset',
        'license_key_w4os',
        'license_signature_w4os',
        'wppu_w4os_license_error',
        'date_format',
        'users_can_register',
        'avatars_can_register',
        'default_role',
        'opensim_rest_config',
        // New v3 beta option arrays
        'w4os_beta'
    ];
    
    /**
     * Transform a value according to the specified transformation
     */
    private static function transform_value($value, $transform, $all_options) {
        switch ($transform) {
            case 'boolean_to_string':
                return $value ? 'true' : 'false';
                
            case 'currency_provider_to_module':
                switch (strtolower($value)) {
                    case 'gloebit': return 'Gloebit';
                    case 'podex': return 'Podex'; 
                    case 'moneyserver':
                    case 'dtlnslmoneyserver':
                    default: return 'MoneyServer';
                }

            case 'is_gloebit_enabled':
                return (strtolower($value) === 'gloebit') ? 'true' : 'false';
                
            case 'get_db_credentials_robust':
                return self::get_db_credentials('robust', $all_options);
                
            case 'get_db_credentials_economy':
            case 'get_db_credentials_currency':
            case 'get_db_credentials_gloebit':
                return self::get_db_credentials('economy', $all_options);
                
            default:
                return $value;
        }
    }
    
    /**
     * Build connection parameters from WordPress options
     * 
     * @param string $type Type of database connection ('robust' or 'currency') 
     * @param array $all_options All available WordPress options
     * @return array|null Connection credentials as an array or null if not found
     */
    private static function get_db_credentials($type, $all_options) {
        if(!is_array($all_options) || empty($all_options)) {
            return null; // No options available
        }

        // First look into w4os-credentials for any existing credentials,
        // must be decrypted with W4OS3::decrypt()
        if (!empty($all_options['w4os-credentials'][$type] ?? null)) {
            // Unlikely to happen, key is usually the instance, not the service type
            return W4OS3::decrypt($all_options≠['w4os-credentials'][$type]);
        }

        // Find the uri for the service type, then look into w4os-credentials
        $serviceURI = null;
        switch($type) {
            case 'robust':
                $serviceURI = $all_options['w4os_login_uri'] ?? null;
                break;
            case 'currency':
            case 'economy':
            case 'gloebit':
                $serviceURI = $all_options['w4os_economy_helper_uri'] ?? null;
                break;
        }
        if(empty($serviceURI)) {
            // shoud return null, return debug instead for now
            return 'No URI found for ' . $type;
        }

        error_log('looking ' . $type . ' by serviceURI ' . $serviceURI );
        // $credentials = W4OS3::get_credentials( $serviceURI );
        $db_credentials = W4OS3::get_db_credentials( $serviceURI );
        if(!empty($db_credentials)) {
            // If credentials are found, return them
            return $db_credentials;
        }

        // Fallback to direct wp optionn storage
        $db_credentials = self::get_database_connection_info($all_options, $type);
        if(!empty($db_credentials['host']) && !empty($db_credentials['name'])) {
            // If we have at least host and database, return them
            return $db_credentials;
        }
        // // Look for credentials in w4os-credentials using the uri as key
        // if (!empty($all_options['w4os-credentials'][$uri] ?? null)) {
        //     // Decrypt the credentials
        //     return W4OS3::decrypt($all_options['w4os-credentials'][$uri]);
        // }
        
        return $type . ' credentials ' . $serviceURI . ' not found in w4os-credentials';
        // Get database connection details based on type if nothing else found
        // $db_info = self::get_database_connection_info($all_options, $type);
        // return implode(';', $db_info);

        // // Determine which database credentials to use
        // $prefix = '';
        // if ($type === 'currency') {
        //     $prefix = 'economy_';
        // } elseif ($type === 'search') {
        //     $prefix = 'search_';
        // }
        
        // // Get database connection details from WordPress options
        // $host = $all_options["w4os_{$prefix}db_host"] ?? $all_options['w4os_db_host'] ?? '';
        // $database = $all_options["w4os_{$prefix}db_database"] ?? $all_options['w4os_db_database'] ?? '';
        // $user = $all_options["w4os_{$prefix}db_user"] ?? $all_options['w4os_db_user'] ?? '';
        // $pass = $all_options["w4os_{$prefix}db_pass"] ?? $all_options['w4os_db_pass'] ?? '';
        // $port = $all_options["w4os_{$prefix}db_port"] ?? $all_options['w4os_db_port'] ?? 3306;
        
        // // Only build connection string if we have minimum required info
        // if (empty($host) || empty($database)) {
        //     return null;
        // }
        
        // // Build MySQL connection string for OpenSim
        // $parts = array();
        // $parts[] = "Data Source=" . $host;
        // $parts[] = "Database=" . $database;
        // if (!empty($user)) $parts[] = "User ID=" . $user;
        // if (!empty($pass)) $parts[] = "Password=" . $pass;
        // if ($port != 3306) $parts[] = "Port=" . $port;
        // $parts[] = "Old Guids=true";
        
        // return implode(';', $parts);
    }
    
    /**
     */
    private static function get_database_connection_info($all_options, $prefix = '') {
        $db_prefix = $prefix ? $prefix . '_' : '';
        
        $type = $all_options["w4os_{$db_prefix}db_type"] ?? 'mysql';
        $db_name = $all_options["w4os_{$db_prefix}db_name"] ?? '';
        error_log("looking for {$type} database w4os_{$db_prefix}db_host");
        $creds = [
            'type' => $type,
            'host' => $all_options["w4os_{$db_prefix}db_host"] ?? null,
            'name' => $all_options["w4os_{$db_prefix}db_name"] ?? $all_options["w4os_{$db_prefix}db_database"] ?? '',
            'user' => $all_options["w4os_{$db_prefix}db_user"] ?? null,
            'pass' => $all_options["w4os_{$db_prefix}db_pass"] ?? null,
            'port' => $all_options["w4os_{$db_prefix}db_port"] ?? ($type === 'mysql' ? 3306 : ($type === 'pgsql' ? 5432 : null)),
            'enabled' => $all_options["w4os_{$db_prefix}db_enabled"] ?? true,
        ];
        return $creds;
    }
    
    /**
     * Migrate WordPress options to Engine Settings using comprehensive mapping
     * 
     * @param array $options Optional array of options to migrate. If empty, gets all mapped options.
     * @return array Migration results
     */
    public static function migrate_wordpress_options($options = null) {
        $results = [
            'migrated' => [],
            'skipped' => [],
            'errors' => []
        ];
        
        // Get all WordPress options that start with 'w4os'
        $all_wp_options = self::get_all_w4os_options();
        
        // Process each INI file and its sections
        foreach (self::$ini_mapping as $ini_file => $file_sections) {
            foreach ($file_sections as $section => $section_mapping) {
                foreach ($section_mapping as $ini_key => $option_config) {
                    try {
                        // Find the best value using precedence rules
                        $value = self::find_option_value_with_precedence($option_config, $all_wp_options);
                        
                        if (!empty($value)) {
                            // Apply transformation if specified
                            if (isset($option_config['transform'])) {
                                $value = self::transform_value($value, $option_config['transform'], $all_wp_options);
                            }
                            
                            // Save to Engine Settings using Section.Key format
                            if (Engine_Settings::set("$section.$ini_key", $value)) {
                                $display_value = is_string($value) ? $value : json_encode($value);
                                $display_value = is_string($display_value) && strlen($display_value) > 50 ? substr($display_value, 0, 47) . '...' : $display_value;
                                $results['migrated'][] = "$section.$ini_key = $display_value (in $ini_file)";
                            } else {
                                $results['errors'][] = "Failed to save $section.$ini_key";
                            }
                        } else {
                            $results['skipped'][] = "$section.$ini_key (no value found)";
                        }
                        
                    } catch (Exception $e) {
                        $results['errors'][] = "Error processing $section.$ini_key: " . $e->getMessage();
                    }
                }
            }
        }
        
        // Handle special array options (like beta settings)
        self::migrate_array_options($results, $all_wp_options);
        
        // Handle credentials separately
        self::migrate_credentials($results, $all_wp_options);
        
        // Clean up any redundant database credentials
        if (class_exists('Engine_Settings')) {
            Engine_Settings::cleanup_redundant_database_credentials();
        }
        
        return $results;
    }
    
    /**
     * Get all WordPress options that start with 'w4os' plus important core options
     */
    private static function get_all_w4os_options() {
        global $wpdb;
        
        $options = [];
        
        // Get all w4os options from wp_options table
        $wp_options = $wpdb->get_results(
            "SELECT option_name, option_value FROM {$wpdb->options} 
             WHERE option_name LIKE 'w4os%' 
             OR option_name IN ('gmt_offset', 'date_format', 'users_can_register', 'avatars_can_register', 'default_role', 'opensim_rest_config')
             ORDER BY option_name"
        );
        
        foreach ($wp_options as $option) {
            $options[$option->option_name] = maybe_unserialize($option->option_value);
        }
        
        // Also get all options from our known list using get_option to ensure completeness
        foreach (self::$all_w4os_options as $option_name) {
            if (!isset($options[$option_name])) {
                $value = get_option($option_name);
                if ($value !== false) {
                    $options[$option_name] = $value;
                }
            }
        }
        
        return $options;
    }
    
    /**
     * Find option value using precedence rules
     * 
     * @param array $option_config Configuration array for the option
     * @param array $all_wp_options All WordPress options 
     * @return mixed Found value or null
     */
    private static function find_option_value_with_precedence($option_config, $all_wp_options) {
        if(! is_array($option_config)) {
            error_log('Invalid option configuration provided: ' . print_r($option_config, true));
            return null; // No options to process
        }
        // Handle special case for transforms that don't need source options (like connection strings)
        if (count($option_config) === 1 && isset($option_config['transform'])) {
            // This is a pure transform (like building connection strings from multiple options)
            return 'TRANSFORM_ONLY';
        }
        
        // Remove transform key if present to get just the option names
        $option_names = array_filter($option_config, function($key) {
            return $key !== 'transform';
        }, ARRAY_FILTER_USE_KEY);
        
        // Go through precedence order
        foreach ($option_names as $option_name) {
            // Handle dotted notation (w4os_settings.key)
            if (strpos($option_name, '.') !== false) {
                list($base_option, $sub_key) = explode('.', $option_name, 2);
                
                if (isset($all_wp_options[$base_option])) {
                    $base_value = $all_wp_options[$base_option];
                    
                    // If it's an array, look for the sub key
                    if (is_array($base_value) && isset($base_value[$sub_key])) {
                        $value = $base_value[$sub_key];
                        if (!empty($value)) {
                            return $value;
                        }
                    }
                }
            } else {
                // Direct option name
                if (isset($all_wp_options[$option_name])) {
                    $value = $all_wp_options[$option_name];
                    if (!empty($value)) {
                        return $value;
                    }
                }
            }
        }
        
        return null;
    }

    
    /**
     * Migrate array-based options like beta settings
     */
    private static function migrate_array_options(&$results, $all_wp_options) {
        // Handle beta options array
        if (isset($all_wp_options['w4os_beta']) && is_array($all_wp_options['w4os_beta'])) {
            foreach ($all_wp_options['w4os_beta'] as $key => $value) {
                try {
                    $ini_key = "W4OS.Beta" . ucfirst($key);
                    $success = Engine_Settings::set($ini_key, $value);
                    
                    if ($success) {
                        $results['migrated'][] = "w4os_beta[{$key}] -> {$ini_key} = {$value}";
                    } else {
                        $results['errors'][] = "Failed to set {$ini_key} = {$value}";
                    }
                } catch (Exception $e) {
                    $results['errors'][] = "Error migrating beta option {$key}: " . $e->getMessage();
                }
            }
        }
        
        // Handle other array options if they exist
        if (isset($all_wp_options['opensim_rest_config']) && is_array($all_wp_options['opensim_rest_config'])) {
            foreach ($all_wp_options['opensim_rest_config'] as $key => $value) {
                try {
                    $ini_key = "Network." . ucfirst($key);
                    $success = Engine_Settings::set($ini_key, $value);
                    
                    if ($success) {
                        $results['migrated'][] = "opensim_rest_config[{$key}] -> {$ini_key} = {$value}";
                    } else {
                        $results['errors'][] = "Failed to set {$ini_key} = {$value}";
                    }
                } catch (Exception $e) {
                    $results['errors'][] = "Error migrating rest config {$key}: " . $e->getMessage();
                }
            }
        }
    }
    
    /**
     * Handle credentials separately - they need special treatment
     * Store them in a separate credentials file for security
     * 
     * @param array $results Migration results array to update
     * @param array $all_wp_options All WordPress options
     */
    private static function migrate_credentials(&$results, $all_wp_options) {
        // Handle w4os-credentials array (encrypted service credentials)
        if (isset($all_wp_options['w4os-credentials']) && is_array($all_wp_options['w4os-credentials'])) {
            // Load existing credentials from JSON file
            Engine_Settings::init();
            $existing_credentials = array();
            
            $credentials_file = Engine_Settings::get_config_dir() . '/credentials.json';
            if (file_exists($credentials_file)) {
                $json_content = file_get_contents($credentials_file);
                if ($json_content) {
                    $existing_credentials = json_decode($json_content, true) ?: array();
                }
            }
            
            // Migrate each encrypted credential entry
            $migrated_count = 0;
            foreach ($all_wp_options['w4os-credentials'] as $service_uri => $encrypted_value) {
                if (!empty($encrypted_value)) {
                    // Extract host:port from service URI for the credential key
                    $parsed = parse_url($service_uri);
                    if ($parsed && isset($parsed['host'])) {
                        $credential_key = $parsed['host'];
                        if (isset($parsed['port'])) {
                            $credential_key .= ':' . $parsed['port'];
                        }
                        
                        // Store the encrypted value directly (it's already encrypted by W4OS3)
                        $existing_credentials[$credential_key] = $encrypted_value;
                        $migrated_count++;
                    }
                }
            }
            
            if ($migrated_count > 0) {
                // Save back to JSON file
                $json_content = json_encode($existing_credentials, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
                $success = file_put_contents($credentials_file, $json_content, LOCK_EX);
                
                if ($success) {
                    chmod($credentials_file, 0600); // Secure permissions
                    $results['migrated'][] = "w4os-credentials ({$migrated_count} service credentials) -> credentials.json";
                } else {
                    $results['errors'][] = "Failed to save encrypted credentials to JSON file";
                }
            }
        }
        
        // NOTE: We intentionally skip w4os_credentials (legacy plain text) 
        // as it's irrelevant and should not be stored in the new system
    }
    
    /**
     * Get available WordPress options for migration
     * This method is called by Engine_Settings::get_available_wordpress_options()
     * 
     * @return array Array of available WordPress options with their current values
     */
    public static function get_available_options() {
        return self::get_all_w4os_options();
    }
    
    /**
     * Get a list of all mapped INI keys that can be migrated
     * 
     * @return array Array of INI section.key combinations
     */
    public static function get_mapped_ini_keys() {
        $mapped_keys = [];
        
        foreach (self::$ini_mapping as $ini_file => $file_sections) {
            foreach ($file_sections as $section => $section_mapping) {
                foreach ($section_mapping as $ini_key => $option_config) {
                    $mapped_keys[] = "$section.$ini_key";
                }
            }
        }
        
        return $mapped_keys;
    }
    
    /**
     * Get mapping information for a specific INI key
     * 
     * @param string $ini_key The INI key in format "Section.Key"
     * @return array|null Mapping configuration or null if not found
     */
    public static function get_mapping_for_key($ini_key) {
        if (strpos($ini_key, '.') === false) {
            return null;
        }
        
        list($section, $key) = explode('.', $ini_key, 2);
        
        // Search through all ini files to find the section
        foreach (self::$ini_mapping as $ini_file => $file_sections) {
            if (isset($file_sections[$section][$key])) {
                return $file_sections[$section][$key];
            }
        }
        
        return null;
    }
}
