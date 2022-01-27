<?php if ( ! defined( 'W4OS_PLUGIN' ) ) die;

// ini_set('display_errors', '1');
// ini_set('display_startup_errors', '1');
// error_reporting(E_ERROR | E_WARNING | E_PARSE);

if(W4OS_DB_CONNECTED)
add_filter( 'authenticate', 'w4os_gridauth',  20, 3 );
function w4os_gridauth ( $user, $username, $password ) {
  if(!W4OS_DB_CONNECTED) return $user;
  global $w4osdb;

  if(!is_object($user)) return false;
  // echo "user was " . $user-ID . '<br>';
  if(is_array($user->errors) && ( @$user->errors['invalid_username'] || @$user->errors['incorrect_password'] ) ) {
    if(w4os_is_email($username)) {
      $match = "Email = '$username'";
    } else if (preg_match('/ /', trim($username) )) {
      $explode=explode(' ', $username);
      $firstname = $explode[0];
      $lastname = $explode[1];
      $match = "FirstName = '$firstname' AND LastName='$lastname'";
    } else {
      // No mail, no avatar name, can't do nothing, give up
      return $user;
    }

    $avatar_query = "SELECT *
    FROM UserAccounts LEFT JOIN auth ON PrincipalID = UUID
    WHERE active = 1 AND Email != ''
    AND $match
    AND passwordHash = md5(concat(md5('$password'),':', passwordSalt))
    ;";
    $avatar_row=$w4osdb->get_row($avatar_query);
    if(is_wp_error($avatar_row)) {
      return $user;
    }
    // echo "avatar_row <pre>" . print_r($avatar_row, true) . '</pre>';

    if(!empty($avatar_row)) {
      $user = get_user_by('email', $avatar_row->Email);
      if(!$user || $user==NULL || is_wp_error($user)) {
        // WP user doesn't exist, create one
        $user_login = w4os_create_user_login($avatar_row->FirstName, $avatar_row->LastName, $avatar_row->Email);
        $newid = wp_insert_user(array(
          'user_login' => $user_login,
          // 'user_pass' => $password,
          'user_email' => $avatar_row->Email,
          'first_name' => $avatar_row->FirstName,
          'last_name' => $avatar_row->LastName,
          'role' => 'grid_user',
          'display_name' => trim($avatar_row->FirstName . ' ' . $avatar_row->LastName),
        ));
        $user = get_user_by('ID', $newid);
        reset_password($user, $password);
        if (is_wp_error( $user )) return $user;
        w4os_profile_sync($newid, $avatar_row->PrincipalID);
      } else {
        // user exists, just sync update password
        w4os_profile_sync($user);
        reset_password($user, $password);
      }
    }
    // $user = get_user_by('ID', 1);
  }
  // echo "user is now " . $user-ID . '<br>';
  // $user = new WP_Error( 'authentication_failed', __( '<strong>ERROR</strong>: Invalid username, email address or incorrect password.' ) );

  return $user;
}

function w4os_is_email(string $address): bool {
  $hits = \preg_match('/^([^@]+)@([^@]+)$/', $address, $matches);

  if ($hits === 0) {
    // email NOT valid
    return false;
  }

  [$address, $localPart, $domain] = $matches;

  $variant = INTL_IDNA_VARIANT_2003;
  if (\defined('INTL_IDNA_VARIANT_UTS46') ) {
    $variant = INTL_IDNA_VARIANT_UTS46;
  }

  $domain = \rtrim(\idn_to_ascii($domain, IDNA_DEFAULT, $variant), '.') . '.';

  if (!\checkdnsrr($domain, 'MX')) {
    return \checkdnsrr($domain, 'A') || \checkdnsrr($domain, 'AAAA');
  } else {
    return true;
  }
}

add_filter( 'login_redirect', 'w4os_redirect_after_login', 10, 3 );
function w4os_redirect_after_login( $redirect_to, $request, $user ){
  $redirect_url = W4OS_LOGIN_PAGE;
  return $redirect_url;
}

/**
 * https://code.tutsplus.com/series/build-a-custom-wordpress-user-flow--cms-816
 */
if(get_option('w4os_login_page') == 'profile') {
  /* Main redirection of the default login page */
  add_action('init','w4os_redirect_login_page');
  function w4os_redirect_login_page() {
    $page_viewed = basename($_SERVER['REQUEST_URI']);

    if($page_viewed == "wp-login.php" && $_SERVER['REQUEST_METHOD'] == 'GET') {
      wp_redirect(W4OS_LOGIN_PAGE);
      exit;
    }
  }

  /* What to do on logout */
  add_action( 'wp_logout', 'w4os_redirect_after_logout' );
  function w4os_redirect_after_logout() {
    // $current_user   = wp_get_current_user();
    // $role_name      = $current_user->roles[0];
    // if ( 'subscriber' === $role_name ) {
    wp_safe_redirect( W4OS_LOGIN_PAGE );
    exit;
    // }
  }

  // add_action( 'login_form_register', 'w4os_redirect_register' );
  // function w4os_redirect_register() {
  //   if ( 'GET' == $_SERVER['REQUEST_METHOD'] ) {
  //     if ( is_user_logged_in() ) $this->redirect_logged_in_user();
  //     else wp_redirect(W4OS_LOGIN_PAGE . "?action=register");
  //     exit;
  //   }
  // }

//
//   /* Where to go if a login failed */
//   add_action('wp_login_failed', 'w4os_login_failed');
//   function w4os_login_failed() {
//     wp_redirect(W4OS_LOGIN_PAGE . '?login=failed');
//     exit;
//   }
//
//   /* Where to go if any of the fields were empty */
//   add_filter('authenticate', 'w4os_verify_user_pass', 1, 3);
//   function w4os_verify_user_pass($user, $username, $password) {
//     if($username == "" || $password == "") {
//       wp_redirect(W4OS_LOGIN_PAGE . "?login=empty");
//       exit;
//     }
//   }
//
//
//
//   // add_action( 'login_form_lostpassword', 'w4os_redirect_lostpassword' );
//   // function w4os_redirect_lostpassword() {
//   //   if ( 'GET' == $_SERVER['REQUEST_METHOD'] ) {
//   //     if ( is_user_logged_in() ) $this->redirect_logged_in_user();
//   //     else wp_redirect(W4OS_LOGIN_PAGE . "?action=lostpassword");
//   //     exit;
//   //   }
//   // }
}
