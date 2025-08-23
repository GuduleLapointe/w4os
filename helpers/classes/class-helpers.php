<?php
/**
 * OpenSim class
 * 
 * This class is responsible for defining constants and loading all classes needed by all scripts.
 * 
 * Classes needed only by some scripts are handled by themselves.
 * 
 * @package magicoli/opensim-helpers
 */

// Start session if not already started
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

class Helpers {
    private static $tmp_dir;
    private static $user_notices = array();
    private static $version;
    private static $version_slug;
    private static $scripts;
    private static $styles;
    private static $is_dev;
    private static $host;

    public static $robust_db;

    public function __construct() {
        self::$host = $_SERVER['HTTP_HOST'] ?? null;
        // If empty, use the host part of grid login uri
        if( empty( self::$host ) ) {
            $login_url = OpenSim::login_uri();
            if( ! empty( $login_url ) ) {
                $parsed = parse_url( $login_url );
                self::$host = $parsed['host'] ?? 'localhost';
            }
        }

        // Check if domain name starts with "dev." or usual wp debug constants are set
        self::$is_dev = ( strpos( self::$host, 'dev.' ) === 0 ) || ( defined( 'OSHELPERS_DEBUG' ) && OSHELPERS_DEBUG ) || ( defined( 'OSHELPERS_DEBUG' ) && OSHELPERS_DEBUG );
    }

    public function init() {
        $this->constants();
        $this->includes();

        // $this->grid = new OpenSim_Grid();
    }

    public function constants() {
        if( ! defined( 'ABSPATH' ) ) {
            define( 'ABSPATH', dirname( __FILE__ ) . '/' );
        }
        define( 'OSHELPERS', true );
        define( 'OSHELPERS_DIR', self::trailingslashit( dirname( __DIR__ ) ) );
        define( 'OSHELPERS_URL', self::url() );
        // ( isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http" ) . "://$_SERVER[HTTP_HOST]$_SERVER[SCRIPT_NAME]" );

    }

    public function includes() {
        // require_once( OPENSIM_ENGINE_PATH . 'classes/class-exception.php' );
        // require_once( OSHELPERS_DIR . 'includes/databases.php' );
        // require_once( OSHELPERS_DIR . 'includes/functions.php' );

        // if ( file_exists( OSHELPERS_DIR . 'includes/config.php' ) ) {
        //     try {
        //         include_once( OSHELPERS_DIR . 'includes/config.php' );
        //     } catch ( Error $e ) {
        //         self::notify_error( $e );
        //     }
        // }

        $this->db_connect();

        // require_once( OSHELPERS_DIR . 'classes/class-locale.php' );
        // require_once( OSHELPERS_DIR . 'classes/class-ini.php' );
        // require_once( OSHELPERS_DIR . 'classes/class-grid.php' );
    }

    public function db_connect() {
        $DatabaseService = self::get_option( 'DatabaseService', false );

        $connectionstring = self::get_option( 'DatabaseService.ConnectionString', false);
        if( $connectionstring ) {
            $creds = self::connectionstring_to_array( $connectionstring );
            $dsn = sprintf(
                'mysql:host=%s;dbname=%s',
                $creds['host'] . ( empty( $creds['port'] ) ? '' : ':' . $creds['port'] ),
                $creds['name'],
            );
            $db = new OSPDO( $dsn, $creds['user'], $creds['pass'] );
            if( $db->connected ) {
                $db->setAttribute( PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION );
                $db->setAttribute( PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC );
                self::$robust_db = $db;
            }
        } else {
            return false;
        }
    }

    public static function url( $path = null ) {
        $url_path = Engine_Settings::get(
            'engine.Helpers.HelpersSlug',
        );
        if(empty($url_path)) {
            $helpers_dir = dirname( __DIR__ );
            $url_path = str_replace( $_SERVER['DOCUMENT_ROOT'], '', $helpers_dir ); 
        }
        $url_path = '/' . ltrim( $url_path, '/' ) . '/';

        $parsed = array(
            'scheme' => isset( $_SERVER['HTTPS'] ) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http',
            'host' => self::$host,
        );
        $url = self::build_url( $parsed ) . ltrim( $url_path );

        if( ! empty( $path ) ) {
            $url .= ltrim( $path, '/' );
        }
        return $url;
    }

    public static function get_version( $sanitized = false ) {
        if( $sanitized && self::$version_slug ) {
            return self::$version_slug;
        } else if ( ! $sanitized && self::$version ) {
            return self::$version;
        }
        if( file_exists( OSHELPERS_DIR . '.version' ) ) {
            $version = file_get_contents( OSHELPERS_DIR . '.version' );
        } else {
            $version = '0.0.0';
        }
        // if( file_exists( '.git/HEAD' ) ) {
        //     $hash = trim( file_get_contents( '.git/HEAD' ) );
        //     $hash = trim( preg_replace( '+.*[:/]+', '', $hash ) );
        //     if( !empty( $hash ) && file_exists( '.git/refs/heads/' . $hash ) ) {
        //         $hash = substr( file_get_contents( '.git/refs/heads/' . $hash ), 0, 7 ) . " ($hash)";
        //     } else {
        //         $hash = substr( $hash, 0, 7 );
        //         $hash .= ' (detached)';
        //     }

        //     $version .= empty( $hash ) ? ' git ' : ' git ' . $hash;
        //     self::$is_dev = ( empty( $hash ) ) ? self::$is_dev : true;
        // }

        self::$version = $version;
        self::$version_slug = sanitize_version( $version );
        if( $sanitized && self::$version_slug ) {
            return self::$version_slug;
        }
        return $version;
    }

    public static function get_temp_dir( $dir = false ) {
        if( isset( self::$tmp_dir ) ) {
            return self::$tmp_dir;
        }

        if ( ! empty( $dir ) && is_dir( $dir ) && is_writable( $dir ) ) {
            $dir = realpath( $dir );
        } else {
            $dirs = array(
                sys_get_temp_dir(),
                dirname( $_SERVER['DOCUMENT_ROOT'] )  . '/tmp',
                ini_get( 'upload_tmp_dir' ),
                '/var/tmp',
                '~/tmp',
            );
            foreach( $dirs as $key => $dir ) {
                if( is_dir( $dir ) && is_writable( $dir ) ) {
                    $dir = realpath( $dir );
                    break;
                }
            }
        }
        if ( ! $dir ) {
            throw new OpenSim_Error( 'No writable temporary directory found.' );
        }
        
        self::$tmp_dir = $dir;
        return $dir;
    }

    // Clone of WP trailingslashit function
    public static function trailingslashit( $value ) {
        return self::untrailingslashit( $value ) . '/';
    }

    // Clone of WP untrailingslashit function
    public static function untrailingslashit( $value ) {
        return rtrim( $value, '/\\' );
    }

    /**
     * Notify user of an error, log it and display it in the admin area
     * 
     * @param mixed $error (string) Error message or (Throwable) Exception
     * @param string $type Error severity: 'info', 'warning', 'danger'
     * @return void
     */
    public static function notify_error( $error, $type = 'warning' ) {
        // Initialize the prefix before error type check
        $prefix = '[' . strtoupper( $type ) . '] ';

        // Retrieve the calling method's information
        if ( $error instanceof Throwable ) {
            $message = $error->getMessage();
        } elseif( is_string($error) ) {
            $message = $error;
        } else {
            $message = _('Unknown error, see log for details');
            error_log('[ERROR] Unidentified error type: ' . gettype( $error ) . ' ' . print_r( $error, true ) . ' in ' . __FILE__ . ':' . __LINE__ );
        }
        if( ! empty( $message ) ) {
            self::notify( $message, $type );
        }        
    }

    public static function notify( $message, $type = 'info' ) {
        $key = md5( $type . $message ); // Make sure we don't have duplicates
        self::$user_notices[$key] = array(
            'message' => $message,
            'type' => $type,
        );
    }
    
    public static function get_notices() {
        $html = '';
        foreach( self::$user_notices as $key => $notice ) {
            $type = $notice['type'] ?? 'info';
            switch( $type ) {
                case 'task-checked':
                    $html .= sprintf(
                        '<div class="form-check %s">
                            <input class="form-check-input" type="checkbox" value="" id="flexCheckChecked" checked readonly>
                            <label class="form-check-label" for="flexCheckChecked">
                                %s
                            </label>
                        </div>',
                        $type,
                        $notice['message']
                    );
                    break;

                    default:
                    $html .= sprintf(
                        '<div class="alert alert-%s my-4">%s</div>',
                        $type,
                        $notice['message']
                    );
            }
        }
        return $html;
    }

    public static function validate_error_type( $type, $fallback = 'light' ) {
        $given = $type;
        $type = in_array( $type, array(
            'primary',
            'secondary',
            'success',
            'danger',
            'warning',
            'info',
            'light',
            'dark',
        ) ) ? $type : $fallback;
        return $type;
    }

    public static function validate_error ( $error, $type = 'light' ) {
        if( is_string( $error )) {
            $error = array( 'message', $error );
        }
        $error = OpenSim::parse_args( $error, array(
            'message' => _('Error'),
            'type' => $type,
        ));
        $error['type'] = self::validate_error_type( $error['type'], $type );
        return $error;
    }

    public static function error_html( $error, $type = null ) {
        $error = self::validate_error( $error, $type );
        $html = sprintf(
            '<div class="text-%s">%s</div>',
            $error['type'],
            $error['message'],
        );
        return $html;
    }

    /**
     * Log message to error_log, adding calling [CLASS] and function before message, and severity if given
     */
    public static function log( $message, $severity = none ) {
        $caller = debug_backtrace( DEBUG_BACKTRACE_IGNORE_ARGS, 2 );
        $caller = $caller[1];
        $class = $caller['class'] ?? '';
        $function = $caller['function'] ?? '';
        $message = sprintf(
            '[%s%s%s] %s%s',
            $class,
            empty( $class ) ? '' : '::',
            $function,
            empty( $severity ) ? '' : strtoupper( $severity ) . ' ',
            $message
        );
        // Add severity if given
        error_log( $message );
    }

    public static function callback_name_string( $callback ) {
        if( is_string( $callback ) ) {
            return $callback;
        }
        if( is_array( $callback ) && is_object( $callback[0] ) ) {
            $callback_name = get_class($callback[0]) . '::' . $callback[1];
            return $callback_name;
        }
        if( is_array( $callback ) ) {
            return $callback[0] . '::' . $callback[1];
        }
        if( is_object( $callback ) ) {
            return get_class( $callback );
        }
        return 'Unknown';
    }

    public static function build_url( $parsed ) {
        if( empty( $parsed['host'] ) ) {
            $url = '';
        } else {
            $url = ( $parsed['scheme'] ?? 'https' ) . '://' . $parsed['host'];
        }
        $url .= $parsed['path'] ?? '';
        if( ! empty( $parsed['query'] ) ) {
            $url .= '?' . $parsed['query'];
        }
        return $url;
    }

    public static function add_query_args( $url, $args ) {
        $parsed = parse_url( $url );
        $query = $parsed['query'] ?? '';
        $query = OpenSim::parse_args( $query, array() );
        $query = array_merge( $query, $args );
        $query = http_build_query( $query );
        $parsed['query'] = $query;
        $url = self::build_url( $parsed );
        return $url;
    }

    /**
     * Basic function to replace WP enqueue_script when not in WP environment.
     * Add the script to a private property that will be used with another method to output all scripts.
     * Use OSHELPERS_URL constant to build the URL unless it's already full.
     * Use self::get_version() to define the version of the script unless it is already defined.
     */
    public static function enqueue_script( $handle, $src, $deps = array(), $ver = false, $in_footer = false ) {
        $handle = preg_match( '/^oshelpers-/', $handle ) ? $handle : 'oshelpers-' . $handle;
        $handle = ( rtrim ( $handle, '-css' ) ) . '-js';

        self::$scripts = self::$scripts ?? array( 'head' => array(), 'footer' => array() );
        if( strpos( $src, '://' ) === false ) {
            $src_file = OSHELPERS_DIR . ltrim( $src, '/' );
            $src = OSHELPERS_URL . ltrim( $src, '/' );
            if(! file_exists( $src_file ) ) {
                error_log( __FUNCTION__ . ' file not found: ' . $src_file . ' in ' . __FILE__ . ':' . __LINE__ );
                return false;
            }
        }
        $ver = empty( $ver ) ? self::get_version( true ) : sanitize_version( $ver );
        $src = self::add_query_args( $src, array( 'ver' => $ver ) );

        $section = $in_footer ? 'footer' : 'head';
        self::$scripts[$section][$handle] = array(
            'src' => $src,
            'deps' => $deps,
            'ver' => $ver ?? self::get_version( true ),
            'in_footer' => $in_footer,
        );
    }

    /**
     * Return or output the html for scripts in the head or footer
     * 
     * @param string $section 'head' or 'footer'
     * @param bool $echo Output the html if true, return it if false
     */
    public static function get_scripts( $section, $echo = false ) {
        if( ! isset( self::$scripts[$section] ) ) {
            return '';
        }
        $html = '';
        if(empty( self::$scripts[$section] ) ) {
            return '';
        }

        $template = '<script id="%s" src="%s" type="text/javascript"></script>';

        $scripts = self::$scripts[$section];
        foreach( $scripts as $handle => $script ) {
            // error_log( 'Script: ' . print_r( $script, true ) );
            $html .= sprintf(
                $template,
                $handle,
                $script['src'],
                empty( $script['ver'] ) ? '' : 'version="' . $script['ver'] . '"'
            );
        }
        if( $echo ) {
            echo $html;
        }
        // error_log( $html );
        return $html;
    }

    public static function enqueue_style( $handle, $src, $deps = array(), $ver = false, $media = 'all' ) {
        $handle = preg_match( '/^oshelpers-/', $handle ) ? $handle : 'oshelpers-' . $handle;
        $handle = ( rtrim ( $handle, '-css' ) ) . '-css';

        self::$styles = self::$styles ?? array( 'head' => array(), 'footer' => array() );
        if( strpos( $src, '://' ) === false ) {
            $src_file = OSHELPERS_DIR . ltrim( $src, '/' );
            $src = OSHELPERS_URL . ltrim( $src, '/' );
            if(! file_exists( $src_file ) ) {
                error_log( __FUNCTION__ . ' file not found: ' . $src_file . ' in ' . __FILE__ . ':' . __LINE__ );
                return false;
            }
        }
        // if( strpos( $src, '://' ) === false ) {
        //     $src = OSHELPERS_URL . ltrim( $src, '/' );
        // }
        $ver = empty( $ver ) ? self::get_version( true ) : sanitize_version( $ver );
        $src = self::add_query_args( $src, array( 'ver' => $ver ) );

        self::$styles['head'][$handle] = array(
            'src' => $src,
            'deps' => $deps,
            'ver' => $ver,
            'media' => $media,
        );
    }

    public static function get_styles( $echo = false ) {
        if( ! isset( self::$styles['head'] ) ) {
            return '';
        }
        $html = '';
        if(empty( self::$styles['head'] ) ) {
            return '';
        }

        $template = '<link id="%s" rel="stylesheet" href="%s" type="text/css" media="%s">';

        $styles = self::$styles['head'];
        foreach( $styles as $handle => $style ) {
            $html .= sprintf(
                $template,
                $handle,
                $style['src'],
                $style['media'],
            );
        }
        if( $echo ) {
            echo $html;
        }
        return $html;
    }

    public static function validate_condition( $condition ) {
        if( is_callable( $condition ) ) {
            return $condition();
        }
        if( is_bool( $condition ) ) {
            return $condition;
        }
        switch( $condition ) {
            case 'logged_in':
                return self::is_logged_in();
            case 'logged_out':
                return self::is_logged_out();
            default:
                return false;
        }
    }

    /**
     * Get user preferred language from browser settings.
     * 
     * @param bool $long Full locale string if true (en_US), language code otherwise (en)
     * @return string
     */
    public static function user_locale( $long = true ) {
        return $long ? OpenSim_Locale::locale() : OpenSim_Locale::lang();
    }

    public static function user_lang( $long = false ) {
        return $long ? OpenSim_Locale::locale() : OpenSim_Locale::lang();
    }

    /**
     * Return the actual language of the content if localization is setup
     */
    public static function content_lang( $long = false ) {
        // When localization is setup, we will return user language
        // For now, we return english.
        $lang = 'en_US';
        
        // return self::user_locale( $long );
        return $long ? $lang : substr( $lang, 0, 2 );
    }

    public static function is_logged_in() {
        // WP is not loaded so constants like COOKIEHASH are not available.
        // Any cookie matching wordpress_logged_in or wordpress_logged_in_*
        // is considered a valid login cookie.
        // If logged_in, use first part of cookie value as user_id
        foreach( $_COOKIE as $key => $value ) {
            if( preg_match( '/^wordpress_logged_in/', $key ) ) {
                $parts = explode( '|', $value );
                $_SESSION['user_id'] = $parts[0];
                break;
            }
        }

        return isset( $_SESSION['user_id'] );
    }

    public static function is_logged_out() {
        return ! self::is_logged_in();
    }

    public static function get_user_id() {
        return $_SESSION['user_id'] ?? false;
    }

    public static function display_name( $fallback = false ) {
        if( ! self::is_logged_in() ) {
            return $fallback;
        }

        // For now, we return the user_id
        return self::get_user_id() ?? $fallback;
    }

    public static function icon( $icon, $size = 'inherit' ) {
        if( is_callable( $icon ) ) {
            $callback = $icon;
            return call_user_func( $callback, $size );
        }
        $size = is_numeric( $size ) ? $size . 'px' : $size;
        return ' <i class="bi bi-' . $icon . '" style="font-size:' . $size . ';"></i> ';
    }

    /**
     * Display small user icon based on in-world Avatar profile picture.
     * Not implemented yet. For now, we use bootstrap icons.
     * In any case, we don't use gravatar yet.
     */
    public static function user_icon( $size = 'inherit' ) {
        if( ! self::is_logged_in() ) {
            return null;
        }
        $size = is_numeric( $size ) ? $size . 'px' : $size;
        return self::icon( 'person-circle', $size );
        // For now, we return user icon with bootstrap library
        // return ' <i class="bi bi-person-circle" style="font-size:' . $size . ';"></i> ';
    }

    /**
     * get_option()
     * 
     * Retrieve an option value from 
     * - $_SESSION['installation']['config'], if exists, organized as an array(
     *    'section' => array(
     *       'option' => 'value',
     *       'option2' => 'value2',
     *   )
     * - self::$config, if exists, organized the same way as $_SESSION['installation']['config']
     * - constants defined includes/config.php (map to be implemented later)
     * - site configuration data (to be implemented later)
     */
    public static function get_option( $option, $default = null ) {
        $config = $_SESSION['installation']['config'] ?? self::$config ?? array();
        if( empty( $config ) ) {
            return $default;
        }
        // Give value of $config[$section][$key] if when given $option = 'section.key'
        if( strpos( $option, '.' ) !== false ) {
            $parts = explode( '.', $option );
            $section = $config[$parts[0]];
            $key = trim($parts[1]);
            return $section[$key] ?? $default;
        }

        // Otherwise return global option
        return $config[$option] ?? $default;
    }

    public static function get_home_url() {
        $parsed = array(
            'scheme' => isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http',
            'host' => $_SERVER['HTTP_HOST'] ?? 'localhost',
            'path' => '/',
        );
        return self::build_url( $parsed );
    }

    static function hop( $url = null, $string = null, $format = true ) {
        if ( empty( $url ) ) {
                // $url = get_option( 'w4os_login_uri' );
                return $string;
        }
        $url = opensim_format_tp( $url, TPLINK_HOP );

        if ( ! $format ) {
                return $url;
        }

        $string    = ( empty( $string ) ) ? $url : $string;
        $classes[] = 'hop';
        $classes[] = 'hop-link';
        if ( preg_match( ':/app/agent/:', $url ) ) {
                $classes[] = 'profile';
        }

        $string = preg_replace( '+.*://+', '', $string );
        return sprintf(
            '<a class="%s" href="%s">%s %s</a>',
            implode( ' ', $classes ),
            $url,
            self::icon( 'door-open' ),
            $string,
            // self::icon( 'box-arrow-up-right' ),
            // self::icon( 'arrow-right-square' ),
        );
    }

    public static function is_error( $thing ) {
        // Throwable includes Exception and probably other things.
        if ( $thing instanceof Throwable ) {
            return true;
        }
        return false;
    }
    
}

$Helpers = new Helpers();
$Helpers->init();
