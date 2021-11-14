<?php if ( ! defined( 'WPINC' ) ) die;

define('W4OS_NULL_KEY', '00000000-0000-0000-0000-000000000000');
// define('W4OS_ZERO_VECTOR', '<0,0,0>');
// define('W4OS_W4OS_DEFAULT_AVATAR_HEIGHT', '1.7');
// define('W4OS_DEFAULT_AVATAR_PARAMS', '33,61,85,23,58,127,63,85,63,42,0,85,63,36,85,95,153,63,34,0,63,109,88,132,63,136,81,85,103,136,127,0,150,150,150,127,0,0,0,0,0,127,0,0,255,127,114,127,99,63,127,140,127,127,0,0,0,191,0,104,0,0,0,0,0,0,0,0,0,145,216,133,0,127,0,127,170,0,0,127,127,109,85,127,127,63,85,42,150,150,150,150,150,150,150,25,150,150,150,0,127,0,0,144,85,127,132,127,85,0,127,127,127,127,127,127,59,127,85,127,127,106,47,79,127,127,204,2,141,66,0,0,127,127,0,0,0,0,127,0,159,0,0,178,127,36,85,131,127,127,127,153,95,0,140,75,27,127,127,0,150,150,198,0,0,63,30,127,165,209,198,127,127,153,204,51,51,255,255,255,204,0,255,150,150,150,150,150,150,150,150,150,150,0,150,150,150,150,150,0,127,127,150,150,150,150,150,150,150,150,0,0,150,51,132,150,150,150');
define('W4OS_DEFAULT_AVATAR', "Default Ruth");
define('W4OS_DEFAULT_HOME', "Welcome");
define('W4OS_DEFAULT_RESTRICTED_NAMES', array("Default", "Test", "Admin", str_replace(' ', '', get_option('w4os_grid_name'))));
define('W4OS_DEFAULT_ASSET_SERVER_URI', '/assets/asset.php?id=');
define('W4OS_DEFAULT_PROVIDE_ASSET_SERVER', true);

if ( ! defined( 'W4OS_SLUG' ) ) define('W4OS_SLUG', basename(dirname(dirname(__FILE__))) );
if ( ! defined( 'W4OS_PLUGIN' ) ) define('W4OS_PLUGIN', W4OS_SLUG . "/w4os.php" );

$plugin_data = get_file_data(WP_PLUGIN_DIR . "/" . W4OS_PLUGIN, array(
  'Name' => 'Plugin Name',
  // 'PluginURI' => 'Plugin URI',
  'Version' => 'Version',
  // 'Description' => 'Description',
  // 'Author' => 'Author',
  // 'AuthorURI' => 'Author URI',
  'TextDomain' => 'Text Domain',
  // 'DomainPath' => 'Domain Path',
  // 'Network' => 'Network',
));

if ( ! defined( 'W4OS_PLUGIN_NAME' ) ) define('W4OS_PLUGIN_NAME', $plugin_data['Name'] );
// if ( ! defined( 'W4OS_SHORTNAME' ) ) define('W4OS_SHORTNAME', preg_replace('/ - .*/', '', W4OS_PLUGIN_NAME ) );
// if ( ! defined( 'W4OS_PLUGIN_URI' ) ) define('W4OS_PLUGIN_URI', $plugin_data['PluginURI'] );
if(file_exists(plugin_dir_path( __DIR__ ) . '.git/refs/heads/master')) $hash = trim(file_get_contents(plugin_dir_path( __DIR__ ) . '.git/refs/heads/master'));
if(!empty($hash)) $plugin_data['Version'] .= '-dev #' . substr($hash, 0, 8);
if ( ! defined( 'W4OS_VERSION' ) ) define('W4OS_VERSION', $plugin_data['Version'] );
// if ( ! defined( 'W4OS_AUTHOR_NAME' ) ) define('W4OS_AUTHOR_NAME', $plugin_data['Author'] );
if ( ! defined( 'W4OS_TXDOM' ) ) define('W4OS_TXDOM', ($plugin_data['TextDomain']) ? $plugin_data['TextDomain'] : W4OS_SLUG );
// if ( ! defined( 'W4OS_DATA_SLUG' ) ) define('W4OS_DATA_SLUG', sanitize_title(W4OS_PLUGIN_NAME) );
// if ( ! defined( 'W4OS_STORE_LINK' ) ) define('W4OS_STORE_LINK', "<a href=" . W4OS_PLUGIN_URI . " target=_blank>" . W4OS_AUTHOR_NAME . "</a>");
// /* translators: %s is replaced by the name of the plugin, untranslated */
// if ( ! defined( 'W4OS_REGISTER_TEXT' ) ) define('W4OS_REGISTER_TEXT', sprintf(__('Get a license key on %s website', W4OS_TXDOM), W4OS_STORE_LINK) );

if(empty(get_option('w4os_assets_slug'))) update_option('w4os_assets_slug', 'assets');
define('W4OS_ASSETS_SERVER', 'http://' . esc_attr(get_option('w4os_login_uri')) . '/assets/'); // (OpenSim.ini: asset_server_url . "/assets/")

define('W4OS_WEB_ASSETS_SERVER_URI',
  (get_option('w4os_provide_asset_server') ==  1)
  ? get_home_url(NULL, '/' . get_option('w4os_assets_slug') . '/')
  : esc_attr(get_option('w4os_asset_server_uri'))
);
if(get_option('w4os_provide_asset_server') ==  1)	update_option('w4os_internal_asset_server_uri', W4OS_WEB_ASSETS_SERVER_URI);

function w4os_load_textdomain() {
	// load_plugin_textdomain( W4OS_TXDOM, false, W4OS_SLUG . '/languages/' );

	global $locale;
	if ( is_textdomain_loaded( W4OS_TXDOM ) ) {
		unload_textdomain( W4OS_TXDOM );
	}
	$mofile = sprintf( '%s-%s.mo', W4OS_TXDOM, $locale );

  $domain_path = path_join( WP_PLUGIN_DIR, W4OS_SLUG."/languages" );
  $loaded = load_textdomain( W4OS_TXDOM, path_join( $domain_path, $mofile ) );
}
add_action( 'init', 'w4os_load_textdomain' );

require_once __DIR__ . '/functions.php';
require_once __DIR__ . '/shortcodes.php';
require_once __DIR__ . '/widgets.php';
require_once __DIR__ . '/woocommerce-fix.php';
if(get_option('w4os_provide_asset_server') == 1 ) require_once __DIR__ . '/assets.php';
// Rapporte les erreurs d'exécution de script


if(get_option('w4os_db_user') && get_option('w4os_db_pass') && get_option('w4os_db_database') && get_option('w4os_db_host')) {
  $w4osdb = new WPDB(
    get_option('w4os_db_user'),
    get_option('w4os_db_pass'),
    get_option('w4os_db_database'),
    get_option('w4os_db_host')
  );
} else {
  echo "<br>w4os_db_user: " . get_option('w4os_db_user');
  echo "<br>w4os_db_pass: " .  get_option('w4os_db_pass');
  echo "<br>w4os_db_database: " .  get_option('w4os_db_database');
  echo "<br>w4os_db_host: " .  get_option('w4os_db_host');

  // echo "<p>i got no options"; die;
  w4os_admin_notice(
    w4os_give_settings_url( __('ROBUST database is not configured. To finish configuration, go to ', 'w4os') )
  );
}

function w4os_check_db_tables() {
	if(defined('W4OS_DB_CONNECTED')) return true;
	global $w4osdb;
  if(empty($w4osdb)) return false; // Might happen when using wp-cli

  if(! empty($w4osdb) &! $w4osdb->check_connection(false)) {
    w4os_admin_notice(
      w4os_give_settings_url( __('Could not connect to the database server, please verify your credentials on ', 'w4os') ),
      'error',
    );
    return false;
  }
  if(!$w4osdb->get_var("SHOW DATABASES LIKE '" . get_option('w4os_db_database') . "'")) {
    w4os_admin_notice(
      w4os_give_settings_url( __('Could not connect to the ROBUST database, please verify database name and/or credentials on ', 'w4os') ),
      'error',
    );
    return false;
  }

	if(!is_object($w4osdb)) return false;
	$required_tables = array(
		// 'AgentPrefs',
		// 'assets',
		// 'auth',
		'Avatars',
		// 'Friends',
		'GridUser',
		'inventoryfolders',
		'inventoryitems',
		// 'migrations',
		// 'MuteList',
		'Presence',
		'regions',
		// 'tokens',
		'UserAccounts',
	);
  $missing_tables=array();
	foreach($required_tables as $table_name) {
		unset($actual_name);
		$lower_name = strtolower($table_name);
		if($w4osdb->get_var("SHOW TABLES LIKE '$table_name'") == $table_name) $actual_name = $table_name;
		else if($w4osdb->get_var("SHOW TABLES LIKE '$lower_name'") == $lower_name) $actual_name = $lower_name;
		if(isset($actual_name)) {
			if (!defined($table_name)) define($table_name, $actual_name);
			continue;
		}
    $missing_tables[] = $table_name;
	}
  if(count($missing_tables) > 0) {
    w4os_admin_notice(
      w4os_give_settings_url(
        sprintf(
          __("Missing tables: %s. The ROBUST database is connected, but it does not seem valid. ", 'w4os'),
          ' <strong><em>' . join(', ', $missing_tables) . '</em></strong>',
        ),
      ),
      'error',
    );
    return false;
  }
	return true;
}
if (!defined('W4OS_DB_CONNECTED')) define('W4OS_DB_CONNECTED', w4os_check_db_tables());

add_action( 'wp_enqueue_scripts', function() {
  wp_enqueue_style( 'w4os-main', plugin_dir_url( dirname(__FILE__) ) . 'css/w4os-min.css', array(), W4OS_VERSION );
  // wp_enqueue_style( 'w4os-main', plugin_dir_url( dirname(__FILE__) ) . 'css/w4os.css', array(), W4OS_VERSION . time() );
} );

if(W4OS_DB_CONNECTED) {
  // if($pagenow == "profile.php" || $pagenow == "user-edit.php")
  require_once __DIR__ . '/profile.php';
}
require_once __DIR__ . '/updates.php';

/**
 * Rewrite rules after any version update or if explicitely requested
 */
if(get_option('w4os_rewrite_rules') || get_option('w4os_rewrite_version') != W4OS_VERSION) {
  wp_cache_flush();
  add_action('init', 'flush_rewrite_rules');
	update_option('w4os_rewrite_rules', false);
  update_option('w4os_rewrite_version', W4OS_VERSION);
}
