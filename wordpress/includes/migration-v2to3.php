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
     * Precedence order:
     * 1. w4os_settings (v3 general options) - highest priority
     * 2. w4os-settings (v2 general options with dash) - medium priority  
     * 3. Direct option name - lowest priority
     */
    private static $ini_mapping = [
        'W4OS' => [
            'ModelFirstName' => ['w4os_settings.model_firstname', 'w4os-settings.model_firstname', 'w4os_model_firstname'],
            'ModelLastName' => ['w4os_settings.model_lastname', 'w4os-settings.model_lastname', 'w4os_model_lastname'],
            'ModelUUID' => ['w4os_settings.model_uuid', 'w4os-settings.model_uuid', 'w4os_model_uuid'],
            'ProfileSlug' => ['w4os_settings.profile_slug', 'w4os-settings.profile_slug', 'w4os_profile_slug'],
            'ProfilePage' => ['w4os_settings.profile_page', 'w4os-settings.profile_page', 'w4os_profile_page'],
            'AssetsSlug' => ['w4os_settings.assets_slug', 'w4os-settings.assets_slug', 'w4os_assets_slug'],
            'LoginPage' => ['w4os_settings.login_page', 'w4os-settings.login_page', 'w4os_login_page'],
            'ShowConfigurationInstructions' => ['w4os_settings.configuration_instructions', 'w4os-settings.configuration_instructions', 'w4os_configuration_instructions', 'transform' => 'boolean_to_string'],
            'PodexErrorMessage' => ['w4os_settings.podex_error_message', 'w4os-settings.podex_error_message', 'w4os_podex_error_message'],
            'PodexRedirectUrl' => ['w4os_settings.podex_redirect_url', 'w4os-settings.podex_redirect_url', 'w4os_podex_redirect_url'],
            'PopularPlacesMax' => ['w4os_settings.popular_places_max', 'w4os-settings.popular_places_max', 'w4os_popular_places_max'],
            'WebSearchMax' => ['w4os_settings.web_search_max', 'w4os-settings.web_search_max', 'w4os_web_search_max'],
            'SyncUsers' => ['w4os_settings.sync_users', 'w4os-settings.sync_users', 'w4os_sync_users', 'transform' => 'boolean_to_string'],
            'EnableRegistration' => ['w4os_settings.enable_registration', 'w4os-settings.enable_registration', 'w4os_enable_registration', 'transform' => 'boolean_to_string'],
        ],
        
        'Helpers' => [
            'OpensimMailSender' => ['w4os_settings.opensim_mail_sender', 'w4os-settings.opensim_mail_sender', 'w4os_offline_sender', 'w4os_opensim_mail_sender'],
            'HypeventsUrl' => ['w4os_settings.hypevents_url', 'w4os-settings.hypevents_url', 'w4os_hypevents_url'],
            'SearchOnly' => ['w4os_settings.search_only', 'w4os-settings.search_only', 'w4os_search_only', 'transform' => 'boolean_to_string'],
            'SearchRegistrars' => ['w4os_settings.search_registrars', 'w4os-settings.search_registrars', 'w4os_search_registrars', 'transform' => 'array_to_json'],
            'CurrencyMoneyTable' => ['w4os_settings.currency_money_table', 'w4os-settings.currency_money_table', 'w4os_currency_money_table'],
            'CurrencyTransactionTable' => ['w4os_settings.currency_transaction_table', 'w4os-settings.currency_transaction_table', 'w4os_currency_transaction_table'],
            'OfflineMessageTable' => ['w4os_settings.offline_message_table', 'w4os-settings.offline_message_table', 'w4os_offline_message_table'],
            'GloebitConversionTable' => ['w4os_settings.gloebit_conversion_table', 'w4os-settings.gloebit_conversion_table', 'w4os_gloebit_conversion_table', 'transform' => 'array_to_json'],
            'GloebitConversionThreshold' => ['w4os_settings.gloebit_conversion_threshold', 'w4os-settings.gloebit_conversion_threshold', 'w4os_gloebit_conversion_threshold'],
        ],
        
        'GridInfoService' => [
            'gridname' => ['w4os_settings.grid_name', 'w4os-settings.grid_name', 'w4os_grid_name'],
            'login' => ['w4os_settings.login_uri', 'w4os-settings.login_uri', 'w4os_login_uri'],
            'economy' => ['w4os_settings.economy_url', 'w4os-settings.economy_url', 'w4os_economy_url', 'w4os_economy_helper_uri'],
        ],
        
        'DatabaseService' => [
            'ConnectionString' => ['w4os_db_host', 'transform' => 'build_connection_string'],
        ],
        
        'SearchService' => [
            'SearchURL' => ['w4os_settings.search_url', 'w4os-settings.search_url', 'w4os_search_url'],
            'Enabled' => ['w4os_settings.provide_search', 'w4os-settings.provide_search', 'w4os_provide_search', 'transform' => 'boolean_to_string'],
            'ConnectionString' => ['w4os_search_db_host', 'transform' => 'build_search_connection_string'],
        ],
        
        'CurrencyService' => [
            'ConnectionString' => ['w4os_economy_db_host', 'transform' => 'build_currency_connection_string'],
        ],
        
        'OfflineMessageService' => [
            'ConnectionString' => ['w4os_db_host', 'transform' => 'build_connection_string'],
        ],
        
        'MoneyServer' => [
            'Enabled' => ['w4os_settings.provide_economy', 'w4os-settings.provide_economy', 'w4os_provide_economy_helpers', 'transform' => 'boolean_to_string'],
            'ScriptKey' => ['w4os_settings.money_script_access_key', 'w4os-settings.money_script_access_key', 'w4os_money_script_access_key'],
            'Rate' => ['w4os_settings.currency_rate', 'w4os-settings.currency_rate', 'w4os_currency_rate'],
            'RatePer' => ['w4os_settings.currency_rate_per', 'w4os-settings.currency_rate_per', 'w4os_currency_rate_per'],
        ],
        
        'Economy' => [
            'economymodule' => ['w4os_settings.currency_provider', 'w4os-settings.currency_provider', 'w4os_currency_provider', 'transform' => 'currency_provider_to_module'],
        ],
        
        'Gloebit' => [
            'Enabled' => ['w4os_settings.gloebit_enabled', 'w4os-settings.gloebit_enabled', 'w4os_gloebit_enabled', 'transform' => 'boolean_to_string'],
            'GLBEnvironment' => ['w4os_settings.gloebit_environment', 'w4os-settings.gloebit_environment', 'w4os_gloebit_environment'],
            'GLBKey' => ['w4os_settings.gloebit_key', 'w4os-settings.gloebit_key', 'w4os_gloebit_key'],
            'GLBSecret' => ['w4os_settings.gloebit_secret', 'w4os-settings.gloebit_secret', 'w4os_gloebit_secret'],
        ],
        
        'Const' => [
            'BaseURL' => ['w4os_settings.base_url', 'w4os-settings.base_url', 'w4os_base_url'],
            'PublicPort' => ['w4os_settings.public_port', 'w4os-settings.public_port', 'w4os_public_port'],
            'PrivatePort' => ['w4os_settings.private_port', 'w4os-settings.private_port', 'w4os_private_port'],
            'WebURL' => ['w4os_settings.web_url', 'w4os-settings.web_url', 'w4os_web_url'],
        ]
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
                
            case 'string_to_boolean': 
                return ($value === 'true' || $value === '1' || $value === 1) ? 'true' : 'false';
                
            case 'currency_provider_to_module':
                // Map currency provider to economy module
                switch (strtolower($value)) {
                    case 'gloebit': return 'Gloebit';
                    case 'podex': return 'Podex'; 
                    case 'moneyserver':
                    case 'dtlnslmoneyserver':
                    default: return 'MoneyServer';
                }
                
            case 'build_connection_string':
                // Build connection string from database components
                $host = $all_options['w4os_db_host'] ?? '';
                $database = $all_options['w4os_db_database'] ?? '';
                $user = $all_options['w4os_db_user'] ?? '';
                $password = $all_options['w4os_db_pass'] ?? '';
                $port = $all_options['w4os_db_port'] ?? 3306;
                
                if (empty($host) || empty($database)) {
                    return null; // Skip if incomplete
                }
                
                $parts = array();
                $parts[] = "Data Source=" . $host;
                $parts[] = "Database=" . $database;
                if ($user) $parts[] = "User ID=" . $user;
                if ($password) $parts[] = "Password=" . $password;
                if ($port != 3306) $parts[] = "Port=" . $port;
                $parts[] = "Old Guids=true";
                return implode(';', $parts);
                
            case 'build_currency_connection_string':
                // Build connection string for currency database
                $host = $all_options['w4os_economy_db_host'] ?? $all_options['w4os_db_host'] ?? '';
                $database = $all_options['w4os_economy_db_database'] ?? '';
                $user = $all_options['w4os_economy_db_user'] ?? $all_options['w4os_db_user'] ?? '';
                $password = $all_options['w4os_economy_db_pass'] ?? $all_options['w4os_db_pass'] ?? '';
                $port = $all_options['w4os_economy_db_port'] ?? $all_options['w4os_db_port'] ?? 3306;
                
                if (empty($host) || empty($database)) {
                    return null; // Skip if incomplete
                }
                
                $parts = array();
                $parts[] = "Data Source=" . $host;
                $parts[] = "Database=" . $database;
                if ($user) $parts[] = "User ID=" . $user;
                if ($password) $parts[] = "Password=" . $password;
                if ($port != 3306) $parts[] = "Port=" . $port;
                $parts[] = "Old Guids=true";
                return implode(';', $parts);
                
            case 'build_search_connection_string':
                // Build connection string for search database (usually same as main)
                $host = $all_options['w4os_search_db_host'] ?? $all_options['w4os_db_host'] ?? '';
                $database = $all_options['w4os_search_db_database'] ?? $all_options['w4os_db_database'] ?? '';
                $user = $all_options['w4os_search_db_user'] ?? $all_options['w4os_db_user'] ?? '';
                $password = $all_options['w4os_search_db_pass'] ?? $all_options['w4os_db_pass'] ?? '';
                $port = $all_options['w4os_search_db_port'] ?? $all_options['w4os_db_port'] ?? 3306;
                
                if (empty($host) || empty($database)) {
                    return null; // Skip if incomplete
                }
                
                $parts = array();
                $parts[] = "Data Source=" . $host;
                $parts[] = "Database=" . $database;
                if ($user) $parts[] = "User ID=" . $user;
                if ($password) $parts[] = "Password=" . $password;
                if ($port != 3306) $parts[] = "Port=" . $port;
                $parts[] = "Old Guids=true";
                return implode(';', $parts);
                
            case 'array_to_json':
                // Convert array values to JSON format for INI storage
                if (is_array($value)) {
                    return json_encode($value, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
                }
                return $value;
                
            default:
                return $value;
        }
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
        
        // Process each INI section
        foreach (self::$ini_mapping as $section => $section_mapping) {
            foreach ($section_mapping as $ini_key => $option_config) {
                try {
                    // Find the best value using precedence rules
                    $value = self::find_option_value_with_precedence($option_config, $all_wp_options);
                    
                    if (!self::is_empty_value($value)) {
                        // Apply transformation if specified
                        if (isset($option_config['transform'])) {
                            $value = self::transform_value($value, $option_config['transform'], $all_wp_options);
                        }
                        
                        // Save to Engine Settings
                        if (Engine_Settings::set("$section.$ini_key", $value)) {
                            $display_value = is_string($value) && strlen($value) > 50 ? substr($value, 0, 47) . '...' : $value;
                            $results['migrated'][] = "$section.$ini_key = $display_value";
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
        
        // Handle special array options (like beta settings)
        self::migrate_array_options($results, $all_wp_options);
        
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
                        if (!self::is_empty_value($value)) {
                            return $value;
                        }
                    }
                }
            } else {
                // Direct option name
                if (isset($all_wp_options[$option_name])) {
                    $value = $all_wp_options[$option_name];
                    if (!self::is_empty_value($value)) {
                        return $value;
                    }
                }
            }
        }
        
        return null;
    }

    /**
     * Check if a value should be considered empty and skipped
     */
    private static function is_empty_value($value) {
        if ($value === null || $value === false) {
            return true;
        }
        
        if (is_string($value) && trim($value) === '') {
            return true;
        }
        
        if (is_array($value) && empty($value)) {
            return true;
        }
        
        return false;
    }
    
    /**
     * Migrate WordPress options stored as arrays
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
        
        foreach (self::$ini_mapping as $section => $section_mapping) {
            foreach ($section_mapping as $ini_key => $option_config) {
                $mapped_keys[] = "$section.$ini_key";
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
        
        if (isset(self::$ini_mapping[$section][$key])) {
            return self::$ini_mapping[$section][$key];
        }
        
        return null;
    }
}
