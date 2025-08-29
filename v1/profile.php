<?php if ( ! defined( 'W4OS_PLUGIN' ) ) {
	die;}
/**
 * Profile
 *
 * @package GuduleLapointe/w4os
 * @author Olivier van Helden <olivier@van-helden.net>
 */

class W4OS_Avatar extends WP_User {

	public function __construct( $id = 0, $name = '', $site_id = '' ) {
		/**
		 * First get WP_User object
		*
		 * @var [type]
		 */
		if ( $id instanceof WP_User ) {
			$this->init( $id->data, $site_id );
			return;
		} elseif ( is_object( $id ) ) {
			$this->init( $id, $site_id );
			return;
		}

		if ( is_uuid( $id ) ) {
			$uuid = $id;
			$id   = null;
		}
		$args   = array_filter(
			array(
				'id'      => $id,
				'uuid'    => $uuid ?? null,
				'name'    => $name,
				'site_id' => $site_id,
			)
		);
		$avatar = new W4OS3_Avatar( $args );

		if ( $avatar->UUID ) {
			$user = get_user_by( 'email', $avatar->Email );
			$id   = $user->ID;

			$this->ID                 = $user->ID;
			$this->data               = $avatar->get_data();
			$this->UUID               = $avatar->UUID;
			$this->FirstName          = $avatar->FirstName;
			$this->LastName           = $avatar->LastName;
			$this->AvatarName         = $avatar->AvatarName;
			$this->AvatarSlug         = $avatar->AvatarSlug;
			$this->AvatarHGName       = $avatar->AvatarHGName;
			$this->ProfilePictureUUID = $avatar->ProfilePictureUUID;
			return;
		} else {
			return false;
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
			$this->data = new stdClass();
		}

		/**
		 * Add W4OS avatar properties
		*
		 * @var string
		 */
		// $this->avatarName = "Avatar of " . $this->user_email;
		$this->UUID       = esc_attr( get_the_author_meta( 'w4os_uuid', $id ) );
		$this->FirstName  = esc_attr( get_the_author_meta( 'w4os_firstname', $id ) );
		$this->LastName   = esc_attr( get_the_author_meta( 'w4os_lastname', $id ) );
		$this->AvatarName = trim( "$this->FirstName $this->LastName" );
		// $this->Created = esc_attr(get_the_author_meta( 'w4os_created', $id ));
		$this->AvatarSlug         = strtolower( $this->FirstName ) . '.' . strtolower( $this->LastName );
		$this->AvatarHGName       = strtolower( $this->FirstName ) . '.' . strtolower( $this->LastName ) . '@' . esc_attr( get_option( 'w4os_login_uri' ) );
		$this->ProfilePictureUUID = get_the_author_meta( 'w4os_profileimage', $id );
		if ( empty( $this->ProfilePictureUUID ) ) {
			$this->ProfilePictureUUID = W4OS_NULL_KEY;
		}
	}

	public function profile_picture( $echo = false ) {
		$html = w4os_get_avatar( $this->ID, 256 );

		if ( $echo ) {
			echo $html;
		} else {
			return $html;
		}
	}

	public function profile_page( $echo = false, $args = array() ) {
		if ( ! W4OS_DB_CONNECTED ) {
			return __( 'Profiles are not available at the moment.', 'w4os' );
		}

		// TODO: check why UUID is not already set as it should be in _construct
		if ( $this->ID && ! $this->UUID ) {
			$this->UUID = esc_attr( get_the_author_meta( 'w4os_uuid', $this->ID ) );
		}

		$v3avatar = new W4OS3_Avatar( $this->UUID );
		return $v3avatar->profile_page( $echo, $args );
	}
}

/**
 * Avatar fields for WP user profile page
 *
 * @param  [type] $user
 */
function w4os_profile_fields( $user ) {
	if ( ! W4OS_DB_CONNECTED ) {
		return;
	}
	// echo "checkpoint"; die();
	if ( $user->ID != wp_get_current_user()->ID ) {
		return;
	}
	global $w4osdb;
	$uuid = w4os_profile_sync( $user );
	echo '    <h3>' . __( 'OpenSimulator', 'w4os' ) . '</h3>';
	echo '<div class=avatar_profile>';
	// if(!$uuid) {
	echo w4os_profile_display( $user );
	// echo "<p>" . __("No avatar", 'w4os') . "</p>";
	echo '</div>';
}

/**
 * Update avatar password in OpenSimulator
 * Fired when user changes WP account password
 *
 * @param integer $user_id
 * @param string  $new_pass new password
 */
function w4os_set_avatar_password( $user_id, $new_pass ) {
	if ( ! W4OS_DB_CONNECTED ) {
		return;
	}
	global $w4osdb;

	if ( is_object( $user_id ) ) {
		$user    = $user_id;
		$user_id = $user->ID;
	}
	if (
	( $user_id && $new_pass && current_user_can( 'edit_user', $user_id ) )
	|| ( isset( $_REQUEST ) && $_REQUEST['action'] == 'resetpass' && $_REQUEST['pass2'] == $new_pass )
	) {
		$user = get_userdata( $user_id );
		if ( ! $user ) {
			return;
		}
		$uuid     = w4os_profile_sync( $user ); // refresh opensim data for this user
		$password = stripcslashes( $new_pass );
		$salt     = md5( w4os_gen_uuid() );
		$hash     = md5( md5( $password ) . ':' . $salt );
		$w4osdb->update(
			'auth',
			array(
				'passwordHash' => $hash,
				'passwordSalt' => $salt,
				// 'webLoginKey' => W4OS_NULL_KEY,
			),
			array(
				'UUID' => $uuid,
			)
		);
	}
}

add_action( 'password_reset', 'w4os_set_avatar_password', 10, 2 );

/**
 * Catch password change from user profile page and save it to OpenSimulator
 *
 * @param  [type] $args [description]
 */
function w4os_save_account_details( $args ) {
	if ( ! W4OS_DB_CONNECTED ) {
		return;
	}
	$avatar = new W4OS_Avatar( $user_id );
	$uuid   = w4os_profile_sync( $avatar ); // refresh opensim data for this user

	// not verified
	if ( $_REQUEST['password_1'] == $_REQUEST['password_2'] ) {
		w4os_set_avatar_password( $user_id, $_REQUEST['password_1'] );
	}
}
add_action( 'save_account_details', 'w4os_save_account_details', 10, 1 );
// add_action('profile_update', 'w4os_save_account_details', 10, 1);

/**
 * Catch password change and save it to OpenSimulator
 * I don't remember when this is fired
 *
 * @param  integer $user_id
 */
function w4os_own_profile_update( $user_id, $old_user_data ) {
	if ( ! W4OS_DB_CONNECTED ) {
		return;
	}
	if ( $_REQUEST['pass1'] == $_REQUEST['pass2'] ) {
		w4os_set_avatar_password( $user_id, $_REQUEST['pass1'] );
	}
}
add_action( 'profile_update', 'w4os_own_profile_update', 10, 2 );

/**
 * Catch password change made by admin and save it to OpenSimulator
 *
 * @param  integer $user_id
 */
function w4os_other_profile_update( $user_id ) {
	if ( ! W4OS_DB_CONNECTED ) {
		return;
	}
	if ( current_user_can( 'edit_user', $user_id ) ) {
		w4os_set_avatar_password( $user_id, $_REQUEST['pass1'] );
	}
}
add_action( 'edit_user_profile_update', 'w4os_other_profile_update', 10, 1 );

function w4os_user_register( $user_id = 0 ) {
	if ( ! W4OS_DB_CONNECTED ) {
		return;
	}
	if ( isset( $_REQUEST['email'] ) & ! empty( $_REQUEST['email'] ) ) {
		global $wpdb;
		$user = $wpdb->get_row( $wpdb->prepare( 'select * from ' . $wpdb->prefix . 'users where user_email = %s', $_REQUEST['email'] ) );
		$uuid = w4os_profile_sync( $user ); // refresh opensim data for this user
		if ( $uuid ) {
			$password = stripcslashes( $_REQUEST['password'] );
			$salt     = md5( w4os_gen_uuid() );
			$hash     = md5( md5( $password ) . ':' . $salt );
			update_user_meta( $user->ID, 'w4os_tmp_salt', $salt );
			update_user_meta( $user->ID, 'w4os_tmp_hash', $hash );
		}
	} else {
		$user = get_user_by( 'ID', $user_id );
		$uuid = w4os_profile_sync( $user ); // refresh opensim data for this user
	}
}
add_action( 'user_register', 'w4os_user_register', 10, 1 );


// function w4os_debug_log($string) {
// file_put_contents ( "../tmp/w4os_debug.log", $string . "\n", FILE_APPEND );
// }

function w4os_update_avatar( $user, $params ) {
	if ( ! W4OS_DB_CONNECTED ) {
		return;
	}
	global $w4osdb;
	switch ( $params['action'] ) {
		case 'update_avatar':
			$uuid = w4os_profile_sync( $user ); // refresh opensim data for this user
			if ( $uuid ) {
				break;
			}

		case 'w4os_create_avatar':
			$uuid = w4os_create_avatar( $user, $params );
			break;
	}

	if ( $uuid && ! w4os_empty( $uuid ) ) {
		$avatar = new W4OS_Avatar( $uuid );
		$avatar->add_role( 'grid_user' );
		/*
		In-world profiles are always public, so are web profiles */
		// if(isset($params['opensim_profileAllow_web'])) {
		$current = $w4osdb->get_row( $w4osdb->prepare( "SELECT * FROM userprofile WHERE useruuid = '%s'", $uuid ), ARRAY_A );
		// $profileAllow_web = ($params['opensim_profileAllow_web']) ? 1 : 0;
		if ( ! $current ) {
			$current = array();
		}
		$new = array_merge(
			$current,
			array(
				'useruuid'   => $uuid,
				/*
				profileAllow and profileMature might be handled in the future */
				// 'profileAllowPublish' => $profileAllowPublish,
				// 'profileMaturePublish' => $profileMaturePublish,
				'profileURL' => w4os_web_profile_url( $user ),
			)
		);
		$w4osdb->replace( 'userprofile', $new );
		// }
	}
	return $uuid;
}

function w4os_create_avatar( $user, $params ) {
	if ( ! W4OS_DB_CONNECTED ) {
		return;
	}
	global $w4osdb;

	$errors = false;
	// w4os_user_notice(print_r($_REQUEST, true), "code");
	$uuid = w4os_profile_sync( $user ); // refresh opensim data for this user
	if ( $uuid ) {
		if ( $params['action'] == 'w4os_create_avatar' ) {
			w4os_user_notice( __( 'This user already has an avatar.', 'w4os' ), 'fail' ) . '<pre>' . print_r( $params, true ) . '</pre>';
		}
		return $uuid;
	}

	$firstname = trim( $params['w4os_firstname'] );
	$lastname  = trim( $params['w4os_lastname'] );
	$model     = trim( ( $params['w4os_model'] ?? '' ) );
	$password  = stripcslashes( $params['w4os_password_1'] );

	// Sanitize names: remove accents and non-alphanumeric characters
	$firstname_original = $firstname;
	$lastname_original = $lastname;

	$firstname = remove_accents( $firstname );
	$lastname = remove_accents( $lastname );

	$firstname = preg_replace( '/[^A-Za-z0-9]/', '', $firstname );
	$lastname = preg_replace( '/[^A-Za-z0-9]/', '', $lastname );

	// Ensure names start with a letter
	if ( strlen( $firstname ) > 0 && is_numeric( $firstname[0] ) ) {
		$firstname = ltrim( $firstname, '0123456789' );
	}
	if ( strlen( $lastname ) > 0 && is_numeric( $lastname[0] ) ) {
		$lastname = ltrim( $lastname, '0123456789' );
	}

	// Capitalize first letter
	if ( strlen( $firstname ) > 0 ) {
		$firstname = ucfirst( strtolower( $firstname ) );
	}
	if ( strlen( $lastname ) > 0 ) {
		$lastname = ucfirst( strtolower( $lastname ) );
	}

	// Show info message if names were converted
	if ( $firstname_original !== $firstname && ! empty( $firstname_original ) ) {
		w4os_user_notice( 
			sprintf( __( 'First name converted from "%s" to "%s" (removed invalid characters)', 'w4os' ), $firstname_original, $firstname ),
			'info' 
		);
	}
	if ( $lastname_original !== $lastname && ! empty( $lastname_original ) ) {
		w4os_user_notice( 
			sprintf( __( 'Last name converted from "%s" to "%s" (removed invalid characters)', 'w4os' ), $lastname_original, $lastname ),
			'info' 
		);
	}

	if ( empty( $model ) ) {
		$model = W4OS_DEFAULT_AVATAR;
	}

	// Check required fields
	if ( empty( $firstname ) || empty( $lastname ) ) {
		$errors = true;
		w4os_user_notice( __( 'First and Last names are required', 'w4os' ), 'fail' );
	}
	if ( empty( $password ) ) {
		$errors = true;
		w4os_user_notice( __( 'Password required', 'w4os' ), 'error' );
	} elseif ( ! wp_check_password( $params['w4os_password_1'], $user->user_pass ) ) {
		$errors = true;
		w4os_user_notice( __( 'The password does not match.', 'w4os' ), 'error' );
	}

	if ( $errors == true ) {
		return false;
	}

	$required = array( 'w4os_firstname', 'w4os_lastname', 'w4os_password_1' );

	// if ( ! w4os_is_strong ($password)) return false; // We now only rely on WP password requirements

	if ( in_array( strtolower( $firstname ), array_map( 'strtolower', W4OS_DEFAULT_RESTRICTED_NAMES ) ) ) {
		w4os_user_notice( sprintf_safe( __( 'The name %s is not allowed', 'w4os' ), "$firstname" ), 'error' );
		return false;
	}
	if ( in_array( strtolower( $lastname ), array_map( 'strtolower', W4OS_DEFAULT_RESTRICTED_NAMES ) ) ) {
		w4os_user_notice( sprintf_safe( __( 'The name %s is not allowed', 'w4os' ), "$lastname" ), 'error' );
		return false;
	}
	if ( ! preg_match( '/^[a-zA-Z0-9]*$/', $firstname . $lastname ) ) {
		w4os_user_notice( __( 'Names can only contain alphanumeric characters', 'w4os' ), 'error' );
		return false;
	}
	// Check if there is already an avatar with this name
	$check_uuid = $w4osdb->get_var( "SELECT PrincipalID FROM UserAccounts WHERE FirstName = '$firstname' AND LastName = '$lastname'" );
	if ( $check_uuid ) {
		w4os_user_notice( sprintf_safe( __( 'There is already a grid user named %s', 'w4os' ), "$firstname $lastname" ), 'fail' );
		return false;
	}
	$newavatar_uuid = w4os_gen_uuid();
	$check_uuid     = $w4osdb->get_var( "SELECT PrincipalID FROM UserAccounts WHERE PrincipalID = '$newavatar_uuid'" );
	if ( $check_uuid ) {
		w4os_user_notice( __( 'This should never happen! Generated a random UUID that already existed. Sorry. Try again.', 'w4os' ), 'fail' );
		return false;
	}

	$salt      = md5( w4os_gen_uuid() );
	$hash      = md5( md5( $password ) . ':' . $salt );
	$user_info = get_userdata( $user->ID );
	// $user_email = get_userdata($user->ID)->user_email;
	$created      = time();
	$HomeRegionID = $w4osdb->get_var( "SELECT UUID FROM regions WHERE regionName = '" . W4OS_DEFAULT_HOME . "'" );
	if ( empty( $HomeRegionID ) ) {
		$HomeRegionID = '00000000-0000-0000-0000-000000000000';
	}
	$result = $w4osdb->insert(
		'UserAccounts',
		array(
			'PrincipalID' => $newavatar_uuid,
			'ScopeID'     => W4OS_NULL_KEY,
			'FirstName'   => $firstname,
			'LastName'    => $lastname,
			'Email'       => $user_info->user_email,
			'ServiceURLs' => 'HomeURI= InventoryServerURI= AssetServerURI=',
			'Created'     => $created,
		)
	);
	if ( ! $result ) {
		w4os_user_notice( __( 'Error while creating user', 'w4os' ), 'fail' );
	}
	if ( $result ) {
		$result = $w4osdb->insert(
			'auth',
			array(
				'UUID'         => $newavatar_uuid,
				'passwordHash' => $hash,
				'passwordSalt' => $salt,
				'webLoginKey'  => W4OS_NULL_KEY,
			)
		);
	}
	if ( ! $result ) {
		w4os_user_notice( __( 'Error while setting password', 'w4os' ), 'fail' );
	}

	if ( $result ) {
		$result = $w4osdb->insert(
			'GridUser',
			array(
				'UserID'       => $newavatar_uuid,
				'HomeRegionID' => $HomeRegionID,
				'HomePosition' => '<128,128,21>',
				'LastRegionID' => $HomeRegionID,
				'LastPosition' => '<128,128,21>',
			)
		);
	}
	if ( ! $result ) {
		w4os_user_notice( __( 'Error while setting home region', 'w4os' ), 'fail' );
	}

	$model_firstname = strstr( $model, ' ', true );
	$model_lastname  = trim( strstr( $model, ' ' ) );
	$model_uuid      = $w4osdb->get_var( "SELECT PrincipalID FROM UserAccounts WHERE FirstName = '$model_firstname' AND LastName = '$model_lastname'" );

	$inventory_uuid = w4os_gen_uuid();
	if ( $result ) {
		$result = $w4osdb->insert(
			'inventoryfolders',
			array(
				'folderName'     => 'My Inventory',
				'type'           => 8,
				'version'        => 1,
				'folderID'       => $inventory_uuid,
				'agentID'        => $newavatar_uuid,
				'parentFolderID' => W4OS_NULL_KEY,
			)
		);
	}
	if ( ! $result ) {
		w4os_user_notice( __( 'Error while creating user inventory', 'w4os' ), 'fail' );
	}

	$bodyparts_uuid       = w4os_gen_uuid();
	$bodyparts_model_uuid = w4os_gen_uuid();
	$currentoutfit_uuid   = w4os_gen_uuid();

	if ( $result ) {
		$folders = array(
			array( 'Textures', 0, 1, w4os_gen_uuid(), $inventory_uuid ),
			array( 'Sounds', 1, 1, w4os_gen_uuid(), $inventory_uuid ),
			array( 'Calling Cards', 2, 2, w4os_gen_uuid(), $inventory_uuid ),
			array( 'Landmarks', 3, 1, w4os_gen_uuid(), $inventory_uuid ),
			array( 'Photo Album', 15, 1, w4os_gen_uuid(), $inventory_uuid ),
			array( 'Clothing', 5, 3, w4os_gen_uuid(), $inventory_uuid ),
			array( 'Objects', 6, 1, w4os_gen_uuid(), $inventory_uuid ),
			array( 'Notecards', 7, 1, w4os_gen_uuid(), $inventory_uuid ),
			array( 'Scripts', 10, 1, w4os_gen_uuid(), $inventory_uuid ),
			array( 'Body Parts', 13, 5, $bodyparts_uuid, $inventory_uuid ),
			array( 'Trash', 14, 1, w4os_gen_uuid(), $inventory_uuid ),
			array( 'Animations', 20, 1, w4os_gen_uuid(), $inventory_uuid ),
			array( 'Gestures', 21, 1, w4os_gen_uuid(), $inventory_uuid ),
			array( 'Lost And Found', 16, 1, w4os_gen_uuid(), $inventory_uuid ),
			array( "$model_firstname $model_lastname outfit", -1, 1, $bodyparts_model_uuid, $bodyparts_uuid ),
			array( 'Current Outfit', 46, 1, $currentoutfit_uuid, $inventory_uuid ),
		// array('My Outfits', 48, 1, $myoutfits_uuid, $inventory_uuid ),
		// array("$model_firstname $model_lastname", 47, 1, $myoutfits_model_uuid, $myoutfits_uuid ),
		// array('Friends', 2, 2, w4os_gen_uuid(), $inventory_uuid ),
		// array('Favorites', 23, w4os_gen_uuid(), $inventory_uuid ),
		// array('All', 2, 1, w4os_gen_uuid(), $inventory_uuid ),
		);
		foreach ( $folders as $folder ) {
			$name     = $folder[0];
			$type     = $folder[1];
			$version  = $folder[2];
			$folderid = $folder[3];
			$parentid = $folder[4];
			if ( $result ) {
				$result = $w4osdb->insert(
					'inventoryfolders',
					array(
						'folderName'     => $name,
						'type'           => $type,
						'version'        => $version,
						'folderID'       => $folderid,
						'agentID'        => $newavatar_uuid,
						'parentFolderID' => $parentid,
					)
				);
			}
			if ( ! $result ) {
				w4os_user_notice( __( "Error while adding folder $folder", 'w4os' ), 'fail' );
			}
			if ( ! $result ) {
				break;
			}
		}
	}

	// if ( $result ) {
	// $result = $w4osdb->get_results("SELECT folderName,type,version FROM inventoryfolders WHERE agentID = '$model_uuid' AND type != 8");
	// if($result) {
	// foreach($result as $row) {
	// $result = $w4osdb->insert (
	// 'inventoryfolders', array (
	// 'folderName' => $row->folderName,
	// 'type' => $row->type,
	// 'version' => $row->version,
	// 'folderID' => w4os_gen_uuid(),
	// 'agentID' => $newavatar_uuid,
	// 'parentFolderID' => $inventory_uuid,
	// )
	// );
	// if( ! $result ) break;
	// }
	// }
	// }

	if ( $result ) {
		$model = $w4osdb->get_results( "SELECT Name, Value FROM Avatars WHERE PrincipalID = '$model_uuid'" );
		// w4os_user_notice(print_r($result, true), 'code');
		// foreach($result as $row) {
		// w4os_user_notice(print_r($row, true), 'code');
		// w4os_user_notice($row->Name . " = " . $row->Value);
		// }

		// foreach($avatars_prefs as $var => $val) {
		if ( $model ) {
			foreach ( $model as $row ) {
				unset( $newitem );
				unset( $newitems );
				unset( $newvalues );
				$Name  = $row->Name;
				$Value = $row->Value;
				if ( strpos( $Name, 'Wearable' ) !== false ) {
					// Must add a copy of item in inventory
					$uuids           = explode( ':', $Value );
					$item            = $uuids[0];
					$asset           = $uuids[1];
					$destinventoryid = $w4osdb->get_var( "SELECT inventoryID FROM inventoryitems WHERE assetID='$asset' AND avatarID='$newavatar_uuid'" );
					if ( empty( $destitem ) ) {
						$newitem                = $w4osdb->get_row( "SELECT * FROM inventoryitems WHERE assetID='$asset' AND avatarID='$model_uuid'", ARRAY_A );
						$destinventoryid        = w4os_gen_uuid();
						$newitem['inventoryID'] = $destinventoryid;
						$newitems[]             = $newitem;
						$Value                  = "$destinventoryid:$asset";
					}
				} elseif ( strpos( $Name, '_ap_' ) !== false ) {
					$items = explode( ',', $Value );
					foreach ( $items as $item ) {
						$asset           = $w4osdb->get_var( "SELECT assetID FROM inventoryitems WHERE inventoryID='$item'" );
						$destinventoryid = $w4osdb->get_var( "SELECT inventoryID FROM inventoryitems WHERE assetID='$asset' AND avatarID='$newavatar_uuid'" );
						if ( empty( $destitem ) ) {
								$newitem                = $w4osdb->get_row( "SELECT * FROM inventoryitems WHERE assetID='$asset' AND avatarID='$model_uuid'", ARRAY_A );
								$destinventoryid        = w4os_gen_uuid();
								$newitem['inventoryID'] = $destinventoryid;
								// $Value = $destinventoryid;
								$newitems[]  = $newitem;
								$newvalues[] = $destinventoryid;
						}
					}
					if ( $newvalues ) {
						$Value = implode( ',', $newvalues );
					}
				}
				if ( ! empty( $newitems ) ) {
					foreach ( $newitems as $newitem ) {
						// $destinventoryid = w4os_gen_uuid();
						// w4os_user_notice("Creating inventory item '$Name' for $firstname");
						$newitem['parentFolderID'] = $bodyparts_model_uuid;
						$newitem['avatarID']       = $newavatar_uuid;
						$result                    = $w4osdb->insert( 'inventoryitems', $newitem );
						if ( ! $result ) {
							w4os_user_notice( __( 'Error while adding inventory item', 'w4os' ), 'fail' );
						}

						// Adding aliases in "Current Outfit" folder to avoid FireStorm error message
						$outfit_link                   = $newitem;
						$outfit_link['assetType']      = 24;
						$outfit_link['assetID']        = $newitem['inventoryID'];
						$outfit_link['inventoryID']    = w4os_gen_uuid();
						$outfit_link['parentFolderID'] = $currentoutfit_uuid;
						$result                        = $w4osdb->insert( 'inventoryitems', $outfit_link );
						if ( ! $result ) {
							w4os_user_notice( __( 'Error while adding inventory outfit link', 'w4os' ), 'fail' );
						}
					}
					// } else {
					// w4os_user_notice("Not creating inventory item '$Name' for $firstname");
				}
				$result = $w4osdb->insert(
					'Avatars',
					array(
						'PrincipalID' => $newavatar_uuid,
						'Name'        => $Name,
						'Value'       => $Value,
					)
				);
				if ( ! $result ) {
					w4os_user_notice( __( 'Error while adding avatar', 'w4os' ), 'fail' );
				}
			}
		}
	}

	if ( ! $result ) {
		w4os_user_notice( __( 'Errors occurred while creating the user', 'w4os' ), 'fail' );
		// w4os_user_notice($sql, 'code');
		return false;
	}

	w4os_user_notice( sprintf_safe( __( 'Avatar %s created successfully.', 'w4os' ), "$firstname $lastname" ), 'success' );

	$check_uuid = w4os_profile_sync( $user ); // refresh opensim data for this user
	return $newavatar_uuid;
}

function w4os_avatar_update_error( $message ) {
	return "<div class='error'><p>$message</p></div>";
}

function w4os_avatar_creation_form( $user ) {
	if ( ! W4OS_DB_CONNECTED ) {
		return;
	}
	if ( $user != wp_get_current_user() ) {
		return;
	}

	// Show success message if avatar was just created
	if ( isset( $_GET['avatar_created'] ) && $_GET['avatar_created'] == '1' ) {
		// Get the user's avatar to show the name in success message
		$avatar = new W4OS_Avatar( $user );
		if ( $avatar->exists() ) {
			w4os_user_notice( 
				W4OS::sprintf_safe( 
					__( 'Avatar %s created successfully! You can now log in to the virtual world.', 'w4os' ), 
					$avatar->FirstName . ' ' . $avatar->LastName 
				), 
				'success' 
			);
		} else {
			w4os_user_notice( __( 'Avatar created successfully! You can now log in to the virtual world.', 'w4os' ), 'success' );
		}
	}

	if ( isset( $_POST['w4os_update_avatar'] ) && isset( $_POST['user_id'] ) ) {
		// Verify nonce
		if ( ! wp_verify_nonce( $_POST['nonce'], 'w4os_create_avatar' ) ) {
			w4os_user_notice( __( 'Security check failed', 'w4os' ), 'fail' );
			return false;
			// return w4os_avatar_update_error( __( 'Security check failed', 'w4os' ), 'fail' );
			// wp_die( 'Security check' );
		}
		
		$result = w4os_profile_fields_save( $_POST['user_id'] );
		
		// Redirect to current page without POST data to prevent resubmission
		$redirect_url = remove_query_arg( array( 'w4os_update_avatar', 'user_id', 'nonce' ) );
		$redirect_url = add_query_arg( 'avatar_created', '1', $redirect_url );
		
		wp_redirect( $redirect_url );
		exit;
	}

	global $w4osdb;

	wp_enqueue_style( 'w4os-wp-forms', site_url( '/wp-admin/css/forms.min.css' ) );
	wp_enqueue_style( 'w4os-forms', plugin_dir_url( __DIR__ ) . 'wordpress/css/forms.css', array(), time() );
	wp_enqueue_script( 'w4os-avatar-name-validation', plugin_dir_url( __DIR__ ) . 'assets/js/avatar-name-validation.js', array(), time(), true );

	if ( isset( $_REQUEST['w4os_firstname'] ) && isset( $_REQUEST['w4os_lastname'] ) ) {
		$firstname = sanitize_text_field( $_REQUEST['w4os_firstname'] );
		$lastname  = sanitize_text_field( $_REQUEST['w4os_lastname'] );
	} elseif ( ! empty( get_user_meta( $user->ID, 'first_name', true ) ) & (bool) empty( get_user_meta( $user->ID, 'last_name', true ) ) ) {
		$firstname = get_user_meta( $user->ID, 'first_name', true );
		$lastname  = get_user_meta( $user->ID, 'last_name', true );
	} else {
		$namearray = preg_split( '/[ \._-]/', $user->user_login );
		$firstname = ( isset( $namearray[0] ) ) ? ucfirst( $namearray[0] ) : '';
		$lastname  = ( isset( $namearray[1] ) ) ? ucfirst( $namearray[1] ) : '';
	}
	$firstname = sanitize_text_field( preg_replace( '/[^[:alnum:]]/', '', $firstname ) );
	$lastname  = sanitize_text_field( preg_replace( '/[^[:alnum:]]/', '', $lastname ) );

	$action = 'w4os_create_avatar';
	$nonce  = wp_create_nonce( $action );

	$content  = '<p>' . __( 'Choose a name below. This is how people will see you in-world.', 'w4os' ) . '</p>';
	$content .= '<div class="w4os-form wrap">';
	$content .= "<form class='edit-account' method='post'>";

	$content .= sprintf(
		'<div class=wrap><table class="form-table">
		<tr>
			<th scope="row"><label for="w4os_firstname">%s</label></th>
			<td><input type="text" class="regular-text" name="w4os_firstname" id="w4os_firstname" value="%s" required pattern="[A-Za-z][A-Za-z0-9]*" title="%s" maxlength="32" /></td>
		</tr>
		<tr>
			<th><label for="w4os_lastname">%s</label></th>
			<td><input type="text" class="regular-text" name="w4os_lastname" id="w4os_lastname" value="%s" required pattern="[A-Za-z][A-Za-z0-9]*" title="%s" maxlength="32" /></td>
		</tr>
		<tr>
			<th><label for="w4os_password_1">%s</label></th>
			<td>
				<input type="password" class="regular-text" name="w4os_password_1" id="w4os_password_1" required />
				<p class="description">%s</p>
			</td>
		</tr>
		%s
	</table></div>',
		__( 'First name', 'w4os' ),
		$firstname,
		__( 'First name must start with a letter and contain only letters and numbers', 'w4os' ),
		__( 'Last name', 'w4os' ),
		$lastname,
		__( 'Last name must start with a letter and contain only letters and numbers', 'w4os' ),
		__( 'Confirm your password', 'w4os' ),
		__( 'Your in-world Avatar password is the same as your password on this website.', 'w4os' ),
		( W4OS_Model::get_models() ) ? sprintf(
			'<tr>
				<th><label for="w4os_model">%s</label></th>
				<td>
					<p>%s</p>
					<p class="description">%s</p>
				</td>
			</tr><tr>
				<td colspan=2>%s</td>
			</tr>',
			__( 'Model', 'w4os' ),
			__( 'Choose the first outfit for your avatar.', 'w4os' ),
			__( 'You can always change your appearance in-world.', 'w4os' ),
			( new W4OS_Model() )->select_model_field(),
		) : ''
	);

	// if ( W4OS_Model::get_models() ) {
	// $content .= ( new W4OS_Model() )->select_model_field();
	// }
	// $content .= '</p>';

	$content .= sprintf(
		'<p class="submit">
			<input type="hidden" name="user_id" value="%1$s">
			<input type="hidden" name="action" value="%2$s">
			<input type="hidden" name="nonce" value="%4$s">
			<button type="submit" class="button button-primary" name="w4os_update_avatar" value="%2$s">%3$s</button>
		</p>',
		$user->ID,
		$action,
		__( 'Create Avatar', 'w4os' ),
		$nonce
	);

	$content .= '  </form>';
	$content .= '</div>';
	return $content;
}

function w4os_gridprofile_html( $atts = array(), $args = array() ) {
	if ( ! W4OS_DB_CONNECTED ) {
		return;
	}
	return w4os_profile_display( wp_get_current_user(), $args );
}

function w4os_profile_display( $user, $args = array() ) {
	if ( ! W4OS_DB_CONNECTED ) {
		return;
	}
	extract( $args );
	if ( ! isset( $content ) ) {
		$content = '';
	}
	if ( ! isset( $before_title ) ) {
		$before_title = '';
	}
	if ( ! isset( $after_title ) ) {
		$after_title = '';
	}
	if ( ! isset( $title ) ) {
		$title = '';
	}

	if ( isset( $user->ID ) && $user->ID == 0 ) {
		$wp_login_url = wp_login_url();
		// $content =  "<p class='avatar not-connected'>" . sprintf_safe(__("%sLog in%s to choose an avatar.", 'w4os'), "<a href='$wp_login_url$wp_login_url'>", "</a>") ."</p>";
		$content = '<div class=w4os-login>' . wp_login_form( array( 'echo' => false ) ) . '</div>';
		return $content;
	}

	global $w4osdb;
	$args = wp_parse_args(
		$args,
		array(
			'mini' => false,
		)
	);
	extract( $args );

	if ( is_string( $user ) && ! is_numeric( $user ) ) {
		// Probably an avatar name
		$name   = $user;
		$avatar = new W4OS3_Avatar( $name );
		if ( ! $avatar->UUID ) {
			return false;
		}
	} elseif ( $user->ID ) {
		$avatar = W4OS3_Avatar::get_user_avatar( $user->ID );
		if ( empty( $avatar ) ) {
			return false;
		}
	} else {
		return false;
	}

	$content = '';
	if ( $avatar->UUID ) {
		$action     = 'w4os_update_avatar';
		$leaveblank = ' (' . __( 'leave blank to leave unchanged', 'w4os' ) . ')';
		if ( $mini ) {
			$content .= '<div class=avatar-name>' . w4os_hop( w4os_grid_profile_url( $avatar ), $avatar->AvatarName ) . '</div>';
			$content .= '<div class=profileImage>' . $avatar->profile_picture() . '</div>';
		} else {
			$content .= $avatar->profile_page();
		}
		// $content.= sprintf_safe(
		// '<div class=profile><div class=profile-pic>%1$s</div><div class=profile-details>%2$s</div></div>',
		// $avatar->profile_picture(),
		// $avatar->AvatarName,
		// );
		// return $content;
	} elseif ( ! empty( $args['mini'] ) ) {
			// Display simple link when 'mini' argument is set
			$content .= sprintf_safe( '<a href="%1$s">%2$s</a>', W4OS_PROFILE_URL, __( 'Create an avatar', 'w4os' ) );
	} else {
		// Display responsive form and hidden dialog
		$content .= '<div id="profilewrapper">';
		$content .= '<div id="profileform">' . w4os_avatar_creation_form( $user ) . '</div>';
		$content .= '<a href="#" id="profilelink" style="display: none;" onclick="openDialog(); return false;">' . __( 'Create an avatar', 'w4os' ) . '</a>';
		$content .= '</div>';

		// Dialog HTML
		$content .= '<dialog id="modalDialog">';
		$content .= '<div id="dialogContent"></div>';
		$content .= '<button onclick="closeDialog()">Close</button>';
		$content .= '</dialog>';

		// JavaScript for responsive behavior and dialog
		$content .= "
		    <script>
		    function checkSize() {
		        var box = document.getElementById('profilewrapper');
		        if (box.offsetWidth < 30 * parseFloat(getComputedStyle(document.documentElement).fontSize)) {
		            document.getElementById('profilelink').style.display = 'block';
		            document.getElementById('profileform').style.display = 'none';
		        }
		    }

		    function openDialog() {
		        var dialog = document.getElementById('modalDialog');
		        var content = document.getElementById('profileform').innerHTML;
		        document.getElementById('dialogContent').innerHTML = content;
		        dialog.showModal(); // Use showModal() for modal dialog
		    }

		    function closeDialog() {
		        var dialog = document.getElementById('modalDialog');
		        dialog.close();
		    }

		    window.onload = checkSize;
		    window.onresize = checkSize;
		    </script>
		    ";
	}

	if ( ! empty( $content ) ) {
		$content = $before_title . $title . $after_title . $content;
	}
	return $content;
}


// function w4os_profile_shortcodes_init() {
// if ( ! W4OS_DB_CONNECTED ) {
// return;
// }
// global $pagenow;
// if ( in_array( $pagenow, array( 'post.php', 'post-new.php', 'admin-ajax.php', '' ) ) || get_post_type() == 'post' ) {
// return;
// }
// if ( wp_is_json_request() ) {
// return;
// }
//
// function w4os_profile_shortcode( $atts = array(), $content = null ) {
// $content .= w4os_profile_display( wp_get_current_user() );
// return "<div class='w4os-shortcode w4os-shortcode-profile'>" . $content . '</div>';
// return $content;
// }
// add_shortcode( 'avatar-profile', 'w4os_profile_shortcode' );
// add_shortcode( 'w4os_profile', 'w4os_profile_shortcode' ); // Backwards compatibility
// add_shortcode( 'gridprofile', 'w4os_profile_shortcode' ); // Backwards compatibility
// }
// add_action( 'init', 'w4os_profile_shortcodes_init' );

function w4os_render_asset( $image_uuid, $size = 256, $default = '', $alt = '', $args = null ) {
	if ( w4os_empty( $image_uuid ) ) {
		return;
	}
	return sprintf_safe(
		'<img src="%1$s" class="asset asset-%3$d %4$s" alt="%2$s" loading="lazy" width="%3$d" height="%3$d">',
		w4os_get_asset_url( $image_uuid ),
		( empty( $alt ) ) ? 'OpenSimulator asset' : $alt,
		$size,
		$args['class'] ?? '',
	);
}

function w4os_get_avatar( $user_id, $size = 96, $default = '', $alt = '', $args = null ) {
	if ( ! W4OS_DB_CONNECTED ) {
		return;
	}
	$image_uuid = get_the_author_meta( 'w4os_profileimage', $user_id );
	if ( empty( $image_uuid ) ) {
		return false;
	}
	if ( $image_uuid === W4OS_NULL_KEY ) {
		return false;
	}
	$args['class'] = "avatar avatar-$size photo";
	if ( empty( $alt ) ) {
		$alt = 'avatar profile picture';
	}
	return w4os_render_asset( $image_uuid, $size, $default, $alt, $args );
	return sprintf_safe(
		'<img src="%1$s" class="avatar avatar-%3$d photo" alt="%2$s" loading="lazy" width="%3$d" height="%3$d">',
		w4os_get_asset_url( $image_uuid ),
		'avatar profile picture',
		$size
	);
}

if ( W4OS_DB_CONNECTED ) {
	add_filter( 'get_avatar', 'w4os_get_avatar_filter', 10, 6 );
}
function w4os_get_avatar_filter( $avatar, $user_id, $size, $default, $alt, $args = array() ) {
	// If is email, try and find user ID
	if ( ! is_numeric( $user_id ) && is_string( $user_id ) && is_email( $user_id ) ) {
		$user = get_user_by( 'email', $user_id );
		if ( $user ) {
			$user_id = $user->ID;
		}
	}
	if ( ! is_numeric( $user_id ) ) {
		return $avatar;
	}
	if ( $args['force_default'] ) {
		return $avatar;
	}

	$avatar_opensim = w4os_get_avatar( $user_id, $size, $default, $alt );
	if ( $avatar_opensim ) {
		return $avatar_opensim;
	}
	return $avatar;
}

// if ( W4OS_DB_CONNECTED ) {
// add_action( 'init', 'w4os_gridprofile_block_init' );
// }
// function w4os_gridprofile_block_init() {
// w4os_block_init( 'avatar-profile', 'Avatar Profile' );
// }

function w4os_gridprofile_block_render( $args = array(), $dumb = '', $block_object = array() ) {
	if ( ! W4OS_DB_CONNECTED ) {
		return;
	}
	$args                 = (array) $block_object;
	$args['before_title'] = '<h4>';
	$args['after_title']  = '</h4>';
	$args['title']        = __( 'Avatar Profile', 'w4os' );
	return sprintf_safe(
		'<div>%s</div>',
		w4os_gridprofile_html( null, $args )
	);
}

function w4os_grid_profile_url( $user_or_id ) {
	if ( get_option( 'w4os_profile_page' ) != 'provide' ) {
		return;
	}
	if ( is_numeric( $user_or_id ) ) {
		$user = get_user_by( 'ID', $user_or_id );
	} else {
		$user = $user_or_id;
	}
	if ( ! is_object( $user ) ) {
		return;
	}
	if ( ! $user->UUID ) {
		return;
	}

	$link = sprintf_safe( '/app/agent/%s/about', $user->UUID );
	if ( W4OS3::in_world_call() ) {
		// SLURL not supported from within profile tab, not sure it is in embedded web browser either
		return false;
		// $link = 'secondlife://' . $link;
		// error_log( 'replacing link with ' . $link );
		// return $link;
	}

	$link = 'hop://' . W4OS_GRID_LOGIN_URI . $link;
	return $link;
}

function w4os_web_profile_url( $user_or_id ) {
	if ( get_option( 'w4os_profile_page' ) != 'provide' ) {
		return;
	}
	if ( is_numeric( $user_or_id ) ) {
		$user = get_user_by( 'ID', $user_or_id );
	} else {
		$user = $user_or_id;
	}
	if ( ! is_object( $user ) ) {
		return;
	}

	$slug = sanitize_title( get_the_author_meta( 'w4os_firstname', $user->ID ) ) . '.' . sanitize_title( get_the_author_meta( 'w4os_lastname', $user->ID ) );
	if ( ! empty( $slug ) ) {
		return get_home_url( null, get_option( 'w4os_profile_slug' ) . '/' . $slug );
	}
}

if ( get_option( 'w4os_profile_page' ) == 'provide' ) {
	require_once __DIR__ . '/profile-page.php';
}

add_action( 'edit_user_profile', 'w4os_keeproles_profile_loading', 10, 1 );
add_action( 'show_user_profile', 'w4os_keeproles_profile_loading', 10, 1 );
function w4os_keeproles_profile_loading( $user ) {
	$trans = get_transient( 'saved_user_roles' );
	if ( empty( $trans ) ) {
		return; // No data to act on, return.
	}
	$uid = $user->ID;
	if ( empty( $trans[ $uid ] ) ) {
		return;
	}
	$roles = $trans[ $uid ];

	if ( in_array( 'grid_user', $roles ) ) {
		$user->add_role( 'grid_user' );
	}

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
	if ( ! $update ) {
		return;
	}
	if ( $errors->has_errors() ) {
		return;
	}
	$uid       = $user->ID;
	$user_temp = new WP_User( $uid );
	$trans     = get_transient( 'saved_user_roles' );
	if ( empty( $trans ) ) {
		$trans = array();
	}
	$trans[ $uid ] = $user_temp->roles;
	set_transient( 'saved_user_roles', $trans );
}


add_action( 'show_user_profile', 'w4os_user_profile_fields', 5 );
add_action( 'edit_user_profile', 'w4os_user_profile_fields', 5 );
function w4os_user_profile_fields( $user ) {
	global $pagenow;
	if ( $pagenow != 'user-edit.php' && $pagenow != 'profile.php' ) {
		return;
	}

	if ( ! $user ) {
		return;
	}

	$has_avatar       = ! w4os_empty( esc_attr( get_the_author_meta( 'w4os_uuid', $user->ID ) ) );
	$avatar           = new W4OS_Avatar( $user );
	$profile_settings = array(
		// 'profile.php' => array(
			'sections' => array(
				'opensimulator' => array(
					'label'  => __( 'OpenSimulator', 'w4os' ),
					'fields' => array(
						'w4os_uuid'            => array(
							'type'     => 'string',
							'label'    => 'UUID',
							'value'    => esc_attr( get_the_author_meta( 'w4os_uuid', $user->ID ) ),
							'disabled' => true,
						),
						'w4os_firstname'       => array(
							'type'     => 'string',
							'label'    => __( 'Avatar First Name', 'w4os' ),
							'value'    => esc_attr( get_the_author_meta( 'w4os_firstname', $user->ID ) ),
							'readonly' => $has_avatar,
						),
						'w4os_lastname'        => array(
							'type'     => 'string',
							'label'    => __( 'Avatar Last Name', 'w4os' ),
							'value'    => esc_attr( get_the_author_meta( 'w4os_lastname', $user->ID ) ),
							'readonly' => $has_avatar,
						),
						'w4os_profileimage'    => array(
							'type'        => 'os_asset',
							'label'       => __( 'Profile Picture', 'w4os' ),
							'value'       => esc_attr( get_the_author_meta( 'w4os_profileimage', $user->ID ) ),
							'placeholder' => ( $has_avatar ) ? __( 'Must be set in the viewer.', 'w4os' ) : '',
							'readonly'    => true,
						),
						'w4os_web_profile_url' => array(
							'type'     => 'url',
							'label'    => __( 'Web Profile URL', 'w4os' ),
							'value'    => w4os_web_profile_url( $user ),
							'readonly' => true,
						),
					/*
					In-world profiles are always public, so are web profiles */
					// 'opensim_profileAllow_web' => array(
					// 'type' => 'boolean',
					// 'label' => __('Public profile', 'w4os'),
					// 'value' => (get_the_author_meta( 'opensim_profileAllow_web', $user->ID ) === true),
					// 'default' => true,
					// 'description' => __('Make avatar profile public (available in search and on the website).', 'w4os')
					// . (($has_avatar) ? sprintf_safe('<p class="description"><a href="%1$s">%1$s</a></p>', w4os_web_profile_url($user) ) : ''),
					// )
					),
				),
			// ),
			),
	);
	$settings_pages = array( 'profile.php' => $profile_settings );

	foreach ( $settings_pages as $page_slug => $page ) {
		foreach ( $page['sections'] as $section_slug => $section ) {
			echo '<h3 id=opensim_profile>' . $section['label'] . '</h3>';
			echo '<table class="form-table">';
			foreach ( $section['fields'] as $field_slug => $field ) {
				echo '<tr><th>' . $field['label'] . '</th><td>';
				$args = array_merge( array( 'option_slug' => $field_slug ), $field );
				w4os_settings_field( $args, $user );
				echo '</td></tr>';
			}
			echo '</table>';
		}
	}
}

add_action( 'personal_options_update', 'w4os_profile_fields_save' );
add_action( 'edit_user_profile_update', 'w4os_profile_fields_save' );
function w4os_profile_fields_save( $user_id ) {

	if ( $user_id != wp_get_current_user()->ID & ! current_user_can( 'edit_user', $user_id ) ) {
		return;
	}
	$user = get_user_by( 'ID', $user_id );
	if ( ! $user ) {
		return;
	}

	$args = array(
		'action'                   => 'update_avatar',
		'w4os_firstname'           => esc_attr( $_POST['w4os_firstname'] ),
		'w4os_lastname'            => esc_attr( $_POST['w4os_lastname'] ),
		'w4os_model'			   => esc_attr( $_POST['w4os_model'] ?? null ),
		'opensim_profileAllow_web' => ( isset( $_POST['opensim_profileAllow_web'] ) ) ? ( esc_attr( $_POST['opensim_profileAllow_web'] ) == true ) : false,
	);
	if ( ! empty( $_POST['w4os_password_1'] ) ) {
		$args['w4os_password_1'] = esc_attr( $_POST['w4os_password_1'] );
	}

	update_user_meta( $user_id, 'w4os_firstname', $args['w4os_firstname'] );
	update_user_meta( $user_id, 'w4os_lastname', $args['w4os_lastname'] );
	update_user_meta( $user_id, 'opensim_profileAllow_web', $args['opensim_profileAllow_web'] );
	// if(!empty($_POST['w4os_firstname']) &! empty($_POST['w4os_lastname']))
	w4os_update_avatar( $user, $args );
}
