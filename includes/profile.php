<?php if ( ! defined( 'W4OS_PLUGIN' ) ) die;
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
    $this->AvatarName = esc_attr(get_the_author_meta( 'w4os_avatarname', $id ));
    $this->AvatarSlug = strtolower($this->FirstName) . "." . strtolower($this->LastName);
    $this->AvatarHGName = strtolower($this->FirstName) . "." . strtolower($this->LastName) . "@" . esc_attr(get_option('w4os_login_uri'));
    $this->ProfilePictureUUID = get_the_author_meta( 'w4os_profileimage', $id );
    if(empty($this->ProfilePictureUUID)) $this->ProfilePictureUUID = W4OS_NULL_KEY;
  }

  public function profile_picture( $echo = false ) {
    $html = w4os_get_avatar( $this->ID, 256 );

    if($echo) echo $html;
    else return $html;
  }

  public function profile_page( $echo = false, $args=array() ) {
    global $wpdb, $w4osdb;

    $content = '';
    $can_list_users = (current_user_can( 'list_users' ) ) ? 'true' : 'false';
    $current_user_email = wp_get_current_user()->user_email;
    // Should not fetch this again, it should be saved in _construct, TO CHECK
    $this->UUID = esc_attr(get_the_author_meta( 'w4os_uuid', $this->ID ));

    $avatar_query = "SELECT *
      FROM UserAccounts LEFT JOIN userprofile ON PrincipalID = userUUID
      WHERE active = 1 AND Email != ''
      AND ( profileAllowPublish = 1 OR $can_list_users OR Email = '$current_user_email')
      AND PrincipalID = '$this->UUID';";

    $avatar_row=$w4osdb->get_row($avatar_query);

    if(is_object($avatar_row)) {
      $keys = array('FirstName' =>NULL, 'LastName' =>NULL, 'profileImage' =>NULL, 'profileAboutText'=>NULL );
      // $keys = array_combine($keys, $keys);
      // $avatar_array=(array)$avatar_row;
      if(!w4os_empty($avatar_row->profileImage)) $avatar_row->profileImageHtml = '<img src=' . w4os_get_asset_url($avatar_row->profileImage) . '>';

      $profile=array_filter(array(
        __('Avatar Name', 'w4os') => $avatar_row->FirstName . " " . $avatar_row->LastName,
        // __('HG Name', 'w4os') => $avatar_row->HGName, // To implement
        // __('Avatar Display Name', 'w4os') => $avatar_row->DisplayName, // To implement
        __('Profile picture', 'w4os') => $avatar_row->profileImageHtml,
        __('Born', 'w4os') => sprintf('%s (%s days)',
        wp_date(get_option( 'date_format' ), $avatar_row->Created),
        floor((current_time('timestamp') - $avatar_row->Created) / 24 / 3600 )),
        __('About', 'w4os') => $avatar_row->profileAboutText,
        __('Languages', 'w4os') => $avatar_row->profileLanguages,
        __('Skills', 'w4os') => $avatar_row->profileSkillsText,
      ));

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
      $content .= w4os_array2table($profile, 'avatar-profile-table' );

    } else {
      return false;
    }
    if($echo) echo $content;
    else return $content;
  }
}

function w4os_profile_dereference($user_or_id) {
  if(!W4OS_DB_CONNECTED) return;
  global $w4osdb;

  if(is_numeric($user_or_id)) $user = get_user_by('ID', $user_or_id);
  else $user = $user_or_id;
  if(!is_object($user)) return;

  delete_user_meta( $user->ID, 'w4os_uuid' );
  delete_user_meta( $user->ID, 'w4os_firstname' );
  delete_user_meta( $user->ID, 'w4os_lastname' );
  delete_user_meta( $user->ID, 'w4os_avatarname' );
  delete_user_meta( $user->ID, 'w4os_profileimage' );
  $roles = (array)$user->roles;
  if(count($roles)==1) $user->add_role(get_option('default_role'));
  $user->remove_role('grid_user');
}

/**
 * Sync avatar info from OpenSimulator
 * @param  object $user_or_id   user object or user id
 * @param  key    $uuid         if set, create link with avatar and update info
 *                              if not set, update avatar info if link exists
 * @return object       [description]
 */
function w4os_profile_sync($user_or_id, $uuid = NULL) {
  if(!W4OS_DB_CONNECTED) return;
  global $w4osdb;


  if(is_numeric($user_or_id)) $user = get_user_by('ID', $user_or_id);
  else $user = $user_or_id;
  if(!is_object($user)) return;


  if(w4os_empty($uuid)) {
    $condition = "Email = '$user->user_email'";
  } else {
    $condition = "PrincipalID = '$uuid'";
  }
  $avatars=$w4osdb->get_results("SELECT PrincipalID, FirstName, LastName, profileImage, profileAboutText
    FROM UserAccounts LEFT JOIN userprofile ON PrincipalID = userUUID
    WHERE active = 1 AND $condition"
  );
  if(empty($avatars)) return false;
  $avatar_row = array_shift($avatars);
  if(w4os_empty($uuid)) $uuid = $avatar_row->PrincipalID;

  if(w4os_empty($uuid)) {
    w4os_profile_dereference($user);
    return false;
  }

  $user->add_role('grid_user');

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
  update_user_meta( $user->ID, 'w4os_avatarname', trim($avatar_row->FirstName . ' ' . $avatar_row->LastName) );
  update_user_meta( $user->ID, 'w4os_profileimage', $avatar_row->profileImage );
  return $uuid;
}


function w4os_profile_sync_all() {
  global $wpdb;
  global $w4osdb;

  $updated = array();
  $UserAccounts=$w4osdb->get_results("SELECT PrincipalID, FirstName, LastName, profileImage, profileAboutText, Email
    FROM UserAccounts LEFT JOIN userprofile ON PrincipalID = userUUID
    WHERE active = 1
    ");
  foreach($UserAccounts as $UserAccount) {
    $user = get_user_by( 'email', $UserAccount->Email );
    if(!$user) continue;
    $uuid = w4os_profile_sync($user);
    $updated[$UserAccount->Email] = $user->ID . ' ' . $uuid;
  }
  w4os_admin_notice(sprintf(__('%s local users updated with avatar data', 'w4os'), count($updated)), 'success');
  return;
}

/**
 * Avatar fields for WP user profile page
 * @param  [type] $user
 */
function w4os_profile_fields( $user ) {
  if(!W4OS_DB_CONNECTED) return;
  // echo "checkpoint"; die();
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
  $avatar = new W4OS_Avatar($user_id);
  $uuid = w4os_profile_sync($avatar); // refresh opensim data for this user

	// not verified
	if($_REQUEST['password_1'] == $_REQUEST['password_2'])
	w4os_set_avatar_password( $user_id, $_REQUEST['password_1'] );
}
add_action('save_account_details', 'w4os_save_account_details', 10, 1);
// add_action('profile_update', 'w4os_save_account_details', 10, 1);


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

function w4os_user_register( $user_id = 0 ) {
  if ( isset($_REQUEST['email']) &! empty($_REQUEST['email']) ) {
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
  } else {
    $user = get_user_by('ID', $user_id);
    $uuid = w4os_profile_sync($user); // refresh opensim data for this user
  }
}
add_action( 'user_register', 'w4os_user_register', 10, 1);


// function w4os_debug_log($string) {
//   file_put_contents ( "../tmp/w4os_debug.log", $string . "\n", FILE_APPEND );
// }

function w4os_update_avatar( $user, $params ) {
  global $w4osdb;
  $uuid = (empty($params['w4os_firstname'])) ? NULL : $params['w4os_firstname'];
  if(!W4OS_DB_CONNECTED) return;
  $errors = false;
  switch ($params['action'] ) {
    case "update_avatar":
    case "w4os_create_avatar":
    // $uuid = w4os_profile_sync($user); // refresh opensim data for this user
    $uuid = w4os_create_avatar($user, $params);
    break;
  }

  if($uuid) {
    $user->add_role('grid_user');
    if(isset($params['opensim_profileAllowPublish'])) {
      $profileAllowPublish = ($params['opensim_profileAllowPublish']) ? 1 : 0;
      $w4osdb->replace(
        'userprofile',
        array(
          'useruuid' => $uuid,
          'profileAllowPublish' => $profileAllowPublish,
          // 'profileMaturePublish' => $profileMaturePublish,
          // 'profileURL' => w4os_get_profile_url($user),
        ),
      );
      $w4osdb->query($w4osdb->prepare($query));
    }
  }
  return $uuid;
}

function w4os_create_avatar( $user, $params ) {
  global $w4osdb;
  // w4os_notice(print_r($_REQUEST, true), "code");
  $uuid = w4os_profile_sync($user); // refresh opensim data for this user
  if ( $uuid ) {
    if($params['action'] == 'w4os_create_avatar') w4os_notice(__("This user already has an avatar.", 'w4os'), 'fail') . '<pre>' . print_r($params, true) . '</pre>';
    return $uuid;
  }
  // echo  "<pre>" . $user->user_pass . "\n" . print_r($user, true) . "</pre>";

  $firstname = trim($params['w4os_firstname']);
  $lastname = trim($params['w4os_lastname']);
  $model = trim($params['w4os_model']);
  if (empty($model)) $model = W4OS_DEFAULT_AVATAR;

  // Check required fields
  if ( ! $firstname ) { $errors=true; w4os_notice(__("First name required", "w4os"), 'fail') ; }
  if ( ! $lastname ) { $errors=true; w4os_notice(__("Last name required", 'w4os'), 'fail') ; }
  if( ! current_user_can( 'edit_user', $user->ID )) {
    if ( ! $params['w4os_password_1'] ) { $errors=true; w4os_notice(__("Password required", 'w4os'), 'error') ; }
    else if ( ! wp_check_password($params['w4os_password_1'], $user->user_pass)) { $errors=true; w4os_notice(__("The password does not match.", 'w4os'), 'error') ; }
  }
  if ( $errors == true ) return false;
  $required=array('w4os_firstname', 'w4os_lastname', 'w4os_password_1');

  $password=stripcslashes($params['w4os_password_1']);
  // if ( ! w4os_is_strong ($password)) return false; // We now only rely on WP password requirements

  if (in_array(strtolower($firstname), array_map('strtolower', W4OS_DEFAULT_RESTRICTED_NAMES))) {
    w4os_notice(sprintf( __( 'The name %s is not allowed', 'w4os' ), "$firstname"), 'error');
    return false;
  }
  if (in_array(strtolower($lastname), array_map('strtolower', W4OS_DEFAULT_RESTRICTED_NAMES))) {
    w4os_notice(sprintf( __( 'The name %s is not allowed', 'w4os' ), "$lastname"), 'error');
    return false;
  }

  if(! preg_match("/^[a-zA-Z0-9]*$/", $firstname.$lastname)) {
    w4os_notice(__( 'Names can only contain alphanumeric characters', 'w4os' ), 'error');
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
}

function w4os_avatar_creation_form ($user) {
  if(!W4OS_DB_CONNECTED) return;
  if($user != wp_get_current_user()) return;

  global $w4osdb;

  $content = "<p class='avatar not-created'>" . __("You have no grid account yet.", 'w4os') . "</p>";

  $content .= "<form class='edit-account wrap' action='' method='post'>";
  $action = 'w4os_create_avatar';

  $firstname = sanitize_text_field(preg_replace("/[^[:alnum:]]/", "", (isset($_REQUEST['w4os_firstname'])) ? $_REQUEST['w4os_firstname'] : get_user_meta( $user->ID, 'first_name', true )));
  $lastname  = sanitize_text_field(preg_replace("/[^[:alnum:]]/", "", (isset($_REQUEST['w4os_lastname']))  ? $_REQUEST['w4os_lastname']  : get_user_meta( $user->ID, 'last_name', true )));

  $content .= "<p>" . __('Choose your avatar name below. This is how people will see you in-world. Once the avatar is created, the name cannot be changed.', 'w4os') . "</p>";

  $content .= "
  <div class='clear'></div>
  <p class='form-row form-row-first'>
    <label for='w4os_firstname'>" . __("Avatar first name", "w4os") . "&nbsp;<span class='required'>*</span></label>
    <input type='text' class='input-text' name='w4os_firstname' id='w4os_firstname' autocomplete='given-name' value='" . esc_attr($firstname) . "' required>
  </p>
  <p class='form-row form-row-last'>
    <label for='w4os_lastname'>" . __("Avatar last name", "w4os") . "&nbsp;<span class='required'>*</span></label>
    <input type='text' class='input-text' name='w4os_lastname' id='w4os_lastname' autocomplete='family-name' value='" . esc_attr($lastname) . "' required>
  </p>
  <div class='clear'></div>
  <p class=description>" . __('Your in-world Avatar password is the same as your password on this website', 'w4os') . "</p>
  <p class='form-row form-row-wide'>
    <label for='w4os_password_1'>" . __('Confirm your password', 'w4os') . "</label>
    <span class='password-input'><input type='password' class='input-text' name='w4os_password_1' id='w4os_password_1' autocomplete='off' required><span class='show-password-input'></span></span>
  </p>";

  $models=$w4osdb->get_results("SELECT FirstName, LastName, profileImage, profileAboutText
    FROM UserAccounts, userprofile
    WHERE PrincipalID = userUUID
    AND (FirstName = '" . get_option('w4os_model_firstname') . "'
    OR LastName = '" . get_option('w4os_model_lastname') . "')
    ORDER BY FirstName, LastName"
  );
  if($models) {
    $content.= "<div class='clear'></div>
    <div class=form-row>
      <label>" . __('Your avatar', 'w4os') . "</label>
      <p class=description>" . __('You can change and customize it in-world, as often as you want.', 'w4os') . "</p>
      <p class='field-model'>";
    foreach($models as $model) {
      $model_name = $model->FirstName . " " . $model->LastName;
      $model_display_name = $model_name;
      if(get_option('w4os_model_firstname') != "")
      $model_display_name = preg_replace('/ *' . get_option('w4os_model_firstname'). ' */', '', $model_display_name);
      if(get_option('w4os_model_lastname') != "")
      $model_display_name = preg_replace('/ *' . get_option('w4os_model_lastname'). ' */', '', $model_display_name);
      $model_display_name = preg_replace('/(.*) *Ruth2 *(.*)/', '\1 \2 <span class="r2">Ruth 2.0</span>', $model_display_name);
      $model_display_name = preg_replace('/(.*) *Roth2 *(.*)/', '\1 \2 <span class="r2">Roth 2.0</span>', $model_display_name);

      if(!empty(W4OS_WEB_ASSETS_SERVER_URI) &! empty($model->profileImage)) $model_img =  "<img class='model-picture' src='" . w4os_get_asset_url($model->profileImage) ."'>";
      if(empty($model_img)) $modelclass="no-picture";
      else $modelclass = "with-picture";
      if($model_name == W4OS_DEFAULT_AVATAR) $checked = " checked"; else $checked="";

      $content .= "
      <label class='model $modelclass'>
      <input type='radio' name='w4os_model' value='$model_name'$checked>
      <span class=model-name>$model_display_name</span>
      $model_img
      </label>";
    }
  }
  $content.= "</p>";
  $content.= "</div>";

  $content .= "
  <p>
    <input type='hidden' name='action' value='$action'>
    <button type='submit' class='woocommerce-Button button' name='w4os_update_avatar' value='$action'>" . __("Save") . "</button>
  </p>";

  $content .= "  </form>";
  return $content;
}

function w4os_gridprofile_html($atts=[], $args=[] ) {
  if(!W4OS_DB_CONNECTED) return;
  return w4os_profile_display(wp_get_current_user(), $args);
}

function w4os_profile_display( $user, $args=[] ) {
  if(!W4OS_DB_CONNECTED) return;
  extract($args);
  if(!isset($content)) $content='';
  if(!isset($before_title)) $before_title='';
  if(!isset($after_title)) $after_title='';
  if(!isset($title)) $title='';

  if($user->ID == 0) {
    $wp_login_url=wp_login_url();
    // $content =  "<p class='avatar not-connected'>" . sprintf(__("%sLog in%s to choose an avatar.", 'w4os'), "<a href='$wp_login_url$wp_login_url'>", "</a>") ."</p>";
    $content = "<div class=w4os-login>" . wp_login_form([ 'echo' => false ]) . "</div>";
    return $content;
  }

  global $w4osdb;
  extract($args);
  $avatar = new W4OS_Avatar($user->ID);

  if ( isset($_REQUEST['w4os_update_avatar'] ) ) {
    $uuid = w4os_update_avatar( $user, array(
      'action' => sanitize_text_field($_REQUEST['action']),
  		'w4os_firstname' => sanitize_text_field($_REQUEST['w4os_firstname']),
  		'w4os_lastname' => sanitize_text_field($_REQUEST['w4os_lastname']),
  		'w4os_model' => sanitize_text_field($_REQUEST['w4os_model']),
  		'w4os_password_1' => $_REQUEST['w4os_password_1'],
    ));
    $avatar = new W4OS_Avatar($user->ID);
  }

  if ($avatar->UUID) {
    $action = 'w4os_update_avatar';
    $leaveblank= " (" . __('leave blank to leave unchanged', "w4os") . ")";
    $content.= sprintf(
      '<div class=profile><div class=profile-pic>%1$s</div><div class=profile-details>%2$s</div></div>',
      $avatar->profile_picture(),
      $avatar->AvatarName,
    );
    // return $content;
  } else {
    $content= w4os_avatar_creation_form ($user);
  }
  if(!empty($content)) {
    $content = $before_title . $title . $after_title . $content;
  }
  return $content;
}


function w4os_profile_shortcodes_init()
{
  if(!W4OS_DB_CONNECTED) return;
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

function w4os_render_asset($image_uuid, $size = 256, $default = '', $alt = '', $args = NULL ) {
  if(w4os_empty($image_uuid)) return;
  return sprintf(
    '<img src="%1$s" class="asset asset-%3$d %4$s" alt="%2$s" loading="lazy" width="%3$d" height="%3$d">',
    w4os_get_asset_url($image_uuid),
    (empty($alt)) ? 'OpenSimulator asset' : $alt,
    $size,
    $args['class'],
  );
}

function w4os_get_avatar( $user_id, $size = 96, $default = '', $alt = '', $args = NULL ) {
  if(!W4OS_DB_CONNECTED) return;
  $image_uuid = get_the_author_meta( 'w4os_profileimage', $user_id );
  if ( empty($image_uuid) ) return false;
  if ( $image_uuid === W4OS_NULL_KEY ) return false;
  $args['class'] = "avatar avatar-$size photo";
  if(empty($alt)) $alt = 'avatar profile picture';
  return w4os_render_asset($image_uuid, $size, $default, $alt, $args);
  return sprintf(
    '<img src="%1$s" class="avatar avatar-%3$d photo" alt="%2$s" loading="lazy" width="%3$d" height="%3$d">',
    w4os_get_asset_url($image_uuid),
    'avatar profile picture',
    $size
  );
}

if(W4OS_DB_CONNECTED)
add_filter( 'get_avatar', 'w4os_get_avatar_filter', 10, 6 );
function w4os_get_avatar_filter( $avatar, $user_id, $size, $default, $alt, $args=array() ) {
  //If is email, try and find user ID
  if( ! is_numeric( $user_id ) && is_email( $user_id ) ){
    $user  =  get_user_by( 'email', $user_id );
    if( $user ){
      $user_id = $user->ID;
    }
  }
  if( ! is_numeric( $user_id ) ){
    return $avatar;
  }
  if($args['force_default']) return $avatar;

  $avatar_opensim = w4os_get_avatar( $user_id, $size, $default, $alt );
  if( $avatar_opensim ) return $avatar_opensim;
  return $avatar;
}

if(W4OS_DB_CONNECTED)
add_action( 'init', 'w4os_gridprofile_block_init' );
function w4os_gridprofile_block_init() {
	w4os_block_init('gridprofile', 'Grid profile');
}

function w4os_gridprofile_block_render($args=[], $dumb="", $block_object=[]) {
  if(!W4OS_DB_CONNECTED) return;
	$args = (array) $block_object;
	$args['before_title'] = '<h4>';
	$args['after_title'] = '</h4>';
	$args['title'] = __('Grid profile', 'w4os');
	return sprintf(
		'<div>%s</div>',
		w4os_gridprofile_html($atts, $args )
	);
}

function w4os_get_profile_url($user_or_id) {
  if(get_option('w4os_profile_page') != 'provide') return;
  if(is_numeric($user_or_id)) $user = get_user_by('ID', $user_or_id);
  else $user = $user_or_id;
  if(!is_object($user)) return;

  $slug = sanitize_title(get_the_author_meta( 'w4os_firstname', $user->ID )) . '.' . sanitize_title(get_the_author_meta( 'w4os_lastname', $user->ID ));
  if(!empty($slug)) return get_home_url(NULL, get_option('w4os_profile_slug') . '/' . $slug);
}

if(get_option('w4os_profile_page')=='provide') {
  require_once(__DIR__ . '/profile-page.php');
}

add_action( 'edit_user_profile', 'w4os_keeproles_profile_loading', 10, 1 );
add_action( 'show_user_profile', 'w4os_keeproles_profile_loading', 10, 1 );
function w4os_keeproles_profile_loading( $user ) {
  $trans = get_transient( 'saved_user_roles' );
  if ( empty( $trans ) ) return; // No data to act on, return.
  $uid = $user->ID;
  if ( empty( $trans[ $uid ] ) ) return;
  $roles = $trans[ $uid ];

  if ( in_array( 'grid_user', $roles ) ) $user->add_role( 'grid_user' );

  $trans = get_transient( 'saved_user_roles' );
  unset( $trans[ $uid ] );

  if ( empty( $trans ) ) {
    delete_transient( 'saved_user_roles' );
  } else {
    set_transient( 'saved_user_roles', $trans );
  }
}
add_action( 'user_profile_update_errors', 'w4os_keeproles_profile_updating', 10, 3 );
function w4os_keeproles_profile_updating( &$errors, $update, &$user ) {
  if ( !$update ) return;
  if ( $errors->has_errors() ) return;
  $uid		 = $user->ID;
  $user_temp	 = new WP_User( $uid );
  $trans = get_transient( 'saved_user_roles' );
  if ( empty( $trans ) ) $trans = array();
  $trans[ $uid ] = $user_temp->roles;
  set_transient( 'saved_user_roles', $trans);
}


add_action( 'show_user_profile', 'w4os_user_profile_fields' );
add_action( 'edit_user_profile', 'w4os_user_profile_fields' );
function w4os_user_profile_fields($user) {
  global $pagenow;
  if($pagenow != 'user-edit.php' && $pagenow != 'profile.php') return;

  if(!$user) return;

  $has_avatar = ! w4os_empty(esc_attr(get_the_author_meta( 'w4os_uuid', $user->ID )));
  $avatar = new W4OS_Avatar($user);
  $profile_settings = array(
    // 'profile.php' => array(
      'sections' => array(
        'opensimulator' => array(
          'label' => __('OpenSimulator', 'w4os'),
          'fields' => array(
            'w4os_uuid' => array(
              'type' => 'string',
              'label' => 'UUID',
              'value' => esc_attr(get_the_author_meta( 'w4os_uuid', $user->ID )),
              'disabled' => true,
            ),
            'w4os_firstname' => array(
              'type' => 'string',
              'label' => 'Avatar First Name',
              'value' => esc_attr(get_the_author_meta( 'w4os_firstname', $user->ID )),
              'readonly' => $has_avatar,
            ),
            'w4os_lastname' => array(
              'type' => 'string',
              'label' => 'Avatar Last Name',
              'value' => esc_attr(get_the_author_meta( 'w4os_lastname', $user->ID )),
              'readonly' => $has_avatar,
            ),
            'w4os_profileimage' => array(
              'type' => 'os_asset',
              'label' => 'Profile Picture',
              'value' => esc_attr(get_the_author_meta( 'w4os_profileimage', $user->ID )),
              'placeholder' => ($has_avatar) ? __('Must be set in the viewer.', 'w4os') : '',
              'readonly' => true,
            ),
            'opensim_profileAllowPublish' => array(
              'type' => 'boolean',
              'label' => __('Public profile', 'w4os'),
            'value' => (get_the_author_meta( 'opensim_profileAllowPublish', $user->ID ) === true),
              'default' => true,
              'description' => __('Make avatar profile public (available in search and on the website).', 'w4os')
              . (($has_avatar) ? sprintf('<p class="description"><a href="%1$s">%1$s</a></p>', w4os_get_profile_url($user) ) : ''),
            )
          ),
        ),
      // ),
    ),
  );
  $settings_pages = array('profile.php' => $profile_settings);

  foreach($settings_pages as $page_slug => $page) {
    foreach($page['sections'] as $section_slug => $section) {
      echo '<h3 id=opensim_profile>' . $section['label'] . '</h3>';
      echo '<table class="form-table">';
      foreach($section['fields'] as $field_slug => $field) {
        echo '<tr><th>' . $field['label'] . '</th><td>';
        $args = array_merge([ 'option_slug' => $field_slug ], $field);
        w4os_settings_field($args, $user);
        echo '</td></tr>';
        // echo '<pre>' . print_r($args, true) . '</pre>';
        //   add_settings_section( $section_slug, (isset($section['name'])) ? $section['name'] : $section_slug, (isset($section['section_callback'])) ? $section['section_callback'] : '', $page_slug );
        //     $field['section'] = $section_slug;
      }
      echo '</table>';
    }
  }
}

add_action( 'personal_options_update', 'w4os_profile_fields_save' );
add_action( 'edit_user_profile_update', 'w4os_profile_fields_save' );
function w4os_profile_fields_save( $user_id ) {
  if ( $user_id != wp_get_current_user()->ID &! current_user_can( 'edit_user', $user_id ) ) {
    return;
  }
  $user = get_user_by('ID', $user_id);
  if(!$user) return;

  $args = array(
    'action' => 'update_avatar',
    'w4os_firstname' => esc_attr($_POST['w4os_firstname']),
    'w4os_lastname' => esc_attr($_POST['w4os_lastname']),
    'opensim_profileAllowPublish' => (esc_attr($_POST['opensim_profileAllowPublish']) == true),
  );

  update_user_meta( $user_id, 'w4os_firstname', $args['w4os_firstname']);
  update_user_meta( $user_id, 'w4os_lastname', $args['w4os_lastname']);
  update_user_meta( $user_id, 'opensim_profileAllowPublish', $args['opensim_profileAllowPublish']);
  // if(!empty($_POST['w4os_firstname']) &! empty($_POST['w4os_lastname']))
  w4os_update_avatar( $user, $args );
}
