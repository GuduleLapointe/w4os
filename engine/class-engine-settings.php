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
    private static $custom_settings = null;
    
    /**
     * @var bool Whether settings have been loaded
     */
    private static $loaded = false;
    
    /**
     * @var string Path to config directory
     */
    private static $config_dir = null;
    
    /**
     * @var string Path to the credentials file
     */
    private static $credentials_file = null;
    
    /**
     * @var array Cached credentials data
     */
    private static $credentials = array();
    
    /**
     * @var bool Whether credentials have been loaded
     */
    private static $credentials_loaded = false;
    
    /**
     * Initialize settings.
     * 
     * Read all ini files in config directory, and store their values in a nested array.
     * 
     * @return void
     */
    public static function init() {
        self::$config_dir = OPENSIM_ENGINE_PATH . '/config';
        self::$credentials_file = self::$config_dir . '/credentials.json';

        self::ensure_config_directory();
        self::load();
    }
    
    /**
     * Ensure config directory exists and is properly secured
     */
    private static function ensure_config_directory() {
        if (!self::$config_dir) {
            self::$config_dir = dirname($ini_file);
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
        
        // Find all files in config directory
        $ini_files = glob(self::$config_dir . '/*.ini');
        if (empty($ini_files)) {
            self::$loaded = true;
            return;
        }

        foreach( $ini_files as $ini_file ) {
            $parsed = parse_ini_file($ini_file, true);
            $file_key = basename($ini_file, '.ini');
            if (isset(self::$settings[$file_key])) {
                // Ignore, we already processed it for some reason
            } else {
                if ($parsed === false) {
                    error_log("Engine_Settings: Failed to parse settings file: " . $ini_file);
                    self::$settings = array();
                } else {
                    self::$settings[$file_key] = $parsed;
                }
            }
        }

        self::$loaded = true;
    }
    
    /**
     * Load credentials from separate JSON credentials file
     */
    private static function load_credentials() {
        if (self::$credentials_loaded) {
            return;
        }
        
        if (!file_exists(self::$credentials_file)) {
            self::$credentials = array();
            self::$credentials_loaded = true;
            return;
        }
        
        $json_content = file_get_contents(self::$credentials_file);
        if ($json_content === false) {
            error_log("Engine_Settings: Failed to read credentials file: " . self::$credentials_file);
            self::$credentials = array();
        } else {
            $parsed = json_decode($json_content, true);
            if ($parsed === null) {
                error_log("Engine_Settings: Failed to parse JSON credentials file: " . self::$credentials_file);
                self::$credentials = array();
            } else {
                self::$credentials = $parsed;
            }
        }
        
        self::$credentials_loaded = true;
    }
    
    /**
     * Save credentials to separate JSON file
     */
    private static function save_credentials() {
        if (empty(self::$credentials_file)) {
            return false;
        }
        
        // Ensure config directory exists and is secured
        self::ensure_config_directory();
        
        $json_content = json_encode(self::$credentials, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
        
        $result = file_put_contents(self::$credentials_file, $json_content, LOCK_EX);
        if ($result === false) {
            error_log("Engine_Settings: Failed to write credentials file: " . self::$credentials_file);
            return false;
        }
        
        // Set very restrictive permissions on the credentials file
        chmod(self::$credentials_file, 0600);
        
        return true;
    }
    
    /**
     * Set a credential value (stored in separate JSON file)
     * 
     * @param string $key Credential key (URL or host:port format)
     * @param mixed $value Credential value (encrypted)
     * @return bool Success
     */
    public static function set_credential($key, $value) {
        self::load_credentials();
        self::$credentials[$key] = $value;
        return self::save_credentials();
    }
    
    /**
     * Get a credential value (from separate JSON file)
     * 
     * @param string $key Credential key (URL or host:port format)
     * @param mixed $default Default value if credential doesn't exist
     * @return mixed Credential value
     */
    public static function get_credential($key, $default = null) {
        self::load_credentials();
        return isset(self::$credentials[$key]) ? self::$credentials[$key] : $default;
    }
    
    /**
     * Get credentials for a specific service using URI-based lookup
     * 
     * @param string $service_key The service key (e.g., "DatabaseService.ConnectionString")
     * @return array|null Decrypted credentials array or null if not found
     */
    public static function get_service_credentials($service_key) {
        // Get the main grid URI from settings to use as credential key
        $login_uri = self::get('GridInfoService.login');
        if (!$login_uri) {
            error_log("Engine_Settings: No GridInfoService.login found for credential lookup");
            return null;
        }
        
        // Extract host:port from login URI
        $parsed = parse_url($login_uri);
        if (!$parsed || !isset($parsed['host'])) {
            error_log("Engine_Settings: Invalid login URI format: " . $login_uri);
            return null;
        }
        
        $credential_key = $parsed['host'];
        if (isset($parsed['port'])) {
            $credential_key .= ':' . $parsed['port'];
        }
        
        // Load credentials from JSON file
        self::load_credentials();
        
        // Look for encrypted credentials for this service URI
        if (!isset(self::$credentials[$credential_key])) {
            return null;
        }
        
        $encrypted_data = self::$credentials[$credential_key];
        
        // Decrypt the credentials using W4OS3 decrypt method
        if (class_exists('W4OS3') && method_exists('W4OS3', 'decrypt')) {
            $decrypted_json = W4OS3::decrypt($encrypted_data);
            if ($decrypted_json) {
                $credentials = json_decode($decrypted_json, true);
                return $credentials;
            }
        }
        
        error_log("Engine_Settings: Failed to decrypt credentials for " . $credential_key);
        return null;
    }
    
    /**
     * Set credentials for a specific service using URI-based storage
     * 
     * @param string $service_key The service key (e.g., "DatabaseService.ConnectionString")
     * @param array $credentials_array The credentials to encrypt and store
     * @return bool Success
     */
    public static function set_service_credentials($service_key, $credentials_array) {
        // Get the main grid URI from settings to use as credential key
        $login_uri = self::get('GridInfoService.login');
        if (!$login_uri) {
            error_log("Engine_Settings: No GridInfoService.login found for credential storage");
            return false;
        }
        
        // Extract host:port from login URI
        $parsed = parse_url($login_uri);
        if (!$parsed || !isset($parsed['host'])) {
            error_log("Engine_Settings: Invalid login URI format: " . $login_uri);
            return false;
        }
        
        $credential_key = $parsed['host'];
        if (isset($parsed['port'])) {
            $credential_key .= ':' . $parsed['port'];
        }
        
        // Encrypt the credentials using W4OS3 encrypt method
        if (class_exists('W4OS3') && method_exists('W4OS3', 'encrypt')) {
            $credentials_json = json_encode($credentials_array, JSON_UNESCAPED_SLASHES);
            $encrypted_data = W4OS3::encrypt($credentials_json);
            
            if ($encrypted_data) {
                self::load_credentials();
                self::$credentials[$credential_key] = $encrypted_data;
                return self::save_credentials();
            }
        }
        
        error_log("Engine_Settings: Failed to encrypt credentials for " . $credential_key);
        return false;
    }
    
    /**
     * Get connection string with credentials resolved from encrypted storage
     * 
     * @param string $service_key The service key (e.g., "DatabaseService.ConnectionString")
     * @return string|null Connection string with credentials or null if not available
     */
    public static function get_connection_string_with_credentials($service_key) {
        // First try to get the connection string from settings
        $connection_string = self::get($service_key);
        
        if ($connection_string) {
            // Parse the connection string to see if it has credentials
            if (class_exists('OSPDO')) {
                $parsed = OSPDO::connectionstring_to_array($connection_string);
                
                // If it has credentials, return as-is
                if (!empty($parsed['user']) && !empty($parsed['pass'])) {
                    return $connection_string;
                }
                
                // If no credentials, try to get them from encrypted storage
                $credentials = self::get_service_credentials($service_key);
                if ($credentials) {
                    // Merge credentials into connection string
                    $merged = array_merge($parsed, $credentials);
                    return OSPDO::array_to_connectionstring($merged);
                }
            }
            
            return $connection_string;
        }
        
        // No connection string in settings, try to build from encrypted credentials
        $credentials = self::get_service_credentials($service_key);
        if ($credentials && class_exists('OSPDO')) {
            return OSPDO::array_to_connectionstring($credentials);
        }
        
        return null;
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
    public static function set($key, $value, $save = true) {
        self::load();

        $default_instance = 'engine'; // Should happen, but just in case
        $default_section = 'Default'; // Should happen be used, but just in case

        $key_parts = explode('.', $key);
        // Insert $default_section in top of parts if count < 2
        if (count($key_parts) < 2) {
            array_unshift($key_parts, $default_section);
        }
        // Insert $default_instance in top of parts if count < 3
        if (count($key_parts) < 3) {
            array_unshift($key_parts, $default_instance);
        }
        $instance = $key_parts[0];
        $section = $key_parts[1];
        $setting_key = implode('.', array_slice($key_parts, 2));

        self::$settings[$instance][$section][$setting_key] = $value;
        
        // Save immediately unless explicitly told not to
        if ($save) {
            return self::save();
        }

        return true;
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
     * @param string $key Setting key in format "section.key" or just "key"
     * @return bool Whether the setting exists
     */
    public static function has($key) {
        self::load();
        
        if (strpos($key, '.') !== false) {
            $key_parts = explode('.', $key);
            $instance = $key_parts[0];
            $section = $key_parts[1];
            $setting_key = implode('.', array_slice($key_parts, 2));
            
            return isset(self::$settings[$instance][$section][$setting_key]);
        } else {
            return isset(self::$settings['default'][$key]);
        }
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
     * Check if system is configured
     * 
     * @return bool Whether basic configuration exists
     */
    public static function is_configured() {
        self::load();
        
        // Check for essential configuration indicators
        $has_db = self::get('DatabaseService.ConnectionString') !== null;
        $has_grid = self::get('GridInfoService.gridname') !== null;
        
        return $has_db || $has_grid || !empty(self::$settings);
    }
    
    /**
     * Save settings to file(s)
     * 
     * @return bool Success
     */
    public static function save() {
        if (empty(self::$settings)) {
            return true; // Nothing to save
        }
        
        $success = true;
        
        // Save each instance to its own .ini file
        foreach (self::$settings as $instance => $instance_data) {
            if ($instance === 'default') {
                $instance = 'engine'; // Default instance name
            }
            
            $ini_file = self::$config_dir . '/' . $instance . '.ini';
            $ini_content = self::array_to_ini($instance_data);
            
            $result = file_put_contents($ini_file, $ini_content, LOCK_EX);
            if ($result === false) {
                error_log("Engine_Settings: Failed to save settings to {$ini_file}");
                $success = false;
            } else {
                // Set restrictive permissions
                chmod($ini_file, 0600);
            }
        }
        
        return $success;
    }
    
    /**
     * Convert array to INI format
     * 
     * @param array $array Settings array
     * @return string INI formatted string
     */
    private static function array_to_ini($array) {
        $ini_string = '';
        
        foreach ($array as $section => $section_data) {
            if (!is_array($section_data)) {
                // Handle flat values (shouldn't happen but just in case)
                $ini_string .= $section . ' = ' . self::format_ini_value($section_data) . "\n";
                continue;
            }
            
            $ini_string .= "[{$section}]\n";
            
            foreach ($section_data as $key => $value) {
                $ini_string .= $key . ' = ' . self::format_ini_value($value) . "\n";
            }
            
            $ini_string .= "\n"; // Empty line between sections
        }
        
        return $ini_string;
    }
    
    /**
     * Format a value for INI file
     * 
     * @param mixed $value Value to format
     * @return string Formatted value
     */
    private static function format_ini_value($value) {
        if (is_bool($value)) {
            return $value ? 'true' : 'false';
        }
        
        if (is_numeric($value)) {
            return (string)$value;
        }
        
        if (is_array($value)) {
            // Convert array to JSON for complex data
            return '"' . addslashes(json_encode($value, JSON_UNESCAPED_SLASHES)) . '"';
        }
        
        if (is_string($value)) {
            // Quote strings that contain special characters
            if (preg_match('/[;\[\]"\'=\s]/', $value)) {
                return '"' . addslashes($value) . '"';
            }
            return $value;
        }
        
        return (string)$value;
    }
    
    /**
     * Get config directory path
     * 
     * @return string Config directory path
     */
    public static function get_config_dir() {
        return self::$config_dir;
    }
    
    /**
     * Clear all settings (for testing purposes)
     * 
     * @return bool Success
     */
    public static function clear_all() {
        self::$settings = array();
        self::$loaded = false;
        
        // Remove all .ini files
        if (self::$config_dir && is_dir(self::$config_dir)) {
            $ini_files = glob(self::$config_dir . '/*.ini');
            foreach ($ini_files as $file) {
                unlink($file);
            }
        }
        
        return true;
    }
    
    /**
     * Get all credentials (for debugging)
     * 
     * @return array All credentials
     */
    public static function get_all_credentials() {
        self::load_credentials();
        return self::$credentials;
    }
}
