<?php if(!is_admin()) die();
define('W4OS_ADMIN', true);

ini_set('display_errors', '1');
ini_set('display_startup_errors', '1');
error_reporting(E_ERROR | E_WARNING | E_PARSE);

function w4os_enqueue_admin_script( $hook ) {
    wp_enqueue_style( 'w4os-admin', plugin_dir_url( __FILE__ ) . 'css/admin.css', array(), W4OS_VERSION );
}
add_action( 'admin_enqueue_scripts', 'w4os_enqueue_admin_script' );

function w4os_register_options_pages() {
	// add_options_page('OpenSimulator settings', 'w4os', 'manage_options', 'w4os', 'w4os_settings_page');
	add_menu_page(
		'OpenSimulator', // page title
		'OpenSimulator', // menu title
		'manage_options', // capability
		'w4os', // slug
		'w4os_status_page', // callable function
		// plugin_dir_path(__FILE__) . 'options.php', // slug
		// null,	// callable function
		plugin_dir_url(__FILE__) . 'images/w4os-logo-24x14.png', // icon url
		2 // position
	);
	add_submenu_page('w4os', __('OpenSimulator Status', "w4os"), __('Status'), 'manage_options', 'w4os', 'w4os_status_page');
	add_submenu_page(
		'w4os', // parent
		__('OpenSimulator Settings', "w4os"), // page title
		__('Settings'), // menu title
		'manage_options', // capability
		'w4os_settings', // menu slug
		'w4os_settings_page' // function
	);
}
add_action('admin_menu', 'w4os_register_options_pages');

function w4os_status_page()
{
	if ( ! current_user_can( 'manage_options' ) ) {
			return;
	}
  global $wpdb, $w4osdb;

	require(plugin_dir_path(__FILE__) . 'status-page.php');
}

function w4os_settings_page()
{
	if ( ! current_user_can( 'manage_options' ) ) {
			return;
	}
?>
	<div class="wrap">
		<h1>OpenSimulator</h1>	<?php screen_icon(); ?>
		<form method="post" action="options.php" autocomplete="off">
			<?php
			settings_fields( 'w4os_settings' );
			do_settings_sections( 'w4os_settings' );
			submit_button();
			 ?>
		</form>
	</div>
<?php
	wp_enqueue_script( 'w4os-admin-settings-form-js', plugins_url( 'js/settings.js', __FILE__ ), array(), W4OS_VERSION );
}

/**
 * Add Avatar name column
 * @param  [type] $columns columns before modification
 * @return [type]          updated columns
 */
function w4os_register_user_columns($columns) {
	$column_name = __('Avatar Name', 'w4os');
	if( get_option('w4os_userlist_replace_name') && array_key_exists( 'name', $columns ) ) {
		$keys = array_keys( $columns );
		$keys[ array_search( 'name', $keys ) ] = 'w4os_avatarname';
		$columns = array_combine( $keys, $columns );
		$columns['w4os_avatarname'] = $column_name;
	} else {
		$new_columns[array_key_first($columns)] = array_shift($columns);
		$new_columns[array_key_first($columns)] = array_shift($columns);
		$new_columns['w4os_avatarname'] = $column_name;
		$columns = array_merge($new_columns, $columns);
	}
	return $columns;
}
add_action('manage_users_columns', 'w4os_register_user_columns');

/**
 * Avatar name column display
 * @param  [type] $value
 * @param  [type] $column_name
 * @param  [type] $user_id
 * @return [type]              updated $value
 */
function w4os_register_user_columns_views($value, $column_name, $user_id) {
	if($column_name == 'w4os_avatarname') {
		// if(empty(get_the_author_meta( 'w4os_uuid', $user_id ))) return "-";
		return get_the_author_meta( 'w4os_avatarname', $user_id );
	}
	return $value;
}
add_action('manage_users_custom_column', 'w4os_register_user_columns_views', 10, 3);

/**
 * Make avatar name column sortable
 */
function w4os_users_sortable_columns( $columns ) {
	$columns['w4os_avatarname'] = 'w4os_avatarname';
	return $columns;
}
add_filter( 'manage_users_sortable_columns', 'w4os_users_sortable_columns');

/**
 * Alter avatarname sortorder to filter out users without avatar
 * @param  [type] $userquery
 */
function w4os_avatar_column_orderby($userquery){
	if('w4os_avatarname'==$userquery->query_vars['orderby']) {//check if church is the column being sorted
		global $wpdb;
		$userquery->query_from .= " LEFT OUTER JOIN $wpdb->usermeta AS alias ON ($wpdb->users.ID = alias.user_id) ";//note use of alias
		$userquery->query_where .= " AND alias.meta_key = 'w4os_avatarname' ";//which meta are we sorting with?
		$userquery->query_orderby = " ORDER BY alias.meta_value ".($userquery->query_vars["order"] == "ASC" ? "asc " : "desc ");//set sort order
	}
}
add_action('pre_user_query', 'w4os_avatar_column_orderby');


function w4os_users_filter_avatars($position)
{
  $options = array(
    'with_avatar' => __('With Avatar', 'w4os'),
    'without_avatar' => __('Without Avatar', 'w4os'),
  );
  foreach($options as $value => $label) {
    $options_html .= sprintf(
      '<option value=%1$s %3$s>%2$s</option>',
      $value,
      $label,
      ( $_GET['filter_avatar_'. $position ] == $value ) ? 'selected' : '',
    );
  }

  $select = sprintf('
    <select name="filter_avatar_%1$s" style="float:none;margin-left:10px;">
      <option value="">%2$s</option>
      %3$s
    </select>',
    $position,
    __( 'Filter users...' ),
    $options_html
  );

  // output <select> and submit button
  echo $select;
  submit_button(__( 'Filter' ), null, $position, false);
}
add_filter('pre_get_users', 'w4os_users_filter_avatars_section');

function w4os_users_filter_avatars_section($query)
{
  global $pagenow;
  if (is_admin() && 'users.php' == $pagenow) {
    if( $_GET['filter_avatar_top'] ) $value = $_GET['filter_avatar_top'];
    else $value = $_GET['filter_avatar_bottom'] ? $_GET['filter_avatar_bottom'] : null;

    if ( !empty($value) )
    {
      switch($value) {
        case 'with_avatar' :
        $compare = 'EXISTS';
        case 'without_avatar':
        $compare = 'NOT EXISTS';

        $meta_query = array(array(
          'key' => 'w4os_uuid',
          'compare' => $compare,
        ));
        break;
      }
      if(isset($meta_query)) $query->set('meta_query', $meta_query);
    }
  }
}
add_action('restrict_manage_users', 'w4os_users_filter_avatars');

/**
 * Now we can launch the actual admin sections
 */
require_once __DIR__ . '/settings.php';
if($pagenow == "index.php") require_once __DIR__ .'/dashboard.php';
