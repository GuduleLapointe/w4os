<?php if ( ! defined( 'W4OS_PLUGIN' ) ) die;

define('W4OS_PROFILE_PATTERN', '^' . esc_attr(get_option('w4os_profile_slug', 'profile')) . '/([a-zA-Z][a-zA-Z9]*)[ \.+-]([a-zA-Z][a-zA-Z9]*)(/.*)?$');
define('W4OS_PROFILE_SELF_PATTERN', '^' . esc_attr(get_option('w4os_profile_slug', 'profile')) . '/?$');

add_action( 'init',  function() {
  add_rewrite_rule( W4OS_PROFILE_PATTERN,
  'index.php?pagename=' . esc_attr(get_option('w4os_profile_slug', 'profile')) . '&post_tyoe=user&profile_firstname=$matches[1]&profile_lastname=$matches[2]&profile_args=$matches[3]', 'top' );
  add_rewrite_rule( W4OS_PROFILE_SELF_PATTERN,
  'index.php?pagename=' . esc_attr(get_option('w4os_profile_slug', 'profile')) . '&post_tyoe=user&profile_args=$matches[1]', 'top' );
} );
update_option('w4os_rewrite_rules', true);
flush_rewrite_rules();

add_filter( 'query_vars', 'w4os_profile_query_vars');
function w4os_profile_query_vars( $query_vars ) {
  $query_vars[] = 'profile_firstname';
  $query_vars[] = 'profile_lastname';
  $query_vars[] = 'profile_args';
  return $query_vars;
}

function w4os_get_avatar_by_name($firstname = '', $lastname = '') {
  $user_query = new WP_User_Query(
    array(
      'meta_query' => array(
        'relation' => 'AND',
        array(
          'key' => 'w4os_firstname',
          'value' => $firstname,
          'compare' => '='
        ),

        array(
          'key' => 'w4os_lastname',
          'value' => $lastname,
          'compare' => '='
        )
      )
    ),
  );
  $users = $user_query->get_results();
  if(!empty($users)) return $users[0];
	return false;
}

add_action( 'login_form_middle', function() {
  $links[] = sprintf(
    '<a href="%1$s" alt="%2$s">%2$s</a>',
    esc_url( wp_lostpassword_url(), 'w4os' ),
    esc_attr__( 'Lost Password', 'textdomain', 'w4os' ),
  );
  if(get_option('users_can_register') || get_option('avatars_can_register') ) $links[] = sprintf(
    '<a href="%1$s" alt="%2$s">%2$s</a>',
    esc_url( wp_registration_url(), 'w4os' ),
    esc_attr__( 'Register', 'textdomain', 'w4os' ),
  );
  if(is_array($links)) return '<p id=nav>' . join(' | ', $links) . '</p>';
});

function w4os_login_form($args = array()) {
  $login  = (isset($_GET['login']) ) ? $_GET['login'] : 0;
  if(!isset($args['echo'])) $args['echo'] = false;
  if(!isset($args['form_id'])) $args['form_id'] = 'w4os-loginform';



  return $errors_output . '<div class=w4os-login>' . wp_login_form($args) . '</div>';
}
add_action( 'template_include', function( $template ) {
  global $wp_query;
  if($wp_query->queried_object->post_name != get_option('w4os_profile_slug')) return $template;
  // echo "post_name " . $wp_query->queried_object->post_name;

  $query_firstname = get_query_var( 'profile_firstname' );
  $query_lastname = get_query_var( 'profile_lastname' );
  if ( empty($query_firstname) || empty($query_lastname) ) {
    if(is_user_logged_in()) {
      $user = wp_get_current_user();
      $avatar = new W4OS_Avatar($user);
      $page_title = __('My Avatar', 'w4os');
      $page_content = $avatar->profile_page();
      if(empty($page_content)) {
        $page_content = '<div>' . w4os_avatar_creation_form($user) . '</div>';
      }
    } else {
      $page_title = __('Log in', 'w4os');
      $page_content = '<div>' . __('Log in to create your avatar, view your profile or set your options.', 'w4os') . '</div>';
      // $page_content .= '<pre>GET ' . print_r($_GET, true) . '</pre>';
      $page_content .= w4os_login_form();
    }
  } else {

  // if ( $query_firstname != '' && $query_lastname != '' ) {
    $user = w4os_get_avatar_by_name($query_firstname, $query_lastname );
    if(! $user || empty($user)) return get_404_template();
    $avatar = new W4OS_Avatar($user);

    if($avatar) $avatar_profile = $avatar->profile_page();
    // if(!$avatar_profile) return get_404_template();

    if($avatar_profile) {
      $avatar_name = esc_attr(get_the_author_meta( 'w4os_firstname', $avatar->ID) . ' ' . get_the_author_meta( 'w4os_lastname', $avatar->ID));
      $page_content = $avatar_profile;
      $page_title = $avatar_name;
      $head_title = sprintf(__("%s's profile", 'w4os'), $avatar_name);

    } else {
      header("Status: 404 Not Found");

      function redirect_404() {
          global $options, $wp_query;
          if ($wp_query->is_404) {
              $page_title = "Unknown avatar";
              // $redirect_404_url = esc_url(get_permalink(get_page_by_title($page_title)));
              // wp_redirect( $redirect_404_url );
              // exit();
          }
      }
      add_action( 'template_redirect', 'redirect_404');
      return get_404_template();
    }
  }

  if(isset($page_content)) {
    add_filter( 'the_content', function($content) use($page_content) {
      return $page_content;
    });
  }

  if(isset($page_title)) {
    add_filter( 'the_title', function($title, $id = NULL) use ($page_title) {
      if ( is_singular() && in_the_loop() && is_main_query() ) {
        return $page_title;
      }
      return $title;
    }, 20, 2 );

    if(!isset($head_title)) $head_title = $page_title;

    if(wp_get_theme()->Name == 'Divi' || wp_get_theme()->parent()->Name == 'Divi') {
      // document_title_parts doesn't work with some themes, workaround...
      add_filter('pre_get_document_title', function() use($head_title) {
        return $head_title . ' – ' . get_bloginfo('name') ;
      }, 20);
    } else {
      // Document_title_parts is preferred as it keeps website SEO preferences
      add_filter('document_title_parts', function($title) use($head_title) {
        $title['title'] = $head_title;
        // $title['site'] = get_option('w4os_grid_name');
        return $title;
      }, 20);
    }
  }

  return $template;
} );

add_action('admin_init', function() {

  add_settings_section('w4os_permalinks', 'W4OS', 'w4os_permalinks_output', 'permalink');
  add_settings_field('w4os_profile_slug', __('Profile base', 'w4os'), 'w4os_profile_slug_output', 'permalink', 'w4os_permalinks');
  if (isset($_POST['permalink_structure'])) {
    $newslug = sanitize_title($_REQUEST['w4os_profile_slug']);
    if(esc_attr(get_option('w4os_profile_slug')) != $newslug || empty($newslug)) {
      if(empty($newslug)) $newslug = 'profile';
      update_option('w4os_profile_slug', $newslug);
      update_option('w4os_rewrite_rules', true);
    }
  }
});

function w4os_permalinks_output() {
  return;
}

function w4os_profile_slug_output() {
	?>
	<input name="w4os_profile_slug" type="text" class="regular-text code" value="<?php echo esc_attr(get_option('w4os_profile_slug', 'profile')); ?>" placeholder="<?php echo 'profile'; ?>" />
	<?php
}

// function w4os_redirect_if_profile() {
//   $url = getenv('REDIRECT_URL');
//   $uuid_pattern='[a-fA-F0-9-]{8}-[a-fA-F0-9-]{4}-[a-fA-F0-9-]{4}-[a-fA-F0-9-]{4}-[a-fA-F0-9-]{12}';
//   $ext_pattern='[a-zA-Z0-9]{3}[a-zA-Z0-9]?';
//   if(! preg_match(
//     '#' . preg_replace(':^/:', '', esc_attr(parse_url(wp_upload_dir()['baseurl'],  PHP_URL_PATH ) ) ) . '/w4os/profile/images/' . $uuid_pattern . '\.' . $ext_pattern . '$' . '#',
//     $url,
//   )) return false;
//
//   $image = explode('.', basename($url));
//   if(count($image) != 2) return false;
//   $query_profile = $image[0];
//   $query_format = $image[1];
//   if ( ! preg_match('/^(jpg|png)$/i', $query_format)) return false;
//
//   require(dirname(__FILE__) . '/profile-render.php');
//   die();
// }
// w4os_redirect_if_profile();
