<?php
/**
 * OpenSimulator Core Class
 * 
 * Framework-agnostic OpenSimulator utilities and communication.
 * Contains only pure PHP code with no WordPress dependencies.
 */

if (!defined('OPENSIM_ENGINE_PATH')) {
    exit;
}

class OpenSim
{
    private static $instance = null;
	private static $key;
    
    public static function getInstance()
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * Encrypt data with key
     * Pure PHP encryption without WordPress dependencies
     */
    public static function encrypt($data, $key = null)
    {
        if (!extension_loaded('openssl') || !function_exists('openssl_encrypt')) {
            return $data; // Return unencrypted if OpenSSL not available
        }
        
        if(empty($key)) {
            return $data; // Return unencrypted if no key provided
        }

        if (!is_string($data)) {
            $data = json_encode($data);
        }
        
        $iv = openssl_random_pseudo_bytes(16);
        $encrypted = openssl_encrypt($data, 'aes-256-cbc', $key, 0, $iv);
        return base64_encode($encrypted . '::' . $iv);
    }
    
    /**
     * Decrypt data with key
     * Pure PHP decryption without WordPress dependencies
     */
    public static function decrypt($data, $key)
    {
        if (!extension_loaded('openssl') || !function_exists('openssl_decrypt')) {
            return $data; // Return raw data if OpenSSL not available
        }
        if( empty($key)) {
            return $data; // Return raw data if no key provided
        }
        if (!is_string($data)) {
            return $data;
        }
        
        if (!preg_match('/^[a-zA-Z0-9\/\r\n+]*={0,2}$/', $data)) {
            return $data;
        }
        
        list($encrypted_data, $iv) = explode('::', base64_decode($data), 2);
        $decrypted = openssl_decrypt($encrypted_data, 'aes-256-cbc', $key, 0, $iv);
        
        $decode = json_decode($decrypted, true);
        if (json_last_error() === JSON_ERROR_NONE) {
            return $decode;
        }
        return $decrypted;
    }
    
    /**
     * Sanitize URI - framework agnostic
     */
    public static function sanitize_uri($login_uri)
    {
        if (empty($login_uri)) {
            return null;
        }
        
        $login_uri = (preg_match('/^https?:\/\//', $login_uri)) ? $login_uri : 'http://' . $login_uri;
        
        $parts = parse_url($login_uri);
        if (!$parts) {
            return null;
        }
        
        $parts = array_merge([
            'scheme' => 'http',
            'port' => preg_match('/osgrid\.org/', $login_uri) ? 80 : 8002,
        ], $parts);
        
        return $parts['scheme'] . '://' . $parts['host'] . ':' . $parts['port'];
    }

    static function sanitize_login_uri( $login_uri ) {
		if ( empty( $login_uri ) ) {
			return;
		}
	
		$login_uri = ( preg_match( '/^https?:\/\//', $login_uri ) ) ? $login_uri : 'http://' . $login_uri;
	
		$parts = OpenSim::parse_args(
			wp_parse_url( $login_uri ),
			array(
				'scheme' => 'http',
				'port'   => preg_match('/osgrid\.org/', $login_uri) ? 80 : 8002,
			),
		);
	
		$login_uri = $parts['scheme'] . '://' . $parts['host'] . ':' . $parts['port'];
	
		return $login_uri;
	}

    public static function parse_args( $args, $defaults ) {
        if( empty( $defaults ) ) {
            $defaults = array();
        }
        if( is_object( $args ) ) {
            $args = get_object_vars( $args );
        } elseif( is_array( $args ) ) {
            $args = $args;
        } else {
            parse_str( $args, $args );
        }
        return array_merge( $defaults, $args );
    }

    public static function sanitize_uri_plus( $url ) {
        $url = trim( $url );
        $url = str_replace( ' ', '+', $url );
        $url = filter_var( $url, FILTER_SANITIZE_URL );
        return $url;
    }


    /**
     * Check if string is valid UUID
     */
    public static function is_uuid($uuid, $accept_null = true)
    {
        if (!is_string($uuid)) {
            return false;
        }
        
        if (!preg_match('/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/', $uuid)) {
            return false;
        }
        
        if (!$accept_null && self::is_null_key($uuid)) {
            return false;
        }
        
        return true;
    }
    
    /**
     * Check if UUID is null key
     */
    public static function is_null_key($uuid)
    {
        $null_keys = [
            '00000000-0000-0000-0000-000000000000',
            '00000000-0000-0000-0000-000000000001',
        ];
        return in_array($uuid, $null_keys);
    }
    
    /**
     * Get grid info from gateway URI
     * Framework-agnostic grid information retrieval
     */
    public static function grid_info($gateway_uri, $force = false)
    {
        // Simple caching without framework dependencies
        static $cache = [];
        $cache_key = md5($gateway_uri);
        
        if (!$force && isset($cache[$cache_key])) {
            return $cache[$cache_key];
        }
        
        $check_login_uri = 'http://' . preg_replace('+.*://+', '', $gateway_uri);
        $xml = self::fast_xml($check_login_uri . '/get_grid_info');
        
        if (!$xml) {
            return false;
        }
        
        $grid_info = (array) $xml;
        $cache[$cache_key] = $grid_info;
        
        return $grid_info;
    }
    
    /**
     * Fast XML retrieval - framework agnostic
     */
    public static function fast_xml($url)
    {
        if (!function_exists('curl_init') || !function_exists('simplexml_load_string')) {
            return null;
        }
        
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        curl_setopt($ch, CURLOPT_USERAGENT, 'OpenSim Engine/1.0');
        
        $html = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        if ($http_code !== 200 || !$html) {
            return null;
        }
        
        $xml = simplexml_load_string($html);
        return $xml;
    }
    
    /**
     * Validate database credentials
     * Framework-agnostic database validation
     */
    public static function validate_db_credentials($db_creds)
    {
        if (empty($db_creds['host']) || empty($db_creds['user']) || 
            empty($db_creds['pass']) || empty($db_creds['name'])) {
            return false;
        }
        
        $port = $db_creds['port'] ?? 3306;
        
        // Suppress connection errors
        $db_conn = @new mysqli($db_creds['host'], $db_creds['user'], $db_creds['pass'], $db_creds['name'], $port);
        
        if ($db_conn && !$db_conn->connect_error) {
            $db_conn->close();
            return true;
        }
        
        return false;
    }
    
    /**
     * Parse connection string to array
     * Framework-agnostic connection string parser
     */
    public static function connectionstring_to_array($connectionstring)
    {
        $parts = explode(';', $connectionstring);
        $creds = [];
        
        foreach ($parts as $part) {
            $pair = explode('=', $part, 2);
            if (count($pair) === 2) {
                $creds[trim($pair[0])] = trim($pair[1]);
            }
        }
        
        return $creds;
    }

	public static function is_true( $value ) {
		if ( is_bool( $value ) ) {
			return $value;
		}
		$value = strtolower( $value );
		return $value === 'true' || $value === 'yes' || $value === true || $value === 1 || $value === '1';
	}

	/**
	 * Complete standard empty() function with NULL key.
	 */
	public static function empty( $var ) {
		if ( ! $var ) {
			return true;
		}
		if ( empty( $var ) ) {
			return true;
		}
		$null_keys = array(
			'00000000-0000-0000-0000-000000000000',
			'00000000-0000-0000-0000-000000000001',
			// W4OS_NULL_KEY, // Not yet defined when this function is called early.
		);
		if ( in_array( $var, $null_keys ) ) {
			return true;
		}
		return false;
	}

    /**
     * Convert bitmask to readable array
     */
    public static function demask($mask, $options, $additionalvalue = null) {
        $result = array();
        
        if (!is_numeric($mask)) {
            return $result;
        }
        
        $mask = intval($mask);
        
        for ($i = 0; $i < count($options); $i++) {
            if ($mask & (1 << $i)) {
                $result[] = $options[$i];
            }
        }
        
        if (!empty($additionalvalue)) {
            $result[] = $additionalvalue;
        }
        
        return $result;
    }
}
