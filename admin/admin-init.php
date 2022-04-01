<?php if(!is_admin()) die();
define('W4OS_ADMIN', true);

// ini_set('display_errors', '1');
// ini_set('display_startup_errors', '1');
// error_reporting(E_ERROR | E_WARNING | E_PARSE);

function w4os_enqueue_admin_script( $hook ) {
    wp_enqueue_style( 'w4os-admin', plugin_dir_url( __FILE__ ) . 'css/admin.css', array(), W4OS_VERSION );
}
add_action( 'admin_enqueue_scripts', 'w4os_enqueue_admin_script' );

function w4os_register_options_pages() {
	// add_options_page('OpenSimulator settings', 'w4os', 'manage_options', 'w4os', 'w4os_settings_page');
	// add_menu_page(
	// 	'OpenSimulator', // page title
	// 	'OpenSimulator', // menu title
	// 	'manage_options', // capability
	// 	'w4os', // slug
	// 	'w4os_status_page', // callable function
	// 	// plugin_dir_path(__FILE__) . 'options.php', // slug
	// 	// null,	// callable function
	// 	plugin_dir_url(__FILE__) . 'images/opensimulator-logo-24x14.png', // icon url
	// 	2 // position
	// );
	// add_submenu_page('w4os', __('OpenSimulator Status', "w4os"), __('Status'), 'manage_options', 'w4os', 'w4os_status_page');
	add_submenu_page(
		'w4os', // parent
		__('OpenSimulator Settings', "w4os"), // page title
		__('Settings'), // menu title
		'manage_options', // capability
		'w4os_settings', // menu slug
		'w4os_settings_page' // function
	);
  if(function_exists('xmlrpc_encode_request')) {
    add_submenu_page(
      'w4os', // parent
      __('OpenSimulator Helpers', "w4os"), // page title
      __('Helpers'), // menu title
      'manage_options', // capability
      'w4os_helpers', // menu slug
      'w4os_helpers_page' // function
    );
  }
}
add_action('admin_menu', 'w4os_register_options_pages', 999);

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
		<h1>OpenSimulator</h1>
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

function w4os_helpers_page()
{
	if ( ! current_user_can( 'manage_options' ) ) {
			return;
	}
?>
	<div class="wrap">
		<h1><?php _e("OpenSimulator Helpers", 'w4os'); ?></h1>
		<form method="post" action="options.php" autocomplete="off">
			<?php
			settings_fields( 'w4os_helpers' );
			do_settings_sections( 'w4os_helpers' );
			submit_button();
			 ?>
		</form>
	</div>
<?php
	wp_enqueue_script( 'w4os-admin-helpers-form-js', plugins_url( 'js/settings.js', __FILE__ ), array(), W4OS_VERSION );
}

/**
 * Add Avatar name column
 * @param  [type] $columns columns before modification
 * @return [type]          updated columns
 */
function w4os_register_user_columns($columns) {
  $insert_columns = array();
	$column_name = __('Avatar Name', 'w4os');
	if( get_option('w4os_userlist_replace_name') && array_key_exists( 'name', $columns ) ) {
		$keys = array_keys( $columns );
		$keys[ array_search( 'name', $keys ) ] = 'w4os_avatarname';
    $columns = array_combine( $keys, $columns );
		$columns['w4os_avatarname'] = $column_name;
    $columns['w4os_created'] = __('Born', 'w4os');
    $columns['w4os_lastseen'] = __('Last Seen', 'w4os');
	} else {
		$insert_columns[array_key_first($columns)] = array_shift($columns);
		$insert_columns[array_key_first($columns)] = array_shift($columns);
		$insert_columns['w4os_avatarname'] = $column_name;
    $insert_columns['w4os_created'] = __('Born', 'w4os');
    $insert_columns['w4os_lastseen'] = __('Last Seen', 'w4os');
	}
  $columns = array_merge($insert_columns, $columns);

	return $columns;
}
add_action('manage_users_columns', 'w4os_register_user_columns');

function w4os_user_actions_profile_view($actions, $user) {
  if(get_option('w4os_profile_page') != 'provide') return $actions;
  if(w4os_empty(get_the_author_meta('w4os_uuid', $user->ID))) return $actions;

	$actions['view'] = sprintf('
    <a class=view href="%s">%s</a>',
    w4os_web_profile_url($user),
    __( 'View profile', 'w4OS' ) . "</a>"
  );
	return $actions;
}
add_filter('user_row_actions', 'w4os_user_actions_profile_view', 10, 2);

/**
 * Avatar name column display
 * @param  [type] $value
 * @param  [type] $column_name
 * @param  [type] $user_id
 * @return [type]              updated $value
 */
function w4os_register_user_columns_views($value, $column_name, $user_id) {
  switch ($column_name) {
    case 'w4os_avatarname': return get_the_author_meta( 'w4os_avatarname', $user_id );
    case 'w4os_created': return w4os_age(get_the_author_meta( 'w4os_created', $user_id ));
    case 'w4os_lastseen': return w4os_date('', get_the_author_meta( 'w4os_lastseen', $user_id ) );
  }
	return $value;
}
add_action('manage_users_custom_column', 'w4os_register_user_columns_views', 10, 3);

function w4os_date( $format, $timestamp = null, $timezone = null ) {
  if(empty($timestamp)) return;
  if(empty($format)) $format = get_option( 'date_format');
  return wp_date($format, $timestamp, $timezone );
}
/**
 * Make avatar name column sortable
 */
function w4os_users_sortable_columns( $columns ) {
  $columns['w4os_avatarname'] = 'w4os_avatarname';
  $columns['w4os_created'] = 'w4os_avatarname';
  $columns['w4os_lastseen'] = 'w4os_lastseen';
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
      '<option value="%1$s" %3$s>%2$s</option>',
      esc_attr($value),
      esc_attr($label),
      esc_html(( $_GET['filter_avatar_'. $position ] == $value ) ? 'selected' : ''),
    );
  }

  $select = sprintf('
    <select name="filter_avatar_%1$s" style="float:none;margin-left:10px;">
      <option value="">%2$s</option>
      %3$s
    </select>',
    esc_attr($position),
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

/**
 * Add post state to special pages in pages list
 * @var [type]
 */
add_filter('display_post_states', 'w4os_custom_post_states',10,2);
function w4os_custom_post_states( $states, $post ) {
  // echo "post<pre>"; print_r($post); echo "</pre>die"; die;
  //
  if ( get_option('w4os_profile_slug') == $post->post_name)
  $states[] = __('OpenSimulator Profile', 'w4os');
  if (!empty(W4OS_GRID_INFO['welcome'])  && $post->ID === url_to_postid(W4OS_GRID_INFO['welcome']))
  $states[] = __('OpenSimulator Splash', 'w4os');
  if (!empty(W4OS_GRID_INFO['search'])   && $post->ID === url_to_postid(W4OS_GRID_INFO['search']))
  $states[] = __('OpenSimulator Search', 'w4os');
  if (!empty(W4OS_GRID_INFO['economy'])  && $post->ID === url_to_postid(W4OS_GRID_INFO['economy']))
  $states[] = __('OpenSimulator Economy', 'w4os');
  if (!empty(W4OS_GRID_INFO['about'])    && $post->ID === url_to_postid(W4OS_GRID_INFO['about']))
  $states[] = __('OpenSimulator About', 'w4os');
  if (!empty(W4OS_GRID_INFO['register']) && $post->ID === url_to_postid(W4OS_GRID_INFO['register']))
  $states[] = __('OpenSimulator Register', 'w4os');
  if (!empty(W4OS_GRID_INFO['password']) && $post->ID === url_to_postid(W4OS_GRID_INFO['password']))
  $states[] = __('OpenSimulator Password', 'w4os');
  if (!empty(W4OS_GRID_INFO['message'])  && $post->ID === url_to_postid(W4OS_GRID_INFO['message']))
  $states[] = __('OpenSimulator Offline Messages', 'w4os');

  return $states;
}

function w4os_process_actions($args = array()) {
  if(empty($_REQUEST['action'])) return;

  if($_REQUEST['action'] == 'w4os_check_urls_now') {
  	if (check_admin_referer('w4os_check_urls_now')) {
  		w4os_get_urls_statuses(w4os_get_grid_info(), true);
    } else {
      w4os_transient_admin_notice(__('The followed link has expired, please try again', 'w4os'));
  	}
  	wp_redirect(admin_url( "admin.php?page=".$_GET["page"] ));
  	exit;
  }

  // w4os_transient_admin_notice(__FUNCTION__ . '<pre>' . print_r($_REQUEST, true) . '</pre>');
  if($_REQUEST['action'] == 'create_page' && isset(W4OS_PAGES[$_REQUEST['helper']])) {
    $action = sanitize_title($_REQUEST['action']);
    $slug = sanitize_title($_REQUEST['slug']);
    $helper = sanitize_title($_REQUEST['helper']);
    $guid = sanitize_title($_REQUEST['guid']);

    if (!check_admin_referer( $action . '_'. $helper)) {
      w4os_transient_admin_notice(__('The followed link has expired, please try again', 'w4os'));
      wp_redirect(admin_url( "admin.php?page=".$_GET["page"] ));
      exit;
    }

    $page = get_page_by_path($slug);
    if(!is_wp_error($page) &! empty($page)) {
      w4os_transient_admin_notice(sprintf(__('Page %s already exists.', 'w4os'), W4OS_PAGES[$helper]['name']), 'error');
    } else {
      $data = W4OS_PAGES[$helper];
      // (empty($_REQUEST['guid'])) ? site_url() . "/$slug" : $_REQUEST['guid'];
      $page_id = wp_insert_post(array(
        'post_name' => $slug,
        'post_title' => $data['name'],
        'post_type' => 'page',
        'post_status' => 'publish',
        'ping_status' => 'closed',
        'ping_status' => false,
        'post_content' => (!empty($data['content'])) ? $data['content'] : $data['description'],
        'guid' => $guid,
      ));
      if(!is_wp_error($page_id)) {
        w4os_get_url_status($guid, true, true);
        w4os_transient_admin_notice(
          sprintf(__('New page %s created.', 'w4os'), '<a href=' . get_permalink($page_id) . '>' . $data['name'] . '</a>'),
          'success',
        );
      } else {
        w4os_transient_admin_notice(sprintf(__('Error while creating page %s.', 'w4os'), W4OS_PAGES[$helper]['name']), 'error');
      }
    }
    wp_redirect(admin_url( "admin.php?page=".$_GET["page"] ));
    exit;
  }
}
add_action('admin_init', 'w4os_process_actions');


add_action('init', function() {
  define('W4OS_PAGES', array(
    'profile' => array(
      'name' => __('Avatar profile', 'w4os'),
      'description' => __('The base URL for avatar web profiles.', 'w4os'),
    ),
    'SearchURL' => array(
      'name' => __('Search Service', 'w4os'),
      'description' => __('Search service used by the viewer. Search can be provided by the simulator core (limited), or by an external service for additional functionalities (like events). Requires OpenSimSearch.Modules.dll.', 'w4os'),
      'third_party_url' => (get_option('w4os_provide_search')) ? '' : 'https://github.com/GuduleLapointe/flexible_helper_scripts',
      'os_config' => [
        'OpenSim.ini' => [
          '[Search]' => [ 'SearchURL = %1$s' ],
          // '[GridInfoService]' => [ 'search = %1$s' ],
        ],
      ],
    ),
    'search' => array(
      'name' => __('Web Search', 'w4os'),
      'description' => __('Web tab of viewer search windows. Relevant if you have a search page providing content from the grid.', 'w4os'),
      'third_party_url' => (get_option('w4os_provide_search')) ? '' : 'https://github.com/GuduleLapointe/flexible_helper_scripts',
      'os_config' => [
        'Robust.HG.ini' => [
          '[LoginService]' => [ 'SearchURL = %1$s' ],
          '[GridInfoService]' => [ 'search = %1$s' ],
        ],
      ],
    ),
    'message' => array(
      'name' => __('Offline messages', 'w4os'),
      'description' => __('Needed by viewers to keep messages while user is offline and deliver them when they come back online. Internal service, not accessed directly by the user.', 'w4os'),
      'os_config' => [ 'Robust.HG.ini' => [ '[GridInfoService]' => [ 'message = %1$s' ]], 'OpenSim.ini' => [ '[Messaging]' => [ 'OfflineMessageURL = %1$s' ]]],
      'third_party_url' => (get_option('w4os_provide_offline')) ? '' : 'https://github.com/GuduleLapointe/flexible_helper_scripts',
    ),
    'welcome' => array(
      'name' => __('Splash', 'w4os'),
      'description' => __("The welcome page displayed in the viewer with the login form. A short, no-scroll page, with only essential info. It is required, or at least highly recommended.", 'w4os'),
      'os_config' => [ 'Robust.HG.ini' => [ '[GridInfoService]' => [ 'welcome = %s' ]]],
      'content' => '<!-- wp:columns {"verticalAlignment":null,"align":"full","className":"is-style-default"} -->
      <div class="wp-block-columns alignfull is-style-default"><!-- wp:column {"verticalAlignment":"bottom","width":"25%"} -->
      <div class="wp-block-column is-vertically-aligned-bottom" style="flex-basis:25%"><!-- wp:site-logo {"align":"center"} /-->

      <!-- wp:w4os/w4os-gridinfo-block -->
      <div class="wp-block-w4os-w4os-gridinfo-block">Grid info</div>
      <!-- /wp:w4os/w4os-gridinfo-block -->

      <!-- wp:w4os/w4os-gridstatus-block -->
      <div class="wp-block-w4os-w4os-gridstatus-block">Grid status</div>
      <!-- /wp:w4os/w4os-gridstatus-block --></div>
      <!-- /wp:column -->

      <!-- wp:column {"verticalAlignment":"top","width":"50%"} -->
      <div class="wp-block-column is-vertically-aligned-top" style="flex-basis:50%"></div>
      <!-- /wp:column -->

      <!-- wp:column {"verticalAlignment":"top","width":"25%"} -->
      <div class="wp-block-column is-vertically-aligned-top" style="flex-basis:25%"><!-- wp:latest-posts {"postsToShow":2,"displayPostContent":true,"excerptLength":20,"displayPostDate":true,"className":"is-style-twentytwentyone-latest-posts-borders"} /--></div>
      <!-- /wp:column --></div>
      <!-- /wp:columns -->',
    ),
    'register' => array(
      'name' => __('Registration page', 'w4os'),
      'description' => __('Link to the user registration.', 'w4os'),
      'recommended' => wp_registration_url(),
      'os_config' => [ 'Robust.HG.ini' => [ '[GridInfoService]' => [ 'register = %s' ]]],
    ),
    'password' => array(
      'name' => __('Password revovery', 'w4os'),
      'description' => __('Link to lost password page.', 'w4os'),
      'recommended' =>  wp_lostpassword_url(),
      'os_config' => [ 'Robust.HG.ini' => [ '[GridInfoService]' => [ 'password = %s' ]]],
    ),
    'economy' => array(
      'name' => __('Economy', 'w4os'),
      'description' => __('Currencies and some other services queried by the viewer. They are not accessed directly by the user.', 'w4os'),
      'external' => true,
      'os_config' => [ 'Robust.HG.ini' => [ '[GridInfoService]' => [ 'economy = %s' ]]],
      'third_party_url' => (get_option('w4os_provide_currency')) ? '' : 'https://github.com/GuduleLapointe/flexible_helper_scripts',
    ),
    'about' => array(
      'name' => __('About this grid', 'w4os'),
      'description' => __('Detailed info page on your website, via a link displayed on the viewer login page.', 'w4os'),
      'os_config' => [ 'Robust.HG.ini' => [ '[GridInfoService]' => [ 'about = %s' ]]],
    ),
    'help' => array(
      'name' => __('Help', 'w4os'),
      'description' => __('Link to a help page on your website.', 'w4os'),
      'os_config' => [ 'Robust.HG.ini' => [ '[GridInfoService]' => [ 'help = %s' ]]],
    ),
  ));
});

function w4os_dashboard_users_html() {
  if(!w4os_db_connected()) {
    echo __('Database not connected', 'w4os');
    return;
  }
	$count = w4os_count_users();
  ?>
<table class="w4os-table user-sync">
  <thead>
    <tr>
      <th></th>
      <th><?php _e('Total', 'w4os');?></th>
      <th><?php if($count['wp_only'] > 0) _e('WP only', 'w4os');?></th>
      <th><?php _e('Sync', 'w4os');?></th>
      <th><?php if($count['grid_only'] > 0) _e('Grid only', 'w4os');?></th>
    </tr>
  </thead>
  <tr>
    <th><?php _e("Grid accounts", 'w4os') ?></th>
    <td><?php echo $count['grid_accounts']; ?></td>
    <td></td>
    <td class=success rowspan=2><?php echo $count['sync']; ?></td>
    <td class=error><?php echo $count['grid_only']; ?></td>
  </tr>
  <tr>
    <th><?php _e("Linked WordPress accounts", 'w4os') ?></th>
    <td><?php echo $count['wp_linked']; ?></td>
    <td class=error><?php echo $count['wp_only']; ?></td>
  </tr>
  <tr>
    <th><?php _e("Avatar models", 'w4os') ?></th>
    <td><?php
    echo $count['models'];
    ?></td>
  </tr>
  <?php if($count['tech'] > 0) { ?>
    <tr>
      <th><?php _e("Other service accounts", 'w4os') ?></th>
      <td><?php
      echo $count['tech'];
      ?></td>
    </tr>
  <?php } ?>
</table>
<?php	if($count['wp_only'] + $count['grid_only'] > 0 |! empty($sync_result)) { ?>
  <table class="w4os-table .notes">
    <tr class=notes>
      <th></th>
      <td>
      <?php
      if($count['grid_only']  > 0 ) {
        echo '<p>' . sprintf(_n(
          '%d grid account has no linked WP account. Syncing will create a new WP account.',
          '%d grid accounts have no linked WP account. Syncing will create new WP accounts.',
          $count['grid_only'],
          'w4os'
        ), $count['grid_only']) . '</p>';
      }
      if($count['wp_only']  > 0 ) {
        echo '<p>' . sprintf(_n(
          '%d WordPress account is linked to an unexisting avatar (wrong UUID). Syncing accounts will keep this WP account but remove the broken reference.',
          '%d WordPress accounts are linked to unexisting avatars (wrong UUID). Syncing accounts will keep these WP accounts but remove the broken reference.',
          $count['wp_only'],
          'w4os'
        ), $count['wp_only']) . '</p>';
      }
      if($count['tech'] > 0) {
        echo '<p>' . sprintf(_n(
          "%d grid account (other than models) has no email address, which is fine as long as it is used only for maintenance or service tasks.",
          "%d grid accounts (other than models) have no email address, which is fine as long as they are used only for maintenance or service tasks.",
          $count['tech'],
          'w4os'
          ) . ' ' . __('Real accounts need a unique email address for W4OS to function properly.', 'w4os'
        ), $count['tech']) . '</p>';
      }
      if($count['grid_only'] + $count['wp_only'] > 0) {
        echo '<form method="post" action="options.php" autocomplete="off">';
        settings_fields( 'w4os_status' );
        echo '<input type="hidden" input-hidden" id="w4os_sync_users" name="w4os_sync_users" value="1">';

        submit_button(__('Synchronize users now', 'w4os'));
        echo '</form>';
        echo '<p class=description>' . __('Synchronization is made at plugin activation and is handled automatically afterwards, but in certain circumstances it may be necessary to initiate it manually to get an immediate result, especially if users have been added or deleted directly from the grid administration console.', 'w4os') . '<p>';
      }
      if($sync_result)
      echo '<p class=info>' . $sync_result . '<p>';
        ?>
      </td>
    </tr>
  </table>
<?php	}
}

function w4os_dependencies_html($foo = NULL, $args = NULL) {
  if(is_array($args)) {
    $success = true;
    if(isset($args['function_exists'])) {
      if(!function_exists($args['function_exists'])) $success = false;
    }
    if(isset($args['extension_loaded'])) {
      if(!extension_loaded($args['extension_loaded'])) {
        $success = false;
      }
    }
    if($success) return w4os_check_mark(true);
    return w4os_check_mark(false);
  }

  /**
   * Kept temporarily untill the transition to metabox options page is fully
   * completed
   */
  if ( ! function_exists('curl_init') ) $php_missing_modules[]='curl';
  if ( ! function_exists('simplexml_load_string') ) $php_missing_modules[]='xml';
  if (!extension_loaded('imagick')) $php_missing_modules[]='imagick';
  if(!empty($php_missing_modules)) {
    echo sprintf(
      '<div class="missing-modules warning"><h2>%s</h2>%s</div>',
      __('Missing PHP modules', 'w4os'),
      sprintf(
        __('These modules were not found: %s. Install them to get the most of this plugin.', 'w4os'),
        '<strong>' . join('</strong>, <strong>', $php_missing_modules) . '</strong>',
      ),
    );
  }
}

function w4os_dashboard_pages_html() {
  if(!w4os_db_connected()) {
    echo __('Database not connected', 'w4os');
    return;
  }
  $grid_info = W4OS_GRID_INFO;
  $grid_info['profile'] = W4OS_LOGIN_PAGE;

  $html = '';
  $grid_running = w4os_grid_running();
  if(!w4os_grid_running()) {
    $other_attributes['disabled'] = true;
    $html .= '<p class="notice warning">' . sprintf(
      __('Grid not running, start Robust server or %scheck OpenSimulator settings%s.', 'w4os'),
      '<a href="' . get_admin_url('', 'admin.php?page=w4os_settings') . '">',
      '</a>',
    );
    return $html;
  }

  $required = W4OS_PAGES;
  $html .= '<table class="w4os-table requested-pages">';
  $html .= '<tr>';
  $html .= '<td>'. w4os_status_icon($grid_running) . '</td>';
  $html .= '<td>';
  $html .= sprintf(
    '<a class=button href="%s">%s</a>',
    admin_url('admin.php?' . wp_nonce_url(
      http_build_query(array(
        'page'=>'w4os',
        'action' => 'w4os_check_urls_now',
      )),
      'w4os_check_urls_now',
    )),
    __('Check pages now', 'w4os'),
  );
  $html .= '</td>';
  $html .= '<td>';
  $html .= '<p class=description>' . sprintf(__('Last checked %s ago.', 'w4os'), human_time_diff(get_transient('w4os_get_url_status_checked') )) . '</p>';
  $html .= '<p class=description>' . __('OpenSimulator pages are checked regularly in the background. Synchronize now only if you made changes and want an immediate status.', 'w4os') . '<p>';
  $html .= '</td></tr>';

  if($grid_running)
  foreach($required as $key => $data) {
    $url = (!empty($grid_info[$key])) ? $grid_info[$key] : '';
    // if (empty($grid_info[$key]) ) $url = "''";
    // else $url = sprintf('<a href="%1$s" target=_blank>%1$s</a>', $grid_info[$key]);
    $success = w4os_get_url_status($url, 'boolean');
    $status_icon = w4os_get_url_status($url, 'icon');
    $html .= sprintf(
      '<tr>
      <td>%2$s</td>
      <td><h3>%1$s</h3>%3$s%4$s%5$s%7$s</td>
      <td>%6$s</td>
      </tr>
      ',
      $data['name'],
      $status_icon,
      (!empty($url)) ? sprintf('<p class=url><a href="%1$s">%1$s</a></p>', $url) : '',
      (!empty($data['description'])) ? '<p class=description>' . $data['description'] . '</p>' : '',
      (!empty($data['recommended']) && $url != $data['recommended']) ? '<p class=warning><span class="w4os-status dashicons dashicons-warning"></span> ' . sprintf(__('Should be %s', 'w4os'), $data['recommended']) . '</p>' : '',
      (!empty($data['os_config']))
      ? sprintf(w4os_format_ini($data['os_config']),(!empty($data['recommended'])) ? $data['recommended'] : $url)
      : '',
      ( $success == false && (!empty($data['third_party_url']))
      ? '<p class=third_party>' .
      sprintf(__('This service requires a separate web application.<br>Try <a href="%1$s" target=_blank>%1$s</a>.', '<w4os>'),
      $data['third_party_url'],
      ) . '</p>'
      : ( ( $success == false && (!empty($url)) )
        ? '<a class=button href="' . admin_url(sprintf('admin.php?%s', wp_nonce_url(http_build_query(array('page'=>'w4os', 'action' => 'create_page', 'helper' => $key, 'guid' => $url, 'slug' => basename(strtok($url, '?')) )), 'create_page_'.$key))) . '">'
          . sprintf(
            'Create %s page',
            $data['name'],
          ) . '</a>'
        : ''
        )
      ),
    );
  }
  $html .= '</table>';
  return $html;
}
