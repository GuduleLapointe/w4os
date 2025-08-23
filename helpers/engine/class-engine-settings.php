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
     * @var string Settings version
     */
    private static $version = '3.0';
    
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
    
    private static $fields_config = array();

    /**
     * @var array Imported options to use instead of regular settings
     */
    private static $imported_options = null;

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
        self::setup_fields_config();
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
     * Load settings from configuration file or use imported options
     */
    private static function load() {
        if (self::$loaded) {
            return;
        }

        // If imported options are available, use them instead of loading from file
        if (self::$imported_options !== null) {
            self::$settings = self::$imported_options;
            self::$loaded = true;
            return;
        }
        
        // Otherwise load from configuration file as usual
        // Find all files in config directory
        $ini_files = glob(self::$config_dir . '/*.ini');
        if (empty($ini_files)) {
            self::$loaded = true;
            return;
        }

        foreach( $ini_files as $ini_file ) {
            $file_key = basename($ini_file, '.ini');
            if (isset(self::$settings[$file_key])) {
                // Ignore, we already processed it for some reason
                continue;
            }
            $parsed = parse_ini_file_decode($ini_file, true);
            if ($parsed === false) {
                error_log("Engine_Settings: Failed to parse settings file: " . $ini_file);
                self::$settings = array();
            } else {
                self::$settings[$file_key] = $parsed;
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
     * @param string $key Credential key (service or host:port format)
     * @param mixed $default Default value if credential doesn't exist
     * @return mixed Credential value
     */
    public static function get_credentials($key, $default = null) {
        self::load_credentials();
        if (isset(self::$credentials[$key])) {
            return self::$credentials[$key];
        }

        switch($key) {
            case 'robust':
            case 'robust.DatabaseService.ConnectionString':
                $service_url = self::get('robust.GridInfoService.login');
                // Default to empty string if not set
                break;
            // case 'opensim':
            // case 'opensim.DatabaseService.ConnectionString':
            //     // Default to empty string if not set
            //     return '';
            default:
                // Return default value for any other key
                return $default;
        }

        if(!empty($service_url) && isset(self::$credentials[$service_url])) {
            return self::$credentials[$service_url];
        }

        return $default;
    }

    /**
     * Get DB credentials for a specific service
     */
    public static function get_db_credentials($key, $default = array()) {
        if(!is_array($default)) {
            $default = [$default];
        }
        $credentials = self::get_credentials($key, $default);
        if(!is_array($credentials)) {
            error_log("Engine_Settings: get_db_credentials expected an array, got: " . print_r($credentials, true) . ' in ' . __FILE__ . ':' . __LINE__);
            return $default;
        }
        $credentials = array_filter(self::get_credentials($key, $default));
        if (empty($credentials)) {
            return $default;
        }
        if(! empty(array_filter($credentials['db'] ?? array()))) {
            return $credentials['db'];
        }
        return $credentials;      
    }

    /**
     * Get Console credentials for a specific service
     */
    public static function get_console_credentials($key, $default = array()) {
        if(!is_array($default)) {
            $default = [$default];
        }
        $credentials = self::get_credentials($key, $default);
        if(!is_array($credentials)) {
            return $default;
        }
        $credentials = array_filter($credentials);
        if (empty($credentials)) {
            return $default;
        }
        if(! empty(array_filter($credentials['console'] ?? array()))) {
            $console = $credentials['console'];
        }
        if(isset($console['user'] ) && isset($console['pass']) && isset($console['port'])) {
            // If host and port are set, return them as console credentials
            return $console;
        }
        return false;
    }
    
    /**
     * Get credentials for a specific service using URI-based lookup
     * 
     * @param string $service_key The service key (e.g., "DatabaseService.ConnectionString")
     * @return array|null Decrypted credentials array or null if not found
     */
    public static function get_service_credentials($service_key) {
        // Get the main grid URI from settings to use as credential key
        $login_uri = self::get('robust.GridInfoService.login');
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
        $login_uri = self::get('robust.GridInfoService.login');
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
        
        $default_instance = 'engine'; // Should happen, but just in case
        $default_section = 'Default'; // Should happen be used, but just in case

        if(is_array($key)) {
            // Should never happen, debug, backtrace and die.
            throw new Error("Engine_Settings: Invalid key type, expected string, got array.");
            die('I though I\'d already be dead by now.');
            // error_log("Engine_Settings: Invalid key type, expected string, got array. Key: " . print_r($key, true));
            // error_log("Engine_Settings: Backtrace: " . print_r(debug_backtrace(), true));
        }

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

        return isset(self::$settings[$instance][$section][$setting_key]) 
            ? self::$settings[$instance][$section][$setting_key] 
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
    public static function configured() {
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
                $ini_string .= $key . ' = ' . self::format_ini_value($value, $key) . "\n";
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
    private static function format_ini_value($value, $key = '') {
        if (is_bool($value)) {
            return $value ? 'true' : 'false';
        }
        if ($value === null) {
            return;
        }
        
        if (is_numeric($value)) {
            return (string)$value;
        }
        
        if (is_array($value)) {
            if(strpos($key, 'ConnectionString') !== false) {
                $value = OSPDO::array_to_connectionstring($value);
                // return '"' . addslashes($value) . '"';
            }
            // Convert array to JSON for complex data, ugly but should be ini compliant. Or not.
            $value = json_encode($value, JSON_UNESCAPED_SLASHES);
        }
        
        if(! is_string($value)) {
            error_log("[WARNING] format_ini_value expected a string, got: " . json_encode($value) . ' in ' . __FILE__ . ':' . __LINE__);    
            return $value;
            // // If it's not a string, convert it to string
            // $value = (string)$value;
        }

        // Trim whitespace and quotes from start and end
        $value = trim($value, " \n\r\t\v\x00\"'"); 

        // Add slashes if value has characters that need escaping
        if (strpos($value, '"') !== false || strpos($value, '\\') !== false) {
            $value = addslashes($value);
        }

        // Quote return value if needed
        if (preg_match('/[;\[\]"\'=\s]/', $value)) {
            return '"' . $value . '"';
        }
        return $value;
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

    /**
     * Setup fields configuration for settings forms.
     * 
     * This must contain field definitions for at least all options
     * currently defined in helpers/includes/helpers-migration-v2to3.php
     * and wordpress/includes/w4os-migration-v2to3.php.
     * 
     * @return void
     */
    private static function setup_fields_config() {
        self::$fields_config = array(
            'w4os' => array(
                'General' => array(
                ),
                'Users' => array(
                    // Feature related to WP Users
                    'LoginPage' => array(
                        'type' => 'select',
                        'label' => _('Login Page'),
                        'options' => 'get_wp_pages',
                        'description' => _('Overrides WP login page.'),
                    ),
                    'ReplaceUserName' => array(
                        'type' => 'checkbox',
                        'label' => _('Replace User Name'),
                        'description' => _('If enabled, replaces the user name with the OpenSim avatar name.'),
                        'default' => true,
                    ),
                ),
                'Profiles' => array(
                    'ProfileSlug' => array(
                        'type' => 'text',
                        'label' => _('Profile Slug'),
                        'description' => _('The slug used for user profiles in the OpenSim instance.'),
                    ),
                    'ProfilePage' => array(
                        'type' => 'select',
                        'label' => _('Profile Page'),
                        'options' => 'get_wp_pages',
                        'description' => _('The WP page used to display profile.'),
                    ),
                ),
            ),
            'engine' => array(
                'General' => array(
                    'settings_version' => array(
                        'type' => 'text',
                        'label' => _('Settings Version'),
                        'description' => _('The version of the settings format.'),
                        'value' => self::$version,
                        'readonly' => true,
                    ),
                    'GridLogoURL' => array(
                        'type' => 'url',
                        'label' => _('Grid Logo URL'),
                        'description' => _('URL to the grid logo image.'),
                    ),
                    'OSHelpersDir' => array(
                        'type' => 'path',
                        'label' => _('OS Helpers Directory'),
                        'description' => _('Path to the OS helpers directory.'),
                    ),
                ),
                'Avatars' => array(
                    'ExcludeModels' => array(
                        'type' => 'checkbox',
                        'label' => _('Exclude Models'),
                        'description' => _('Excludes default models from the avatar list and statitics.'),
                        'default' => true,
                    ),
                    'ExcludeNoMail' => array(
                        'type' => 'checkbox',
                        'label' => _('Exclude No Mail'),
                        'description' => _('Consider avatars without email as technical accounts, exclude from the avatar list and statistics.'),
                        'default' => true,
                    ),
                ),
                'Models' => array(
                    'Match' => array(
                        'type' => 'select',
                        'label' => _('Match rule'),
                        'options' => array(
                            'first' => _('First Name'),
                            'any' => _('Any'),
                            'last' => _('Last Name'),
                            'uuid' => _('Custom List'),
                        ),
                        'description' => _('The rule used to match avatars to default models, first name, last name, first or last name, or a list of existing avatars.'),
                        'default' => 'any',
                    ),
                    'MatchPattern' => array(
                        'type' => 'text',
                        'label' => _('Match Pattern'),
                        'description' => _('The pattern used to match avatars to default models.'),
                        'default' => 'Default',
                    ),
                    'List' => array(
                        'type' => 'select2',
                        'multiple' => true,
                        'label' => _('Default Models List'),
                        'description' => _('List of default avatar models to use when match is set to Custom List.'),
                        'options' => 'get_avatars',
                    ),
                ),
                'Search' => array(
                    'SearchDB' => array(
                        'type' => 'db_credentials',
                        'label' => _('Search Database'),
                        'description' => _('Database connection string for the search service.'),
                    ),
                    'SearchEventsTable' => array(
                        'type' => 'text',
                        'label' => _('Search Events Table'),
                        'description' => _('Name of the table for search events.'),
                    ),
                    'SearchRegionTable' => array(
                        'type' => 'text',
                        'label' => _('Search Region Table'),
                        'description' => _('Name of the table for search regions.'),
                    ),
                    'HypeventsUrl' => array(
                        'type' => 'url',
                        'label' => _('Hypevents URL'),
                        'description' => _('URL for Hypevents integration.'),
                    ),
                ),
                'OfflineMessages' => array(
                    'OfflineDB' => array(
                        'type' => 'db_credentials',
                        'label' => _('Offline Database'),
                        'description' => _('Database connection string for the offline message service.'),
                    ),
                    'OfflineMessageTable' => array(
                        'type' => 'text',
                        'label' => _('Offline Message Table'),
                        'description' => _('Name of the table for offline messages.'),
                    ),
                    'MuteDB' => array(
                        'type' => 'db_credentials',
                        'label' => _('Mute Database'),
                        'description' => _('Database connection string for the mute service.'),
                    ),
                    'MuteListTable' => array(
                        'type' => 'text',
                        'label' => _('Mute List Table'),
                        'description' => _('Name of the table for the mute list.'),
                    ),
                     'OfflineHelperUri' => array(
                        'type' => 'url',
                        'label' => _('Offline Messages Helper URI'),
                        'description' => _('URI for the offline helper service.'),
                    ),
                    'SenderEmail' => array(
                        'type' => 'email',
                        'label' => _('Sender A'),
                        'description' => _('Email address used for sending OpenSim mails.'),
                    ),
                ),
                'Economy' => array(
                    'provider' => array(
                        'type' => 'select',
                        'label' => _('Currency Provider'),
                        'options' => array(
                            '' => _('None (no economy at all)'),
                            'free' => _('Free (no real transactions, required to allow land ownership)'),
                            'gloebit' => _('Gloebit'),
                            'podex' => _('Podex (with MoneyServer module'),
                            'moneyserver' => _('MoneyServer (with other currency provider)'),
                        ),
                        'default' => '',
                        'description' => _('Currency provider for the OpenSim economy.'),
                    ),
                    'CurrencyMoneyTable' => array(
                        'type' => 'text',
                        'label' => _('Currency Money Table'),
                        'description' => _('Name of the table for currency money transactions.'),
                    ),
                    'CurrencyTransactionTable' => array(
                        'type' => 'text',
                        'label' => _('Currency Transaction Table'),
                        'description' => _('Name of the table for currency transactions.'),
                    ),
                    'CurrencyRate' => array(
                        'type' => 'float',
                        'label' => _('Currency Rate'),
                        'description' => _('Exchange rate for the currency.'),
                    ),
                    'GloebitConversionThreshold' => array(
                        'type' => 'float',
                        'label' => _('Gloebit Conversion Threshold'),
                        'description' => _('Threshold for Gloebit conversion.'),
                    ),
                    'GloebitConversionTable' => array(
                        'type' => 'text',
                        'label' => _('Gloebit Conversion Table'),
                        'description' => _('Name of the table for Gloebit conversions.'),
                    ),
                    'PodexErrorMessage' => array(
                        'type' => 'text',
                        'label' => _('Podex Error Message'),
                        'description' => _('Error message to display for Podex errors.'),
                    ),
                    'PodexRedirectUrl' => array(
                        'type' => 'url',
                        'label' => _('Podex Redirect URL'),
                        'description' => _('URL to redirect users after Podex transactions.'),
                    ),
                ),
            ),
            'robust' => array(
                'Const' => array(
                    'BaseHostname' => array(
                        'type' => 'hostname',
                        'label' => _('Base Hostname'),
                        'description' => _('Base hostname for the OpenSim instance.'),
                    ),
                    'BaseURL' => array(
                        'type' => 'url',
                        'label' => _('Base URL'),
                        'description' => _('Base URL for the OpenSim instance.'),
                    ),
                    'PublicPort' => array(
                        'type' => 'integer',
                        'default' => 8002,
                        'label' => _('Public Port'),
                        'description' => _('Public port for the OpenSim instance.'),
                    ),
                    'PrivatePort' => array(
                        'type' => 'integer',
                        'default' => 8003,
                        'label' => _('Private Port'),
                        'description' => _('Private port for the OpenSim instance.'),
                    ),
                ),
                'DatabaseService' => array(
                    'StorageProvider' => array(
                        'type' => 'select',
                        'label' => _('Storage Provider'),
                        'options' => array(
                            'OpenSim.Data.MySQL.dll' => _('MySQL'),
                            'OpenSim.Data.PGSQL.dll' => _('PostgreSQL'),
                        ),
                        'default' => 'OpenSim.Data.MySQL.dll',
                        'description' => _('Storage provider for the OpenSim database.'),
                    ),
                    'ConnectionString' => array(
                        'type' => 'db_credentials',
                        'label' => _('Database Connection String'),
                        'description' => _('Database connection string for the OpenSim database.'),
                    ),
                ),
                'LoginService' => array(
                    'SearchURL' => array(
                        'type' => 'url',
                        'label' => _('Search URL'),
                        'description' => _('URL for the in-world search service.'),
                    ),
                    'Currency' => array(
                        'type' => 'text',
                        'label' => _('Currency Name'),
                        'description' => _('Name of the currency used in the grid.'),
                    ),
                    'DestinationGuide' => array(
                        'type' => 'url',
                        'label' => _('Destinations Guide URL'),
                        'description' => _('URL for the destination guide service.'),
                    ),
                    'DSTZone' => array(
                        'type' => 'text',
                        'label' => _('DST Zone'),
                        'description' => _('Time zone for Daylight Saving Time rules. Set to "none" if OPENSIM_USE_UTC_TIME is false.'),
                    ),
                ),
                'GridInfoService' => array(
                    'register' => array(
                        'type' => 'url',
                        'label' => _('Registration URL'),
                        'description' => _('URL for the grid registration service.'),
                    ),
                    'password' => array(
                        'type' => 'url',
                        'label' => _('Password Recovery URL'),
                        'description' => _('URL for the password recovery service.'),
                    ),
                    'gridname' => array(
                        'type' => 'text',
                        'label' => _('Grid Name'),
                        'description' => _('Name of the OpenSim grid.'),
                    ),
                    'gridnick' => array(
                        'type' => 'text',
                        'label' => _('Grid Nickname'),
                        'description' => _('Nickname for the OpenSim grid.'),
                        'callback' => 'sanitize_slug',
                    ),
                    'login' => array(
                        'type' => 'url',
                        'label' => _('Login URI'),
                        'description' => _('URI for the OpenSim login service.'),
                    ),
                    'economy' => array(
                        'type' => 'url',
                        'label' => _('Economy Helper URL'),
                        'description' => _('URL for the economy service.'),
                    ),
                    'search' => array(
                        'type' => 'url',
                        'label' => _('Web Search URL'),
                        'description' => _('URL for the web search service.'),
                    ),
                    'OfflineMessageURL' => array(
                        'type' => 'url',
                        'label' => _('Offline Message URL'),
                        'description' => _('URI for the offline message service.'),
                    ),
                ),
                'Network' => array(
                    'ConsoleUser' => array(
                        'type' => 'text',
                        'label' => _('Console User'),
                        'description' => _('Username for the OpenSim console.'),
                    ),
                    'ConsolePass' => array(
                        'type' => 'password',
                        'label' => _('Console Password'),
                        'description' => _('Password for the OpenSim console user.'),
                    ),
                    'ConsolePort' => array(
                        'type' => 'integer',
                        'default' => 8004,
                        'label' => _('Console Port'),
                        'description' => _('Port for the OpenSim console service.'),
                    ),
                ),
                'AssetService' => array(
                    'ConnectionString' => array(
                        'type' => 'db_credentials',
                        'label' => _('Asset Database Connection String'),
                        'description' => _('Database connection string for the OpenSim asset service.'),
                    ),
                ),
                'UserProfilesService' => array(
                    'ConnectionString' => array(
                        'type' => 'db_credentials',
                        'label' => _('User Profiles Database Connection String'),
                        'description' => _('Database connection string for the OpenSim user profiles service.'),
                    ),
                ),
            ),
            'opensim' => array(
                'DatabaseService' => array(
                    'StorageProvider' => array(
                        'type' => 'select',
                        'label' => _('Storage Provider'),
                        'options' => array(
                            'OpenSim.Data.MySQL.dll' => _('MySQL'),
                            'OpenSim.Data.PGSQL.dll' => _('PostgreSQL'),
                        ),
                        'default' => 'OpenSim.Data.MySQL.dll',
                        'description' => _('Storage provider for the OpenSim main database.'),
                    ),
                    'ConnectionString' => array(
                        'type' => 'db_credentials',
                        'label' => _('OpenSim Database Connection String'),
                        'description' => _('Database connection string for the OpenSim main database.'),
                    ),
                ),
                'Search' => array(
                    'Module' => array(
                        'type' => 'text',
                        'label' => _('Search Module'),
                        'default' => 'OpenSimSearch',
                        'description' => _('Module used for the in-world search service.'),
                    ),
                    'SearchURL' => array(
                        'type' => 'url',
                        'label' => _('In-world Search URL'),
                        'description' => _('URL for the in-world search service.'),
                    ),
                ),
                'SimulatorFeatures' => array(
                    'DestinationGuide' => array(
                        'type' => 'url',
                        'label' => _('Destination Guide URL'),
                        'description' => _('URL for the destination guide service.'),
                    ),
                ),
                'DataSnapshot' => array(
                    'gridname' => array(
                        'type' => 'text',
                        'label' => _('Grid Name'),
                        'default' => 'robust.GridInfoService.gridname',
                        'description' => _('Name of the OpenSim grid for data snapshots.'),
                    ),
                    'registrars' => array(
                        'type' => 'url',
                        'multiple' => true,
                        'label' => _('Data Registrars'),
                        'description' => _('Register URLs for search engines.'),
                        'default' => array(
                            'DATA_SRV_Self' => '{BaseURL}/helpers/register.php', // Will be honored if ProvideSearch is true
                            'DATA_SRV_2do' => 'http://2do.directory/helpers/register.php',
                        ),
                    ),
                ),
                'Economy' => array(
                    'economymodule' => array(
                        'type' => 'select',
                        'label' => _('Economy Module'),
                        'options' => array(
                            'BetaGridLikeMoneyModule' => _('Free transactions only'),
                            'Gloebit' => _('Gloebit'),
                            'DTLNSLMoneyModule' => _('Podex and other currency providers'),
                        ),
                        'description' => _('Module used for the economy service.'),
                    ),
    // CurrencyServer = "https://speculoos.world:8008/"
    // UserServer = "${Const|BaseURL}:${Const|PublicPort}"
                    'CurrencyServer' => array(
                        'type' => 'url',
                        'label' => _('Currency Server URL'),
                        'placeholder' => 'https://yougrid.org:8008/',
                        'description' => _('MoneyServer URL.'),
                    ),
                    'UserServer' => array(
                        'type' => 'url',
                        'label' => _('User Server URL'),
                        'description' => _('Use.'),
                    ),
                    

                    'economy' => array(
                        'type' => 'url',
                        'label' => _('Economy URL'),
                        'description' => _('URL for the economy service.'),
                    ),
                    'SellEnabled' => array(
                        'type' => 'checkbox',
                        'label' => _('Enable Selling'),
                        'description' => _('Whether selling is enabled in the economy service.'),
                        'default' => true,
                    ),
                    'PriceUpload' => array(
                        'type' => 'integer', // We might need to enable float for some unknown yet currency providers, but let's use integer for now
                        'label' => _('Price Upload'),
                        'default' => 0,
                        'description' => _('Price for uploading items in the economy service.'),
                    ),
                    'PriceGroupCreate' => array(
                        'type' => 'integer', // We might need to enable float for some unknown yet currency providers, but let's use integer for now
                        'label' => _('Price Group Create'),
                        'default' => 0,
                        'description' => _('Price for creating groups in the economy service.'),
                    ),
                ),
                'Gloebit' => array(
                    'Enabled' => array(
                        'type' => 'checkbox',
                        'label' => _('Enable Gloebit'),
                        'description' => _('Whether Gloebit is enabled as the currency provider.'),
                        'default' => false,
                    ),
                    'GLBSpecificStorageProvider' => array(
                        'type' => 'select',
                        'label' => _('Gloebit Storage Provider'),
                        'options' => array(
                            'OpenSim.Data.MySQL.dll' => _('MySQL'),
                            'OpenSim.Data.PGSQL.dll' => _('PostgreSQL'),
                        ),
                        'default' => 'OpenSim.Data.MySQL.dll',
                        'description' => _('Storage provider for Gloebit transactions.'),
                    ),
                    'GLBSpecificConnectionString' => array(
                        'type' => 'db_credentials',
                        'label' => _('Gloebit Database Connection String'),
                        'description' => _('Database connection string for Gloebit transactions.'),
                    ),
                    'GLBOwnerEmail' => array(
                        'type' => 'email',
                        'label' => _('Gloebit Owner Email'),
                        'default' => '{helpers.SenderEmail}',
                        'description' => _('Email address of the Gloebit owner.'),
                    ),
                ),
                'Messaging' => array(
                    'Enabled' => array(
                        'type' => 'checkbox',
                        'label' => _('Enable Offline Messages'),
                        'description' => _('Whether offline messages are enabled.'),
                        'default' => true,
                    ),
                    'OfflineMessageModule' => array(
                        'type' => 'select',
                        'label' => _('Offline Message Module'),
                        'options' => array(
                            'OfflineMessageModule' => _('Offline Message Module'),
                            'Offline Message Module V2' => _('Offline Message Module V2'),
                        ),
                        'default' => 'OfflineMessageModule',
                        'description' => _('Module used for handling offline messages.'),
                        'condition' => array(
                            'field' => 'Messaging.Enabled',
                            'value' => true,
                        ),
                    ),
                    'OfflineMessageURL' => array(
                        'type' => 'url',
                        'label' => _('Offline Message URL'),
                        'description' => _('URI for the offline message service.'),
                        'condition' => array(
                            'field' => 'Messaging.Enabled',
                            'value' => true,
                        ),
                    ),
                ),
            ),
            'moneyserver' => array(
                'MoneyServer' => array(
                    'Enabled' => array(
                        'type' => 'checkbox',
                        'label' => _('Enable Money Server'),
                        'description' => _('Whether the Money Server is enabled.'),
                        'default' => true,
                    ),
                    'ServerPort' => array(
                        'type' => 'integer',
                        'default' => 8008,
                        'label' => _('Money Server Port'),
                        'description' => _('Must not be used by or other services.'),
                        'condition' => array(
                            'field' => 'MoneyServer.Enabled',
                            'value' => true,
                        ),
                    ),
                    'BankerAvatar' => array(
                        'type' => 'uuid',
                        'label' => _('Banker Avatar'),
                        'description' => _('UUID of the banker for the Money Server. If "00000000-0000-0000-0000-000000000000" is specified, all avatars can get money from system. If empty, nobody can get money.'),
                        'condition' => array(
                            'field' => 'MoneyServer.Enabled',
                            'value' => true,
                        ),
                    ),
                    'DefaultBalance' => array(
                        'type' => 'integer',
                        'label' => _('Default Balance'),
                        'default' => 0,
                        'description' => _('If the user is not found in database, they will be created with the default balance.'),
                        'condition' => array(
                            'field' => 'MoneyServer.Enabled',
                            'value' => true,
                        ),
                    ),
                    'EnableAmountZero' => array(
                        'type' => 'checkbox',
                        'label' => _('Enable Zero Amount Transactions'),
                        'description' => _('Whether to allow transactions with zero amount.'),
                        'default' => false,
                        'condition' => array(
                            'field' => 'MoneyServer.Enabled',
                            'value' => true,
                        ),
                    ),
                    'EnableForceTransfer' => array(
                        'type' => 'checkbox',
                        'label' => _('Enable Force Transfer'),
                        'description' => _('Set to true to allow the use of llGiveMoney() when the payer is not logged in.'),
                        'default' => true,
                        'condition' => array(
                            'field' => 'MoneyServer.Enabled',
                            'value' => true,
                        ),
                    ),
                    'EnableScriptSendMoney' => array(
                        'type' => 'checkbox',
                        'label' => _('Enable Script Send Money'),
                        'description' => _('Whether to allow currency helper to send money.'),
                        'default' => true,
                        'condition' => array(
                            'field' => 'MoneyServer.Enabled',
                            'value' => true,
                        ),
                    ),
                    'MoneyScriptAccessKey' => array(
                        'type' => 'text',
                        'label' => _('Script Key'),
                        'description' => _('Key used for scripts to access the Money Server.'),
                        'condition' => array(
                            'field' => 'MoneyServer.Enabled',
                            'value' => true,
                        ),
                    ),
                    // // ScriptKey kept for reference only, probably obsolete.
                    // 'ScriptKey' => array(
                    //     'type' => 'text',
                    //     'label' => _('Script Key'),
                    //     'disabled' => true,
                    //     'description' => _('Found in some snippets, instead of MoneyScriptAccessKey, but MoneyScriptAccessKey is probably the one to use.'),
                    // ),
                    'MoneyScriptIPaddress' => array(
                        'type' => 'text',
                        'label' => _('Script IP Address'),
                        'readonly' => true,
                        'description' => _('Used to generate Script key. Leave empty to use the server IP address.'),
                        'condition' => array(
                            'field' => 'MoneyServer.Enabled',
                            'value' => true,
                        ),
                    ),
                    'EnableHGAvatar' => array(
                        'type' => 'checkbox',
                        'label' => _('Enable Hypergrid Avatar'),
                        'description' => _('Whether to allow hypergrid avatars to use the Money Server.'),
                        'default' => true,
                    ),
                    'HGAvatarDefaultBalance' => array(
                        'type' => 'integer',
                        'label' => _('Hypergrid Avatar Default Balance'),
                        'default' => 0,
                        'description' => _('Default balance for hypergrid avatars.'),
                        'condition' => array(
                            array(
                                'field' => 'MoneyServer.Enabled',
                                'value' => true,
                            ),
                            array(
                                'field' => 'EnableHGAvatar',
                                'value' => true,
                            ),
                        ),
                        'EnableGuestAvatar' => array(
                            'type' => 'checkbox',
                            'label' => _('Enable Guest Avatar'),
                            'description' => _('Whether to allow guest avatars to use the Money Server.'),
                            'default' => false,
                        ),
                        'GuestAvatarDefaultBalance' => array(
                            'type' => 'integer',
                            'label' => _('Guest Avatar Default Balance'),
                            'default' => 0,
                            'description' => _('Default balance for guest avatars.'),
                            'condition' => array(
                                array(
                                    'field' => 'MoneyServer.Enabled',
                                    'value' => true,
                                ),
                                array(
                                    'field' => 'MoneEnableGuestAvatar',
                                    'value' => true,
                                ),
                            ),
                        ),
                    ),
                    // 'Rate' => array(
                    //     'type' => 'float',
                    //     'label' => _('Currency Rate'),
                    //     'default' => 4.0, // 4/1000 is the most common rate
                    //     'description' => _('Exchange rate for the currency used by the Money Server.'),
                    // ),
                    // 'RatePer' => array(
                    //     'type' => 'integer',
                    //     'label' => _('Rate Per'),
                    //     'default' => 'USD',
                    //     'description' => _('Currency unit for the exchange rate (e.g., USD, EUR).'),
                    // ),
                ),
                'MySql' => array(
                    // _condition applies to the whole section, not just the first field
                    '_condition' => array(
                        'field' => 'MoneyServer.Enabled',
                        'value' => true,
                    ),
                    'hostname' => array(
                        'type' => 'hostname',
                        'label' => _('MySQL server Hostname'),
                        'description' => _('Hostname for the MySQL database used by the Money Server.'),
                    ),
                    'port' => array(
                        'type' => 'integer',
                        'default' => 3306,
                        'label' => _('MySQL Port'),
                        'description' => _('Port for the MySQL database used by the Money Server.'),
                    ),
                    'database' => array(
                        'type' => 'text',
                        'label' => _('Database name'),
                        'description' => _('Name of the MySQL database used by the Money Server.'),
                    ),
                    'username' => array(
                        'type' => 'text',
                        'label' => _('MySQL Username'),
                        'description' => _('Username for the MySQL database used by the Money Server.'),
                    ),
                    'password' => array(
                        'type' => 'password',
                        'label' => _('MySQL Password'),
                        'description' => _('Password for the MySQL database used by the Money Server.'),
                    ),
                    'pooling' => array(
                        'type' => 'checkbox',
                        'label' => _('Connection Pooling'),
                        'default' => false,
                    ),
                    'MaxConnection' => array(
                        'type' => 'integer',
                        'default' => 20,
                        'label' => _('Max Connections'),
                        'description' => _('Max DB connections kept by Money Server.'),
                    ),
                ),
                'Certificate' => array(
                    '_condition' => array(
                        'field' => 'MoneyServer.Enabled',
                        'value' => true,
                    ),
                    'CACertFilename' => array(
                        'type' => 'text',
                        'label' => _('CA Certificate Filename'),
                        'description' => _('Path to the CA certificate file for client/server certificate verification.'),
                    ),
                    'ServerCertFilename' => array(
                        'type' => 'path',
                        'label' => _('Server Certificate Filename'),
                        'description' => _('Path to the server certificate file for HTTPS server mode (relative to MoneyServer.exe).'),
                        'options' => array(
                            'SineWaveCert.pfx' => 'SineWaveCert.pfx', // ServerCertPassword = "123"
                            'server_cert.p12' => 'server_cert.p12', // ServerCertPassword = ""
                        ),
                    ),
                    'ServerCertPassword' => array(
                        'type' => 'password',
                        'label' => _('Server Certificate Password'),
                        'description' => _('Password for the server certificate file.'),
                        'condition' => array(
                            'field' => 'Certificate.ServerCertFilename',
                            'not_empty' => true,
                        ),
                        
                    ),
                    'CheckClientCert' => array(
                        'type' => 'checkbox',
                        'label' => _('Check Client Certificate'),
                        'default' => false,
                        'description' => _('Client Authentication from Region Server.'),
                    ),
                    'ClientCrlFilename' => array(
                        'type' => 'text',
                        'label' => _('Client CRL Filename'),
                        'description' => _('Path to the client CRL (Certificate Revocation List?) file.'),
                        'options' => array(
                            'clcrl.crt' => 'clcrl.crt', // Client Authentication from Region Server
                            'client_cert.p12' => 'client_cert.p12', // XML RPC to Region Server (Client Mode)
                        ),
                        'condition' => array(
                            'field' => 'Certificate.CheckClientCert',
                            'value' => true,
                        ),
                    ),
                    'ClientCertPassword' => array(
                        'type' => 'password',
                        'label' => _('Client Certificate Password'),
                        'description' => _('Password for the client certificate file.'),
                        'condition' => array(
                            'field' => 'Certificate.ClientCertFilename',
                            'value' => 'cient_cert.p12',
                        ),
                    ),
                ),
            ),
        );
    }        

    /**
     * Set imported options to use instead of regular settings
     */
    public static function set_imported_options($options) {
        self::$imported_options = $options;
        self::$loaded = false; // Force reload to use imported options
    }
    
    /**
     * Check if using imported options
     */
    public static function using_imported_options() {
        return self::$imported_options !== null;
    }

    /**
     * Error log configuration migration required.
     */
    public static function log_migration_required() {
        $backtrace = debug_backtrace();

        error_log(
            '[WARNING] Settings Migration required ' . caller_details()
        );
        // return false; // Indicate that migration is required
    }
    
}
