<?php if ( ! defined( 'WPINC' ) ) {
	die;}

// ini_set('display_errors', '1');
// ini_set('display_startup_errors', '1');
// error_reporting(E_ERROR | E_WARNING | E_PARSE);

define( 'W4OS_NULL_KEY', '00000000-0000-0000-0000-000000000000' );
// define('W4OS_ZERO_VECTOR', '<0,0,0>');
// define('W4OS_W4OS_DEFAULT_AVATAR_HEIGHT', '1.7');
// define('W4OS_DEFAULT_AVATAR_PARAMS', '33,61,85,23,58,127,63,85,63,42,0,85,63,36,85,95,153,63,34,0,63,109,88,132,63,136,81,85,103,136,127,0,150,150,150,127,0,0,0,0,0,127,0,0,255,127,114,127,99,63,127,140,127,127,0,0,0,191,0,104,0,0,0,0,0,0,0,0,0,145,216,133,0,127,0,127,170,0,0,127,127,109,85,127,127,63,85,42,150,150,150,150,150,150,150,25,150,150,150,0,127,0,0,144,85,127,132,127,85,0,127,127,127,127,127,127,59,127,85,127,127,106,47,79,127,127,204,2,141,66,0,0,127,127,0,0,0,0,127,0,159,0,0,178,127,36,85,131,127,127,127,153,95,0,140,75,27,127,127,0,150,150,198,0,0,63,30,127,165,209,198,127,127,153,204,51,51,255,255,255,204,0,255,150,150,150,150,150,150,150,150,150,150,0,150,150,150,150,150,0,127,127,150,150,150,150,150,150,150,150,0,0,150,51,132,150,150,150');
define( 'W4OS_DEFAULT_AVATAR', 'Default Ruth' );
define( 'W4OS_DEFAULT_HOME', 'Welcome' );
define( 'W4OS_DEFAULT_RESTRICTED_NAMES', array( 'Default', 'Test', 'Admin', str_replace( ' ', '', get_option( 'w4os_grid_name' ) ) ) );
define( 'W4OS_DEFAULT_ASSET_SERVER_URI', '/assets/asset.php?id=' );
define( 'W4OS_DEFAULT_PROVIDE_ASSET_SERVER', true );
define( 'W4OS_ASSETS_DEFAULT_FORMAT', 'jpg' );
define( 'W4OS_NOTFOUND_IMG', '201ce950-aa38-46d8-a8f1-4396e9d6be00' );
define( 'W4OS_NOTFOUND_PROFILEPIC', '201ce950-aa38-46d8-a8f1-4396e9d6be00' );

define( 'W4OS_DIR', wp_normalize_path( dirname( dirname( __FILE__ ) ) ) );

if ( ! defined( 'W4OS_SLUG' ) ) {
	define( 'W4OS_SLUG', basename( W4OS_DIR ) );
}

if ( ! defined( 'W4OS_PLUGIN' ) ) {
	define( 'W4OS_PLUGIN', W4OS_SLUG . '/w4os.php' );
}

$plugin_data = get_file_data(
	WP_PLUGIN_DIR . '/' . W4OS_PLUGIN,
	array(
		'Name'       => 'Plugin Name',
		// 'PluginURI' => 'Plugin URI',
		'Version'    => 'Version',
		// 'Description' => 'Description',
		// 'Author' => 'Author',
		// 'AuthorURI' => 'Author URI',
		'TextDomain' => 'Text Domain',
	// 'DomainPath' => 'Domain Path',
	// 'Network' => 'Network',
	)
);

if ( ! defined( 'W4OS_PLUGIN_NAME' ) ) {
	define( 'W4OS_PLUGIN_NAME', $plugin_data['Name'] );
}
// if ( ! defined( 'W4OS_SHORTNAME' ) ) define('W4OS_SHORTNAME', preg_replace('/ - .*/', '', W4OS_PLUGIN_NAME ) );
// if ( ! defined( 'W4OS_PLUGIN_URI' ) ) define('W4OS_PLUGIN_URI', $plugin_data['PluginURI'] );
if ( file_exists( plugin_dir_path( __DIR__ ) . '.git/refs/heads/master' ) ) {
	$hash = trim( file_get_contents( plugin_dir_path( __DIR__ ) . '.git/refs/heads/master' ) );
}
if ( ! empty( $hash ) ) {
	$plugin_data['Version'] .= ' ' . substr( $hash, 0, 8 ) . '-' . time();
}
if ( ! defined( 'W4OS_VERSION' ) ) {
	define( 'W4OS_VERSION', $plugin_data['Version'] );
}
// if ( ! defined( 'W4OS_AUTHOR_NAME' ) ) define('W4OS_AUTHOR_NAME', $plugin_data['Author'] );
if ( ! defined( 'W4OS_TXDOM' ) ) {
	define( 'W4OS_TXDOM', ( $plugin_data['TextDomain'] ) ? $plugin_data['TextDomain'] : W4OS_SLUG );
}
// if ( ! defined( 'W4OS_DATA_SLUG' ) ) define('W4OS_DATA_SLUG', sanitize_title(W4OS_PLUGIN_NAME) );
// if ( ! defined( 'W4OS_STORE_LINK' ) ) define('W4OS_STORE_LINK', "<a href=" . W4OS_PLUGIN_URI . " target=_blank>" . W4OS_AUTHOR_NAME . "</a>");
// /* translators: %s is replaced by the name of the plugin, untranslated */
// if ( ! defined( 'W4OS_REGISTER_TEXT' ) ) define('W4OS_REGISTER_TEXT', W4OS::sprintf_safe(__('Get a license key on %s website', W4OS_TXDOM), W4OS_STORE_LINK) );

define( 'W4OS_LOGIN_PAGE', get_home_url( null, get_option( 'w4os_profile_slug' ) ) );

require_once dirname( __DIR__ ) . '/vendor/autoload.php';
require_once __DIR__ . '/functions.php';

define( 'W4OS_GRID_LOGIN_URI', w4os_grid_login_uri() );
if ( empty( get_option( 'w4os_assets_slug' ) ) ) {
	update_option( 'w4os_assets_slug', 'assets' );
}
define( 'W4OS_GRID_ASSETS_SERVER', W4OS_GRID_LOGIN_URI . '/assets/' );
if ( get_option( 'w4os_profile_page' ) == 'provide' ) {
	define( 'W4OS_PROFILE_URL', get_home_url( null, get_option( 'w4os_profile_slug' ) ) );
}
define( 'W4OS_GRID_INFO', w4os_get_grid_info() );

require_once dirname( __DIR__ ) . '/templates/templates.php';
require_once __DIR__ . '/w4osdb.php';
require_once __DIR__ . '/shortcodes.php';
// require_once __DIR__ . '/widgets.php';
require_once __DIR__ . '/users.php';
require_once __DIR__ . '/gridauth.php';
if ( w4os_check_db_tables( 'userprofile' ) ) {
	require_once __DIR__ . '/profile.php';
}
require_once __DIR__ . '/cron.php';
if ( function_exists( 'xmlrpc_encode_request' ) ) {
	require_once dirname( __DIR__ ) . '/helpers/wp-load.php';
}
require_once dirname( __DIR__ ) . '/blocks/blocks.php';

if ( W4OS_DB_CONNECTED ) {
	if ( get_option( 'w4os_sync_users' ) ) {
		add_action( 'init', 'w4os_sync_users' );
	}
}
if ( in_array( 'woocommerce/woocommerce.php', apply_filters( 'active_plugins', get_option( 'active_plugins' ) ) ) ) {
	require_once __DIR__ . '/woocommerce.php';
}

add_filter( 'script_loader_tag', 'w4os_add_crossorigin', 10, 2 );
function w4os_add_crossorigin( $tag, $handle ) {
	if ( 'w4os-fa' === $handle ) {
		return str_replace( '>', ' crossorigin="anonymous" >', $tag );
	}
	return $tag;
}

/**
 * Rewrite rules after any version update or if explicitely requested
 */
if ( get_option( 'w4os_flush_rewrite_rules' ) || get_option( 'w4os_rewrite_version' ) != W4OS_VERSION ) {
	wp_cache_flush();
	add_action( 'init', 'flush_rewrite_rules' );
	update_option( 'w4os_flush_rewrite_rules', false );
	update_option( 'w4os_rewrite_version', W4OS_VERSION );
}


add_filter( 'body_class', 'w4os_css_classes_body' );
function w4os_css_classes_body( $classes ) {
	if ( ! is_array( W4OS_GRID_INFO ) ) {
		return $classes;
	}

	$post = get_post();
	if ( ! $post ) {
		return array();
	}
	$helper = array_search( $post->guid, W4OS_GRID_INFO );
	if ( ! empty( $helper ) ) {
		$classes[] = 'w4os-' . $helper;
	}
	return $classes;
}
