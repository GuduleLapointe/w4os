<?php
/**
 * Helper functions for validating Engine Settings vs WordPress options
 */

if (!defined('ABSPATH') && !defined('OPENSIM_ENGINE')) {
    exit;
}

/**
 * Compare Engine Settings value with WordPress option value
 * Used for validation during transition period
 * 
 * @param string $ini_key Engine Settings key (e.g., 'W4OS.ModelFirstName')
 * @param string $wp_option WordPress option name (e.g., 'w4os_model_firstname')
 * @param mixed $default Default value if both are empty
 * @return array Comparison results
 */
function w4os_validate_setting($ini_key, $wp_option, $default = null) {
    $results = [
        'ini_key' => $ini_key,
        'wp_option' => $wp_option,
        'old_value' => get_option($wp_option, $default),
        'new_value' => null,
        'match' => false,
        'error' => null
    ];
    
    try {
        if (class_exists('Engine_Settings')) {
            $results['new_value'] = Engine_Settings::get($ini_key, $default);
        }
    } catch (Exception $e) {
        $results['error'] = $e->getMessage();
    }
    
    // Compare values (convert to strings for comparison)
    $old_str = is_array($results['old_value']) ? json_encode($results['old_value']) : (string)$results['old_value'];
    $new_str = is_array($results['new_value']) ? json_encode($results['new_value']) : (string)$results['new_value'];
    
    $results['match'] = ($old_str === $new_str);
    
    return $results;
}

/**
 * Log validation results for debugging
 * 
 * @param array $validation_result Result from w4os_validate_setting()
 * @param bool $log_matches Whether to log successful matches (default: false)
 */
function w4os_log_validation($validation_result, $log_matches = false) {
    if (!$validation_result['match'] || $log_matches) {
        $message = sprintf(
            'Setting validation: %s | WP: %s | Engine: %s | Match: %s',
            $validation_result['ini_key'],
            var_export($validation_result['old_value'], true),
            var_export($validation_result['new_value'], true),
            $validation_result['match'] ? 'YES' : 'NO'
        );
        
        if ($validation_result['error']) {
            $message .= ' | Error: ' . $validation_result['error'];
        }
        
        error_log('[W4OS Settings Validation] ' . $message);
    }
}

/**
 * Get a setting using the old method, with optional validation against new method
 * This is a drop-in replacement for get_option() during testing
 * 
 * @param string $wp_option WordPress option name
 * @param mixed $default Default value
 * @param string $ini_key Optional Engine Settings key for validation
 * @param bool $log_validation Whether to log validation results
 * @return mixed Option value
 */
function w4os_get_option_with_validation($wp_option, $default = null, $ini_key = null, $log_validation = false) {
    $value = get_option($wp_option, $default);
    
    // If ini_key is provided, validate against Engine Settings
    if ($ini_key && $log_validation) {
        $validation = w4os_validate_setting($ini_key, $wp_option, $default);
        w4os_log_validation($validation);
    }
    
    return $value;
}

/**
 * Test all mapped settings and return results
 * Useful for debugging and validation
 * 
 * @return array Array of validation results
 */
function w4os_test_all_mapped_settings() {
    if (!class_exists('W4OS3_Migration_2to3')) {
        return ['error' => 'W4OS3_Migration_2to3 class not available'];
    }
    
    $results = [];
    $mapped_keys = W4OS3_Migration_2to3::get_mapped_ini_keys();
    $wp_options = W4OS3_Migration_2to3::get_available_options();
    
    foreach ($mapped_keys as $ini_key) {
        $mapping = W4OS3_Migration_2to3::get_mapping_for_key($ini_key);
        if (!$mapping) continue;
        
        // Find the primary WordPress option for this INI key
        $option_names = array_filter($mapping, function($key) {
            return $key !== 'transform';
        }, ARRAY_FILTER_USE_KEY);
        
        $wp_option = reset($option_names); // Get first option name
        
        $validation = w4os_validate_setting($ini_key, $wp_option);
        $results[] = $validation;
    }
    
    return $results;
}

/**
 * Build connection string from database info array/string
 */
function build_connection_string_from_info($db_info) {
    if (is_string($db_info)) {
        // Already a connection string, parse and return parsed format
        return OSPDO::connectionstring_to_array($db_info);
    }
    
    if (is_array($db_info) && class_exists('OSPDO')) {
        // Use OSPDO to build connection string then parse back to array for comparison
        $connection_string = OSPDO::array_to_connectionstring($db_info);
        return OSPDO::connectionstring_to_array($connection_string);
    }
    
    if (is_array($db_info)) {
        // Manual conversion for consistency
        $parts = [];
        
        if (isset($db_info['host'])) $parts['Data Source'] = $db_info['host'];
        if (isset($db_info['database'])) $parts['Database'] = $db_info['database'];
        if (isset($db_info['user'])) $parts['User ID'] = $db_info['user'];
        if (isset($db_info['pass'])) $parts['Password'] = $db_info['pass'];
        if (isset($db_info['port']) && $db_info['port'] != 3306) $parts['Port'] = $db_info['port'];
        $parts['Old Guids'] = 'true';
        
        ksort($parts);
        return $parts;
    }
    
    return [];
}

/**
 * Convert parsed connection array back to string format
 */
function array_to_connection_string_format($parts_array) {
    if (empty($parts_array)) {
        return '';
    }
    
    $formatted_parts = [];
    foreach ($parts_array as $key => $value) {
        $formatted_parts[] = "$key=$value";
    }
    
    return implode(';', $formatted_parts);
}

/**
 * Enhanced comparison that handles connection strings properly
 */
function compare_values_enhanced($old_value, $new_value, $key = '') {
    // Handle empty values - if both are empty, they match
    if (empty($old_value) && empty($new_value)) {
        return 'match';
    }
    
    // If one is empty and the other isn't, they differ
    if (empty($old_value) !== empty($new_value)) {
        return 'differ';
    }
    
    // Special handling for connection strings
    if (stripos($key, 'connectionstring') !== false || stripos($key, 'db') !== false) {
        $old_parsed = build_connection_string_from_info($old_value);
        $new_parsed = OSPDO::connectionstring_to_array($new_value);
        
        return ($old_parsed === $new_parsed) ? 'match' : 'differ';
    }
    
    // Boolean comparison
    if (is_bool($old_value) || is_bool($new_value)) {
        $old_bool = normalize_boolean($old_value);
        $new_bool = normalize_boolean($new_value);
        return ($old_bool === $new_bool) ? 'match' : 'differ';
    }
    
    // Array comparison
    if (is_array($old_value) || is_array($new_value)) {
        // Normalize both to arrays
        $old_array = is_array($old_value) ? $old_value : [$old_value];
        $new_array = is_array($new_value) ? $new_value : [$new_value];
        
        return ($old_array === $new_array) ? 'match' : 'differ';
    }
    
    // String comparison
    return (trim((string)$old_value) === trim((string)$new_value)) ? 'match' : 'differ';
}

/**
 * Normalize boolean values to consistent format
 */
function normalize_boolean($value) {
    if( is_string($value) && in_array( strtolower($value), [ 'false', 'no', 'yes'] ) ) {
        return false;
    }
    return (bool)$value;
}

/**
 * Format value for display in comparison tables
 */
function format_value_for_display($value) {
    if (is_null($value)) {
        return '<em style="color: #999;">null</em>';
    }
    
    if (is_bool($value)) {
        return $value ? '<strong>true</strong>' : '<strong>false</strong>';
    }
    
    if (is_array($value)) {
        if (empty($value)) {
            return '<em style="color: #999;">empty array</em>';
        }
        return '<pre>' . esc_html(json_encode($value, JSON_PRETTY_PRINT)) . '</pre>';
    }
    
    if (is_string($value) && trim($value) === '') {
        return '<em style="color: #999;">empty string</em>';
    }
    
    return esc_html($value);
}
