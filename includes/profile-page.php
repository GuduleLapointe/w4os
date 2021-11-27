<?php if ( ! defined( 'W4OS_PLUGIN' ) ) die;

define('W4OS_PROFILE_PATTERN', '^' . esc_attr(get_option('w4os_profile_slug', 'profile')) . '/([a-zA-Z][a-zA-Z9]*)[ \.+-]([a-zA-Z][a-zA-Z9]*)(/.*)?$');

add_action( 'init',  function() {
  add_rewrite_rule( W4OS_PROFILE_PATTERN,
  'index.php?pagename=' . esc_attr(get_option('w4os_profile_slug', 'profile')) . '&post_tyoe=user&profile_firstname=$matches[1]&profile_lastname=$matches[2]&profile_args=$matches[3]', 'top' );
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

add_action( 'template_include', function( $template ) {
  $query_firstname = get_query_var( 'profile_firstname' );
  $query_lastname = get_query_var( 'profile_lastname' );
  if ( $query_firstname != '' && $query_lastname != '' ) {
    $user = w4os_get_avatar_by_name($query_firstname, $query_lastname );
    if(! $user || empty($user)) return get_404_template();
    $avatar = new W4OS_Avatar($user);

    if($avatar) $avatar_profile = $avatar->profile_page();
    // if(!$avatar_profile) return get_404_template();

    if($avatar_profile) {
      $avatar_name = esc_attr(get_the_author_meta( 'w4os_firstname', $avatar->ID) . ' ' . get_the_author_meta( 'w4os_lastname', $avatar->ID));


      add_filter( 'the_content', function($content) use($avatar_profile) {
        return $avatar_profile;
      } );

      add_filter( 'the_title', function($title, $id = NULL) use ($avatar_name) {
        if ( is_singular() && in_the_loop() && is_main_query() ) {
          return $avatar_name;
        }
        return $title;
      }, 10, 2 );

      // Doesn't work. Might be launched too late, when header is already set
      add_filter('pre_get_document_title', function() use($avatar_name) {
        return sprintf(__("%s's profile - %s", 'w4os'), $avatar_name, get_option('w4os_grid_name'));
      }, 20);

      return $template;
    }
    return get_404_template();
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
