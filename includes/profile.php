<?php
// require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/class.php';

add_action( 'show_user_profile', 'w4os_profile_fields' );
add_action( 'edit_user_profile', 'w4os_profile_fields' );
add_action( 'personal_options_update', 'w4os_profile_fields_save' );
add_action( 'edit_user_profile_update', 'w4os_profile_fields_save' );

function w4os_profile_sync($user) {
  global $w4osdb;
  $uuid = $w4osdb->get_var("SELECT PrincipalID FROM UserAccounts WHERE Email = '$user->user_email'");
  update_user_meta( $user->ID, 'w4os_uuid', $uuid );
  update_user_meta( $user->ID, 'w4os_firstname', $w4osdb->get_var("SELECT FirstName FROM UserAccounts WHERE PrincipalID = '$uuid'") );
  update_user_meta( $user->ID, 'w4os_lastname', $w4osdb->get_var("SELECT LastName FROM UserAccounts WHERE PrincipalID = '$uuid'") );
  return $uuid;
}

function w4os_profile_fields( $user ) {
  global $w4osdb;
  $uuid = w4os_profile_sync($user);
  echo "    <h3>" . __("OpenSimulator", "w4os") ."</h3>";
  if(!$uuid) {
    echo "<p>" . __("No avatar") . "</p>";
  } else {
   ?>

    <table class="form-table">
    <tr>
        <th><label for="w4os_uuid"><?php _e("Avatar UUID", "w4os"); ?></label></th>
        <td>
            <?php echo esc_attr( get_the_author_meta( 'w4os_uuid', $user->ID ) ); ?>
        </td>
    </tr>
    <tr>
        <th><label for="w4os_firstname"><?php _e("Avatar name", "w4os"); ?></label></th>
        <td>
          <?php echo esc_attr( get_the_author_meta( 'w4os_firstname', $user->ID ) ) . " " . esc_attr( get_the_author_meta( 'w4os_lastname', $user->ID ) ); ?>
        </td>
    </tr>
    </table>
<?php }
}

function w4os_profile_edit( $user ) {
  global $w4osdb;
  $content.="<h3>" . __("OpenSimulator profile") . "</h3>";
  $content.='<table class="form-table">'
  . '<tr>'
  . '<th>' . __("Avatar UUID") . '</th>'
  . '<td>' . get_the_author_meta( 'w4os_uuid', $user->ID ) . '</td>'
  . '</tr>'
  . '<tr>'
  . '<th>' . __("Avatar name") . '</th>'
  . '<td>' . get_the_author_meta( 'w4os_firstname', $user->ID ) . " " . get_the_author_meta( 'w4os_lastname', $user->ID ) . '</td>'
  . '</tr>'
  . '</table>';

  return $content;
}

function w4os_is_strong ($password) {
  $min_length = 8;
  // Given password
  $uppercase = preg_match('@[A-Z]@', $password);
  $lowercase = preg_match('@[a-z]@', $password);
  $number    = preg_match('@[0-9]@', $password);
  // $specialChars = preg_match('@[^\w]@', $password);

  if(!$uppercase || !$lowercase || !$number || strlen($password) < $min_length) {
    w4os_notice(sprintf( __( 'Password must contain at least %s characters, including uppercase, lowercase and number', 'w4os' ), $min_length), 'fail');
    return false;
  } else {
    return true;
  }
}

function w4os_update_avatar( $user, $params ) {
  global $w4osdb;
  switch ($params['action'] ) {
    case "w4os_create_avatar":
    // w4os_notice(print_r($_REQUEST, true), "code");
    $uuid = w4os_profile_sync($user); // refresh opensim data for this user
    if ( $uuid ) {
      w4os_notice(__("This user already has an avatar.", 'w4os'), 'fail');
      return $uuid;
    }

    $firstname = trim($params['w4os_firstname']);
    $lastname = trim($params['w4os_lastname']);
    // Check required fields
    // $errors="hop";
    $required=array('w4os_firstname', 'w4os_lastname', 'w4os_password_1', 'w4os_password_2');
      if ( ! $firstname ) { $errors=true; w4os_notice(__("First name required", "w4os"), 'fail') ; }
    if ( ! $lastname ) { $errors=true; w4os_notice(__("Last name required", 'w4os'), 'fail') ; }
    if ( ! $params['w4os_password_1'] && ! $params['w4os_password_2'] ) { $errors=true; w4os_notice(__("Password required", 'w4os'), 'fail') ; }
    else if ( $params['w4os_password_1'] != $params['w4os_password_2'] ) { $errors=true; w4os_notice(__("Passwords do not match", 'w4os'), 'fail') ; }
    if ( $errors == true ) return false;
    $password=stripcslashes($params['w4os_password_1']);
    if ( ! w4os_is_strong ($password)) return false;

    if (in_array(strtolower($firstname), array_map('strtolower', DEFAULT_RESTRICTED_NAMES))) {
      w4os_notice(sprintf( __( 'The name %s is not allowed', 'w4os' ), "$firstname"), 'fail');
      return false;
    }
    if (in_array(strtolower($lastname), array_map('strtolower', DEFAULT_RESTRICTED_NAMES))) {
      w4os_notice(sprintf( __( 'The name %s is not allowed', 'w4os' ), "$lastname"), 'fail');
      return false;
    }

    if(! preg_match("/^[a-zA-Z0-9]*$/", $firstname.$lastname)) {
      w4os_notice(__( 'Names can only contain alphanumeric characters', 'w4os' ), 'fail');
      return false;
    }
    // Check if there is already an avatar with this name

    $check_uuid = $w4osdb->get_var("SELECT PrincipalID FROM UserAccounts WHERE FirstName = '$firstname' AND LastName = '$lastname'");
    if ( $check_uuid ) {
      w4os_notice(sprintf( __( 'There is already a grid user named %s', 'w4os' ), "$firstname $lastname"), 'fail');
      return false;
    }
    // Hash password

    $new_user_uuid = gen_uuid();
    $check_uuid = $w4osdb->get_var("SELECT PrincipalID FROM UserAccounts WHERE PrincipalID = '$new_user_uuid'");
    if ( $check_uuid ) {
      w4os_notice(__( 'This should never happen! Generated a random UUID that already existed. Sorry. Try again.', 'w4os' ), 'fail');
      return false;
    }

    $salt = md5(gen_uuid());
    $hash = md5(md5($password) . ":" . $salt);
    $user_email = get_userdata($user->ID)->data->user_email;
    $created = mktime();

    $result = $w4osdb->insert (
      'UserAccounts', array (
        'PrincipalID' => $new_user_uuid,
        'ScopeID' => NULL_KEY,
        'FirstName'   => $firstname,
        'LastName'   => $lastname,
        'Email' => $user_email,
        'ServiceURLs' => 'HomeURI= InventoryServerURI= AssetServerURI=',
        'Created' => $created,
      )
    );
    if ($result) $result = $w4osdb->insert (
      'Auth', array (
        'UUID' => $new_user_uuid,
        'passwordHash'   => $hash,
        'passwordSalt'   => $salt,
        'webLoginKey' => NULL_KEY,
      )
    );
    if ($result) $result = $w4osdb->insert (
      'GridUser', array (
        'UserID' => $new_user_uuid,
        'HomeRegionID' => 'd996deab-4247-4634-92a5-98808a65878e', // TODO: should be replaced by default home
        'HomePosition' => '<127,127,21>',
        'LastRegionID' => 'd996deab-4247-4634-92a5-98808a65878e',
        'LastPosition' => '<127,127,21>',
      )
    );

    $default_firstname=strstr(DEFAULT_AVATAR, " ", true);
    $default_lastname=trim(strstr(DEFAULT_AVATAR, " "));
    $default_uuid = $w4osdb->get_var("SELECT PrincipalID FROM UserAccounts WHERE FirstName = '$default_firstname' AND LastName = '$default_lastname'");

    $inventory_uuid = gen_uuid();
    if ($result) $result = $w4osdb->insert (
      'inventoryfolders', array (
        'folderName' => 'My Inventory',
        'type' => 8,
        'version' => 1,
        'folderID' => $inventory_uuid,
        'agentID' => $new_user_uuid,
        'parentFolderID' => NULL_KEY,
      )
    );

    if ( $result ) {
      $folders = array(
        array('Textures', 0, 1 ),
        array('Sounds', 1, 1 ),
        array('Calling Cards', 2, 2 ),
        array('Landmarks', 3, 1 ),
        array('Photo Album', 15, 1 ),
        array('Clothing', 5, 3 ),
        array('Objects', 6, 1 ),
        array('Notecards', 7, 1 ),
        array('Scripts', 10, 1 ),
        array('Body Parts', 13, 5 ),
        array('Trash', 14, 1 ),
        array('Animations', 20, 1 ),
        array('Gestures', 21, 1 ),
        array('Lost And Found', 16, 1 ),
        // array('Friends', 2, 2 ),
        // array('Favorites', 23, 1 ),
        // array('Current Outfit', 46, 1 ),
        // array('All', 2, 1 ),
      );
      foreach($folders as $folder) {
        $name = $folder[0];
        $type = $folder[1];
        $version = $folder[2];
        if ($result) $result = $w4osdb->insert (
          'inventoryfolders', array (
            'folderName' => $name,
            'type' => $type,
            'version' => $version,
            'folderID' => gen_uuid(),
            'agentID' => $new_user_uuid,
            'parentFolderID' => $inventory_uuid,
          )
        );
        if( ! $result ) break;
      }
    }

    // if ( $result ) {
    //   $result = $w4osdb->get_results("SELECT folderName,type,version FROM inventoryfolders WHERE agentID = '$default_uuid' AND type != 8");
    //   if($result) {
    //     foreach($result as $row) {
    //       $result = $w4osdb->insert (
    //         'inventoryfolders', array (
    //           'folderName' => $row->folderName,
    //           'type' => $row->type,
    //           'version' => $row->version,
    //           'folderID' => gen_uuid(),
    //           'agentID' => $new_user_uuid,
    //           'parentFolderID' => $inventory_uuid,
    //         )
    //       );
    //       if( ! $result ) break;
    //     }
    //   }
    // }

    if ( $result ) {
      $result = $w4osdb->get_results("SELECT Name, Value FROM Avatars WHERE PrincipalID = '$default_uuid'");
      // w4os_notice(print_r($result, true), 'code');
      // foreach($result as $row) {
      //   w4os_notice(print_r($row, true), 'code');
      //   w4os_notice($row->Name . " = " . $row->Value);
      // }

      // foreach($avatars_prefs as $var => $val) {
      if($result) {
        foreach($result as $row) {
          $w4osdb->insert (
            'Avatars', array (
              'PrincipalID' => $new_user_uuid,
              'Name' => $row->Name,
              'Value' => $row->Value,
            )
          );
        }
      }
    }

    if( ! $result ) {
      w4os_notice("Could not insert the user", fail);
      w4os_notice($sql, 'code');
      return false;
    }

    w4os_notice(sprintf( __( 'Avatar %s created successfully.', 'w4os' ), "$firstname $lastname"), 'success');

    $check_uuid = w4os_profile_sync($user); // refresh opensim data for this user
    return $new_user_uuid;
    // $result = $w4osdb->get_var("$sql");

    //   $uuid = w4os_profile_sync($user); // refresh opensim data for this user
    break;

    default:
    w4os_notice(sprintf( __( 'Action %s not implemented', 'w4os' ), $params['action']), 'fail');
  }
  // show_message ("<pre>" . print_r($_REQUEST, true) . "</pre>");
  // show_message ( "Updating user" );
}

function w4os_profile_wc_edit( $user ) {
  if($user->ID == 0) {
    $content = __("Log in to display your avatar");
    return $content;
  }
  ####
  ## TODO: Check if user is current user
  ## Otherwise, do not allow edit, and display profile only if public
  ####

  global $w4osdb;

  if ( $_REQUEST['w4os_update_avatar'] ) {
    $uuid = w4os_update_avatar( $user, $_REQUEST);
  } else {
    $uuid = w4os_profile_sync($user); // refresh opensim data for this user
    if(! $uuid) w4os_notice(__("You have no grid account yet. Fill the form below to create your avatar."));
  }

  $content="
  <form class='woocommerce-EditAccountForm edit-account' action='' method='post'>";

  if ($uuid) {
    $action = 'w4os_update_avatar';
    $leaveblank= " (" . __('leave blank to leave unchanged', "w4os") . ")";
    $content.= "
    <p class='woocommerce-form-row woocommerce-form-row--first form-row form-row-first'>
      <label for='w4os_firstname'>" . __("Avatar first name", "w4os") . "&nbsp;</label>
      " . get_the_author_meta( 'w4os_firstname', $user->ID ) ."
    </p>
    <p class='woocommerce-form-row woocommerce-form-row--last form-row form-row-last'>
      <label for='w4os_lastname'>" . __("Avatar last name", "w4os") . "&nbsp;</label>
      " . get_the_author_meta( 'w4os_lastname', $user->ID ) . "
    </p>
    <p class='woocommerce-form-row woocommerce-form-row--wide form-row form-row-wide'>
    		<label for='w4os_uuid'>" . __("UUID", "w4os") . "</label>
    		$uuid
  	</p>";
    ### Current password disabled, password change not yet implemented
    ###
    // $content .="
    // <fieldset>
  	// 	<legend>Changement de mot de passe</legend>
    //
  	// 	<p class='woocommerce-form-row woocommerce-form-row--wide form-row form-row-wide'>
  	// 		<label for='w4os_password_current'>" . __('Current password', "w4os") . "$leaveblank)</label>
  	// 		<span class='password-input'><input type='password' class='woocommerce-Input woocommerce-Input--password input-text' name='w4os_password_current' id='w4os_password_current' autocomplete='off'><span class='show-password-input'></span></span>
  	// 	</p>";
  	###
  	### End current password part
  } else {
    $action = 'w4os_create_avatar';
    $content .= "
      <span><em>" . __("Your name in the virtual world (not required to be your real name). You can pick anything you want. Almost.", "w4os") . "</em></span>
      <div class='clear'></div>

      <p class='woocommerce-form-row woocommerce-form-row--first form-row form-row-first'>
    		<label for='w4os_firstname'>" . __("Avatar first name", "w4os") . "&nbsp;<span class='required'>*</span></label>
    		<input type='text' class='woocommerce-Input woocommerce-Input--text input-text' name='w4os_firstname' id='w4os_firstname' autocomplete='given-name' value='$_REQUEST[w4os_firstname]'>
    	</p>
    	<p class='woocommerce-form-row woocommerce-form-row--last form-row form-row-last'>
    		<label for='w4os_lastname'>" . __("Avatar last name", "w4os") . "&nbsp;<span class='required'>*</span></label>
    		<input type='text' class='woocommerce-Input woocommerce-Input--text input-text' name='w4os_lastname' id='w4os_lastname' autocomplete='family-name' value='$_REQUEST[w4os_lastname]'>
    	</p>
      <div class='clear'></div>
      ";

      ### This common part should be moved after the enf of if close, once we implement password change
      ###
      $content.= "
      <span><em>" . __("Your avatar password is not required to be the same as your website account password. Changes made here will not affect your website password.", "w4os") . "</em></span>
      <p class='woocommerce-form-row woocommerce-form-row--wide form-row form-row-wide'>
      <label for='w4os_password_1'>" . __('New password') . "$leaveblank</label>
      <span class='password-input'><input type='password' class='woocommerce-Input woocommerce-Input--password input-text' name='w4os_password_1' id='w4os_password_1' autocomplete='off'><span class='show-password-input'></span></span>
      </p>
      <p class='woocommerce-form-row woocommerce-form-row--wide form-row form-row-wide'>
      <label for='w4os_password_2'>" . __('Confirm new password') . "</label>
      <span class='password-input'><input type='password' class='woocommerce-Input woocommerce-Input--password input-text' name='w4os_password_2' id='w4os_password_2' autocomplete='off'><span class='show-password-input'></span></span>
      </p>";
      if ($uuid) $content.="    	</fieldset>";



      $content .= "
      <p>
      <input type='hidden' name='action' value='$action'>
      <button type='submit' class='woocommerce-Button button' name='w4os_update_avatar' value='$action'>" . __("Save") . "</button>
      </p>";
      ###
      ### End of common part to move
  }
  $content .= "  </form>";
  return $content;
}

function w4os_profile_fields_save( $user_id ) {
    if ( !current_user_can( 'edit_user', $user_id ) ) {
        return false;
    }
    // update_user_meta( $user_id, 'w4os_uuid', $_POST['w4os_uuid'] );
    // update_user_meta( $user_id, 'w4os_firstname', $_POST['w4os_firstname'] );
    // update_user_meta( $user_id, 'w4os_lastname', $_POST['w4os_lastname'] );
}

function w4os_profile_shortcodes_init()
{
	function w4os_profile_shortcode($atts = [], $content = null)
	{
    // $current_user = wp_get_current_user();
    $content .= w4os_profile_wc_edit(wp_get_current_user());
    // $content .= "<pre>" . print_r($atts, true) . "</pre>";
    // $content .= "<pre>" . print_r($current_user, true) . "</pre>";
		return $content;
	}
	add_shortcode('w4os_profile', 'w4os_profile_shortcode');
}
add_action('init', 'w4os_profile_shortcodes_init');
