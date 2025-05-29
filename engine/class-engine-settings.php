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
                    // Preserve connection string format
                    $ini .= "$key = $value\n";
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
        
        if (empty($value)) {
            return '""';
        }
        
        // Quote strings that contain special characters
        if (is_string($value) && (
            strpos($value, ' ') !== false ||
            strpos($value, '"') !== false ||
            strpos($value, "'") !== false ||
            strpos($value, ';') !== false ||
            strpos($value, '#') !== false
        )) {
            return '"' . str_replace('"', '""', $value) . '"';
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
}
