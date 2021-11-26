<?php if ( ! defined( 'W4OS_PLUGIN' ) ) die;

global $wpdb, $w4osdb;

// define('W4OS_PROFILE_PATTERN', '^/' . esc_attr(get_option('w4os_profile_slug', 'profile')) . '/([a-zA-Z][a-zA-Z9]*)[ \.+-]([a-zA-Z][a-zA-Z9]*)(/.*)?$');
define('W4OS_PROFILE_PATTERN', '^/' . esc_attr(get_option('w4os_profile_slug', 'profile')) . '/([a-zA-Z][a-zA-Z9]*)[ \.+-]([a-zA-Z][a-zA-Z9]*)/?$');
update_option('w4os_rewrite_rules', true);

// add_action( 'init',  function() {
  // rewrite rule for /profile/firstname.lastname
  // add_rewrite_tag('%avatarslug%', '([a-zA-Z][a-zA-Z9]*[\.-][a-zA-Z][a-zA-Z9]*)', 'avatarslug=');
  add_rewrite_rule( W4OS_PROFILE_PATTERN,
  'index.php?pagename=profile&profile_firstname=$matches[1]&profile_lastname=$matches[2]&profile_args=$matches[3]', 'top' );
// } );
// flush_rewrite_rules();

// add_filter( 'query_vars', 'w4os_profile_query_vars');
// function w4os_profile_query_vars( $query_vars ) {
//   $query_vars[] = 'profile_firstname';
//   $query_vars[] = 'profile_lastname';
//   $query_vars[] = 'profile_args';
//   return $query_vars;
// }

$url = getenv('REDIRECT_URL');
if(preg_match('!' . W4OS_PROFILE_PATTERN .'!', $url)) {
  $args=explode('/', preg_replace('!' . W4OS_PROFILE_PATTERN .'!', '\\1/\\2\\3', $url));
  $profile_firstname = array_shift($args);
  $profile_lastname = array_shift($args);
  $can_list_users = (current_user_can( 'list_users' ) ) ? 'true' : 'false';
  $current_user_email = wp_get_current_user()->user_email;

  $avatar_row=$w4osdb->get_row("SELECT *
    FROM UserAccounts LEFT JOIN userprofile ON PrincipalID = userUUID
    WHERE active = 1 AND Email != ''
    AND ( profileAllowPublish = 1 OR $can_list_users OR Email = '$current_user_email')
    AND FirstName = '$profile_firstname' AND LastName = '$profile_lastname';"
  );
  if($avatar_row) {
    // We found it, not a 404 anymore
    add_filter( 'pre_handle_404', function() {
      return true;
    } );

    // add_filter( 'the_title', 'suppress_if_blurb', 10, 2 );
    // function suppress_if_blurb( $title, $id = null ) {
    //   if ( is_singular() && in_the_loop() && is_main_query() ) {
    //     return "profile page";
    //   }
    //   return $title;
    // }
    add_filter('document_title_parts', function ( $title ) use ($avatar_row) {
      // $title is an array of title parts, including one called `title`
      $title['title'] = sprintf(__("%s's profile", 'w4os'),
        $avatar_row->FirstName . " " . $avatar_row->LastName,
      );
      return $title;
    });
    get_header();

    add_action( 'template_include', function($template) use ( $avatar_row, $current_user_email ) {
      $plugindir = dirname( __DIR__ );
      $template_slug=str_replace('.php', '', basename($template));
      $post_type_slug=get_post_type();
      $custom = "$plugindir/templates/$template_slug-$post_type_slug.php";

      if(is_object($avatar_row)) {
        $keys = array('FirstName' =>NULL, 'LastName' =>NULL, 'profileImage' =>NULL, 'profileAboutText'=>NULL );
        // $keys = array_combine($keys, $keys);
        // $avatar_array=(array)$avatar_row;
        if(!w4os_empty($avatar_row->profileImage)) $avatar_row->profileImageHtml = '<img src=' . w4os_get_asset_url($avatar_row->profileImage) . '>';
        $profile=array_filter(array(
          // 'Avatar Name' => $avatar_row->FirstName . " " . $avatar_row->LastName,
          __('Profile picture', 'w4os') => $avatar_row->profileImageHtml,
          __('Born', 'w4os') => sprintf('%s (%s days)',
          wp_date(get_option( 'date_format' ), $avatar_row->Created),
          floor((current_time('timestamp') - $avatar_row->Created) / 24 / 3600 )),
          __('About', 'w4os') => $avatar_row->profileAboutText,
          __('Languages', 'w4os') => $avatar_row->profileLanguages,
          __('Skills', 'w4os') => $avatar_row->profileSkillsText,
        ));
        // if(w4os_empty($profile['Profile picture'])) unset($profile['Profile picture']));
        // else $profile['Profile picture'] = sprintf('<img src=%s>', $profile['Profile picture']);
        // $content = "<header><h1 class=entry-title>" . $avatar_row->FirstName . " " . $avatar_row->LastName . "</h1></header>";
        $header = "<header>" . apply_filters('the_title', '<h1>' . $avatar_row->FirstName . " " . $avatar_row->LastName . "</h1>") . "</header>";
        if($avatar_row->profileAllowPublish != 1) {
          if($avatar_row->Email == $current_user_email) {
            $content.= sprintf(
              '<p class=notice>%s</p>',
              __('This is a preview, your profile is actually private.', 'w4os'),
            );
          } else {
            $content.= sprintf(
              '<p class=notice>%s</p>',
              __('This profile is private but you are admin, so you can look. Be fair.', 'w4os'),
            );
          }
        }
        // $content .= w4os_array2table((array)$avatar_row);
      }
      $content .= w4os_array2table($profile);
      $content = apply_filters( 'the_content', $content );
      $content = '<article class=entry><div class=entry-content>' . $header . $content . '</div></article>';
      echo "$content";

      if(file_exists($custom)) return $custom;
      return $template;
    } );
  }
}


add_filter( 'the_content', 'w4os_the_content');
function w4os_the_content ( $content ) {
  global $template;
  if(function_exists('wc_print_notices')) wc_print_notices();
  $plugindir = dirname( __DIR__ );
  $post_type_slug=get_post_type();
  $template_slug=str_replace('.php', '', basename($template));
  $custom_slug = "content-$template_slug-$post_type_slug";
  $custom = "$plugindir/templates/$custom_slug.php";
  // echo 'content template ' . $custom . '<br>';
  if(file_exists($custom)) {
    ob_start();
    include $custom;
    $custom_content = ob_get_clean();
    $content = "<div class='" . w4os_SLUG . " content $template_slug $post_type_slug'>$custom_content</div>";
  }
  return $content;
}

// add_action( 'template_include', function( $template ) {
//   if ( get_query_var( 'profile_uuid' ) == false || get_query_var( 'profile_uuid' ) == '' ) {
//     return $template;
//   }
//   return dirname(__FILE__) . '/profile-render.php';
// } );

// function wpd_foo_get_param($args = NULL) {
// //   global $wp_query;
//   if(preg_match('!' . W4OS_PROFILE_PATTERN .'!', '/' . get_query_var( 'pagename' ))) {
//   // if( false !== get_query_var( 'os_firstname') && false !== get_query_var( 'os_lastname' ) ) {
//     // echo "<pre>" . print_r($args, true) . '</pre>';
//     echo "parse query "
//     . 'pagename=' . get_query_var( 'pagename' ) . "<br>"
//     . '- avatarslug=' . get_query_var( 'avatarslug') . '<br>'
//     . '- profile_args=' . get_query_var( 'profile_args' ) . "<br>"
//     ;
//   }
// }
// add_action( 'parse_query', 'wpd_foo_get_param' );

// echo W4OS_PROFILE_PATTERN;

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
