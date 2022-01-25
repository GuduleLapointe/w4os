<?php
/*
 * This file contains functions that aren't used anymore.
 * It is not loaded and can probably be deleted but it is kept for now by
 * abundance of caution.
 */

/*********************************************************************************
 * functions-opensim.php v1.1.0 for OpenSim 	by Fumi.Iseki  2013 10/20
 *
 * 			Copyright (c) 2009,2010,2011,2013   http://www.nsl.tuis.ac.jp/
 *
 *			supported versions of OpenSim are 0.6.7, 0.6.8, 0.6.9 and 0.7.x
 *			functions-opensim.php is needed
 *			classes-db.php is needed
 *
 *********************************************************************************/


/*********************************************************************************
 * Function List

// for DB
 function  opensim_new_db($timeout=60)
 function  opensim_get_db_version(&$db=null)
 function  opensim_users_update_time(&$db=null)
 function  opensim_get_update_time($table, &$db=null)
 function  opensim_check_db(&$db=null)

// for Avatar
 function  opensim_get_avatars_num(&$db=null)
 function  opensim_get_avatar_name($uuid, &$db=null)
 function  opensim_get_avatar_uuid($name, &$db=null)
 function  opensim_get_avatar_session($uuid, &$db=null)
 function  opensim_get_avatar_info($uuid, &$db=null)
 function  opensim_get_avatars_infos($condition='', &$db=null)
 function  opensim_get_avatars_profiles_from_users($condition='', &$db=null)
 function  opensim_get_avatar_online($uuid, &$db=null)
 function  opensim_get_avatar_flags($uuid, &$db=null)
 function  opensim_set_avatar_flags($uuid, $flags=0, &$db=null)
 function  opensim_create_avatar($UUID, $firstname, $lastname, $passwd, $homeregion, $base_avatar='00000000-0000-0000-0000-000000000000', &$db=null)
 function  opensim_delete_avatar($uuid, &$db=null)

// for Region
 function  opensim_get_regions_num(&$db=null)
 function  opensim_get_region_uuid($name, &$db=null)
 function  opensim_get_region_name($id, &$db=null)
 function  opensim_get_regions_names($condition='', &$db=null)
 function  opensim_get_region_info($region, &$db=null)
 function  opensim_get_regions_infos($condition='', &$db=null)
 function  opensim_set_current_region($uuid, $regionid, &$db=null)

// for Home Region
 function  opensim_get_home_region($uuid, &$db=null)
 function  opensim_set_home_region($uuid, $hmregion, $pos_x='128', $pos_y='128', $pos_z='0', &$db=null)

// for Estate
 function  opensim_set_region_estate($region, $estate, $owner, &$db=null)
 function  opensim_create_estate($estate, $owner, &$db=null)
 function  opensim_get_estates_infos(&$db=null)
 function  opensim_get_estate_info($region, &$db=null)
 function  opensim_set_region_estateid($region, $estateid, &$db=null)
 function  opensim_set_estate_owner($region, $owner, &$db=null)
 function  opensim_del_estate($id, &$db=null)
 function  opensim_update_estate($id, $name, $owner, &$db=null)

// for Parcel
 function  opensim_get_parcel_name($parcel, &$db=null)
 function  opensim_get_parcel_info($parcel, &$db=null)

// for Assets
 function  opensim_get_asset_data($uuid, &$db=null)
 function  opensim_display_texture_data($uuid, $prog, $xsize='0', $ysize='0', $cachedir='', $use_tga=false)

// for Inventory
 function  opensim_create_avatar_inventory($uuid, $base_uuid, $db=null)
 function  opensim_create_default_avatar_wear($uuid, $invent, $db=null)
 function  opensim_create_default_inventory_items($uuid, $db=null)
 function  opensim_create_default_inventory_folders($uuid, &$db=null)
 function  opensim_create_avatar_wear_dup($touuid, $fromid, $invent, &$db=null)
 function  opensim_create_inventory_items_dup($touuid, $fromid, $folder, $db=null)
 function  opensim_create_inventory_folders_dup($touuid, $fromid, $db=null)

// for Password
 function  opensim_get_password($uuid, $tbl='', &$db=null)
 function  opensim_set_password($uuid, $passwdhash, $passwdsalt='', $tbl='', &$db=null)

// for Update Data Base
//function  opensim_supply_passwordSalt(&$db=null)
 function  opensim_succession_agents_to_griduser($region_id, &$db=null)
 function  opensim_succession_useraccounts_to_griduser($region_id, &$db=null)
 function  opensim_succession_data($region_name, &$db=null)

// for Voice (VoIP)
 function  opensim_get_voice_mode($region, &$db=null)
 function  opensim_set_voice_mode($region, $mode, &$db=null)

// for Currency
 function opensim_save_transaction($sourceId, $destId, $amount, $type, $flags, $description, &$db=null)
 function opensim_get_currency_balance($uuid, &$db=null)

// Tools
 function  opensim_get_servers_ip(&$db=null)
 function  opensim_get_server_info($uuid, &$db=null)
 function  opensim_is_access_from_region_server()
 function  opensim_check_secure_session($uuid, $regionid, $secure, &$db=null)
 function  opensim_check_region_secret($uuid, $secret, &$db=null)
 function  opensim_clear_login_table(&$db=null)


// Debug or Test
 function  opensim_debug_command(&$db=null)



**********************************************************************************/




define('OPENSIM_V06',	'opnesim_0.6');
define('OPENSIM_V07',	'opnesim_0.7');
define('AURORASIM',		'aurora-sim');

$OpenSimVersion = null;


//
define('DEFAULT_ASSET_SHAPE',	'66c41e39-38f9-f75a-024e-585989bfab73');
define('DEFAULT_ASSET_SKIN',	'77c41e39-38f9-f75a-024e-585989bbabbb');
define('DEFAULT_ASSET_HAIR',	'd342e6c0-b9d2-11dc-95ff-0800200c9a66');
define('DEFAULT_ASSET_EYES',	'4bb6fa4d-1cd2-498a-a84c-95c1a0e745a7');
define('DEFAULT_ASSET_SHIRT',	'00000000-38f9-1111-024e-222222111110');
define('DEFAULT_ASSET_PANTS',	'00000000-38f9-1111-024e-222222111120');

define('DEFAULT_AVATAR_HEIGHT', '1.690999');
define('DEFAULT_AVATAR_PARAMS', '33,61,85,23,58,127,63,85,63,42,0,85,63,36,85,95,153,63,34,0,63,109,88,132,63,136,81,85,103,136,127,0,150,150,150,127,0,0,0,0,0,127,0,0,255,127,114,127,99,63,127,140,127,127,0,0,0,191,0,104,0,0,0,0,0,0,0,0,0,145,216,133,0,127,0,127,170,0,0,127,127,109,85,127,127,63,85,42,150,150,150,150,150,150,150,25,150,150,150,0,127,0,0,144,85,127,132,127,85,0,127,127,127,127,127,127,59,127,85,127,127,106,47,79,127,127,204,2,141,66,0,0,127,127,0,0,0,0,127,0,159,0,0,178,127,36,85,131,127,127,127,153,95,0,140,75,27,127,127,0,150,150,198,0,0,63,30,127,165,209,198,127,127,153,204,51,51,255,255,255,204,0,255,150,150,150,150,150,150,150,150,150,150,0,150,150,150,150,150,0,127,127,150,150,150,150,150,150,150,150,0,0,150,51,132,150,150,150');



/////////////////////////////////////////////////////////////////////////////////////
//
// Load Function
//

require_once('functions-opensim.php');
require_once('classes-db.php');





/////////////////////////////////////////////////////////////////////////////////////
//
// for DB
//

function  opensim_new_db($timeout=60)
{
	$db = new DB(OPENSIM_DB_HOST, OPENSIM_DB_NAME, OPENSIM_DB_USER, OPENSIM_DB_PASS, OPENSIM_DB_MYSQLI, $timeout);

	return $db;
}








//
// InnoDB の場合は常に 0 を返す
//
function  opensim_users_update_time(&$db=null)
{
	global $OpenSimVersion;

	if (!is_object($db)) $db = opensim_new_db();
	if ($OpenSimVersion==null) opensim_get_db_version($db);

	if ($db->exist_table('GridUser')) 	$table = 'GridUser';
	else if ($db->exist_table('users')) $table = 'users';
	else return 0;

	$utime = $db->get_update_time($table);
	return $utime;
}



//
// InnoDB では常に 0 を返す
//
function  opensim_get_update_time($table, &$db=null)
{
	if ($table=='') return 0;

	if (!is_object($db)) $db = opensim_new_db();
	$utime = $db->get_update_time($table);

	return $utime;
}



function  opensim_users_count_records(&$db=null)
{
	global $OpenSimVersion;

	if (!is_object($db)) $db = opensim_new_db();
	if ($OpenSimVersion==null) opensim_get_db_version($db);

	if ($db->exist_table('GridUser')) 	$table = 'GridUser';
	else if ($db->exist_table('users')) $table = 'users';
	else return 0;

	$count = opensim_count_records($table, $db);
	return $count;
}




function  opensim_count_records($table, &$db=null)
{
	if ($table=='') return 0;

	if (!is_object($db)) $db = opensim_new_db();

	$count = 0;
	$db->query('SELECT COUNT(*) FROM '.$table);
	if ($db->Errno==0) {
		list($count) = $db->next_record();
	}
	return $count;
}





function  opensim_check_db(&$db=null)
{
	global $OpenSimVersion;

	$ret['grid_status']		 = false;
	$ret['now_online']		 = 0;
	$ret['lastmonth_online'] = 0;
	$ret['user_count']		 = 0;
	$ret['region_count']	 = 0;

	//
	if (!is_object($db)) $db = opensim_new_db(3);
	if ($db==null) {
		return $ret;
	}
	if ($OpenSimVersion==null) opensim_get_db_version($db);

	if ($db->exist_table('regions')) {
		$db->query('SELECT COUNT(*) FROM regions');
		if ($db->Errno==0) {
			list($ret['region_count']) = $db->next_record();
		}
	}

	if ($db->exist_table('GridUser')) {				// 0.7
		$db->query('SELECT COUNT(*) FROM UserAccounts');
		list($ret['user_count']) = $db->next_record();
		if ($db->exist_table('Presence')) {			// 0.7
			$db->query("SELECT COUNT(DISTINCT Presence.UserID) FROM GridUser,Presence ".
							"WHERE Online='True' and GridUser.UserID=Presence.UserID and RegionID!='00000000-0000-0000-0000-000000000000'");
		}
		else {										// 0.7 StandAlone mode
			$db->query("SELECT COUNT(*) FROM GridUser WHERE Online='True'");
		}
		list($ret['now_online']) = $db->next_record();
		$db->query('SELECT COUNT(*) FROM GridUser WHERE Login>unix_timestamp(from_unixtime(unix_timestamp(now())-2419200))');
		list($ret['lastmonth_online']) = $db->next_record();
		$ret['grid_status'] = true;
	}
	else if ($db->exist_table('users')) {			// 0.6.x
		$db->query('SELECT COUNT(*) FROM users');
		list($ret['user_count']) = $db->next_record();
		$db->query("SELECT COUNT(*) FROM agents WHERE agentOnline='1'");
		list($ret['now_online']) = $db->next_record();
		$db->query('SELECT COUNT(*) FROM agents WHERE logintime>unix_timestamp(from_unixtime(unix_timestamp(now())-2419200))');
		list($ret['lastmonth_online']) = $db->next_record();
		$ret['grid_status'] = true;
	}

	return $ret;
}




/////////////////////////////////////////////////////////////////////////////////////
//
// for Avatar
//

function  opensim_get_avatars_num(&$db=null)
{
	global $OpenSimVersion;

	if (!is_object($db)) $db = opensim_new_db();
	if ($OpenSimVersion==null) opensim_get_db_version($db);

	$num = 0;

	if ($db->exist_table('UserAccounts')) {
		$db->query('SELECT COUNT(*) FROM UserAccounts');
		list($num) = $db->next_record();
	}
	else if ($db->exist_table('users')) {
		$db->query('SELECT COUNT(*) FROM users');
		list($num) = $db->next_record();
	}
	else {
		$num = -1;
	}

	return $num;
}



function  opensim_get_avatar_name($uuid, &$db=null)
{
	global $OpenSimVersion;

	$name = array();
	if (!isUUID($uuid) or $uuid=='00000000-0000-0000-0000-000000000000') return $name;

	if (!is_object($db)) $db = opensim_new_db();
	if ($OpenSimVersion==null) opensim_get_db_version($db);

	$firstname = null;
	$lastname  = null;
	$fullname  = null;

	if ($db->exist_table('UserAccounts')) {
		$db->query("SELECT FirstName,LastName FROM UserAccounts WHERE PrincipalID='$uuid'");
		list($firstname, $lastname) = $db->next_record();
	}
	else if ($db->exist_table('users')) {
		$db->query("SELECT username,lastname FROM users WHERE UUID='$uuid'");
		list($firstname, $lastname) = $db->next_record();
	}

	$fullname = $firstname.' '.$lastname;
	if ($fullname==' ') $fullname = null;

	$name['firstname'] = $firstname;
	$name['lastname']  = $lastname;
	$name['fullname']  = $fullname;

	return $name;
}



function  opensim_get_avatar_uuid($name, &$db=null)
{
	global $OpenSimVersion;

	if (!isAlphabetNumericSpecial($name)) return false;

	//$avatar_name = explode(' ', $name);
	$avatar_name = preg_split("/ /", $name, 0, PREG_SPLIT_NO_EMPTY);
	$firstname = $avatar_name[0];
	$lastname  = $avatar_name[1];
	if ($firstname=='') return false;
	if ($lastname=='') $lastname = 'Resident';

	if (!is_object($db)) $db = opensim_new_db();
	if ($OpenSimVersion==null) opensim_get_db_version($db);

	$uuid = null;
	if ($db->exist_table('UserAccounts')) {
		$db->query("SELECT PrincipalID FROM UserAccounts WHERE FirstName='$firstname' and LastName='$lastname'");
		list($uuid) = $db->next_record();
	}
	else if ($db->exist_table('users')) {
		$db->query("SELECT UUID FROM users WHERE username='$firstname' and lastname='$lastname'");
		list($uuid) = $db->next_record();
	}

	return $uuid;
}






function  opensim_get_avatar_info($uuid, &$db=null)
{
	global $OpenSimVersion;

	if (!isUUID($uuid)) return null;

	if (!is_object($db)) $db = opensim_new_db();
	if ($OpenSimVersion==null) opensim_get_db_version($db);

	//$online = false;
	$profileText  = '';
	$profileImage = '';
	$firstText	= '';
	$firstImage   = '';
	$partner	  = '';

	if ($db->exist_table('GridUser')) {
		$db->query('SELECT PrincipalID,FirstName,LastName,HomeRegionID,Created,Login FROM UserAccounts'.
						" LEFT JOIN GridUser ON PrincipalID=UserID WHERE PrincipalID='$uuid'");
		list($UUID, $firstname, $lastname, $regionUUID, $created, $lastlogin) = $db->next_record();
		$db->query("SELECT regionName,serverIP,serverHttpPort,serverURI FROM regions WHERE uuid='$regionUUID'");
		list($regionName, $serverIP, $serverHttpPort, $serverURI) = $db->next_record();
	}
	else if ($db->exist_table('users')) {
		$db->query("SELECT UUID,username,lastname,homeRegion,created,lastLogin,profileAboutText,profileFirstText,profileImage,profileFirstImage,partner".
						" FROM users WHERE uuid='$uuid'");
		list($UUID, $firstname, $lastname, $rgnHandle, $created, $lastlogin, $profileText, $firstText, $profileImage, $firstImage, $partner) = $db->next_record();
		$db->query("SELECT uuid,regionName,serverIP,serverHttpPort,serverURI FROM regions WHERE regionHandle='$rgnHandle'");
		list($regionUUID, $regionName, $serverIP, $serverHttpPort, $serverURI) = $db->next_record();
	}
	else {
		return null;
	}


	$fullname = $firstname.' '.$lastname;
	if ($fullname==' ') $fullname = null;

	$avinfo['UUID'] 		  = $UUID;
	$avinfo['firstname'] 	  = $firstname;
	$avinfo['lastname'] 	  = $lastname;
	$avinfo['fullname']   	  = $fullname;
	$avinfo['created'] 		  = $created;
	$avinfo['lastlogin'] 	  = $lastlogin;
	$avinfo['regionUUID'] 	  = $regionUUID;
	$avinfo['regionName'] 	  = $regionName;
	$avinfo['serverIP'] 	  = $serverIP;
	$avinfo['serverHttpPort'] = $serverHttpPort;
	$avinfo['serverURI'] 	  = $serverURI;
	$avinfo['profileText']	  = $profileText;
	$avinfo['profileImage']	  = $profileImage;
	$avinfo['firstText']	  = $firstText;
	$avinfo['firstImage'] 	  = $firstImage;
	$avinfo['partner']	  	  = $partner;
	//$avinfo['online']		  = $online;

	return $avinfo;
}



//
// Attention: When call this function, please check $condition for prevention of SQL Injection.
//
// return:
//		$avinfos[$UUID]['UUID']		 ... UUID
//		$avinfos[$UUID]['firstname'] ... first name
//		$avinfos[$UUID]['lastname']  ... lasti name
//		$avinfos[$UUID]['created']   ... created time
//		$avinfos[$UUID]['lastlogin'] ... lastlogin time
//		$avinfos[$UUID]['hmregion']  ... uuid of home region
//
function  opensim_get_avatars_infos($condition='', &$db=null)
{
	global $OpenSimVersion;

	if (!is_object($db)) $db = opensim_new_db();
	if ($OpenSimVersion==null) opensim_get_db_version($db);

	$avinfos = array();

	if ($db->exist_table('GridUser')) {
		$db->query('SELECT PrincipalID,FirstName,LastName,Created,Login,homeRegionID FROM UserAccounts '.
							'LEFT JOIN GridUser ON PrincipalID=UserID '.$condition);
	}
	else if ($db->exist_table('users')) {
		$db->query('SELECT users.UUID,username,lastname,created,lastLogin,regions.uuid FROM users '.
							'LEFT JOIN regions ON homeRegion=regionHandle '.$condition);
	}
	else {
		return null;
	}

	if ($db->Errno==0) {
		while (list($UUID,$firstname,$lastname,$created,$lastlogin,$hmregion) = $db->next_record()) {
			$avinfos[$UUID]['UUID']		 = $UUID;
			$avinfos[$UUID]['firstname'] = $firstname;
			$avinfos[$UUID]['lastname']  = $lastname;
			$avinfos[$UUID]['created']   = $created;
			$avinfos[$UUID]['lastlogin'] = $lastlogin;
			$avinfos[$UUID]['hmregion']  = $hmregion;
		}
	}

	return $avinfos;
}



//
// Attention: When call this function, please check $condition for prevention of SQL Injection.
//
function  opensim_get_avatars_profiles_from_users($condition='', &$db=null)
{
	global $OpenSimVersion;

	if (!is_object($db)) $db = opensim_new_db();
	if ($OpenSimVersion==null) opensim_get_db_version($db);

	$profs = null;

	if ($db->exist_table('users')) {
		$db->query('SELECT UUID,profileCanDoMask,profileWantDoMask,profileAboutText,'.
						'profileFirstText,profileImage,profileFirstImage,partner,email FROM users '.$condition);
		if ($db->Errno==0) {
			$profs = array();
			while (list($UUID,$skilmask,$wantmask,$abouttext,$firsttext,$image,$firstimage,$partnar,$email) = $db->next_record()) {
				$profs[$UUID]['UUID'] 		= $UUID;
				$profs[$UUID]['SkillsMask'] = $skilmask;
				$profs[$UUID]['WantToMask'] = $wantmask;
				$profs[$UUID]['AboutText']  = $abouttext;
				$profs[$UUID]['FirstAboutText'] = $firsttext;
				$profs[$UUID]['Image'] 	   	= $image;
				$profs[$UUID]['FirstImage'] = $firstimage;
				$profs[$UUID]['Partnar']	= $partnar;
				$profs[$UUID]['Email'] 	   	= $email;
			}
		}
	}

	return $profs;
}



function  opensim_get_avatar_online($uuid, &$db=null)
{
	global $OpenSimVersion;

	if (!isUUID($uuid)) return null;

	if (!is_object($db)) $db = opensim_new_db();
	if ($OpenSimVersion==null) opensim_get_db_version($db);

	$online = false;
	$null_region = '00000000-0000-0000-0000-000000000000';
	$region	  	 = '00000000-0000-0000-0000-000000000000';
	$rgn_name	 = '';

	/*
	if ($db->exist_field('Presence', 'Online')) {	// old 0.7Dev
		$db->query("SELECT Online,RegionID FROM Presence WHERE UserID='$uuid' and RegionID!='$null_region'");
		if ($db->Errno==0) {
			list($onln, $region) = $db->next_record();
			if ($onln=='true') {
				$rgn_name = opensim_get_region_name($region);
				if ($rgn_name!='') $online = true;
			}
		}
	}
	*/

	if ($db->exist_table('Presence')) {		// 0.7
		$db->query("SELECT RegionID FROM Presence,GridUser WHERE Presence.UserID='$uuid'".
					" and RegionID!='$null_region' and Presence.UserID=GridUser.UserID and GridUser.Online='True'");
		if ($db->Errno==0) {
			list($region) = $db->next_record();
			$rgn_name = opensim_get_region_name($region);
			if ($rgn_name!='') $online = true;
		}
	}
	else if ($db->exist_table('GridUser')) {		// 0.7 StandAlone mode
		$db->query("SELECT Online,LastRegionID FROM GridUser WHERE UserID='$uuid'");
		if ($db->Errno==0) {
			list($onln, $region) = $db->next_record();
			if ($onln=='True') {
				$rgn_name = opensim_get_region_name_by_i($region);
				if ($rgn_name!='') $online = true;
			}
		}
	}
	else if ($db->exist_table('agents')) {			// 0.6.x
		$db->query("SELECT agentOnline,currentRegion FROM agents WHERE UUID='$uuid' AND logoutTime='0'");
		if ($db->Errno==0) {
			list($onln, $region) = $db->next_record();
			if ($onln=='1') {
				$rgn_name = opensim_get_region_name($region);
				if ($rgn_name!='') $online = true;
			}
		}
	}

	$ret['online'] 		= $online;
	$ret['region_id'] 	= $region;
	$ret['region_name'] = $rgn_name;
	return $ret;
}




function  opensim_get_avatar_flags($uuid, &$db=null)
{
	global $OpenSimVersion;

	if (!isUUID($uuid)) return null;

	if (!is_object($db)) $db = opensim_new_db();
	if ($OpenSimVersion==null) opensim_get_db_version($db);

	// for 0.7
	if ($db->exist_table('UserAccounts')) {
		$db->query("SELECT UserFlags FROM UserAccounts WHERE PrincipalID='$uuid'");
		if ($db->Errno==0) {
			list($flags) = $db->next_record();
			return $flags;
		}
	}

	// for 0.6
	else if ($db->exist_table('users')) {
		$db->query("SELECT userFlags FROM users WHERE UUID='$uuid'");
		if ($db->Errno==0) {
			list($flags) = $db->next_record();
			return $flags;
		}
	}

	return 0;
}



function  opensim_set_avatar_flags($uuid, $flags=0, &$db=null)
{
	global $OpenSimVersion;

	if (!isUUID($uuid)) 	return false;
	if (!is_numeric($flags)) return false;

	if (!is_object($db)) $db = opensim_new_db();
	if ($OpenSimVersion==null) opensim_get_db_version($db);

	// for 0.7
	if ($db->exist_table('UserAccounts')) {
		$query_str = "UPDATE UserAccounts SET UserFlags='$flags' WHERE PrincipalID='$uuid'";
		$db->query($query_str);
		if ($db->Errno==0) return true;
	}

	// for 0.6
	else if ($db->exist_table('users')) {
		$query_str = "UPDATE users SET userFlags='$flags' WHERE UUID='$uuid'";
		$db->query($query_str);
		if ($db->Errno==0) return true;
	}

	return false;
}



function  opensim_create_avatar($UUID, $firstname, $lastname, $passwd, $homeregion, $base_avatar='00000000-0000-0000-0000-000000000000', &$db=null)
{
	global $OpenSimVersion;

	if (!isUUID($UUID)) return false;
	if (!isAlphabetNumericSpecial($firstname))  return false;
	if (!isAlphabetNumericSpecial($lastname))   return false;
	if (!isAlphabetNumericSpecial($passwd))		return false;
	if (!isAlphabetNumericSpecial($homeregion)) return false;

	if (!is_object($db)) $db = opensim_new_db();
	if ($OpenSimVersion==null) opensim_get_db_version($db);

	$nulluuid   = '00000000-0000-0000-0000-000000000000';
	$passwdsalt = make_random_hash();
	$passwdhash = md5(md5($passwd).":".$passwdsalt);

	$db->query("SELECT uuid,regionHandle FROM regions WHERE regionName='$homeregion'");
	$errno = $db->Errno;
	if ($errno==0) {
		list($regionID,$regionHandle) = $db->next_record();

		// for 0.7
		if ($db->exist_table('UserAccounts')) {
			$serviceURLs = 'HomeURI= GatekeeperURI= InventoryServerURI= AssetServerURI=';
			$db->query('INSERT INTO UserAccounts (PrincipalID,ScopeID,FirstName,LastName,Email,ServiceURLs,Created,UserLevel,UserFlags,UserTitle) '.
								  "VALUES ('$UUID','$nulluuid','$firstname','$lastname','','$serviceURLs','".time()."','0','0','')");
			$errno = $db->Errno;
			if ($errno==0) {

				if ($db->exist_table('GridUser')) {
					$db->query('INSERT INTO GridUser (UserID,HomeRegionID,HomePosition,HomeLookAt,LastRegionID,LastPosition,LastLookAt,Online,Login,Logout) '.
									"VALUES ('$UUID','$regionID','<128,128,0>','<0,0,0>','$regionID','<128,128,0>','<0,0,0>','false','0','0')");
				}
				$errno = $db->Errno;
			}
			if ($errno==0) {
				$db->query('INSERT INTO auth (UUID,passwordHash,passwordSalt,webLoginKey,accountType) '.
								  "VALUES ('$UUID','$passwdhash','$passwdsalt','$nulluuid','UserAccount')");
				$errno = $db->Errno;
			}
			//
			if ($errno==0) {
				opensim_create_avatar_inventory($UUID, $base_avatar, $db);
			}
			else {
				$db->query("DELETE FROM UserAccounts WHERE PrincipalID='$UUID'");
				$db->query("DELETE FROM auth		 WHERE UUID='$UUID'");
				$db->query("DELETE FROM inventoryfolders WHERE agentID='$UUID'");
				if ($db->exist_table('GridUser')) $db->query("DELETE FROM GridUser WHERE UserID='$UUID'");
			}
		}

		// for 0.6
		else if ($db->exist_table('users')) {
			$db->query('INSERT INTO users (UUID,username,lastname,passwordHash,passwordSalt,homeRegion,'.
										  'homeLocationX,homeLocationY,homeLocationZ,homeLookAtX,homeLookAtY,homeLookAtZ,'.
										  'created,lastLogin,userInventoryURI,userAssetURI,profileCanDoMask,profileWantDoMask,'.
										  'profileAboutText,profileFirstText,profileImage,profileFirstImage,homeRegionID) '.
						"VALUES ('$UUID','$firstname','$lastname','$passwdhash','$passwdsalt','$regionHandle',".
								"'128','128','128','100','100','100',".
								"'".time()."','0','','','0','0','','','$nulluuid','$nulluuid','$regionID')");

			if ($db->Errno!=0) {
				$db->query("DELETE FROM users WHERE UUID='$UUID'");
				if (!$db->exist_table('UserAccounts')) $errno = 99;
			}
		}
	}

	if ($errno!=0) return false;
	return true;
}



//
// データベースからアバタ情報を削除する．
//
function  opensim_delete_avatar($uuid, &$db=null)
{
	global $OpenSimVersion;

	if (!isUUID($uuid)) return false;

	if (!is_object($db)) $db = opensim_new_db();
	if ($OpenSimVersion==null) opensim_get_db_version($db);

	if ($db->exist_table('UserAccounts')) {
		$db->query("DELETE FROM UserAccounts WHERE PrincipalID='$uuid'");
		$db->query("DELETE FROM auth		 WHERE UUID='$uuid'");
		$db->query("DELETE FROM Avatars	  WHERE PrincipalID='$uuid'");
		$db->query("DELETE FROM Friends	  WHERE PrincipalID='$uuid'");
		$db->query("DELETE FROM tokens	   WHERE UUID='$uuid'");
		if ($db->exist_table('Presence')) $db->query("DELETE FROM Presence WHERE UserID='$uuid'");
		if ($db->exist_table('GridUser')) $db->query("DELETE FROM GridUser WHERE UserID='$uuid'");
		if ($db->exist_table('Avatars'))  $db->query("DELETE FROM Avatars  WHERE PrincipalID='$uuid'");
	}

	if ($db->exist_table('users')) {
		$db->query("DELETE FROM users		WHERE UUID='$uuid'");
		$db->query("DELETE FROM agents	   WHERE UUID='$uuid'");
		$db->query("DELETE FROM avatarappearance  WHERE Owner='$uuid'");
		$db->query("DELETE FROM avatarattachments WHERE UUID='$uuid'");
		$db->query("DELETE FROM userfriends	 WHERE ownerID='$uuid'");
	}

	$db->query("DELETE FROM estate_managers	 WHERE uuid='$uuid'");
	$db->query("DELETE FROM estate_users	 WHERE uuid='$uuid'");
	$db->query("DELETE FROM estateban		 WHERE bannedUUID='$uuid'");
	$db->query("DELETE FROM inventoryfolders WHERE agentID='$uuid'");
	$db->query("DELETE FROM inventoryitems	 WHERE avatarID='$uuid'");
	$db->query("DELETE FROM landaccesslist   WHERE AccessUUID='$uuid'");
	$db->query("DELETE FROM regionban		 WHERE bannedUUID='$uuid'");

	// for DTL Money Server
	if ($db->exist_table('balances')) {
		//$db->query("DELETE FROM transactions WHERE UUID='$uuid'");
		$db->query("DELETE FROM balances WHERE user LIKE '".$uuid."@%'");
		$db->query("DELETE FROM userinfo WHERE user LIKE '".$uuid."@%'");
	}

	return true;
}




/////////////////////////////////////////////////////////////////////////////////////
//
// for Region
//

function  opensim_get_regions_num(&$db=null)
{
	global $OpenSimVersion;

	$num = 0;

	if (!is_object($db)) $db = opensim_new_db();
	if ($OpenSimVersion==null) opensim_get_db_version($db);

	$db->query('SELECT COUNT(*) FROM regions');
	list($num) = $db->next_record();

	return $num;
}



function  opensim_get_region_uuid($name, &$db=null)
{
	global $OpenSimVersion;

//	$name = addslashes($name);
//	if (!isAlphabetNumericSpecial($name)) return false;

	if (!is_object($db)) $db = opensim_new_db();
	if ($OpenSimVersion==null) opensim_get_db_version($db);

	$uuid = '';
	if ($name!='') {
		$query = "SELECT uuid FROM regions WHERE regionName='$name'";
		$db->query($query);
		list($uuid) = $db->next_record();
	}

	return $uuid;
}



function  opensim_get_region_name($id, &$db=null)
{
	global $OpenSimVersion;

	if (!isUUID($id) and !is_numeric($id)) return null;

	if (!is_object($db)) $db = opensim_new_db();
	if ($OpenSimVersion==null) opensim_get_db_version($db);

	if (isUUID($id)) {
		$db->query("SELECT regionName FROM regions WHERE uuid='$id'");
		list($regionName) = $db->next_record();
	}
	else {
		$db->query("SELECT regionName FROM regions WHERE regionHandle='$id'");
		list($regionName) = $db->next_record();
	}

	return $regionName;
}



//
// Attention: When call this function, please check $condition for prevention of SQL Injection.
//
function  opensim_get_regions_names($condition='', &$db=null)
{
	global $OpenSimVersion;

	if (!is_object($db)) $db = opensim_new_db();
	if ($OpenSimVersion==null) opensim_get_db_version($db);

	if ($condition!='') {
		$replace_str = '/[;\'#-]/';
		$$condition = preg_replace($replace_str, '', $condition);
	}

	$regions = array();
	$db->query("SELECT regionName FROM regions ".$condition);
	while ($db->Errno==0 and list($region)=$db->next_record()) {
		$regions[] = $region;
	}

	return $regions;
}



function  opensim_get_region_info($region, &$db=null)
{
	global $OpenSimVersion;

	if (!isUUID($region)) return null;
	if ($region=='00000000-0000-0000-0000-000000000000') return null;

	if (!is_object($db)) $db = opensim_new_db();
	if ($OpenSimVersion==null) opensim_get_db_version($db);

	$sql = "SELECT regionHandle,regionName,regionSecret,serverIP,serverHttpPort,serverURI,locX,locY,sizeX,sizeY FROM regions WHERE uuid='$region'";
	$db->query($sql);

	list($regionHandle, $regionName, $regionSecret, $serverIP, $serverHttpPort, $serverURI, $locX, $locY, $sizeX, $sizeY) = $db->next_record();
	$rginfo = opensim_get_estate_info($region, $db);

	$rginfo['regionHandle']   = $regionHandle;
	$rginfo['regionName'] 	  = $regionName;
	$rginfo['regionSecret']   = $regionSecret;
	$rginfo['serverIP'] 	  = $serverIP;
	$rginfo['serverHttpPort'] = $serverHttpPort;
	$rginfo['serverURI'] 	  = $serverURI;
	$rginfo['locX'] 		  = $locX;
	$rginfo['locY'] 		  = $locY;
	$rginfo['sizeX'] 		  = $sizeX;
	$rginfo['sizeY'] 		  = $sizeY;

	return $rginfo;
}



//
// Attention: When call this function, please check $condition for prevention of SQL Injection.
//
//	return:
//		$rginfos[$UUID]['UUID']		  	 ... UUID
//		$rginfos[$UUID]['regionName'] 	 ... name of region
//		$rginfos[$UUID]['locX']		  	 ... location X
//		$rginfos[$UUID]['locY']		  	 ... location Y
//		$rginfos[$UUID]['sizeX']		 ... size X
//		$rginfos[$UUID]['sizeY']		 ... size Y
//		$rginfos[$UUID]['serverIP']	  	 ... IP address of server
//		$rginfos[$UUID]['serverPort'] 	 ... port num of server
//		$rginfos[$UUID]['serverURI']  	 ... URI of server
//		$rginfos[$UUID]['owner_uuid'] 	 ... UUID of region owner
//		$rginfos[$UUID]['estate_id'] 	 ... ID of estate
//		$rginfos[$UUID]['estate_owner']  ... UUID of estate owner
//		$rginfos[$UUID]['estate_name']   ... estate name
//		$rginfos[$UUID]['est_firstname'] ... first name
//		$rginfos[$UUID]['est_lastname']  ... last name
//		$rginfos[$UUID]['est_fullname']  ... full name
//
function  opensim_get_regions_infos($condition='', &$db=null)
{
	global $OpenSimVersion;

	if (!is_object($db)) $db = opensim_new_db();
	if ($OpenSimVersion==null) opensim_get_db_version($db);

	if ($condition!='') {
		$replace_str = '/[;\'#-]/';
		$$condition = preg_replace($replace_str, '', $condition);
	}

	$rginfos = array();

	$items = ' regions.uuid,regionName,locX,locY,sizeX,sizeY,serverIP,serverURI,serverHttpPort,owner_uuid,estate_map.EstateID,EstateOwner,EstateName,';
 	$join1 = ' FROM regions LEFT JOIN estate_map ON RegionID=regions.uuid ';
 	$join2 = ' LEFT JOIN estate_settings ON estate_map.EstateID=estate_settings.EstateID ';

	if ($db->exist_table('UserAccounts')) {
		$uname = 'firstname,lastname ';
		$join3 = ' LEFT JOIN UserAccounts ON EstateOwner=UserAccounts.PrincipalID ';
		$frmwh = ' FROM UserAccounts WHERE UserAccounts.PrincipalID=';
	}
	else if ($db->exist_table('users')) {
		$uname = 'username,lastname ';
		$join3 = ' LEFT JOIN users ON EstateOwner=users.UUID ';
		$frmwh = ' FROM users WHERE users.UUID=';
	}
	else {
		return null;
	}

	$query_str = 'SELECT '.$items.$uname.$join1.$join2.$join3.$condition;

	$db->query($query_str);
	if ($db->Errno==0) {
		while (list($UUID,$regionName,$locX,$locY,$sizeX,$sizeY,$serverIP,$serverURI,$serverPort,
						$owneruuid,$estateid,$estateowner,$estatename,$firstname,$lastname) = $db->next_record()) {
			$rginfos[$UUID]['UUID']		  	= $UUID;
			$rginfos[$UUID]['regionName'] 	= $regionName;
			$rginfos[$UUID]['locX']		  	= $locX;
			$rginfos[$UUID]['locY']		  	= $locY;
			$rginfos[$UUID]['sizeX']		= $sizeX;
			$rginfos[$UUID]['sizeY']		= $sizeY;
			$rginfos[$UUID]['serverIP']	  	= $serverIP;
			$rginfos[$UUID]['serverPort'] 	= $serverPort;
			$rginfos[$UUID]['serverURI']  	= $serverURI;
			$rginfos[$UUID]['owner_uuid'] 	= $owneruuid;
			$rginfos[$UUID]['estate_id'] 	= $estateid;
			$rginfos[$UUID]['estate_owner'] = $estateowner;
			$rginfos[$UUID]['estate_name']  = $estatename;
			$rginfos[$UUID]['est_firstname']= $firstname;
			$rginfos[$UUID]['est_lastname'] = $lastname;
			$rginfos[$UUID]['est_fullname'] = null;
			$fullname = $firstname.' '.$lastname;
			if ($fullname!=' ') $rginfos[$UUID]['est_fullname'] = $fullname;
		}
	}

	// Region Owner
	foreach($rginfos as $region) {
		$rginfos[$region['UUID']]['rgn_firstname'] = null;
		$rginfos[$region['UUID']]['rgn_lastname']  = null;
		$rginfos[$region['UUID']]['rgn_fullname']  = null;

		if ($region['owner_uuid']!=null) {
			$db->query('SELECT '.$uname.$frmwh."'".$region['owner_uuid']."'");
			list($firstname,$lastname) = $db->next_record();
			$rginfos[$region['UUID']]['rgn_firstname'] = $firstname;
			$rginfos[$region['UUID']]['rgn_lastname']  = $lastname;
			$fullname = $firstname.' '.$lastname;
			if ($fullname!=' ') $rginfos[$region['UUID']]['rgn_fullname'] = $fullname;
		}
	}

	return $rginfos;
}







/////////////////////////////////////////////////////////////////////////////////////
//
// for Home Region
//

function  opensim_get_home_region($uuid, &$db=null)
{
	global $OpenSimVersion;

	if (!isUUID($uuid)) return null;

	if (!is_object($db)) $db = opensim_new_db();
	if ($OpenSimVersion==null) opensim_get_db_version($db);

	$region_name = '';
	if ($db->exist_table('GridUser')) {
		$db->query("SELECT regionName FROM GridUser,regions WHERE HomeRegionID=uuid AND UserID='$uuid'");
		list($region_name) = $db->next_record();
	}
	else if ($db->exist_table('users')) {
		$db->query("SELECT regionName FROM users,regions WHERE homeRegionID=regions.uuid AND users.UUID='$uuid'");
		list($region_name) = $db->next_record();
	}

	return $region_name;
}



function  opensim_set_home_region($uuid, $hmregion, $pos_x='128', $pos_y='128', $pos_z='0', &$db=null)
{
	global $OpenSimVersion;

	if (!isUUID($uuid)) return false;
//	if (!isAlphabetNumericSpecial($hmregion)) return false;
	if (!is_numeric($pos_x) or !is_numeric($pos_y) or !is_numeric($pos_z)) return false;

	if (!is_object($db)) $db = opensim_new_db();
	if ($OpenSimVersion==null) opensim_get_db_version($db);

//	$hmregion = addslashes($hmregion);
	$db->query("SELECT uuid,regionHandle FROM regions WHERE regionName='$hmregion'");
	$errno = $db->Errno;
	if ($errno==0) {
		list($regionID, $regionHandle) = $db->next_record();

		if ($db->exist_table('GridUser')) {
			$homePosition = "<$pos_x,$pos_y,$pos_z>";
			$db->query("UPDATE GridUser SET HomeRegionID='$regionID',HomePosition='$homePosition' WHERE UserID='$uuid'");
			$errno = $db->Errno;
		}

		if ($db->exist_table('users') and $errno==0) {
			$homePosition = "homeLocationX='$pos_x',homeLocationY='$pos_y',homeLocationZ='$pos_z' ";
			$db->query("UPDATE users SET homeRegion='$regionHandle',homeRegionID='$regionID',$homePosition WHERE UUID='$uuid'");
			if ($db->Errno!=0) {
				if (!$db->exist_table('auth')) $errno = 99;
			}
		}
	}

	if ($errno!=0) return false;
	return true;
}




/////////////////////////////////////////////////////////////////////////////////////
//
// for Estate
//

//
// リージョンID $region のエステート名を $estate にし，オーナーを $owner(UUID) にする．
// エステート名と オーナー(UUID) の組み合わせが存在しない場合は，新しくエステートを作成する．
//
function  opensim_set_region_estate($region, $estate, $owner, &$db=null)
{
	if (!isUUID($region) or $estate=='' or !isUUID($owner)) return false;

	if (!is_object($db)) $db = opensim_new_db();

	$estate_id = opensim_create_estate($estate, $owner, $db);
	if ($estate_id==0) return false;

	$db->query("UPDATE estate_map SET EstateID='$estate_id' WHERE RegionID='$region'");

	if ($db->Errno!=0) return false;
	return true;
}


//
// Estate名 $estate, オーナーUUID $owner のエステートを作成して ID を返す．
// 既に有る場合も,そのエステートのIDを返す．
// エラーの場合は 0を返す．
//
function  opensim_create_estate($estate, $owner, &$db=null)
{
	if ($estate=='' or !isUUID($owner)) return 0;

	if (!is_object($db)) $db = opensim_new_db();

	$db->query("SELECT EstateID FROM estate_settings WHERE EstateName='$estate' AND EstateOwner='$owner'");
	if ($db->Errno==0) {
		list($eid) = $db->next_record();
		if (intval($eid)>0) return $eid;
	}

    $insert_columns = 'EstateName,AbuseEmailToEstateOwner,DenyAnonymous,ResetHomeOnTeleport,FixedSun,DenyTransacted,BlockDwell,'.
					  'DenyIdentified,AllowVoice,UseGlobalTime,PricePerMeter,TaxFree,AllowDirectTeleport,RedirectGridX,RedirectGridY,'.
					  'ParentEstateID,SunPosition,EstateSkipScripts,BillableFactor,PublicAccess,AbuseEmail,EstateOwner,DenyMinors,'.
					  'AllowLandmark,AllowParcelChanges,AllowSetHome';
    $insert_values = "'$estate','0','0','0','0','0','0','0','1','1','1','0','1','0','0','1','0','0','0','1','','$owner','0','1','1','1'";

    $db->query("INSERT INTO estate_settings ($insert_columns) VALUES ($insert_values)");

	$db->query("SELECT EstateID FROM estate_settings WHERE EstateName='$estate' AND EstateOwner='$owner'");
	if ($db->Errno==0) {
		list($eid) = $db->next_record();
		return $eid;
	}
	return 0;
}



function  opensim_get_estates_infos(&$db=null)
{
	global $OpenSimVersion;

	$estates = array();

	if (!is_object($db)) $db = opensim_new_db();
	if ($OpenSimVersion==null) opensim_get_db_version($db);

	$db->query('SELECT EstateID,EstateOwner,EstateName FROM estate_settings ORDER BY EstateID');
	if ($db->Errno==0) {
		while (list($estateid, $estateown, $estatename) = $db->next_record()) {
			$estates[$estateid]['estate_id'] 	= $estateid;
			$estates[$estateid]['estate_owner']	= $estateown;
			$estates[$estateid]['estate_name'] 	= $estatename;
			$estates[$estateid]['firstname'] 	= '';
			$estates[$estateid]['lastname']  	= '';
			$estates[$estateid]['fullname']  	= '';
		}
	}

	foreach($estates as $estate) {
		$avatar = opensim_get_avatar_name($estate['estate_owner']);
		if ($avatar!=null) {
			$estateid = $estate['estate_id'];
			$estates[$estateid]['firstname'] = $avatar['firstname'];
			$estates[$estateid]['lastname']  = $avatar['lastname'];
			$estates[$estateid]['fullname']  = $avatar['fullname'];
		}
	}

	return $estates;
}



//
// SIMのリージョンIDからエステートの情報を返す．
//
function  opensim_get_estate_info($region, &$db=null)
{
	global $OpenSimVersion;

	if (!isUUID($region)) return null;

	$firstname = null;
	$lastname  = null;
	$fullname  = null;
	$owneruuid = null;

	if (!is_object($db)) $db = opensim_new_db();
	if ($OpenSimVersion==null) opensim_get_db_version($db);

	if ($db->exist_table('UserAccounts')) {
		$rqdt = 'PrincipalID,FirstName,LastName,estate_settings.EstateID,EstateOwner,EstateName';
		$tbls = 'UserAccounts,estate_map,estate_settings';
		$cndn = "RegionID='$region' AND estate_map.EstateID=estate_settings.EstateID AND EstateOwner=PrincipalID";
	}
	else if ($db->exist_table('users')) {
		$rqdt = 'UUID,username,lastname,estate_settings.EstateID,EstateOwner,EstateName';
		$tbls = 'users,estate_map,estate_settings';
		$cndn = "RegionID='$region' AND estate_map.EstateID=estate_settings.EstateID AND EstateOwner=UUID";
	}
	else {
		return null;
	}

	$db->query('SELECT '.$rqdt.' FROM '.$tbls.' WHERE '.$cndn);
	list($owneruuid, $firstname, $lastname, $estateid, $estateowner, $estatename) = $db->next_record();

	$fullname = $firstname.' '.$lastname;
	if ($fullname==' ') $fullname = null;

	// owner name
	$name['firstname']   = $firstname;
	$name['lastname']    = $lastname;
	$name['fullname']    = $fullname;
	//
	$name['owner_uuid']  = $owneruuid;
	$name['estate_id']   = $estateid;
	$name['estate_owner']= $estateowner;
	$name['estate_name'] = $estatename;

	return $name;
}


//
// リージョンのエステートを変更する．
//
function  opensim_set_region_estateid($region, $estateid, &$db=null)
{
	if (!isUUID($region) or !is_numeric($estateid)) return false;

	if (!is_object($db)) $db = opensim_new_db();

	$db->query("SELECT EstateID FROM estate_settings WHERE EstateID='$estateid'");
	list($esid) = $db->next_record();
	if (intval($esid)==0) return;

	$db->query("SELECT EstateID FROM estate_map WHERE RegionID='$region'");
	list($esid) = $db->next_record();

	if (intval($esid)!=$estateid) {
		$db->query("UPDATE estate_map SET EstateID='$estateid' WHERE RegionID='$region'");
	}
	else if (intval($esid)==0) {
		$db->query("INSERT INTO estate_map (RegionID,EstateID) VALUES ('$region','$estateid')");
	}
}


//
// リージョンのエステートを変更せずに，オーナーのみ変更する．
// 従って，同じエステートを持つ他のリージョンのオーナーも変更される．
//
function  opensim_set_estate_owner($region, $owner, &$db=null)
{
	if (!isUUID($region) or !isUUID($owner))  return false;

	if (!is_object($db)) $db = opensim_new_db();

	$db->query("UPDATE estate_settings,estate_map SET EstateOwner='$owner' WHERE estate_settings.EstateID=estate_map.EstateID AND RegionID='$region'");
	$errno = $db->Errno;

	if ($errno==0) $db->query("UPDATE regions SET owner_uuid='$owner' WHERE uuid='$region'");
	if ($errno!=0) return false;

	return true;
}


function  opensim_del_estate($id, &$db=null)
{
	if (!is_numeric($id)) return;
	if (!is_object($db)) $db = opensim_new_db();

	$db->query("DELETE from estate_settings WHERE EstateID=$id");
}


function  opensim_update_estate($id, $name, $owner, &$db=null)
{
	global $OpenSimVersion;

	if (!is_numeric($id)) return;
	if (!$name and !$owner) return;
	if (!is_object($db)) $db = opensim_new_db();
	if ($OpenSimVersion==null) opensim_get_db_version($db);

	if ($name) {
		$db->query("UPDATE estate_settings SET EstateName='$name' WHERE EstateID=$id");
	}

	$uuid = opensim_get_avatar_uuid($owner);
	if ($uuid) {
		$db->query("UPDATE estate_settings SET EstateOwner='$uuid' WHERE EstateID=$id");
	}
}




/////////////////////////////////////////////////////////////////////////////////////
//
// for Parcel
//

function  opensim_get_parcel_name($parcel, &$db=null)
{
	global $OpenSimVersion;

	if (!isUUID($parcel)) return null;

	if (!is_object($db)) $db = opensim_new_db();
	if ($OpenSimVersion==null) opensim_get_db_version($db);

	$name = null;
	$db->query("SELECT name FROM land WHERE UUID='$parcel'");

	if ($db->Errno==0) list($name) = $db->next_record();

	return $name;
}



function  opensim_get_parcel_info($parcel, &$db=null)
{
	global $OpenSimVersion;

	if (!isUUID($parcel)) return null;

	if (!is_object($db)) $db = opensim_new_db();
	if ($OpenSimVersion==null) opensim_get_db_version($db);

	$info = array();

	$items = "RegionUUID,Name,Description,OwnerUUID,Category,SalePrice,LandStatus,LandFlags,LandingType,Dwell";
	$query_str = "SELECT ".$items." FROM land WHERE UUID='".$parcel."'";

	$db->query($query_str);
	if ($db->Errno==0) $info = $db->next_record();

	return $info;
}




/////////////////////////////////////////////////////////////////////////////////////
//
// for Assets
//

function  opensim_get_asset_data($uuid, &$db=null)
{
	global $OpenSimVersion;

	if (!isUUID($uuid)) return $asset;

	if (!is_object($db)) $db = opensim_new_db();
	if ($OpenSimVersion==null) opensim_get_db_version($db);

	$asset = array();

	$db->query("SELECT name,description,assetType,data,asset_flags,CreatorID FROM assets WHERE id='$uuid'");
	list($name, $desc, $type, $data, $flag, $creator) = $db->next_record();

	$asset['UUID'] 	  = $uuid;
	$asset['name'] 	  = $name;
	$asset['desc'] 	  = $desc;
	$asset['type'] 	  = $type;
	$asset['data'] 	  = $data;
	$asset['flag'] 	  = $flag;
	$asset['creator'] = $creator;

	return $asset;
}



function  opensim_display_texture_data($uuid, $prog, $xsize='0', $ysize='0', $cachedir='', $use_tga=false)
{
	if (!isUUID($uuid)) return false;
	if ($prog==null or $prog=='') return false;

	if ($cachedir=='') $cachedir = '/tmp';
	$cachefile = $cachedir.'/'.$uuid;


	// PHP module
	$imagick = null;
	if ($prog=='imagick') {
		if (class_exists('Imagick')) {
			$imagick = new Imagick();
		}
		else {
			echo '<h4>PHP module Imagick is not installed!!</h4>';
			return false;
		}
	}

	// Linux Command
	else {
		if (file_exists('/usr/local/bin/'.$prog)) 	   $path = '/usr/local/bin/';
		else if (file_exists('/usr/bin/'.$prog)) 	   $path = '/usr/bin/';
		else if (file_exists('/usr/X11R6/bin/'.$prog)) $path = '/usr/X11R6/bin/';
		else if (file_exists('/bin/'. $prog)) 		   $path = '/bin/';
		else {
			echo '<h4>program '.$prog.' is not found!!</h4>';
			return false;
		}

		if ($prog=='jasper') {		// JasPer does not support Targa image format.
			$use_tga = false;
		}
	}


	// Check j2k to TGA command
	if ($use_tga) {
		$tga_com = get_j2k_to_tga_command();
		if ($tga_com=='') $use_tga = false;
	}


	// get and save image
	if (! ((!$use_tga and file_exists($cachefile)) or ($use_tga and file_exists($cachefile.'.tga')))) {
		$imgdata = '';

		// from MySQL Server
		$asset = opensim_get_asset_data($uuid);
		if ($asset) {
			if ($asset['type']==0) {
				$imgdata = $asset['data'];
			}
		}
		else {
			echo '<h4>asset uuid is not found!! ('.htmlspecialchars($uuid).')</h4>';
			return false;
		}

/*		// from Asset Server
		//$asset_url = $ASSET_SERVER_URL.'/assets/'.$uuid;
		$asset_url = 'http://202.26.159.200:8003/assets/'.$uuid;
		$fp = fopen($asset_url, "rb");
		stream_set_timeout($fp, 5);
		$content = stream_get_contents($fp);
		fclose($fp);
		if (!$content) {
			echo '<h4>asset uuid is not found!! ('.htmlspecialchars($uuid).')</h4>';
			return false;
		}

		$xml = new SimpleXMLElement($content);
		$imgdata = base64_decode($xml->Data);
*/

		// Save Image Data
		$fp = fopen($cachefile, 'wb');
		fwrite($fp, $imgdata);
		fclose($fp);

		if ($use_tga) {
			if (!j2k_to_tga($cachefile)) $use_tga = false;
		}
	}

	if ($use_tga && file_exists($cachefile.'.tga')) $cachefile .= '.tga';


	//
	// program for image processing of jpeg2000
	//

	// Imagick of PHP
	if ($prog=='imagick' and $imagick!=null) {
		$ret = $imagick->readImage($cachefile);
		if (!$ret) {
			echo '<h4>Imagick could not read '.$cachefile.'!!</h4>';
			return false;
		}
		$imagick->setImageFormat('JPEG');
		if ($xsize>0 and $ysize>0) {
			$imagick->scaleImage($xsize, $ysize);
		}

		header("Content-Type: image/jpeg");
		echo $imagick;
	}

	// ImageMagic (convert)
	else if ($prog=='convert') {
		$imgsize = '';
		if ($xsize>0 and $ysize>0) $imgsize = ' -resize '.$xsize.'x'.$ysize.'!';
		$prog = $path.'convert '. $cachefile.$imgsize.' jpeg:-';

		header("Content-Type: image/jpeg");
		passthru($prog);
	}

	// Jasper
	else if ($prog=='jasper') {
		$conv = '';
		if ($xsize>0 and $ysize>0) {
			$conv = get_image_size_convert_command($xsize, $ysize);
			if ($conv!='') $conv = ' | '.$conv;
		}
		$prog = $path.'jasper -f '.$cachefile.' -T jpg'.$conv;

		header("Content-Type: image/jpeg");
		passthru($prog);
	}

	return true;
}




/////////////////////////////////////////////////////////////////////////////////////
//
// for Inventory
//


function  opensim_create_avatar_inventory($uuid, $base_uuid, &$db=null)
{
	if (!is_object($db)) $db = opensim_new_db();

	$name = opensim_get_avatar_name($base_uuid, $db);

	if (isset($name['fullname'])) {
		$folder = opensim_create_inventory_folders_dup($uuid, $base_uuid, $db);
		$invent = opensim_create_inventory_items_dup($uuid, $base_uuid, $folder, $db);
		opensim_create_avatar_wear_dup($uuid, $base_uuid, $invent, $db);
	}
	else {
		opensim_create_default_inventory_folders($uuid, $db);
		$invent = opensim_create_default_inventory_items($uuid, $db);
		opensim_create_default_avatar_wear($uuid, $invent, $db);
	}
}




function  opensim_create_avatar_wear_dup($touuid, $fromid, $invent, &$db=null)
{
	if (!$invent or !is_array($invent)) return false;
	if (!is_object($db)) $db = opensim_new_db();
	if (!$db->exist_table('Avatars')) return false;

	$db->query("SELECT * FROM Avatars WHERE PrincipalID='$fromid'");
	$errno = $db->Errno;

	if ($errno==0) {
		$db2 = opensim_new_db();
		while (list($PrincipalID,$Name,$Value) = $db->next_record()) {
			if (!strncasecmp($Name, 'Wearable ', 9)) {
				$id = explode(':', $Value);
				if (isUUID($id[0]) and isUUID($id[1])) {
					if (isset($invent[$id[0]])) $Value = $invent[$id[0]].':'.$id[1];
				}
			}
			else if (!strncasecmp($Name, '_ap_', 4)) {
				if (isUUID($Value)) {
					if (isset($invent[$Value])) $Value = $invent[$Value];
				}
			}

			$Name  = addslashes($Name);
			$Value = addslashes($Value);
			//
			$db2->query("INSERT INTO Avatars (PrincipalID,Name,Value) VALUES ('$touuid','$Name','$Value')");
		}
	}

	if ($errno!=0) return false;
	return true;
}




//
// コピーしたアイテムのIDのコピー元とコピー先の対応を格納した配列を返す．
//
function  opensim_create_inventory_items_dup($touuid, $fromid, $folder, &$db=null)
{
	$invent = array();
	if (!$folder or !is_array($folder)) return $invent;
	if (!is_object($db)) $db = opensim_new_db();

	$db->query("SELECT * FROM inventoryitems WHERE avatarID='$fromid'");
	$errno = $db->Errno;

	if ($errno==0) {
		$db2 = opensim_new_db();
		while (list($assetID,$assetType,$inventoryName,$inventoryDescription,$inventoryNextPermissions,$inventoryCurrentPermissions,
		 			$invType,$creatorID,$inventoryBasePermissions,$inventoryEveryOnePermissions,$salePrice,$saleType,$creationDate,
		 			$groupID,$groupOwned,$flags,$inventoryID,$avatarID,$parentFolderID,$inventoryGroupPermissions) = $db->next_record()) {

			if (isset($folder[$parentFolderID]) and $folder[$parentFolderID]->type=='46') continue;		// Current Outfit

			$inventoryName = addslashes($inventoryName);
			$inventoryDescription = addslashes($inventoryDescription);

			$avatarID = $touuid;
			$inventID = make_random_guid();
			if (isset($folder[$parentFolderID])) $parent = $folder[$parentFolderID]->folderID;
			else 								 $parent = '00000000-0000-0000-0000-000000000000';
			$invent[$inventoryID] = $inventID;
			//
			$db2->query('INSERT INTO inventoryitems (assetID,assetType,inventoryName,inventoryDescription,inventoryNextPermissions,'.
									'inventoryCurrentPermissions,invType,creatorID,inventoryBasePermissions,inventoryEveryOnePermissions,salePrice,'.
									'saleType,creationDate,groupID,groupOwned,flags,inventoryID,avatarID,parentFolderID,inventoryGroupPermissions) '.
							"VALUES ('$assetID','$assetType','$inventoryName','$inventoryDescription','$inventoryNextPermissions','$inventoryCurrentPermissions',".
		 							"'$invType','$creatorID','$inventoryBasePermissions','$inventoryEveryOnePermissions','$salePrice','$saleType','$creationDate',".
		 							"'$groupID','$groupOwned','$flags','$inventID','$avatarID','$parent','$inventoryGroupPermissions')");
		}
	}

	return $invent;
}




//
// 作成したフォルダーの情報を返す．キーはコピー元フォルダーのフォルダーID
//
function  opensim_create_inventory_folders_dup($touuid, $fromid, &$db=null)
{
	if (!is_object($db)) $db = opensim_new_db();

	$folder = array();
	if (!isUUID($fromid)) return $folder;

	$db->query("SELECT * FROM inventoryfolders WHERE agentID='$fromid'");
	$errno = $db->Errno;

	if ($errno==0) {
		while(list($folderName,$type,$version,$folderID,$agentID,$parentFolderID) = $db->next_record()) {
			$folder[$folderID] = new stdClass();
			$folder[$folderID]->folderName = $folderName;
			$folder[$folderID]->type = $type;
			$folder[$folderID]->version	= $version;
			$folder[$folderID]->folderID = make_random_guid();
			$folder[$folderID]->agentID = $touuid;
			$folder[$folderID]->parentFolderID = $parentFolderID;
		}

		foreach($folder as $fid=>$fld) {
			$parent = '00000000-0000-0000-0000-000000000000';
			if ($fld->parentFolderID) {
				if (isset($folder[$fld->parentFolderID])) $parent = $folder[$fld->parentFolderID]->folderID;
			}
			$folder[$fid]->parentFolderID = $parent;

			$folderName = addslashes($fld->folderName);
			$folderType = $fld->type;
			$version	= $fld->version;
			$folderID   = $fld->folderID;
			//
			$db->query("INSERT INTO inventoryfolders (folderName,type,version,folderID,agentID,parentFolderID) ".
					   		"VALUES ('$folderName','$folderType','$version','$folderID','$touuid','$parent')");
		}
	}

	return $folder;
}




function  opensim_create_default_avatar_wear($uuid, $invent, &$db=null)
{
	if (!is_object($db)) $db = opensim_new_db();
	if (!$db->exist_table('Avatars')) return false;

	$db->query("INSERT INTO Avatars (PrincipalID,Name,Value) VALUES ('$uuid','AvatarHeight','".DEFAULT_AVATAR_HEIGHT."')");
	$db->query("INSERT INTO Avatars (PrincipalID,Name,Value) VALUES ('$uuid','AvatarType','1')");
	$db->query("INSERT INTO Avatars (PrincipalID,Name,Value) VALUES ('$uuid','Serial','0')");
	$db->query("INSERT INTO Avatars (PrincipalID,Name,Value) VALUES ('$uuid','VisualParams','".DEFAULT_AVATAR_PARAMS."')");

	if (is_array($invent)) {
		if (isset($invent['Shape']))
			$db->query("INSERT INTO Avatars (PrincipalID,Name,Value) VALUES ('$uuid','Wearable 0:0','".$invent['Shape'].':'.DEFAULT_ASSET_SHAPE."')");
		if (isset($invent['Skin']))
			$db->query("INSERT INTO Avatars (PrincipalID,Name,Value) VALUES ('$uuid','Wearable 1:0','".$invent['Skin']. ':'.DEFAULT_ASSET_SKIN."')");
		if (isset($invent['Hair']))
			$db->query("INSERT INTO Avatars (PrincipalID,Name,Value) VALUES ('$uuid','Wearable 2:0','".$invent['Hair']. ':'.DEFAULT_ASSET_HAIR."')");
		if (isset($invent['Eyes']))
			$db->query("INSERT INTO Avatars (PrincipalID,Name,Value) VALUES ('$uuid','Wearable 3:0','".$invent['Eyes']. ':'.DEFAULT_ASSET_EYES."')");
		if (isset($invent['Shirt']))
			$db->query("INSERT INTO Avatars (PrincipalID,Name,Value) VALUES ('$uuid','Wearable 4:0','".$invent['Shirt'].':'.DEFAULT_ASSET_SHIRT."')");
		if (isset($invent['Pants']))
			$db->query("INSERT INTO Avatars (PrincipalID,Name,Value) VALUES ('$uuid','Wearable 5:0','".$invent['Pants'].':'.DEFAULT_ASSET_PANTS."')");
	}

	return true;
}




function  opensim_create_default_inventory_items($uuid, &$db=null)
{
	if (!isUUID($uuid)) return false;
	if (!is_object($db)) $db = opensim_new_db();

	$db->query("SELECT folderID FROM inventoryfolders WHERE agentID='$uuid' AND type='13'");	// Body Parts Folder
	list($body_folder) = $db->next_record();
	$db->query("SELECT folderID FROM inventoryfolders WHERE agentID='$uuid' AND type='5'");		// Clothing Folder
	list($cloth_folder) = $db->next_record();
	if (!$body_folder or !$cloth_folder) return false;

	$default_inv = array();

	$create_time = time();
	$insert_columns = 'assetID,assetType,inventoryName,inventoryDescription,inventoryNextPermissions,inventoryCurrentPermissions,invType,'.
					  'creatorID,inventoryBasePermissions,inventoryEveryOnePermissions,creationDate,flags,inventoryID,avatarID,parentFolderID,'.
					  'inventoryGroupPermissions';
	$insert_common  = "'','581632','581632','18','$uuid','581632','581632','$create_time'";

	$invID = make_random_guid();
	$db->query("INSERT INTO inventoryitems ($insert_columns) ".
					"VALUES ('".DEFAULT_ASSET_SHAPE."','13','Default Shape',$insert_common,'0','$invID','$uuid','$body_folder', '581632')");
	$errno = $db->Errno;

	if ($errno==0) {
		$default_inv['Shape'] = $invID;
		$invID = make_random_guid();
		$db->query("INSERT INTO inventoryitems ($insert_columns) ".
					"VALUES ('".DEFAULT_ASSET_SKIN. "','13','Default Skin', $insert_common,'1','$invID','$uuid','$body_folder', '581632')");
		$errno = $db->Errno;
	}
	if ($errno==0) {
		$default_inv['Skin'] = $invID;
		$invID = make_random_guid();
		$db->query("INSERT INTO inventoryitems ($insert_columns) ".
					"VALUES ('".DEFAULT_ASSET_HAIR. "','13','Default Hair', $insert_common,'2','$invID','$uuid','$body_folder', '581632')");
		$errno = $db->Errno;
	}
	if ($errno==0) {
		$default_inv['Hair'] = $invID;
		$invID = make_random_guid();
		$db->query("INSERT INTO inventoryitems ($insert_columns) ".
					"VALUES ('".DEFAULT_ASSET_EYES. "','13','Default Eyes', $insert_common,'3','$invID','$uuid','$body_folder', '581632')");
		$errno = $db->Errno;
	}
	if ($errno==0) {
		$default_inv['Eyes'] = $invID;
		$invID = make_random_guid();
		$db->query("INSERT INTO inventoryitems ($insert_columns) ".
					"VALUES ('".DEFAULT_ASSET_SHIRT. "','5','Default Shirt',$insert_common,'4','$invID','$uuid','$cloth_folder','581632')");
		$errno = $db->Errno;
	}
	if ($errno==0) {
		$default_inv['Shirt'] = $invID;
		$invID = make_random_guid();
		$db->query("INSERT INTO inventoryitems ($insert_columns) ".
					"VALUES ('".DEFAULT_ASSET_PANTS. "','5','Default Pants',$insert_common,'5','$invID','$uuid','$cloth_folder','581632')");
		$errno = $db->Errno;
	}
	if ($errno==0) {
		$default_inv['Pants'] = $invID;
	}

	return $default_inv;
}




function  opensim_create_default_inventory_folders($uuid, &$db=null)
{
	global $OpenSimVersion;

	if (!isUUID($uuid)) return false;

	if (!is_object($db)) $db = opensim_new_db();
	if ($OpenSimVersion==null) opensim_get_db_version($db);

	$my_inventory = make_random_guid();
	$db->query('INSERT INTO inventoryfolders (folderName,type,version,folderID,agentID,parentFolderID) '.
					  "VALUES ('My Inventory','8','1','$my_inventory','$uuid','00000000-0000-0000-0000-000000000000')");
	//
	$db->query('INSERT INTO inventoryfolders (folderName,type,version,folderID,agentID,parentFolderID) '.
					  "VALUES ('Textures','0','1','".make_random_guid()."','$uuid','$my_inventory')");
	$db->query('INSERT INTO inventoryfolders (folderName,type,version,folderID,agentID,parentFolderID) '.
					  "VALUES ('Sounds','1','1','".make_random_guid()."','$uuid','$my_inventory')");
	$db->query('INSERT INTO inventoryfolders (folderName,type,version,folderID,agentID,parentFolderID) '.
					  "VALUES ('Calling Cards','2','1','".make_random_guid()."','$uuid','$my_inventory')");
	$db->query('INSERT INTO inventoryfolders (folderName,type,version,folderID,agentID,parentFolderID) '.
					  "VALUES ('Landmarks','3','1','".make_random_guid()."','$uuid','$my_inventory')");
	$db->query('INSERT INTO inventoryfolders (folderName,type,version,folderID,agentID,parentFolderID) '.
					  "VALUES ('Clothing','5','1','".make_random_guid()."','$uuid','$my_inventory')");
	$db->query('INSERT INTO inventoryfolders (folderName,type,version,folderID,agentID,parentFolderID) '.
					  "VALUES ('Objects','6','1','".make_random_guid()."','$uuid','$my_inventory')");
	$db->query('INSERT INTO inventoryfolders (folderName,type,version,folderID,agentID,parentFolderID) '.
					  "VALUES ('Notecards','7','1','".make_random_guid()."','$uuid','$my_inventory')");
	$db->query('INSERT INTO inventoryfolders (folderName,type,version,folderID,agentID,parentFolderID) '.
					  "VALUES ('Scripts','10','1','".make_random_guid()."','$uuid','$my_inventory')");
	$db->query('INSERT INTO inventoryfolders (folderName,type,version,folderID,agentID,parentFolderID) '.
					  "VALUES ('Body Parts','13','1','".make_random_guid()."','$uuid','$my_inventory')");
	$db->query('INSERT INTO inventoryfolders (folderName,type,version,folderID,agentID,parentFolderID) '.
					  "VALUES ('Trash','14','1','".make_random_guid()."','$uuid','$my_inventory')");
	$db->query('INSERT INTO inventoryfolders (folderName,type,version,folderID,agentID,parentFolderID) '.
					  "VALUES ('Photo Album','15','1','".make_random_guid()."','$uuid','$my_inventory')");
	$db->query('INSERT INTO inventoryfolders (folderName,type,version,folderID,agentID,parentFolderID) '.
					  "VALUES ('Lost And Found','16','1','".make_random_guid()."','$uuid','$my_inventory')");
	$db->query('INSERT INTO inventoryfolders (folderName,type,version,folderID,agentID,parentFolderID) '.
					  "VALUES ('Animations','20','1','".make_random_guid()."','$uuid','$my_inventory')");
	$db->query('INSERT INTO inventoryfolders (folderName,type,version,folderID,agentID,parentFolderID) '.
					  "VALUES ('Gestures','21','1','".make_random_guid()."','$uuid','$my_inventory')");
	return true;
}




/////////////////////////////////////////////////////////////////////////////////////
//
// for Password
//

function  opensim_get_password($uuid, $tbl='', &$db=null)
{
	global $OpenSimVersion;

	if (!isUUID($uuid)) return null;
	if (!isAlphabetNumeric($tbl, true)) return null;

	if (!is_object($db)) $db = opensim_new_db();
	if ($OpenSimVersion==null) opensim_get_db_version($db);

	$passwdhash = null;
	$passwdsalt = null;

	if ($tbl=='' or $tbl=='auth') {
		if ($db->exist_table('auth')) {
			$db->query("SELECT passwordHash,passwordSalt FROM auth WHERE UUID='$uuid'");
			list($passwdhash, $passwdsalt) = $db->next_record();
		}
	}

	if ($passwdhash==null and $passwdsalt==null) {
		if ($tbl=='' or $tbl=='users') {
			if ($db->exist_table('users')) {
				$db->query("SELECT passwordHash,passwordSalt FROM users WHERE UUID='$uuid'");
				list($passwdhash, $passwdsalt) = $db->next_record();
			}
		}
	}

	$ret['passwordHash'] = $passwdhash;
	$ret['passwordSalt'] = $passwdsalt;
	return $ret;
}



function  opensim_set_password($uuid, $passwdhash, $passwdsalt='', $tbl='', &$db=null)
{
	global $OpenSimVersion;

	if (!isUUID($uuid)) return false;
	if (!isAlphabetNumeric($passwdhash)) return false;
	if (!isAlphabetNumeric($passwdsalt, true)) return false;
	if (!isAlphabetNumeric($tbl, true)) return false;

	if (!is_object($db)) $db = opensim_new_db();
	if ($OpenSimVersion==null) opensim_get_db_version($db);

	$setpasswd = "passwordHash='$passwdhash'";
	if ($passwdsalt!='') {
		$setpasswd .= ",passwordSalt='$passwdsalt'";
	}

	$errno = 0;
	if ($tbl=='' or $tbl=='auth') {
		if ($db->exist_table('auth')) {
			$db->query("UPDATE auth SET ".$setpasswd." WHERE UUID='$uuid'");
			$errno = $db->Errno;
		}
	}

	if (($tbl=='' or $tbl=='users') and $errno==0) {
		if ($db->exist_table('users')) {
			$db->query("UPDATE users SET ".$setpasswd." WHERE UUID='$uuid'");
			if ($db->Errno!=0) {
				if (!$db->exist_table('auth')) $errno = 99;
			}
		}
	}

	if ($errno!=0) return false;
	return true;
}




/////////////////////////////////////////////////////////////////////////////////////
//
// for Update Data Base
//

/*
function  opensim_supply_passwordSalt(&$db=null)
{
	if (!is_object($db)) $db = opensim_new_db();

	$dp2 = opensim_new_db();
	if ($db->exist_table('auth')) {
		$db->query('SELECT UUID,passwordHash,passwordSalt FROM auth');
		while ($data = $db->next_record()) {
			if ($data['passwordSalt']=='') {
				$passwdSalt = make_random_hash();
				$passwdHash = md5($data['passwordHash'].':'.$passwdSalt);
				opensim_set_password($data['UUID'], $passwdHash, $passwdSalt, 'auth', $db2);
			}
		}
	}

	if ($db->exist_table('users')) {
		$db->query('SELECT UUID,passwordHash,passwordSalt FROM users');
		while ($data = $db->next_record()) {
			if ($data['passwordSalt']=='') {
				$passwdSalt = make_random_hash();
				$passwdHash = md5($data['passwordHash'].':'.$passwdSalt);
				opensim_set_password($data['UUID'], $passwdHash, $passwdSalt, 'users', $db2);
			}
		}
	}

	return;
}
*/




function  opensim_succession_agents_to_griduser($region_id, &$db=null)
{
	global $OpenSimVersion;

	if (!isUUID($region_id)) return false;

	if (!is_object($db)) $db = opensim_new_db();
	if ($OpenSimVersion==null) opensim_get_db_version($db);

	$db->query('SELECT agents.UUID,currentRegion,loginTime,logoutTime,homeRegion,'.
								'homeLocationX,homeLocationY,homeLocationZ FROM agents,users WHERE agents.UUID=users.UUID');
	$errno = $db->Errno;

	if ($errno==0) {
		$db2 = opensim_new_db();
		while(list($UUID,$currentRegion,$login,$logout,$homeHandle,$locX,$locY,$locZ) = $db->next_record()) {
			$db2->query("SELECT uuid FROM regions WHERE regionHandle='$homeHandle'");
			list($homeRegion) = $db2->next_record();
			if ($homeRegion==null) {
				$homeRegion = $region_id;
				$locX = '128';
				$locY = '128';
				$locZ = '20';
			}

			$db2->query("SELECT UserID,HomeRegionID FROM GridUser WHERE UserID='$UUID'");
			list($userid, $hmregion) = $db2->next_record();

			if ($userid==null) {
				if ($login!=0 and $logout<$login) $logout = $login;

				$db2->query('INSERT INTO GridUser (UserID,HomeRegionID,HomePosition,HomeLookAt,LastRegionID,LastPosition,LastLookAt,Online,Login,Logout) '.
							"VALUES ('$UUID','$homeRegion','<$locX,$locY,$locZ>','<0,0,0>','$currentRegion','<128,128,0>','<0,0,0>','False','$login','$logout')");
				$errno = $db2->Errno;

				if ($errno!=0) {
					$db->query("DELETE FROM GridUser WHERE UserID='$UUID'");
				}
			}
			else if ($hmregion=='00000000-0000-0000-0000-000000000000' or $hmregion==null) {
				$db2->query("UPDATE GridUser SET HomeRegionID='$homeRegion',HomePosition='<$locX,$locY,$locZ>' WHERE UserID='$UUID'");
			}
		}
	}

	if ($errno!=0) return false;
	return true;
}



function  opensim_succession_useraccounts_to_griduser($region_id, &$db=null)
{
	global $OpenSimVersion;

	if (!isUUID($region_id)) return false;

	if (!is_object($db)) $db = opensim_new_db();
	if ($OpenSimVersion==null) opensim_get_db_version($db);

	$db->query('SELECT PrincipalID FROM UserAccounts');
	$errno = $db->Errno;
	$homeRegion = $region_id;

	if ($errno==0) {
		$db2 = opensim_new_db();
		while(list($UUID) = $db->next_record()) {
			$db2->query("SELECT UserID,HomeRegionID FROM GridUser WHERE UserID='$UUID'");
			list($userid, $hmregion) = $db2->next_record();

			if ($userid==null) {
				$db2->query('INSERT INTO GridUser (UserID,HomeRegionID,HomePosition,HomeLookAt,LastRegionID,LastPosition,LastLookAt,Online,Login,Logout) '.
							"VALUES ('$UUID','$homeRegion','<128,128,0>','<0,0,0>','$homeRegion','<128,128,0>','<0,0,0>','False','0','0')");
				$errno =$db2->Errno;

				if ($errno!=0) {
					$db->query("DELETE FROM GridUser WHERE UserID='$UUID'");
				}
			}
			else if ($hmregion=='00000000-0000-0000-0000-000000000000' or $hmregion==null) {
				$db2->query("UPDATE GridUser SET HomeRegionID='$homeRegion',HomePosition='<128,128,0>' WHERE UserID='$UUID'");
			}
		}
	}

	if ($errno!=0) return false;
	return true;
}




//
// agents -> GridUser
// UserAccounts -> GridUser
//
//		$region_name is default home region name.
//
function  opensim_succession_data($region_name, &$db=null)
{
	global $OpenSimVersion;

	if (!isAlphabetNumericSpecial($region_name, true)) return false;

	if (!is_object($db)) $db = opensim_new_db();
	if ($OpenSimVersion==null) opensim_get_db_version($db);

	$exist_agents   = $db->exist_table('agents');
	$exist_griduser = $db->exist_table('GridUser');
	$exist_usracnt  = $db->exist_table('UserAccounts');

	$region_id = '';
	if ($region_name!='') {
		$region_id = opensim_get_region_uuid($region_name);
	}
	if ($region_id=='') $region_id = '00000000-0000-0000-0000-000000000000';

	if ($exist_agents and $exist_griduser) {
		opensim_succession_agents_to_griduser($region_id);
	}

	if ($exist_usracnt and $exist_griduser) {
		opensim_succession_useraccounts_to_griduser($region_id);
	}

	return;
}



//
//
function  opensim_recreate_presence(&$db=null)
{
	global $OpenSimVersion;

	if (!is_object($db)) $db = opensim_new_db();
	if ($OpenSimVersion==null) opensim_get_db_version($db);

	if ($db->exist_field('Presence', 'HomeRegionID')) {
		$db->query('DROP TABLE Presence');
		$db->query("DELETE FROM migrations WHERE name='Presence'");
	}
	// Creation is automatic by ROBUST server.

	return;
}





/////////////////////////////////////////////////////////////////////////////////////
//
// for Voice (VoIP)
//

function  opensim_get_voice_mode($region, &$db=null)
{
	global $OpenSimVersion;

	if (!isUUID($region)) return -1;

	if (!is_object($db)) $db = opensim_new_db();
	if ($OpenSimVersion==null) opensim_get_db_version($db);

	$voiceflag = 0x60000000;

	$db->query("SELECT LandFlags FROM land WHERE RegionUUID='$region'");
	while (list($flag) = $db->next_record()) {
		$voiceflag &= $flag;
	}

	if		($voiceflag==0x20000000) return 1;
	else if ($voiceflag==0x40000000) return 2;
	return 0;
}



function  opensim_set_voice_mode($region, $mode, &$db=null)
{
	global $OpenSimVersion;

	if (!isUUID($region)) false;
	if (!preg_match('/^[0-2]$/', $mode)) false;

	if (!is_object($db)) $db = opensim_new_db();
	if ($OpenSimVersion==null) opensim_get_db_version($db);

	$colum  = 0;
	$vflags = array();

	$db->query("SELECT UUID,LandFlags FROM land WHERE RegionUUID='$region'");
	while (list($UUID, $flag) = $db->next_record()) {
		$flag &= 0x9fffffff;
		if ($mode==1)	  $flag |= 0x20000000;
		else if ($mode==2) $flag |= 0x40000000;

		$vflags[$colum]['UUID'] = $UUID;
		$vflags[$colum]['flag'] = $flag;
		$colum++;
	}

	foreach($vflags as $vflag) {
		$UUID = $vflag['UUID'];
		$flag = $vflag['flag'];
		$db->query("UPDATE land SET LandFlags='$flag' WHERE UUID='$UUID'");
	}

	return true;
}




/////////////////////////////////////////////////////////////////////////////////////
//
// for Currency






function opensim_get_currency_balance($agentid, &$db=null)
{
	global $OpenSimVersion;

	if (!isUUID($agentid)) return;

	if (!is_object($db)) $db = opensim_new_db();
	if ($OpenSimVersion==null) opensim_get_db_version($db);

	$userid = $db->escape($agentid);
	$db->query("SELECT balance FROM ".CURRENCY_MONEY_TBL." WHERE user='".$userid."'");

	$cash = 0;
	if ($db->Errno==0) list($cash) = $db->next_record();

	return (integer)$cash;
}




/////////////////////////////////////////////////////////////////////////////////////
//
// Tools
//

function  opensim_get_servers_ip(&$db=null)
{
	global $OpenSimVersion;

	if (!is_object($db)) $db = opensim_new_db();
	if ($OpenSimVersion==null) opensim_get_db_version($db);

	$ips = array();

	$db->query("SELECT DISTINCT serverIP FROM regions");
	if ($db->Errno==0) {
		$count = 0;
		while (list($server) = $db->next_record()) {
			$ips[$count] = gethostbyname($server);
			$count++;
		}
	}

	return $ips;
}






function  opensim_is_access_from_region_server()
{
	$ip_match = false;
	$remote_addr = $_SERVER['REMOTE_ADDR'];
	$server_addr = $_SERVER['SERVER_ADDR'];

	if ($remote_addr==$server_addr or $remote_addr=="127.0.0.1") return true;

	$ips = opensim_get_servers_ip();

	foreach($ips as $ip) {
		if ($ip==$remote_addr) {
			$ip_match = true;
			break;
		}
	}

	return $ip_match;
}



//



//



function  opensim_clear_login_table(&$db=null)
{
	global $OpenSimVersion;

	if (!is_object($db)) $db = opensim_new_db();
	if ($OpenSimVersion==null) opensim_get_db_version($db);

	//
	if ($OpenSimVersion==OPENSIM_V07) {
		$db->query('DELETE FROM Presence');
	}

 	else if ($OpenSimVersion==OPENSIM_V06) {
		//$db->query('DELETE FROM agents');
		return true;
	}

	else return false;


	return true;
}





/////////////////////////////////////////////////////////////////////////////////////
//
// Debug or Test
//

function  opensim_debug_command(&$db=null)
{
	if (!is_object($db)) $db = opensim_new_db();

	$db->query('SELECT name,assetType,id,asset_flags FROM assets');

	while (list($name,$type,$id,$flags) = $db->next_record()) {
		echo $name." ".$type." ".$id." ".$flags."<br />";
	}
}

function simpleXMLToArray(SimpleXMLElement $xml,$attributesKey=null,$childrenKey=null,$valueKey=null){

	if($childrenKey && !is_string($childrenKey)){$childrenKey = '@children';}
	if($attributesKey && !is_string($attributesKey)){$attributesKey = '@attributes';}
	if($valueKey && !is_string($valueKey)){$valueKey = '@values';}

	$return = array();
	$name = $xml->getName();
	$_value = trim((string)$xml);
	if(!strlen($_value)){$_value = null;};

	if($_value!==null){
		if($valueKey){$return[$valueKey] = $_value;}
		else{$return = $_value;}
	}

	$children = array();
	$first = true;
	foreach($xml->children() as $elementName => $child){
		$value = simpleXMLToArray($child,$attributesKey, $childrenKey,$valueKey);
		if(isset($children[$elementName])){
			if(is_array($children[$elementName])){
				if($first){
					$temp = $children[$elementName];
					unset($children[$elementName]);
					$children[$elementName][] = $temp;
					$first=false;
				}
				$children[$elementName][] = $value;
			}else{
				$children[$elementName] = array($children[$elementName],$value);
			}
		}
		else{
			$children[$elementName] = $value;
		}
	}
	if($children){
		if($childrenKey){$return[$childrenKey] = $children;}
		else{$return = array_merge($return,$children);}
	}

	$attributes = array();
	foreach($xml->attributes() as $name=>$value){
		$attributes[$name] = trim($value);
	}
	if($attributes){
		if($attributesKey){$return[$attributesKey] = $attributes;}
		else{$return = array_merge($return, $attributes);}
	}

	return $return;
}

function GetURL($host, $port, $url)
{
    $url = "http://$host:$port/$url";

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);

    $data = curl_exec($ch);
    if (curl_errno($ch) == 0)
    {
        curl_close($ch);
        return $data;
    }

    curl_close($ch);
    return "";
}

function j2k_to_tga($file, $iscopy=true)
{
	if (!file_exists($file)) return false;

	$com_totga = get_j2k_to_tga_command();
	if ($com_totga=='') return false;

	if ($iscopy) $ret = copy  ($file, $file.'.j2k');
	else 		 $ret = rename($file, $file.'.j2k');
	if (!$ret) return false;

	exec("$com_totga -i $file.j2k -o $file.tga 1>/dev/null 2>&1");
	unlink($file.'.j2k');

	return true;
}

function get_j2k_to_tga_command()
{
	$command = find_command_path('j2k_to_image');
	return $command;
}

function get_image_size_convert_command($xsize, $ysize)
{
	if (!is_numeric($xsize) or !is_numeric($ysize)) return '';

	$command = find_command_path('convert');
	if ($command=='') return '';

	$prog = $command.' - -geometry '.$xsize.'x'.$ysize.'! -';
	return $prog;
}

function find_command_path($command)
{
	$path = '';
	if (file_exists('/usr/local/bin/'.$command))	  $path = '/usr/local/bin/';
	else if (file_exists('/usr/bin/'.$command))		  $path = '/usr/bin/';
	else if (file_exists('/usr/X11R6/bin/'.$command)) $path = '/usr/X11R6/bin/';
	else if (file_exists('/bin/'.$command))			  $path = '/bin/';
	else return '';

	return $path.$command;
}

function isAlphabetNumeric($str, $nullok=false)
{
	if ($str!='0' and $str==null) return $nullok;
	if (!preg_match('/^\w+$/', $str)) return false;
	return true;
}

function isAlphabetNumericSpecial($str, $nullok=false)
{
	if ($str!='0' and $str==null) return $nullok;
	if (!preg_match('/^[_a-zA-Z0-9 &@%#\-\.]+$/', $str)) return false;
	return true;
}

function opensim_get_db_version(&$deprecated=null)
{
  global $OpenSimDB;

	if (tableExists($OpenSimDB, [ 'GridUser' ])) $OpenSimVersion = OPENSIM_V07;
  else if (tableExists($OpenSimDB, [ 'users' ])) $OpenSimVersion = OPENSIM_V06;
  else if (tableExists($OpenSimDB, [ 'UserID' ])) $OpenSimVersion = AURORASIM;
  else {
    error_log('Invalid OpenSimulator database');
    die();
  }
	return $OpenSimVersion;
}

function aurora_split_key_value($str)
{
	$info = array();
	$str  = trim($str);

	if (substr($str, 0, 1)=='{' and substr($str, -1)=='}') {
		$str = substr($str, 1, -1);
		$inbrkt = 0;
		$inquot = false;
		$inkkko = false;
		$isakey = true;
		$key    = "";
		$val    = "";

		for ($i=0; $i<strlen($str); $i++) {
			$cc = substr($str, $i, 1);

			if ($inbrkt==0 and !$inquot and ($cc=='"' or $cc=='\'')) {
				$inquot = true;
			}
			else if ($inbrkt==0 and $inquot and ($cc=='"' or $cc=='\'')) {
				$inquot = false;
			}
			else if ($inbrkt==0 and $isakey  and !$inquot and !$inkkko and $cc==':') {
				$isakey = false;
			}
			else if ($inbrkt==0 and !$isakey and !$inquot and !$inkkko and $cc==',') {
				if (substr($val, 0, 1)=='{' and substr($val, -1)=='}') {
					$info[$key] = aurora_split_key_value($val);
				}
				else $info[$key] = $val;

				$isakey = true;
				$key    = "";
				$val    = "";
			}
			else {
				if      ($cc=='{') $inbrkt++;
				else if ($cc=='}') $inbrkt--;
				else {
					if      ($inbrkt==0 and !$inkkko and $cc=='[') $inkkko = true;
					else if ($inbrkt==0 and $inkkko  and $cc==']') $inkkko = false;
				}

				if ($isakey) $key .= $cc;
				else         $val .= $cc;
			}
		}

		//
		if ($key!="") {
			if (substr($val, 0, 1)=='{' and substr($val, -1)=='}') {
				$info[$key] = aurora_split_key_value($val);
			}
			else $info[$key] = $val;
		}
	}

	return $info;
}
