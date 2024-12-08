<?php
/**
 * Tranisition initialisation class for v2 to v3.
 * 
 * This class loads the classes and functions needed to test v3 features
 * while keeping v2 features available.
 * 
 * It will replace both legacy/init.php and includes/loader.php when all 
 * new v3 features are validated, and all remaining v2 or legacy features 
 * are ported to v3.
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
    die;
}

/**
 * Main plugin class.
 * 
 * This class loads the classes and functions needed to test v3 features
 */
class W4OS3 {
    public $robust_db;
    // public function __construct() {
    //     // Safety only, this class should not be instantiated.
    //     self::init();
    // }

    public function init() {
        self::constants();
        self::includes();

        // Connect to the robust database and make it available to all classes.
        $this->robust_db = new W4OS_WPDB( W4OS_DB_ROBUST );

        // Register hooks
        
        // add_action( 'admin_menu', [ __CLASS__, 'add_submenus' ] );
    }

    function db( $db = 'robust' ) {
        if ( $db == 'robust' ) {
            return $this->robust_db;
        }
        // return $this->robust_db;
    }

    public static function constants() {
        define( 'W4OS_PLUGIN_DIR', plugin_dir_path( __DIR__) );
        define( 'W4OS_PLUGIN', basename(W4OS_PLUGIN_DIR) . '/w4os.php' );
        define( 'W4OS_INCLUDES_DIR', plugin_dir_path( __FILE__ ) );
        define( 'W4OS_TEMPLATES_DIR', W4OS_INCLUDES_DIR . 'templates/' );
        define( 'W4OS_ENABLE_V3', W4OS3::get_option( 'enable-v3-features', false ) );
        define( 'W4OS_PATTERN_NAME', '[A-Za-z][A-Za-z0-9]* [A-Za-z][A-Za-z0-9]*' ); // Moved to v3 init class

        define( 'W4OS_DB_ROBUST', array(
            'user'     => get_option( 'w4os_db_user', 'opensim' ),
            'pass'     => get_option( 'w4os_db_pass', 'opensim' ),
            'database' => get_option( 'w4os_db_database', 'opensim' ),
            'host'     => get_option( 'w4os_db_host', 'localhost' ),
            'port'     => get_option( 'w4os_db_port', '3306' ),
            // 'type'     => 'mysql',
        ) );
    }

    public static function includes() {
        // Transition classes will be loaded here.

        // First we include all the files
        require_once W4OS_INCLUDES_DIR . '2to3-settings.php';
        require_once W4OS_INCLUDES_DIR . 'class-db.php';
        
        // Load v3 features if enabled
        if ( W4OS_ENABLE_V3 ) {
            // Include v3 feature files
            require_once W4OS_INCLUDES_DIR . '2to3-avatar.php';
            require_once W4OS_INCLUDES_DIR . '2to3-region.php';
        }

        // Once all files are loaded, we start the classes.
        W4OS3_Settings::init();

        if ( W4OS_ENABLE_V3 ) {
            $AvatarClass = new W4OS3_Avatar(); $AvatarClass->init();
            $RegionClass = new W4OS3_Region(); $RegionClass->init();
        }
    }

    // Replicate core add_submenu_page to simplify other classes code.
    public static function add_submenu_page( $parent_slug, $page_title, $menu_title, $capability, $menu_slug, $callback = '', $position = null ) {
        $parent_slug = 'w4os';
        $prefix = $parent_slug . '-';
        if ( ! preg_match( '/^' . $prefix . '/', $menu_slug ) ) {
            $menu_slug = $prefix . $menu_slug;
        }
        add_submenu_page(
            $parent_slug,
            $page_title,
            $menu_title,
            $capability,
            $menu_slug,
            $callback,
            $position,
        );
    }


    static function get_option( $option, $default = false ) {
        if(is_array($option) && isset($option[1])) {
            $option_main = $option[0];
            $option = $option[1];
        } else {
    		$options_main = 'w4os_settings';
        }
		$result        = $default;
		if ( preg_match( '/:/', $option ) ) {
			$options_main = strstr( $option, ':', true );
			$option        = trim( strstr( $option, ':' ), ':' );
		}

		$options = get_option( $options_main );
		if ( $options && isset( $options[ $option ] ) ) {
			$result = $options[ $option ];
		}

		// } else {
		// $result = get_option($option, $default);
		// }
		return $result;
	}

	static function update_option( $option, $value, $autoload = null ) {
        if(is_array($option) && isset($option[1])) {
            $option_main = $option[0];
            $option = $option[1];
        } else if ( preg_match( '/:/', $option ) ) {
            $options_main = strstr( $option, ':', true );
            $option        = trim( strstr( $option, ':' ), ':' );
        } else {
    		$options_main = null;
        }
        $options            = get_option( $options_main );
        $options[ $option ] = $value;
        $result              = update_option( $options_main, $options, $autoload );

        return $result;
	}

    static function is_new_post( $args = null ) {
		global $pagenow;
		// make sure we are on the backend
		if ( ! is_admin() ) {
			return false;
		}
		return in_array( $pagenow, array( 'post-new.php' ) );
		// return in_array( $pagenow, array( 'post.php', 'post-new.php' ) );
	}
}

$w4os3 = new W4OS3();
$w4os3->init();

