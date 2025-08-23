<?php
/**
 * Ini class for OpenSimulator Helpers
 * 
 * Handles the .ini format, including parsing and converting to arrays.
**/

class OpenSim_Ini {
    private $file;
    private $ini;
    private $config = array();
    private $raw_ini_array;

    private static $user_notices;

    public function __construct( $args ) {
        if( empty( $args ) ) {
            throw new OpenSim_Error( __FUNCTION__ .'() empty value received');
        }

        if( is_string( $args ) && file_exists( $args ) ) {
            try {
                $file_content = file_get_contents( $args );
            } catch (Throwable $e) {
                $this->notify_error( $e, 'Error reading file' );
            }
            $content = file_get_contents( $args );
            $this->raw_ini_array = explode( "\n", $content );
        } elseif( is_string( $args ) ) {
            $this->raw_ini_array = explode( "\n", $args );
        } elseif( is_array( $args ) ) {
            $this->raw_ini_array = $args;
        } else {
            throw new OpenSim_Error( __CLASS__ .' accepts only string, array or file path value' );
        }

        $this->sanitize_and_parse( $this->raw_ini_array );
    }

    public function get_config() {
        return $this->config;
    }

    public function get_ini() {
        return $this->ini;
    }

	/**
	 * Sanitize an INI string. Make sure each value is encosed in quotes.
     * Convert constants to their value.
	 */
	private function sanitize_and_parse() {
		$this->ini = '';
        $this->config = array();

        $lines = $this->raw_ini_array;

        $section = '_';
		foreach ( $lines as $line ) {
			$line = trim( $line );
			if ( empty( $line ) || preg_match('/^\s*;/', $line ) ) {
                $this->ini .= "$line\n";
				continue;
			}
			$parts = explode( '=', $line );
            if( preg_match( '/^\[[a-zA-Z]+\]$/' , $line)) {
                $section = trim( $line, '[]' );
                $this->ini .= "$line\n";
                continue;
            }
			if ( count( $parts ) < 2 ) {
				$this->ini .= "$line\n";
				continue;
			}
			// use first part as key, the rest as value
			$key   = trim( array_shift( $parts ) );
			$value = trim( implode( '=', $parts ), '\" ');

            // Replace constants with their value for $config array, leave untouched for $ini.
            $config_value = $value;
            while ( preg_match( '/\${Const\|([a-zA-Z]+)}/', $config_value, $matches ) ) {
                $const = $matches[1];
                $config_value = str_replace( '${Const|' . $const . '}', $this->config['Const'][$const], $config_value );
            }
            $this->config[$section][$key] = $config_value;

            if( is_numeric( $value ) || in_array( $value, array( "true", "false" ) ) ) {
                $this->ini .= "$key = $value\n";
            } else {
                $this->ini .= "$key = \"$value\"\n";
            }
		}
	}
}
