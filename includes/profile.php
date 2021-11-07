<?php
/**
 * Profile
 *
 * @package	w4os
 * @author Olivier van Helden <olivier@van-helden.net>
 */

class W4OS_Avatar extends WP_User {

  public function __construct($id = 0, $name='', $site_id='' )
  {
    /**
     * First get WP_User object
     * @var [type]
     */
    if ( $id instanceof WP_User ) {
      $this->init( $id->data, $site_id );
      return;
    } elseif ( is_object( $id ) ) {
      $this->init( $id, $site_id );
      return;
    }

    if ( ! empty( $id ) && ! is_numeric( $id ) ) {
      $name = $id;
      $id   = 0;
    }

    if ( $id ) {
      $data = self::get_data_by( 'id', $id );
    } else {
      $data = self::get_data_by( 'login', $name );
    }

    if ( $data ) {
      $this->init( $data, $site_id );
    } else {
      $this->data = new stdClass;
    }

    /**
     * Add W4OS avatar properties
     * @var string
     */
    // $this->avatarName = "Avatar of " . $this->user_email;
    $this->UUID = esc_attr(get_the_author_meta( 'w4os_uuid', $id ));
    $this->FirstName = esc_attr(get_the_author_meta( 'w4os_firstname', $id ));
    $this->LastName = esc_attr(get_the_author_meta( 'w4os_lastname', $id ));
    $this->AvatarName = $this->FirstName . " " . $this->LastName;
    $this->AvatarHGName = strtolower($this->FirstName) . "." . strtolower($this->LastName) . "@" . esc_attr(get_option('w4os_login_uri'));
    $this->ProfilePictureUUID = get_the_author_meta( 'w4os_profileimage', $id );
    if(empty($this->ProfilePictureUUID)) $this->ProfilePictureUUID = W4OS_NULL_KEY;
  }

  public function profile_picture( $echo = false ) {
      $html = sprintf('<img class=profile-img src="%1$s" alt="%2$s\'s profile picture" title="%2$s">',
        W4OS_WEB_ASSETS_SERVER_URI . $this->ProfilePictureUUID,
        $this->AvatarName,
      );
      if($echo) echo $html;
      else return $html;
  }
}

add_action( 'show_user_profile', 'w4os_profile_fields' );
add_action( 'edit_user_profile', 'w4os_profile_fields' );
add_action( 'personal_options_update', 'w4os_profile_fields_save' );
add_action( 'edit_user_profile_update', 'w4os_profile_fields_save' );

/**
 * Sync avatar info from OpenSimulator
 * @param  object $user [description]
 * @return object       [description]
 */
function w4os_profile_sync($user) {
  global $w4osdb;

  // $uuid = $w4osdb->get_var("SELECT PrincipalID FROM UserAccounts WHERE Email = '$user->user_email'");
  $avatars=$w4osdb->get_results("SELECT PrincipalID, FirstName, LastName, profileImage, profileAboutText
    FROM UserAccounts, userprofile
    WHERE PrincipalID = userUUID
    AND Email = '$user->user_email'
    ");
  if(count($avatars) != 1) return W4OS_NULL_KEY;
  $avatar_row = array_shift($avatars);
  $uuid = $avatar_row->PrincipalID;

  // if($models) {
  //   $content.= "<div class='clear'></div>";
  //   $content.= "<div class=form-row>";
  //   $content .= "<label>" . __('Your avatar', 'w4os') . "</label>";
  //   $content .= "<p class=description>" . __('You can change and customize it in-world, as often as you want.', 'w4os') . "</p>";
  //   $content .= "
  //   <p class='field-model woocommerce-form-row woocommerce-form-row--wide form-row form-row-wide'>";
  //   foreach($models as $model) {
  //     $model_name = $model->FirstName . " " . $model->LastName;

  update_user_meta( $user->ID, 'w4os_uuid', $uuid );
  update_user_meta( $user->ID, 'w4os_firstname', $avatar_row->FirstName );
  update_user_meta( $user->ID, 'w4os_lastname', $avatar_row->LastName );
  update_user_meta( $user->ID, 'w4os_profileimage', $avatar_row->profileImage );
  return $uuid;
}

/**
 * Avatar fields for WP user profile page
 * @param  [type] $user
 */
function w4os_profile_fields( $user ) {
  if($user->ID != wp_get_current_user()->ID) return;
  global $w4osdb;
  $uuid = w4os_profile_sync($user);
  echo "    <h3>" . __("OpenSimulator", "w4os") ."</h3>";
  echo "<div class=avatar_profile>";
  // if(!$uuid) {
    echo w4os_profile_display($user);
    // echo "<p>" . __("No avatar", 'w4os') . "</p>";
  echo "</div>";
}

/**
 * Update avatar password in OpenSimulator
 * Fired when user changes WP account password
 * @param integer $user_id
 * @param string  $new_pass new password
 */
function w4os_set_avatar_password( $user_id, $new_pass ) {
	global $w4osdb;

	if( $user_id && $new_pass && current_user_can('edit_user',$user_id ) ) {

		$user = get_userdata( $user_id );
		if (! $user ) return;
		$uuid = w4os_profile_sync($user); // refresh opensim data for this user
		$password=stripcslashes($new_pass);
		$salt = md5(w4os_gen_uuid());
		$hash = md5(md5($password) . ":" . $salt);
		$w4osdb->update (
			'auth',
			array (
				'passwordHash'   => $hash,
				'passwordSalt'   => $salt,
				// 'webLoginKey' => W4OS_NULL_KEY,
			),
			array (
				'UUID' => $uuid,
			)
		);
	}
}

/**
 * Catch password change from user profile page and save it to OpenSimulator
 * @param  [type] $args [description]
 */
function w4os_save_account_details ( $args ) {
	// not verified
	if($_REQUEST['password_1'] == $_REQUEST['password_2'])
	w4os_set_avatar_password( $user_id, $_REQUEST['password_1'] );
}
add_action('save_account_details', 'w4os_save_account_details', 10, 1);

/**
 * Catch password change from WooCommerceand save it to OpenSimulator
 * @param  integer $user_id
 */
function w4os_woocommerce_save_account_details ( $user_id ) {
	if($_REQUEST['password_1'] == $_REQUEST['password_2'])
	w4os_set_avatar_password( $user_id, $_REQUEST['password_1'] );
}
add_action('woocommerce_save_account_details', 'w4os_woocommerce_save_account_details', 10, 1);

/**
 * Catch password change and save it to OpenSimulator
 * I don't remember when this is fired
 * @param  integer $user_id
 */
function w4os_own_profile_update( $user_id, $old_user_data ) {
	if($_REQUEST['pass1'] == $_REQUEST['pass2'])
	w4os_set_avatar_password( $user_id, $_REQUEST['pass1'] );
}
add_action( 'profile_update', 'w4os_own_profile_update', 10, 2 );

/**
 * Catch password change made by admin and save it to OpenSimulator
 * @param  integer $user_id
 */
function w4os_other_profile_update($user_id) {
	if ( current_user_can('edit_user',$user_id) ) {
		w4os_set_avatar_password( $user_id, $_REQUEST['pass1'] );
	}
}
add_action( 'edit_user_profile_update', 'w4os_other_profile_update', 10, 1);

add_action( 'user_register',
function() {
  if ( $_REQUEST['email'] ) {
    global $wpdb;
    $user = $wpdb->get_row($wpdb->prepare("select * from ".$wpdb->prefix."users where user_email = %s", $_REQUEST['email']));
    $uuid = w4os_profile_sync($user); // refresh opensim data for this user
    if( $uuid ) {
      $password=stripcslashes($_REQUEST['password']);
      $salt = md5(w4os_gen_uuid());
      $hash = md5(md5($password) . ":" . $salt);
      update_user_meta( $user->ID, 'w4os_tmp_salt', $salt );
      update_user_meta( $user->ID, 'w4os_tmp_hash', $hash );
    }
  }
}
, 10, 1);

add_action('woocommerce_before_customer_login_form', 'w4os_verify_user', 5);
function w4os_verify_user() {
  if(!is_user_logged_in()) {
    if(isset($_GET['action']) && $_GET['action'] == 'verify_account') {
      $verify = 'false';
      if(isset($_GET['user_login']) && isset($_GET['key'])) {
        global $wpdb;
        $user = $wpdb->get_row($wpdb->prepare("select * from ".$wpdb->prefix."users where user_login = %s and user_activation_key = %s", $_GET['user_login'], $_GET['key']));
        $uuid = w4os_profile_sync($user); // refresh opensim data for this user
        if($uuid) {
          $salt = get_user_meta( $user->ID, 'w4os_tmp_salt', true );
          $hash = get_user_meta( $user->ID, 'w4os_tmp_hash', true );
          if( $salt && $hash ) {
            global $w4osdb;
            $w4osdb->update (
              'auth',
              array (
                'passwordHash'   => $hash,
                'passwordSalt'   => $salt,
                // 'webLoginKey' => W4OS_NULL_KEY,
              ),
              array (
                'UUID' => $uuid,
              )
            );
          }
        }
      }
    }
  }
}

// function w4os_debug_log($string) {
//   file_put_contents ( "../tmp/w4os_debug.log", $string . "\n", FILE_APPEND );
// }

function w4os_update_avatar( $user, $params ) {
  global $w4osdb;
  $errors = false;
  switch ($params['action'] ) {
    case "w4os_create_avatar":
    // w4os_notice(print_r($_REQUEST, true), "code");
    $uuid = w4os_profile_sync($user); // refresh opensim data for this user
    if ( $uuid ) {
      w4os_notice(__("This user already has an avatar.", 'w4os'), 'fail');
      return $uuid;
    }
    // echo  "<pre>" . $user->user_pass . "\n" . print_r($user, true) . "</pre>";

    $firstname = trim($params['w4os_firstname']);
    $lastname = trim($params['w4os_lastname']);
    $model = trim($params['w4os_model']);
    if (empty($model)) $model = W4OS_DEFAULT_AVATAR;

    // Check required fields
    $required=array('w4os_firstname', 'w4os_lastname', 'w4os_password_1');
    if ( ! $firstname ) { $errors=true; w4os_notice(__("First name required", "w4os"), 'fail') ; }
    if ( ! $lastname ) { $errors=true; w4os_notice(__("Last name required", 'w4os'), 'fail') ; }
    if ( ! $params['w4os_password_1'] ) { $errors=true; w4os_notice(__("Password required", 'w4os'), 'fail') ; }
    else if ( ! wp_check_password($params['w4os_password_1'], $user->user_pass)) { $errors=true; w4os_notice(__("The password does not match.", 'w4os'), 'fail') ; }
    if ( $errors == true ) return false;

    $password=stripcslashes($params['w4os_password_1']);
    // if ( ! w4os_is_strong ($password)) return false; // We now only rely on WP password requirements

    if (in_array(strtolower($firstname), array_map('strtolower', W4OS_DEFAULT_RESTRICTED_NAMES))) {
      w4os_notice(sprintf( __( 'The name %s is not allowed', 'w4os' ), "$firstname"), 'fail');
      return false;
    }
    if (in_array(strtolower($lastname), array_map('strtolower', W4OS_DEFAULT_RESTRICTED_NAMES))) {
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

    $newavatar_uuid = w4os_gen_uuid();
    $check_uuid = $w4osdb->get_var("SELECT PrincipalID FROM UserAccounts WHERE PrincipalID = '$newavatar_uuid'");
    if ( $check_uuid ) {
      w4os_notice(__( 'This should never happen! Generated a random UUID that already existed. Sorry. Try again.', 'w4os' ), 'fail');
      return false;
    }

    $salt = md5(w4os_gen_uuid());
    $hash = md5(md5($password) . ":" . $salt);
    $user_email = get_userdata($user->ID)->data->user_email;
    $created = mktime();
    $HomeRegionID = $w4osdb->get_var("SELECT UUID FROM regions WHERE regionName = '" . W4OS_DEFAULT_HOME . "'");
    if(empty($HomeRegionID)) $HomeRegionID = '00000000-0000-0000-0000-000000000000';

    $result = $w4osdb->insert (
      'UserAccounts', array (
        'PrincipalID' => $newavatar_uuid,
        'ScopeID' => W4OS_NULL_KEY,
        'FirstName'   => $firstname,
        'LastName'   => $lastname,
        'Email' => $user_email,
        'ServiceURLs' => 'HomeURI= InventoryServerURI= AssetServerURI=',
        'Created' => $created,
      )
    );
    if ( !$result ) w4os_notice(__("Error while creating user", 'w4os'), 'fail');
    if ($result) $result = $w4osdb->insert (
      'auth', array (
        'UUID' => $newavatar_uuid,
        'passwordHash'   => $hash,
        'passwordSalt'   => $salt,
        'webLoginKey' => W4OS_NULL_KEY,
      )
    );
    if ( !$result ) w4os_notice(__("Error while setting password", 'w4os'), 'fail');

    if ($result) $result = $w4osdb->insert (
      'GridUser', array (
        'UserID' => $newavatar_uuid,
        'HomeRegionID' => $HomeRegionID,
        'HomePosition' => '<128,128,21>',
        'LastRegionID' => $HomeRegionID,
        'LastPosition' => '<128,128,21>',
      )
    );
    if ( !$result ) w4os_notice(__("Error while setting home region", 'w4os'), 'fail');

    $model_firstname=strstr($model, " ", true);
    $model_lastname=trim(strstr($model, " "));
    $model_uuid = $w4osdb->get_var("SELECT PrincipalID FROM UserAccounts WHERE FirstName = '$model_firstname' AND LastName = '$model_lastname'");

    $inventory_uuid = w4os_gen_uuid();
    if ($result) $result = $w4osdb->insert (
      'inventoryfolders', array (
        'folderName' => 'My Inventory',
        'type' => 8,
        'version' => 1,
        'folderID' => $inventory_uuid,
        'agentID' => $newavatar_uuid,
        'parentFolderID' => W4OS_NULL_KEY,
      )
    );
    if ( !$result ) w4os_notice(__("Error while creating user inventory", 'w4os'), 'fail');

    $bodyparts_uuid = w4os_gen_uuid();
    $bodyparts_model_uuid = w4os_gen_uuid();
    $currentoutfit_uuid = w4os_gen_uuid();
    // $myoutfits_uuid = w4os_gen_uuid();
    // $myoutfits_model_uuid = w4os_gen_uuid();
    if ( $result ) {
      $folders = array(
        array('Textures', 0, 1, w4os_gen_uuid(), $inventory_uuid ),
        array('Sounds', 1, 1, w4os_gen_uuid(), $inventory_uuid ),
        array('Calling Cards', 2, 2, w4os_gen_uuid(), $inventory_uuid ),
        array('Landmarks', 3, 1, w4os_gen_uuid(), $inventory_uuid ),
        array('Photo Album', 15, 1, w4os_gen_uuid(), $inventory_uuid ),
        array('Clothing', 5, 3, w4os_gen_uuid(), $inventory_uuid ),
        array('Objects', 6, 1, w4os_gen_uuid(), $inventory_uuid ),
        array('Notecards', 7, 1, w4os_gen_uuid(), $inventory_uuid ),
        array('Scripts', 10, 1, w4os_gen_uuid(), $inventory_uuid ),
        array('Body Parts', 13, 5, $bodyparts_uuid, $inventory_uuid ),
        array('Trash', 14, 1, w4os_gen_uuid(), $inventory_uuid ),
        array('Animations', 20, 1, w4os_gen_uuid(), $inventory_uuid ),
        array('Gestures', 21, 1, w4os_gen_uuid(), $inventory_uuid ),
        array('Lost And Found', 16, 1, w4os_gen_uuid(), $inventory_uuid ),
        array("$model_firstname $model_lastname outfit", -1, 1, $bodyparts_model_uuid, $bodyparts_uuid ),
        array('Current Outfit', 46, 1, $currentoutfit_uuid, $inventory_uuid ),
        // array('My Outfits', 48, 1, $myoutfits_uuid, $inventory_uuid ),
        // array("$model_firstname $model_lastname", 47, 1, $myoutfits_model_uuid, $myoutfits_uuid ),
        // array('Friends', 2, 2, w4os_gen_uuid(), $inventory_uuid ),
        // array('Favorites', 23, w4os_gen_uuid(), $inventory_uuid ),
        // array('All', 2, 1, w4os_gen_uuid(), $inventory_uuid ),
      );
      foreach($folders as $folder) {
        $name = $folder[0];
        $type = $folder[1];
        $version = $folder[2];
        $folderid = $folder[3];
        $parentid = $folder[4];
        if ($result) $result = $w4osdb->insert (
          'inventoryfolders', array (
            'folderName' => $name,
            'type' => $type,
            'version' => $version,
            'folderID' => $folderid,
            'agentID' => $newavatar_uuid,
            'parentFolderID' => $parentid,
          )
        );
        if( !$result ) w4os_notice(__("Error while adding folder $folder", 'w4os'), 'fail');
        if( ! $result ) break;
      }
    }

    // if ( $result ) {
    //   $result = $w4osdb->get_results("SELECT folderName,type,version FROM inventoryfolders WHERE agentID = '$model_uuid' AND type != 8");
    //   if($result) {
    //     foreach($result as $row) {
    //       $result = $w4osdb->insert (
    //         'inventoryfolders', array (
    //           'folderName' => $row->folderName,
    //           'type' => $row->type,
    //           'version' => $row->version,
    //           'folderID' => w4os_gen_uuid(),
    //           'agentID' => $newavatar_uuid,
    //           'parentFolderID' => $inventory_uuid,
    //         )
    //       );
    //       if( ! $result ) break;
    //     }
    //   }
    // }

    if ( $result ) {
      $model_exist = $w4osdb->get_results("SELECT Name, Value FROM Avatars WHERE PrincipalID = '$model_uuid'");
      // w4os_notice(print_r($result, true), 'code');
      // foreach($result as $row) {
      //   w4os_notice(print_r($row, true), 'code');
      //   w4os_notice($row->Name . " = " . $row->Value);
      // }

      // foreach($avatars_prefs as $var => $val) {
      if($model_exist) {
        foreach($result as $row) {
          unset($newitem);
          unset($newitems);
          unset($newvalues);
          $Name = $row->Name;
          $Value = $row->Value;
          if (strpos($Name, 'Wearable') !== FALSE) {
            // Must add a copy of item in inventory
            $uuids = explode(":", $Value);
            $item = $uuids[0];
            $asset = $uuids[1];
            $destinventoryid = $w4osdb->get_var("SELECT inventoryID FROM inventoryitems WHERE assetID='$asset' AND avatarID='$newavatar_uuid'");
            if(!$destitem) {
              $newitem = $w4osdb->get_row("SELECT * FROM inventoryitems WHERE assetID='$asset' AND avatarID='$model_uuid'", ARRAY_A);
              $destinventoryid = w4os_gen_uuid();
              $newitem['inventoryID'] = $destinventoryid;
              $newitems[] = $newitem;
              $Value = "$destinventoryid:$asset";
            }
          } else if (strpos($Name, '_ap_') !== FALSE) {
            $items = explode(",", $Value);
            foreach($items as $item) {
              $asset = $w4osdb->get_var("SELECT assetID FROM inventoryitems WHERE inventoryID='$item'");
              $destinventoryid = $w4osdb->get_var("SELECT inventoryID FROM inventoryitems WHERE assetID='$asset' AND avatarID='$newavatar_uuid'");
              if(!$destitem) {
                $newitem = $w4osdb->get_row("SELECT * FROM inventoryitems WHERE assetID='$asset' AND avatarID='$model_uuid'", ARRAY_A);
                $destinventoryid = w4os_gen_uuid();
                $newitem['inventoryID'] = $destinventoryid;
                // $Value = $destinventoryid;
                $newitems[] = $newitem;
                $newvalues[] = $destinventoryid;
              }
            }
            if($newvalues) $Value = implode(",", $newvalues);
          }
          if(!empty($newitems)) {
            foreach($newitems as $newitem) {
              // $destinventoryid = w4os_gen_uuid();
              // w4os_notice("Creating inventory item '$Name' for $firstname");
              $newitem['parentFolderID'] = $bodyparts_model_uuid;
              $newitem['avatarID'] = $newavatar_uuid;
              $result = $w4osdb->insert ('inventoryitems', $newitem);
              if( !$result ) w4os_notice(__("Error while adding inventory item", 'w4os'), 'fail');
              // w4os_notice(print_r($newitem, true), 'code');
              // echo "<pre>" . print_r($newitem, true) . "</pre>"; exit;

              // Adding aliases in "Current Outfit" folder to avoid FireStorm error message
              $outfit_link=$newitem;
              $outfit_link['assetType']=24;
              $outfit_link['assetID']=$newitem['inventoryID'];
              $outfit_link['inventoryID'] = w4os_gen_uuid();
              $outfit_link['parentFolderID'] = $currentoutfit_uuid;
              $result = $w4osdb->insert ('inventoryitems', $outfit_link);
              if( !$result ) w4os_notice(__("Error while adding inventory outfit link", 'w4os'), 'fail');
            }
          // } else {
          //   w4os_notice("Not creating inventory item '$Name' for $firstname");
          }
          $result = $w4osdb->insert (
            'Avatars', array (
              'PrincipalID' => $newavatar_uuid,
              'Name' => $Name,
              'Value' => $Value,
            )
          );
          if( !$result ) w4os_notice(__("Error while adding avatar", 'w4os'), 'fail');
        }
      }
    }

    if( ! $result ) {
      // w4os_notice(__("Errors occurred while creating the user", 'w4os'), 'fail');
      // w4os_notice($sql, 'code');
      return false;
    }

    w4os_notice(sprintf( __( 'Avatar %s created successfully.', 'w4os' ), "$firstname $lastname"), 'success');

    $check_uuid = w4os_profile_sync($user); // refresh opensim data for this user
    return $newavatar_uuid;
    // $result = $w4osdb->get_var("$sql");

    //   $uuid = w4os_profile_sync($user); // refresh opensim data for this user
    break;

    default:
    w4os_notice(sprintf( __( 'Action %s not implemented', 'w4os' ), $params['action']), 'fail');
  }
  // show_message ("<pre>" . print_r($_REQUEST, true) . "</pre>");
  // show_message ( "Updating user" );
}

function w4os_profile_display( $user ) {
  if($user->ID == 0) {
    $wp_login_url=wp_login_url();
    // $content =  "<p class='avatar not-connected'>" . sprintf(__("%sLog in%s to choose an avatar.", 'w4os'), "<a href='$wp_login_url$wp_login_url'>", "</a>") ."</p>";
    $content = "<div class=w4os-login>" . wp_login_form([ 'echo' => false ]) . "</div>";
    return $content;
  }

  global $w4osdb;
  $avatar = new W4OS_Avatar($user->ID);

  ####
  ## TODO: Check if user is current user
  ## Otherwise, do not allow edit, and display profile only if public
  ####

  if ( isset($_REQUEST['w4os_update_avatar'] ) ) {
    $uuid = w4os_update_avatar( $user, array(
      'action' => sanitize_text_field($_REQUEST['action']),
  		'w4os_firstname' => sanitize_text_field($_REQUEST['w4os_firstname']),
  		'w4os_lastname' => sanitize_text_field($_REQUEST['w4os_lastname']),
  		'w4os_model' => sanitize_text_field($_REQUEST['w4os_model']),
  		'w4os_password_1' => $_REQUEST['w4os_password_1'],
    ));
    $avatar = new W4OS_Avatar($user->ID);
  // } else {
  //   if(! $avatar->UUID) echo "<p class='avatar not-created'>" . __("You have no grid account yet. Fill the form below to create your avatar.", 'w4os') . "</p>";
  }

  if ($avatar->UUID) {
    $action = 'w4os_update_avatar';
    $leaveblank= " (" . __('leave blank to leave unchanged', "w4os") . ")";
    $content.= sprintf(
      '<div class=profile><div class=profile-pic>%1$s</div><div class=profile-details>%2$s</div></div>',
      $avatar->profile_picture(),
      $avatar->AvatarName,
    );
    return $content;
  }
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

    echo "<p class='avatar not-created'>" . __("You have no grid account yet. Fill the form below to create your avatar.", 'w4os') . "</p>";

    $content="
    <form class='woocommerce-EditAccountForm edit-account wrap' action='' method='post'>";
    $action = 'w4os_create_avatar';

    $firstname = sanitize_text_field(preg_replace("/[^[:alnum:]]/", "", (isset($_REQUEST['w4os_firstname'])) ? $_REQUEST['w4os_firstname'] : get_user_meta( $user->ID, 'first_name', true )));
    $lastname  = sanitize_text_field(preg_replace("/[^[:alnum:]]/", "", (isset($_REQUEST['w4os_lastname']))  ? $_REQUEST['w4os_lastname']  : get_user_meta( $user->ID, 'last_name', true )));

    $content .= "<p class=description>" . __('Choose your avatar name below. This is how people will see you in-world. Once the avatar is created, it cannot be changed.', 'w4os') . "</p>";

    $content .= "
      <div class='clear'></div>

      <p class='woocommerce-form-row woocommerce-form-row--first form-row form-row-first'>
    		<label for='w4os_firstname'>" . __("Avatar first name", "w4os") . "&nbsp;<span class='required'>*</span></label>
    		<input type='text' class='woocommerce-Input woocommerce-Input--text input-text' name='w4os_firstname' id='w4os_firstname' autocomplete='given-name' value='" . esc_attr($firstname) . "' required>
    	</p>
    	<p class='woocommerce-form-row woocommerce-form-row--last form-row form-row-last'>
    		<label for='w4os_lastname'>" . __("Avatar last name", "w4os") . "&nbsp;<span class='required'>*</span></label>
    		<input type='text' class='woocommerce-Input woocommerce-Input--text input-text' name='w4os_lastname' id='w4os_lastname' autocomplete='family-name' value='" . esc_attr($lastname) . "' required>
    	</p>
      <div class='clear'></div>
      ";

      // <p class='woocommerce-form-row woocommerce-form-row--wide form-row form-row-wide'>
      // <label for='w4os_password_1'>" . __('New password') . "$leaveblank</label>
      // <span class='password-input'><input type='password' class='woocommerce-Input woocommerce-Input--password input-text' name='w4os_password_1' id='w4os_password_1' autocomplete='off' required><span class='show-password-input'></span></span>
      // <span class=description>" . __("The password to log in-world is the same as your password on this website.", "w4os") . "</span>
      // </p>
      $content.= "
      <p class=description>" . __('Your in-world Avatar password is the same as your password on this website', 'w4os') . "</p>
      <p class='woocommerce-form-row woocommerce-form-row--wide form-row form-row-wide'>
        <label for='w4os_password_1'>" . __('Confirm your password', 'w4os') . "</label>
        <span class='password-input'><input type='password' class='woocommerce-Input woocommerce-Input--password input-text' name='w4os_password_1' id='w4os_password_1' autocomplete='off' required><span class='show-password-input'></span></span>
      </p>
      ";

      $models=$w4osdb->get_results("SELECT FirstName, LastName, profileImage, profileAboutText
        FROM UserAccounts, userprofile
        WHERE PrincipalID = userUUID
        AND (FirstName = '" . get_option('w4os_model_firstname') . "'
        OR LastName = '" . get_option('w4os_model_lastname') . "')
        ORDER BY FirstName, LastName
        ");
      if($models) {
        $content.= "<div class='clear'></div>";
        $content.= "<div class=form-row>";
        $content .= "<label>" . __('Your avatar', 'w4os') . "</label>";
        $content .= "<p class=description>" . __('You can change and customize it in-world, as often as you want.', 'w4os') . "</p>";
        $content .= "
        <p class='field-model woocommerce-form-row woocommerce-form-row--wide form-row form-row-wide'>";
        foreach($models as $model) {
          $model_name = $model->FirstName . " " . $model->LastName;
          $model_display_name = $model_name;
          if(get_option('w4os_model_firstname') != "")
          $model_display_name = preg_replace('/ *' . get_option('w4os_model_firstname'). ' */', '', $model_display_name);
          if(get_option('w4os_model_lastname') != "")
          $model_display_name = preg_replace('/ *' . get_option('w4os_model_lastname'). ' */', '', $model_display_name);
          $model_display_name = preg_replace('/(.*) *Ruth2 *(.*)/', '\1 \2 <span class="r2">Ruth 2.0</span>', $model_display_name);
          $model_display_name = preg_replace('/(.*) *Roth2 *(.*)/', '\1 \2 <span class="r2">Roth 2.0</span>', $model_display_name);

          // if($model->profileImage != W4OS_NULL_KEY)
          // $model_img =  "<img src='/assets/asset.php?id=" . $model->profileImage ."'>";
          if(!empty(W4OS_WEB_ASSETS_SERVER_URI)) $model_img =  "<img class='model-picture' src='" . W4OS_WEB_ASSETS_SERVER_URI . $model->profileImage ."'>";
          if(empty($model_img)) $modelclass="no_picture";
          else $modelclass = "with_picture";
          if($model_name == W4OS_DEFAULT_AVATAR) $checked = " checked"; else $checked="";

          $content .= "
          <label class='$modelclass'>
            <input type='radio' name='w4os_model' value='$model_name'$checked>
            <span class=model-name>$model_display_name</span>
            $model_img
          </label>";
        }
        $content.= "
        </p>";
        $content.= "</div>";
      }

      // if ($uuid) $content.="    	</fieldset>";

      $content .= "
      <p>
      <input type='hidden' name='action' value='$action'>
      <button type='submit' class='woocommerce-Button button' name='w4os_update_avatar' value='$action'>" . __("Save") . "</button>
      </p>";
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
  if(! W4OS_DB_CONNECTED) return;
  global $pagenow;
  if ( in_array( $pagenow, array( 'post.php', 'post-new.php', 'admin-ajax.php', '' ) ) || get_post_type() == 'post' ) return;
  if ( wp_is_json_request()) return;

	function w4os_profile_shortcode($atts = [], $content = null)
	{
    $content .= w4os_profile_display(wp_get_current_user());
    return "<div class='w4os-shortcode w4os-shortcode-profile'>" . $content . "</div>";
		return $content;
	}
  add_shortcode('w4os_profile', 'w4os_profile_shortcode');
	add_shortcode('gridprofile', 'w4os_profile_shortcode');
}
add_action('init', 'w4os_profile_shortcodes_init');
