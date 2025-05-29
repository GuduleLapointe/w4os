<?php
/**
 * Engine Settings Manager
 * 
 * Manages configuration in .ini format for standalone use.
 * This allows the engine to work independently of WordPress or other frameworks.
 */

if (!defined('ABSPATH') && !defined('OPENSIM_ENGINE')) {
    exit;
}

class Engine_Settings {
    
    /**
     * @var array Cached settings data
     */
    private static $settings = array();
    
    /**
     * @var string Path to the settings file
     */
    private static $settings_file = null;
    
    /**
     * @var bool Whether settings have been loaded
     */
    private static $loaded = false;
    
    /**
     * @var string Path to config directory
     */
    private static $config_dir = null;
    
    /**
     * Initialize settings with file path
     */
    public static function init($settings_file = null) {
        if (empty($settings_file)) {
            // Default to engine/config/engine.ini 
            self::$config_dir = OPENSIM_ENGINE_PATH . '/config';
            $settings_file = self::$config_dir . '/engine.ini';
        }
        
        self::$settings_file = $settings_file;
        self::ensure_config_directory();
        self::load();
    }
    
    /**
     * Ensure config directory exists and is properly secured
     */
    private static function ensure_config_directory() {
        if (!self::$config_dir) {
            self::$config_dir = dirname(self::$settings_file);
        }
        
        // Create directory if it doesn't exist with restrictive permissions
        if (!is_dir(self::$config_dir)) {
            if (!mkdir(self::$config_dir, 0700, true)) {
                error_log("Engine_Settings: Failed to create config directory: " . self::$config_dir);
                return false;
            }
        }
        
        // Set proper permissions (owner read/write only)
        chmod(self::$config_dir, 0700);
        
        // Create .htaccess to deny web access
        $htaccess_file = self::$config_dir . '/.htaccess';
        if (!file_exists($htaccess_file)) {
            $htaccess_content = "# Deny all web access to config directory\n";
            $htaccess_content .= "Require all denied\n";
            $htaccess_content .= "# Legacy Apache 2.2 compatibility\n";
            $htaccess_content .= "Order deny,allow\n";
            $htaccess_content .= "Deny from all\n";
            file_put_contents($htaccess_file, $htaccess_content);
        }
        
        return true;
    }
    
    /**
     * Load settings from .ini file
     */
    private static function load() {
        if (self::$loaded) {
            return;
        }
        
        // Don't create default settings - let it be empty until first save
        if (!file_exists(self::$settings_file)) {
            self::$settings = array();
            self::$loaded = true;
            return;
        }
        
        $parsed = parse_ini_file(self::$settings_file, true);
        if ($parsed === false) {
            error_log("Engine_Settings: Failed to parse settings file: " . self::$settings_file);
            self::$settings = array();
        } else {
            self::$settings = $parsed;
        }
        
        self::$loaded = true;
    }
    
    /**
     * Get a setting value
     * 
     * @param string $key Setting key in format "section.key" or just "key" for default section
     * @param mixed $default Default value if setting doesn't exist
     * @return mixed Setting value
     */
    public static function get($key, $default = null) {
        self::load();
        
        // Handle section.key format
        if (strpos($key, '.') !== false) {
            list($section, $setting_key) = explode('.', $key, 2);
            return isset(self::$settings[$section][$setting_key]) 
                ? self::$settings[$section][$setting_key] 
                : $default;
        }
        
        // Handle flat key (default section)
        return isset(self::$settings['default'][$key]) 
            ? self::$settings['default'][$key] 
            : $default;
    }
    
    /**
     * Set a setting value
     * 
     * @param string $key Setting key in format "section.key" or just "key" for default section
     * @param mixed $value Setting value
     * @return bool Success
     */
    public static function set($key, $value) {
        self::load();
        
        // Handle section.key format
        if (strpos($key, '.') !== false) {
            list($section, $setting_key) = explode('.', $key, 2);
            if (!isset(self::$settings[$section])) {
                self::$settings[$section] = array();
            }
            self::$settings[$section][$setting_key] = $value;
        } else {
            // Handle flat key (default section)
            if (!isset(self::$settings['default'])) {
                self::$settings['default'] = array();
            }
            self::$settings['default'][$key] = $value;
        }
        
        return self::save();
    }
    
    /**
     * Get all settings for a section
     * 
     * @param string $section Section name
     * @return array Section settings
     */
    public static function get_section($section) {
        self::load();
        return isset(self::$settings[$section]) ? self::$settings[$section] : array();
    }
    
    /**
     * Set multiple settings for a section
     * 
     * @param string $section Section name
     * @param array $settings Settings array
     * @return bool Success
     */
    public static function set_section($section, $settings) {
        self::load();
        self::$settings[$section] = $settings;
        return self::save();
    }
    
    /**
     * Delete a setting
     * 
     * @param string $key Setting key in format "section.key" or just "key"
     * @return bool Success
     */
    public static function delete($key) {
        self::load();
        
        if (strpos($key, '.') !== false) {
            list($section, $setting_key) = explode('.', $key, 2);
            if (isset(self::$settings[$section][$setting_key])) {
                unset(self::$settings[$section][$setting_key]);
                // Remove empty sections
                if (empty(self::$settings[$section])) {
                    unset(self::$settings[$section]);
                }
            }
        } else {
            if (isset(self::$settings['default'][$key])) {
                unset(self::$settings['default'][$key]);
            }
        }
        
        return self::save();
    }
    
    /**
     * Delete an entire section
     * 
     * @param string $section Section name
     * @return bool Success
     */
    public static function delete_section($section) {
        self::load();
        
        if (isset(self::$settings[$section])) {
            unset(self::$settings[$section]);
            return self::save();
        }
        
        return true; // Section didn't exist, consider it successful
    }
    
    /**
     * Check if a setting exists
     * 
     * @param string $key Setting key
     * @return bool
     */
    public static function has($key) {
        self::load();
        
        if (strpos($key, '.') !== false) {
            list($section, $setting_key) = explode('.', $key, 2);
            return isset(self::$settings[$section][$setting_key]);
        }
        
        return isset(self::$settings['default'][$key]);
    }
    
    /**
     * Get all settings
     * 
     * @return array All settings
     */
    public static function all() {
        self::load();
        return self::$settings;
    }
    
    /**
     * Check if settings have been configured (file exists and has content)
     * 
     * @return bool True if configured
     */
    public static function is_configured() {
        self::load();
        return !empty(self::$settings);
    }
    
    /**
     * Save settings to .ini file
     * 
     * @return bool Success
     */
    private static function save() {
        if (empty(self::$settings_file)) {
            return false;
        }
        
        // Ensure config directory exists and is secured
        self::ensure_config_directory();
        
        $ini_content = self::array_to_ini(self::$settings);
        
        $result = file_put_contents(self::$settings_file, $ini_content, LOCK_EX);
        if ($result === false) {
            error_log("Engine_Settings: Failed to write settings file: " . self::$settings_file);
            return false;
        }
        
        // Set restrictive permissions on the config file
        chmod(self::$settings_file, 0600);
        
        return true;
    }
    
    /**
     * Convert array to .ini format string
     * 
     * @param array $array Settings array
     * @return string INI formatted string
     */
    private static function array_to_ini($array) {
        $ini = "; Engine Settings Configuration\n";
        $ini .= "; Generated on " . date('Y-m-d H:i:s') . "\n\n";
        
        foreach ($array as $section => $settings) {
            if (!is_array($settings)) {
                // Handle non-sectioned settings
                $ini .= "$section = " . self::format_ini_value($settings) . "\n";
                continue;
            }
            
            $ini .= "[$section]\n";
            foreach ($settings as $key => $value) {
                // Special handling for DatabaseService credentials arrays
                if ($section === 'DatabaseService' && $key === 'credentials' && is_array($value)) {
                    // Skip credentials array - the ConnectionString should be preserved separately
                    continue;
                } elseif ($section === 'DatabaseService' && $key === 'ConnectionString') {
                    // Preserve connection string format but ensure proper quoting
                    $ini .= "$key = " . self::format_ini_value($value) . "\n";
                } elseif (is_array($value) && isset($value['saveformat']) && $value['saveformat'] === 'connection_string') {
                    // Convert credentials array back to connection string format
                    if (class_exists('Helpers')) {
                        $connection_string = Helpers::array_to_connectionstring($value);
                        $ini .= "ConnectionString = $connection_string\n";
                    }
                } else {
                    // Handle regular key-value pairs
                    $ini .= "$key = " . self::format_ini_value($value) . "\n";
                }
            }
            $ini .= "\n";
        }
        
        return $ini;
    }
    
    /**
     * Format a value for .ini file
     * 
     * @param mixed $value Value to format
     * @return string Formatted value
     */
    private static function format_ini_value($value) {
        if (is_bool($value)) {
            return $value ? 'true' : 'false';
        }
        
        if (is_numeric($value)) {
            return (string) $value;
        }
        
        // Handle arrays - use more readable formats
        if (is_array($value)) {
            // For simple arrays with numeric keys, use comma-separated format
            if (array_keys($value) === range(0, count($value) - 1)) {
                // Simple indexed array
                $items = array_map(function($item) {
                    return is_string($item) ? $item : json_encode($item);
                }, $value);
                return implode(',', $items);
            } else {
                // Associative array - use JSON but without excessive quoting
                $json = json_encode($value, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
                // Don't quote JSON if it doesn't contain problematic characters for INI
                if (strpos($json, ';') === false && strpos($json, '#') === false) {
                    return $json;
                } else {
                    return '"' . str_replace('"', '""', $json) . '"';
                }
            }
        }
        
        if (empty($value)) {
            return '""';
        }
        
        // Check if the string is already JSON (from our transformation)
        if (is_string($value) && (
            (substr($value, 0, 1) === '{' && substr($value, -1) === '}') ||
            (substr($value, 0, 1) === '[' && substr($value, -1) === ']')
        )) {
            // Already JSON-encoded, avoid double quoting if safe
            if (strpos($value, ';') === false && strpos($value, '#') === false) {
                return $value;
            } else {
                return '"' . str_replace('"', '""', $value) . '"';
            }
        }
        
        // Debug logging for ConnectionString values
        if (strpos($value, 'Data Source=') === 0) {
            error_log("Engine_Settings DEBUG format_ini_value ConnectionString: " . var_export($value, true) . " -> semicolon check: " . (strpos($value, ';') !== false ? 'YES' : 'NO'));
        }
        
        // Quote strings that contain special characters (including semicolons for connection strings)
        if (is_string($value) && (
            strpos($value, ' ') !== false ||
            strpos($value, '"') !== false ||
            strpos($value, "'") !== false ||
            strpos($value, ';') !== false ||
            strpos($value, '#') !== false
        )) {
            $quoted = '"' . str_replace('"', '""', $value) . '"';
            if (strpos($value, 'Data Source=') === 0) {
                error_log("Engine_Settings DEBUG format_ini_value ConnectionString QUOTED: " . var_export($quoted, true));
            }
            return $quoted;
        }
        
        return (string) $value;
    }
    
    /**
     * Import settings from OpenSim .ini format with variable resolution
     * 
     * @param array $opensim_config OpenSim config array
     * @return bool Success
     */
    public static function import_from_opensim($opensim_config) {
        if (!is_array($opensim_config)) {
            return false;
        }
        
        // Convert OpenSim config to engine format while preserving connection strings
        $engine_config = array();
        
        // Map DatabaseService section - preserve connection string format
        if (isset($opensim_config['DatabaseService'])) {
            $engine_config['DatabaseService'] = array();
            
            // Preserve original connection string if it exists
            if (isset($opensim_config['DatabaseService']['ConnectionString'])) {
                $engine_config['DatabaseService']['ConnectionString'] = $opensim_config['DatabaseService']['ConnectionString'];
            }
            
            // Also add parsed credentials for programmatic access
            if (class_exists('Helpers') && !empty($opensim_config['DatabaseService']['ConnectionString'])) {
                $db_creds = Helpers::connectionstring_to_array($opensim_config['DatabaseService']['ConnectionString']);
                $engine_config['DatabaseService']['credentials'] = $db_creds;
            }
            
            // Copy other DatabaseService settings as-is
            foreach ($opensim_config['DatabaseService'] as $key => $value) {
                if ($key !== 'ConnectionString') {
                    $engine_config['DatabaseService'][$key] = $value;
                }
            }
        }
        
        // Map other sections directly - preserve OpenSim format
        $sections_to_preserve = array('Network', 'Const', 'GridInfoService', 'LoginService', 'UserAccountService', 'AuthenticationService');
        
        foreach ($sections_to_preserve as $section) {
            if (isset($opensim_config[$section])) {
                $engine_config[$section] = $opensim_config[$section];
            }
        }
        
        // Merge any additional sections not in the preserve list
        foreach ($opensim_config as $section => $settings) {
            if (!in_array($section, $sections_to_preserve) && $section !== 'DatabaseService') {
                $engine_config[$section] = $settings;
            }
        }
        
        self::$settings = array_merge(self::$settings, $engine_config);
        return self::save();
    }
    
    /**
     * Import settings from OpenSim .ini file
     * 
     * @param string $ini_file_path Path to .ini file
     * @return bool Success
     */
    public static function import_from_ini_file($ini_file_path) {
        if (!file_exists($ini_file_path)) {
            error_log("Engine_Settings: INI file not found: " . $ini_file_path);
            return false;
        }
        
        try {
            // Use the existing OpenSim_Ini class that handles constants and special formatting
            $opensim_ini = new OpenSim_Ini($ini_file_path);
            $opensim_config = $opensim_ini->get_config();
            
            if (empty($opensim_config)) {
                error_log("Engine_Settings: No config data parsed from INI file: " . $ini_file_path);
                return false;
            }
            
            // Check for missing constants and report them
            if (method_exists($opensim_ini, 'has_errors') && $opensim_ini->has_errors()) {
                $missing_constants = $opensim_ini->get_missing_constants();
                error_log("Engine_Settings: Missing constants found in INI file: " . implode(', ', $missing_constants));
                
                // Still proceed with import but log the issues
                foreach ($missing_constants as $constant) {
                    error_log("Engine_Settings: Constant not found: \${$constant}");
                }
            }
            
            return self::import_from_opensim($opensim_config);
            
        } catch (Exception $e) {
            error_log("Engine_Settings: Failed to parse INI file: " . $ini_file_path . " - " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Parse OpenSim connection string format
     * 
     * @param string $connection_string Connection string
     * @return array Parsed components
     */
    private static function parse_connection_string($connection_string) {
        $parts = explode(';', $connection_string);
        $config = array();
        
        foreach ($parts as $part) {
            $pair = explode('=', $part, 2);
            if (count($pair) === 2) {
                $config[trim($pair[0])] = trim($pair[1]);
            }
        }
        
        return $config;
    }
    
    /**
     * Get the settings file path
     * 
     * @return string Settings file path
     */
    public static function get_file_path() {
        return self::$settings_file;
    }
    
    /**
     * Get the config directory path
     * 
     * @return string Config directory path
     */
    public static function get_config_dir() {
        return self::$config_dir;
    }
    
    /**
     * Clear all settings
     * 
     * @return bool Success
     */
    public static function clear_all() {
        self::load();
        self::$settings = array();
        return self::save();
    }
    
    /**
     * Fix existing array values that were saved as "Array" string
     * 
     * @return bool Success
     */
    public static function fix_array_values() {
        self::load();
        
        $fixed = false;
        
        // Common array constants that might have been saved as "Array"
        $array_constants = array(
            'GLOEBIT_CONVERSION_TABLE' => array(
                '400' => 199,
                '1050' => 499,
                '2150' => 999,
                '4500' => 1999,
                '11500' => 4999
            ),
            'EVENT_CATEGORIES' => array(
                'discussions' => 18,
                'sports' => 19,
                'live_music' => 20,
                'commercial' => 22,
                'nightlife' => 23,
                'games' => 24,
                'pageants' => 25,
                'education' => 26,
                'arts' => 27,
                'charity' => 28,
                'miscellaneous' => 29
            ),
            'SEARCH_REGISTRARS' => array(), // Empty array
            'ROBUST_DB' => null, // Will be skipped if not properly defined
            'CURRENCY_DB' => null,
            'SEARCH_DB' => null,
            'OFFLINE_DB' => null,
            'ROBUST_CONSOLE' => null
        );
        
        // Check each section for "Array" string values or JSON strings that could be arrays
        foreach (self::$settings as $section_name => $section_data) {
            if (!is_array($section_data)) continue;
            
            foreach ($section_data as $key => $value) {
                $should_fix = false;
                $new_value = null;
                
                // Case 1: Value is literally "Array" string
                if ($value === 'Array' && isset($array_constants[$key]) && $array_constants[$key] !== null) {
                    $new_value = $array_constants[$key];
                    $should_fix = true;
                }
                
                // Case 2: Value is a JSON string that could be an array
                elseif (is_string($value) && isset($array_constants[$key]) && $array_constants[$key] !== null) {
                    // Check if it's a quoted JSON string like "{""400"":199,...}"
                    $unquoted = trim($value, '"');
                    $unquoted = str_replace('""', '"', $unquoted); // Unescape double quotes
                    
                    if (substr($unquoted, 0, 1) === '{' || substr($unquoted, 0, 1) === '[') {
                        // It's JSON, replace with our clean array
                        $new_value = $array_constants[$key];
                        $should_fix = true;
                    }
                }
                
                if ($should_fix) {
                    self::$settings[$section_name][$key] = $new_value;
                    $fixed = true;
                    error_log("Engine_Settings: Fixed array value for $key in section $section_name");
                }
            }
        }
        
        return $fixed ? self::save() : true;
    }
    
    /**
     * Migrate from legacy PHP constants to INI format
     * 
     * @param array $constants Array of PHP constants (typically from config.php)
     * @return bool Success
     */
    public static function migrate_from_constants($constants) {
        if (!is_array($constants)) {
            error_log("Engine_Settings: migrate_from_constants() expects array, got " . gettype($constants));
            return false;
        }
        
        // Read the expected constants from example.constants file
        $constants_file = dirname(OPENSIM_ENGINE_PATH) . '/tmp/example.constants';
        $expected_constants = array();
        if (file_exists($constants_file)) {
            $file_contents = file_get_contents($constants_file);
            $expected_constants = array_filter(array_map('trim', explode("\n", $file_contents)));
            // Remove comments and empty lines
            $expected_constants = array_filter($expected_constants, function($line) {
                return !empty($line) && !str_starts_with($line, '//') && $line !== 'define(...)';
            });
        }
        
        // Complete mapping table: CONSTANT_NAME => 'ini_file.section.variable_name'
        $mapping = array(
            // === Const section - Infrastructure URLs and ports ===
            'OPENSIM_LOGIN_URI' => array(
                'robust.Const.BaseURL' => 'extract_base_url',
                'robust.Const.PublicPort' => 'extract_port',
                'robust.Const.PrivatePort' => 'extract_port_plus_one',
                'robust.GridInfoService.login' => 'value_with_slash'
            ),
            'CURRENCY_HELPER_URL' => array(
                'robust.Const.WebURL' => 'extract_base_url',
                'robust.GridInfoService.economy' => 'value'
            ),
            'OSHELPERS_URL' => array(
                'robust.Const.WebURL' => 'extract_base_url',
                'robust.GridInfoService.SearchURL' => 'value_with_search_path'
            ),
            
            // === GridInfoService section ===
            'OPENSIM_GRID_NAME' => 'robust.GridInfoService.gridname',
            'OPENSIM_GRID_LOGO_URL' => 'robust.GridInfoService.gridnick',
            
            // === LoginService section ===
            'CURRENCY_NAME' => 'robust.LoginService.Currency',
            
            // === Network section ===
            'ROBUST_CONSOLE' => array(
                'robust.Network.ConsoleUser' => 'array_extract:ConsoleUser',
                'robust.Network.ConsolePass' => 'array_extract:ConsolePass',
                'robust.Network.ConsolePort' => 'array_extract:ConsolePort'
            ),
            
            // === Database connections - main Robust DB ===
            'ROBUST_DB' => array(
                'robust.DatabaseService.ConnectionString' => 'array_to_connection_string',
                'robust.DatabaseService.StorageProvider' => 'static:OpenSim.Data.MySQL.dll'
            ),
            // Note: Removing individual OPENSIM_DB_* mappings to DatabaseService to avoid conflicts
            
            // === OPENSIM_DB dual-purpose handling ===
            'OPENSIM_DB' => array(
                'helpers.HelperSettings.SearchOnly' => 'opensim_db_to_search_only',
                'robust.DatabaseService.ConnectionString' => 'opensim_db_array_to_connection_string'
            ),
            // Note: Individual OPENSIM_DB_* constants are NOT mapped to avoid storing redundant data
            
            // === Currency database - if different ===
            'CURRENCY_DB_HOST' => array(
                'robust.CurrencyService.ConnectionString' => 'build_from_parts',
                'helpers.HelperSettings.CurrencyDbHost' => 'value'
            ),
            'CURRENCY_DB_PORT' => 'helpers.HelperSettings.CurrencyDbPort', 
            'CURRENCY_DB_NAME' => 'helpers.HelperSettings.CurrencyDbName',
            'CURRENCY_DB_USER' => 'helpers.HelperSettings.CurrencyDbUser',
            'CURRENCY_DB_PASS' => 'helpers.HelperSettings.CurrencyDbPass',
            
            // === Search database - if different ===
            'SEARCH_DB_HOST' => array(
                'robust.SearchService.ConnectionString' => 'build_from_parts',
                'helpers.HelperSettings.SearchDbHost' => 'value'
            ),
            'SEARCH_DB_PORT' => 'helpers.HelperSettings.SearchDbPort',
            'SEARCH_DB_NAME' => 'helpers.HelperSettings.SearchDbName',
            'SEARCH_DB_USER' => 'helpers.HelperSettings.SearchDbUser',
            'SEARCH_DB_PASS' => 'helpers.HelperSettings.SearchDbPass',
            
            // === Offline messages database - if different ===
            'OFFLINE_DB_HOST' => array(
                'robust.OfflineMessageService.ConnectionString' => 'build_from_parts',
                'helpers.HelperSettings.OfflineDbHost' => 'value'
            ),
            'OFFLINE_DB_PORT' => 'helpers.HelperSettings.OfflineDbPort',
            'OFFLINE_DB_NAME' => 'helpers.HelperSettings.OfflineDbName',
            'OFFLINE_DB_USER' => 'helpers.HelperSettings.OfflineDbUser',
            'OFFLINE_DB_PASS' => 'helpers.HelperSettings.OfflineDbPass',
            
            // === Economy/Currency provider settings ===
            'CURRENCY_PROVIDER' => array(
                'robust.Economy.economymodule' => 'currency_provider_to_module',
                'robust.Gloebit.Enabled' => 'currency_provider_is_gloebit',
                'robust.MoneyServer.Enabled' => 'currency_provider_is_moneyserver'
            ),
            'CURRENCY_SCRIPT_KEY' => 'robust.MoneyServer.ScriptKey',
            'CURRENCY_RATE' => 'robust.MoneyServer.Rate',
            'CURRENCY_RATE_PER' => 'robust.MoneyServer.RatePer',
            'CURRENCY_USE_MONEYSERVER' => 'robust.MoneyServer.Enabled',
            'GLOEBIT_SANDBOX' => 'robust.Gloebit.GLBEnvironment:sandbox_to_env',
            
            // === All remaining constants from example.constants (mapped to HelperSettings with PascalCase) ===
            'CURRENCY_MONEY_TBL' => 'helpers.HelperSettings.CurrencyMoneyTable',
            'CURRENCY_TRANSACTION_TBL' => 'helpers.HelperSettings.CurrencyTransactionTable',
            'CURRENCY_HELPER_PATH' => 'helpers.HelperSettings.CurrencyHelperPath',
            'OFFLINE_MESSAGE_TBL' => 'helpers.HelperSettings.OfflineMessageTable',
            'SEARCH_TABLE_EVENTS' => 'helpers.HelperSettings.SearchTableEvents',
            'SEARCH_REGION_TABLE' => 'helpers.HelperSettings.SearchRegionTable',
            'MUTE_LIST_TBL' => 'helpers.HelperSettings.MuteListTable',
            'HYPEVENTS_URL' => 'helpers.HelperSettings.HypeventsUrl',
            'OSHELPERS_DIR' => 'helpers.HelperSettings.OshelpersDir',
            'OPENSIM_MAIL_SENDER' => 'helpers.HelperSettings.OpensimMailSender',
            'OPENSIM_USE_UTC_TIME' => 'helpers.HelperSettings.OpensimUseUtcTime',
            'EVENTS_NULL_KEY' => 'helpers.HelperSettings.EventsNullKey',
            'PODEX_ERROR_MESSAGE' => 'helpers.HelperSettings.PodexErrorMessage',
            'PODEX_REDIRECT_URL' => 'helpers.HelperSettings.PodexRedirectUrl',
            'GLOEBIT_CONVERSION_THRESHOLD' => 'helpers.HelperSettings.GloebitConversionThreshold',
            'GLOEBIT_CONVERSION_TABLE' => 'helpers.HelperSettings.GloebitConversionTable',
            'OSHELPERS' => 'helpers.HelperSettings.Oshelpers',
            
            // === Additional constants from example.constants that need to be handled (PascalCase) ===
            'CURRENCY_ADMIN_AVATAR' => 'helpers.HelperSettings.CurrencyAdminAvatar',
            'CURRENCY_BANK_AVATAR' => 'helpers.HelperSettings.CurrencyBankAvatar',
            'CURRENCY_CONVERT_THRESHOLD' => 'helpers.HelperSettings.CurrencyConvertThreshold',
            'CURRENCY_CONVERT_AFTER' => 'helpers.HelperSettings.CurrencyConvertAfter',
            'CURRENCY_BANKER_AVATAR' => 'helpers.HelperSettings.CurrencyBankerAvatar',
            'CURRENCY_CONVERTER_NAME' => 'helpers.HelperSettings.CurrencyConverterName',
            'CURRENCY_CONVERSION_RATE' => 'helpers.HelperSettings.CurrencyConversionRate',
            'CURRENCY_DIVISIBILITY' => 'helpers.HelperSettings.CurrencyDivisibility',
            'CURRENCY_DENOM' => 'helpers.HelperSettings.CurrencyDenom',
            'CURRENCY_ECONOMY_URL' => 'helpers.HelperSettings.CurrencyEconomyUrl',
            'CURRENCY_ENABLE_GROUPS' => 'helpers.HelperSettings.CurrencyEnableGroups',
            'CURRENCY_ENABLE_SIMULATOR_IP_CHECK' => 'helpers.HelperSettings.CurrencyEnableSimulatorIpCheck',
            'CURRENCY_MAX_GROUP_CHARGE' => 'helpers.HelperSettings.CurrencyMaxGroupCharge',
            'CURRENCY_GROUP_CREATE_FEE' => 'helpers.HelperSettings.CurrencyGroupCreateFee',
            'CURRENCY_GROUP_JOIN_FEE' => 'helpers.HelperSettings.CurrencyGroupJoinFee',
            'CURRENCY_ENABLE_LAND_SALES' => 'helpers.HelperSettings.CurrencyEnableLandSales',
            'CURRENCY_LAND_FEE' => 'helpers.HelperSettings.CurrencyLandFee',
            'CURRENCY_SELL_ENABLED' => 'helpers.HelperSettings.CurrencySellEnabled',
            'CURRENCY_TELEPORT_MIN_PRICE' => 'helpers.HelperSettings.CurrencyTeleportMinPrice',
            'CURRENCY_UPLOAD_CHARGE' => 'helpers.HelperSettings.CurrencyUploadCharge',
            'DTL_PAYMENT_MODULE' => 'helpers.HelperSettings.DtlPaymentModule',
            'DTL_PAYMENT_HANDLER' => 'helpers.HelperSettings.DtlPaymentHandler',
            'ENABLE_SEARCH' => 'helpers.HelperSettings.EnableSearch',
            'EVENT_CATEGORIES' => 'helpers.HelperSettings.EventCategories',
            'EVENT_CATEGORY_DISCUSSIONS' => 'helpers.HelperSettings.EventCategoryDiscussions',
            'EVENT_CATEGORY_SPORTS' => 'helpers.HelperSettings.EventCategorySports',
            'EVENT_CATEGORY_LIVE_MUSIC' => 'helpers.HelperSettings.EventCategoryLiveMusic',
            'EVENT_CATEGORY_COMMERCIAL' => 'helpers.HelperSettings.EventCategoryCommercial',
            'EVENT_CATEGORY_NIGHTLIFE' => 'helpers.HelperSettings.EventCategoryNightlife',
            'EVENT_CATEGORY_GAMES' => 'helpers.HelperSettings.EventCategoryGames',
            'EVENT_CATEGORY_PAGEANTS' => 'helpers.HelperSettings.EventCategoryPageants',
            'EVENT_CATEGORY_EDUCATION' => 'helpers.HelperSettings.EventCategoryEducation',
            'EVENT_CATEGORY_ARTS' => 'helpers.HelperSettings.EventCategoryArts',
            'EVENT_CATEGORY_CHARITY' => 'helpers.HelperSettings.EventCategoryCharity',
            'EVENT_CATEGORY_MISCELLANEOUS' => 'helpers.HelperSettings.EventCategoryMiscellaneous',
            'FORMAT_SEARCH_TIME' => 'helpers.HelperSettings.FormatSearchTime',
            'GLOEBIT_CONVERSION_MODULE' => 'helpers.HelperSettings.GloebitConversionModule',
            'GLOEBIT_ENABLE_LANDTOOL' => 'helpers.HelperSettings.GloebitEnableLandtool',
            'GLOEBIT_ERROR_MESSAGE' => 'helpers.HelperSettings.GloebitErrorMessage',
            'GLOEBIT_GENERIC_MESSAGE' => 'helpers.HelperSettings.GloebitGenericMessage',
            'GLOEBIT_GRID_SHORT_NAME' => 'helpers.HelperSettings.GloebitGridShortName',
            'GLOEBIT_LANDTOOL_ACCESS_TOKEN' => 'helpers.HelperSettings.GloebitLandtoolAccessToken',
            'GLOEBIT_LANDTOOL_ADMIN_TOKEN' => 'helpers.HelperSettings.GloebitLandtoolAdminToken',
            'GLOEBIT_MESSAGE_NOTIFICATION' => 'helpers.HelperSettings.GloebitMessageNotification',
            'GLOEBIT_OAUTH_TOKEN' => 'helpers.HelperSettings.GloebitOauthToken',
            'GLOEBIT_OWNER_NAME' => 'helpers.HelperSettings.GloebitOwnerName',
            'GLOEBIT_OWNER_EMAIL' => 'helpers.HelperSettings.GloebitOwnerEmail',
            'GLOEBIT_WELCOME_MESSAGE' => 'helpers.HelperSettings.GloebitWelcomeMessage',
            'GROUPS_DB' => 'helpers.HelperSettings.GroupsDb',
            'GROUPS_DB_HOST' => 'helpers.HelperSettings.GroupsDbHost',
            'GROUPS_DB_NAME' => 'helpers.HelperSettings.GroupsDbName',
            'GROUPS_DB_PASS' => 'helpers.HelperSettings.GroupsDbPass',
            'GROUPS_DB_PORT' => 'helpers.HelperSettings.GroupsDbPort',
            'GROUPS_DB_USER' => 'helpers.HelperSettings.GroupsDbUser',
            'MUTE_LIST_DB' => 'helpers.HelperSettings.MuteListDb',
            'MUTE_LIST_DB_HOST' => 'helpers.HelperSettings.MuteListDbHost',
            'MUTE_LIST_DB_NAME' => 'helpers.HelperSettings.MuteListDbName',
            'MUTE_LIST_DB_PASS' => 'helpers.HelperSettings.MuteListDbPass',
            'MUTE_LIST_DB_PORT' => 'helpers.HelperSettings.MuteListDbPort',
            'MUTE_LIST_DB_USER' => 'helpers.HelperSettings.MuteListDbUser',
            'MUTE_DB_HOST' => 'helpers.HelperSettings.MuteDbHost',
            'MUTE_DB_NAME' => 'helpers.HelperSettings.MuteDbName', 
            'MUTE_DB_PASS' => 'helpers.HelperSettings.MuteDbPass',
            'MUTE_DB_USER' => 'helpers.HelperSettings.MuteDbUser',
            'PROFILE_ENABLE_CLASSIFIEDS' => 'helpers.HelperSettings.ProfileEnableClassifieds',
            'PROFILE_ENABLE_PICKS' => 'helpers.HelperSettings.ProfileEnablePicks',
            'PROFILE_ENABLE_PARTNER' => 'helpers.HelperSettings.ProfileEnablePartner',
            'ROBUST_SERVICE' => 'helpers.HelperSettings.RobustService',
            'ROBUST_SERVICE_HOST' => 'helpers.HelperSettings.RobustServiceHost',
            'ROBUST_SERVICE_PORT' => 'helpers.HelperSettings.RobustServicePort',
            'SEARCH_ENABLE_CLASSIFIEDS' => 'helpers.HelperSettings.SearchEnableClassifieds',
            'SEARCH_ENABLE_EVENTS' => 'helpers.HelperSettings.SearchEnableEvents',
            'SEARCH_ENABLE_LAND' => 'helpers.HelperSettings.SearchEnableLand',
            'SEARCH_ENABLE_PLACES' => 'helpers.HelperSettings.SearchEnablePlaces',
            'SEARCH_ENABLE_PEOPLE' => 'helpers.HelperSettings.SearchEnablePeople',
            'SEARCH_ENABLE_GROUPS' => 'helpers.HelperSettings.SearchEnableGroups',
            'XMLRPC_ADMIN' => 'helpers.HelperSettings.XmlrpcAdmin',
            'XMLRPC_ADMIN_PASSWORD' => 'helpers.HelperSettings.XmlrpcAdminPassword',
        );
        
        // Process all expected constants, even if not defined
        $ini_config = array();
        $db_configs = array(); // Track database configurations for deduplication
        
        // Process constants that exist (not just expected ones)
        foreach ($constants as $constant_name => $value) {
            if (!isset($mapping[$constant_name])) {
                // Check if it should be mapped to HelperSettings with PascalCase
                $opensim_prefixes = array('OPENSIM_', 'ROBUST_', 'CURRENCY_', 'SEARCH_', 'OFFLINE_', 'GLOEBIT_', 'PODEX_', 'HYPEVENTS_', 'EVENTS_', 'DTL_', 'ENABLE_', 'EVENT_', 'FORMAT_', 'GROUPS_', 'MUTE_', 'PROFILE_', 'XMLRPC_');
                $mapped = false;
                foreach ($opensim_prefixes as $prefix) {
                    if (strpos($constant_name, $prefix) === 0) {
                        // Check if this is an individual DB constant that should be skipped when ConnectionString exists
                        if (self::should_skip_individual_db_constant($constant_name, $constants)) {
                            $mapped = true; // Mark as mapped to avoid error log, but skip storage
                            break;
                        }
                        
                        // Convert unmapped constants to PascalCase for better readability
                        $pascal_key = self::constant_to_pascal_case($constant_name);
                        $ini_config['HelperSettings'][$pascal_key] = $value;
                        $mapped = true;
                        break;
                    }
                }
                if (!$mapped) {
                    error_log("Engine_Settings: No mapping found for constant: $constant_name");
                }
                continue;
            }
            
            $constant_mapping = $mapping[$constant_name];
            
            // Handle complex mappings (one constant to multiple destinations)
            if (is_array($constant_mapping)) {
                foreach ($constant_mapping as $destination => $transform) {
                    self::apply_mapping($constant_name, $value, $destination, $transform, $ini_config, $db_configs, $constants);
                }
            } else {
                // Simple mapping (one constant to one destination)
                self::apply_mapping($constant_name, $value, $constant_mapping, 'value', $ini_config, $db_configs, $constants);
            }
        }
        
        // Merge with existing settings
        self::load();
        self::$settings = array_merge(self::$settings, $ini_config);
        
        // Post-process: Remove redundant individual DB credentials when ConnectionStrings exist
        self::cleanup_redundant_db_credentials();
        
        return self::save();
    }
    
    /**
     * Apply a single mapping transformation
     */
    private static function apply_mapping($constant_name, $value, $destination, $transform, &$ini_config, &$db_configs, $all_constants) {
        // Parse destination: ini_file.section.key[:modifier]
        $parts = explode('.', $destination);
        if (count($parts) < 3) {
            error_log("Engine_Settings: Invalid destination format: $destination");
            return;
        }
        
        $ini_file = $parts[0]; // 'robust' or 'helpers'
        $section = $parts[1];
        $key_with_modifier = $parts[2];
        
        // Extract modifier if present (e.g., "ConnectionString:host")
        $modifier = null;
        if (strpos($key_with_modifier, ':') !== false) {
            list($key, $modifier) = explode(':', $key_with_modifier, 2);
        } else {
            $key = $key_with_modifier;
        }
        
        // Apply transformation
        $transformed_value = self::transform_value($value, $transform, $modifier, $all_constants, $constant_name);
        
        // Debug logging
        if ($constant_name === 'OPENSIM_DB' || $constant_name === 'ROBUST_CONSOLE' || $constant_name === 'ROBUST_DB') {
            error_log("Engine_Settings DEBUG: $constant_name -> $destination ($transform:$modifier) = " . var_export($transformed_value, true));
        }
        
        // Handle database connection strings specially
        if ($key === 'ConnectionString') {
            // For ConnectionString, use the transformed value directly
            // Don't call handle_database_connection as it may override our value
            if ($transformed_value !== null) {
                if (!isset($ini_config[$section])) {
                    $ini_config[$section] = array();
                }
                // Debug logging for ConnectionString formatting
                error_log("Engine_Settings DEBUG ConnectionString: $constant_name -> $section.$key = " . var_export($transformed_value, true));
                $ini_config[$section][$key] = $transformed_value;
            }
            return;
        }
        
        // Skip if transformation returned null (e.g., condition not met)
        if ($transformed_value === null) {
            return;
        }
        
        // Set the value in the config
        if (!isset($ini_config[$section])) {
            $ini_config[$section] = array();
        }
        $ini_config[$section][$key] = $transformed_value;
    }
    
    /**
     * Transform a value according to the specified transformation
     */
    private static function transform_value($value, $transform, $modifier, $all_constants, $constant_name) {
        // Handle colon-separated transformations (e.g., "array_extract:ConsoleUser")
        if (strpos($transform, ':') !== false && !$modifier) {
            list($transform, $modifier) = explode(':', $transform, 2);
        }
        
        switch ($transform) {
            case 'value':
                // Handle arrays properly - convert to JSON for storage
                if (is_array($value)) {
                    return json_encode($value, JSON_UNESCAPED_SLASHES);
                }
                return $value;
                
            case 'value_with_slash':
                return $value ? rtrim($value, '/') . '/' : null;
                
            case 'value_with_search_path':
                return $value ? rtrim($value, '/') . '/search/' : null;
                
            case 'extract_base_url':
                if ($value) {
                    $parsed = parse_url($value);
                    return $parsed ? ($parsed['scheme'] ?? 'http') . '://' . ($parsed['host'] ?? '') : null;
                }
                return null;
                
            case 'extract_port':
                if ($value) {
                    $parsed = parse_url($value);
                    return $parsed ? ($parsed['port'] ?? 8002) : null;
                }
                return null;
                
            case 'extract_port_plus_one':
                if ($value) {
                    $parsed = parse_url($value);
                    return $parsed ? (($parsed['port'] ?? 8002) + 1) : null;
                }
                return null;
                
            case 'array_extract':
                if (is_array($value) && $modifier && isset($value[$modifier])) {
                    return $value[$modifier];
                }
                return null;
                
            case 'static':
                // Return a static value (the modifier contains the value)
                return $modifier;
                
            case 'array_to_connection_string':
                if (is_array($value)) {
                    $parts = array();
                    if (isset($value['host'])) $parts[] = "Data Source=" . $value['host'];
                    if (isset($value['name'])) $parts[] = "Database=" . $value['name'];
                    if (isset($value['user'])) $parts[] = "User ID=" . $value['user'];
                    if (isset($value['pass'])) $parts[] = "Password=" . $value['pass'];
                    if (isset($value['port']) && $value['port'] != 3306) $parts[] = "Port=" . $value['port'];
                    $parts[] = "Old Guids=true";
                    return implode(';', $parts) . ';';
                }
                return null;
                
            case 'currency_provider_to_module':
                // Map currency provider to economy module
                if ($value) {
                    switch (strtolower($value)) {
                        case 'gloebit': return 'Gloebit';
                        case 'podex': return 'Podex';
                        case 'moneyserver':
                        case 'dtlnslmoneyserver':
                        default: return 'BetaGridLikeMoneyModule';
                    }
                }
                return 'BetaGridLikeMoneyModule'; // Default
                
            case 'currency_provider_is_gloebit':
                return ($value && strtolower($value) === 'gloebit') ? 'true' : 'false';
                
            case 'currency_provider_is_moneyserver':
                return ($value && (strtolower($value) === 'moneyserver' || strtolower($value) === 'dtlnslmoneyserver')) ? 'true' : 'false';
                
            case 'env_to_boolean':
                // Convert GLBEnvironment to boolean (true for sandbox, false for production)
                return ($value === 'sandbox') ? 'true' : 'false';
                
            case 'boolean_to_env':
                // Convert boolean to environment string
                return ($value === true || $value === 'true') ? 'sandbox' : 'production';
                
            case 'sandbox_to_env':
                // Convert GLOEBIT_SANDBOX boolean to environment string
                return ($value === true || $value === 'true') ? 'sandbox' : 'production';
                
            case 'condition':
                // Return value only if condition is met
                if ($modifier && strpos($modifier, '=') !== false) {
                    list($condition_constant, $condition_value) = explode('=', $modifier, 2);
                    if (isset($all_constants[$condition_constant]) && $all_constants[$condition_constant] == $condition_value) {
                        return $value;
                    }
                }
                return null;
                
            case 'missing_constant':
                // Mark as missing constant for later replacement
                return "[MISSING_CONSTANT:$constant_name]";
                
            case 'to_pascal_case':
                // Convert CONSTANT_NAME to PascalCase
                return self::constant_to_pascal_case($constant_name);
                
            case 'opensim_db_to_search_only':
                // Convert OPENSIM_DB boolean to SearchOnly (inverted logic)
                if (is_bool($value)) {
                    return !$value; // If OPENSIM_DB is true, SearchOnly is false
                }
                return null; // Skip if it's an array
                
            case 'opensim_db_array_to_connection_string':
                // Convert OPENSIM_DB array to connection string, or build from individual constants
                if (is_array($value)) {
                    // OPENSIM_DB is an array, use it directly
                    $parts = array();
                    if (isset($value['host'])) $parts[] = "Data Source=" . $value['host'];
                    if (isset($value['name'])) $parts[] = "Database=" . $value['name'];
                    if (isset($value['user'])) $parts[] = "User ID=" . $value['user'];
                    if (isset($value['pass'])) $parts[] = "Password=" . $value['pass'];
                    if (isset($value['port']) && $value['port'] != 3306) $parts[] = "Port=" . $value['port'];
                    $parts[] = "Old Guids=true";
                    return implode(';', $parts) . ';';
                } else {
                    // OPENSIM_DB is boolean, build from individual OPENSIM_DB_* constants
                    $host = $all_constants['OPENSIM_DB_HOST'] ?? '';
                    $name = $all_constants['OPENSIM_DB_NAME'] ?? '';
                    $user = $all_constants['OPENSIM_DB_USER'] ?? '';
                    $pass = $all_constants['OPENSIM_DB_PASS'] ?? '';
                    $port = $all_constants['OPENSIM_DB_PORT'] ?? 3306;
                    
                    if (empty($host) || empty($name)) {
                        return null; // Skip if incomplete
                    }
                    
                    $parts = array();
                    $parts[] = "Data Source=" . $host;
                    $parts[] = "Database=" . $name;
                    if ($user) $parts[] = "User ID=" . $user;
                    if ($pass) $parts[] = "Password=" . $pass;
                    if ($port != 3306) $parts[] = "Port=" . $port;
                    $parts[] = "Old Guids=true";
                    return implode(';', $parts) . ';';
                }
                
            case 'build_from_parts':
                // Build connection string from individual DB constants based on constant name
                $prefix = null;
                if (strpos($constant_name, 'CURRENCY_DB') === 0) {
                    $prefix = 'CURRENCY_DB';
                } elseif (strpos($constant_name, 'SEARCH_DB') === 0) {
                    $prefix = 'SEARCH_DB';
                } elseif (strpos($constant_name, 'OFFLINE_DB') === 0) {
                    $prefix = 'OFFLINE_DB';
                } elseif (strpos($constant_name, 'OPENSIM_DB') === 0) {
                    $prefix = 'OPENSIM_DB';
                }
                
                if (!$prefix) {
                    return null;
                }
                
                $host = $all_constants[$prefix . '_HOST'] ?? '';
                $name = $all_constants[$prefix . '_NAME'] ?? '';
                $user = $all_constants[$prefix . '_USER'] ?? '';
                $pass = $all_constants[$prefix . '_PASS'] ?? '';
                $port = $all_constants[$prefix . '_PORT'] ?? 3306;
                
                if (empty($host) || empty($name)) {
                    return null; // Skip if incomplete
                }
                
                $parts = array();
                $parts[] = "Data Source=" . $host;
                $parts[] = "Database=" . $name;
                if ($user) $parts[] = "User ID=" . $user;
                if ($pass) $parts[] = "Password=" . $pass;
                if ($port != 3306) $parts[] = "Port=" . $port;
                $parts[] = "Old Guids=true";
                return implode(';', $parts) . ';';
                
            default:
                error_log("Engine_Settings: Unknown transformation: $transform for constant $constant_name");
                return $value;
        }
    }
    
    /**
     * Check if an individual DB constant should be skipped when ConnectionString will be generated
     */
    private static function should_skip_individual_db_constant($constant_name, $constants) {
        // Individual DB constants that should be skipped if we're generating ConnectionStrings
        $skip_patterns = array(
            'OPENSIM_DB_HOST', 'OPENSIM_DB_PORT', 'OPENSIM_DB_NAME', 'OPENSIM_DB_USER', 'OPENSIM_DB_PASS',
            // Keep the others as they might have dual purpose (ConnectionString + individual values for helpers)
        );
        
        // Only skip OPENSIM_DB_* if OPENSIM_DB is defined (meaning we'll generate ConnectionString)
        if (in_array($constant_name, $skip_patterns) && isset($constants['OPENSIM_DB'])) {
            return true;
        }
        
        return false;
    }
    
    /**
     * Remove redundant individual DB credentials when ConnectionStrings exist
     */
    private static function cleanup_redundant_db_credentials() {
        if (!isset(self::$settings['HelperSettings'])) {
            return;
        }
        
        $helper_settings = &self::$settings['HelperSettings'];
        
        // Check each service that might have ConnectionString
        $services = array(
            'DatabaseService' => array('OpensimDb'),  // Skip OPENSIM_DB_* values
            'CurrencyService' => array('CurrencyDb'), // Skip CURRENCY_DB_* values  
            'SearchService' => array('SearchDb'),     // Skip SEARCH_DB_* values
            'OfflineMessageService' => array('OfflineDb') // Skip OFFLINE_DB_* values
        );
        
        foreach ($services as $service => $prefixes) {
            // Check if this service has a ConnectionString
            if (isset(self::$settings[$service]['ConnectionString'])) {
                // Remove individual credentials for this service
                foreach ($prefixes as $prefix) {
                    $credentials_to_remove = array(
                        $prefix . 'Host',
                        $prefix . 'Port', 
                        $prefix . 'Name',
                        $prefix . 'User',
                        $prefix . 'Pass'
                    );
                    
                    foreach ($credentials_to_remove as $cred_key) {
                        if (isset($helper_settings[$cred_key])) {
                            unset($helper_settings[$cred_key]);
                            error_log("Engine_Settings: Removed redundant credential $cred_key (ConnectionString exists for $service)");
                        }
                    }
                }
            }
        }
    }
    
    /**
     * Convert CONSTANT_NAME format to PascalCase
     * 
     * @param string $constant_name Constant name in UPPER_CASE format
     * @return string PascalCase version
     */
    private static function constant_to_pascal_case($constant_name) {
        // Split by underscores and convert each part
        $parts = explode('_', $constant_name);
        $pascal_parts = array();
        
        foreach ($parts as $part) {
            // Convert to lowercase then capitalize first letter
            $pascal_parts[] = ucfirst(strtolower($part));
        }
        
        return implode('', $pascal_parts);
    }
    
    /**
     * Handle database connection string creation with deduplication
     */
    private static function handle_database_connection($constant_name, $value, $section, $modifier, &$db_configs, $all_constants) {
        // Build database credentials array
        $db_creds = array();
        
        if (is_array($value)) {
            // Already an array (ROBUST_DB, CURRENCY_DB, etc.)
            $db_creds = $value;
        } else {
            // Build from individual constants
            $prefix_map = array(
                'DatabaseService' => 'OPENSIM_DB',
                'CurrencyService' => 'CURRENCY_DB', 
                'SearchService' => 'SEARCH_DB',
                'OfflineMessageService' => 'OFFLINE_DB'
            );
            
            $prefix = $prefix_map[$section] ?? 'OPENSIM_DB';
            
            $db_creds = array(
                'host' => $all_constants[$prefix . '_HOST'] ?? '',
                'port' => $all_constants[$prefix . '_PORT'] ?? 3306,
                'name' => $all_constants[$prefix . '_NAME'] ?? '',
                'user' => $all_constants[$prefix . '_USER'] ?? '',
                'pass' => $all_constants[$prefix . '_PASS'] ?? '',
            );
        }
        
        // Skip if incomplete
        if (empty($db_creds['host']) || empty($db_creds['name'])) {
            return null;
        }
        
        // Check for deduplication (only create separate DB if different from main)
        if ($modifier === 'check_different' && $section !== 'DatabaseService') {
            $main_db = $db_configs['DatabaseService'] ?? array();
            if (!empty($main_db) && 
                $db_creds['host'] === $main_db['host'] &&
                $db_creds['name'] === $main_db['name'] &&
                $db_creds['user'] === $main_db['user']) {
                return null; // Same as main DB, don't create separate connection
            }
        }
        
        // Store for deduplication checking
        $db_configs[$section] = $db_creds;
        
        // Generate connection string
        if (class_exists('Helpers')) {
            $db_creds['saveformat'] = 'connection_string';
            return Helpers::array_to_connectionstring($db_creds);
        }
        
        return null;
    }
    
    /**
     * Get current PHP constants as array for migration
     * 
     * @return array Array of defined constants
     */
    public static function get_current_constants() {
        $constants = array();
        
        // Get all defined constants
        $all_constants = get_defined_constants(true);
        $user_constants = $all_constants['user'] ?? array();
        
        // Filter for OpenSim/helpers related constants with expanded prefixes
        $opensim_prefixes = array('OPENSIM_', 'ROBUST_', 'CURRENCY_', 'SEARCH_', 'OFFLINE_', 'GLOEBIT_', 'PODEX_', 'HYPEVENTS_', 'EVENTS_', 'DTL_', 'ENABLE_', 'EVENT_', 'FORMAT_', 'GROUPS_', 'MUTE_', 'PROFILE_', 'XMLRPC_');
        
        foreach ($user_constants as $name => $value) {
            foreach ($opensim_prefixes as $prefix) {
                if (strpos($name, $prefix) === 0) {
                    // Properly handle array values - capture the actual array, not the string "Array"
                    if (is_array($value)) {
                        $constants[$name] = $value;
                    } else {
                        $constants[$name] = $value;
                    }
                    break;
                }
            }
        }
        
        // Also check global variables that might contain arrays (common pattern in PHP config)
        $global_array_vars = array('ROBUST_DB', 'CURRENCY_DB', 'SEARCH_DB', 'OFFLINE_DB', 'GROUPS_DB', 'MUTE_LIST_DB', 'ROBUST_CONSOLE', 'GLOEBIT_CONVERSION_TABLE', 'EVENT_CATEGORIES');
        
        foreach ($global_array_vars as $var_name) {
            if (isset($GLOBALS[$var_name]) && is_array($GLOBALS[$var_name])) {
                $constants[$var_name] = $GLOBALS[$var_name];
            }
        }
        
        return $constants;
    }
}
