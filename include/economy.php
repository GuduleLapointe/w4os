<?php
/*
 * economy.php
 *
 * Provides functions required only by currency.php and landtool.php
 *
 * Part of "flexible_helpers_scripts" collection
 *   https://github.com/GuduleLapointe/flexible_helper_scripts
 *   by Gudule Lapointe <gudule@speculoos.world>
 *
 * Requires an OpenSimulator Money Server
 *    [DTL/NSL Money Server for OpenSim](http://www.nsl.tuis.ac.jp/xoops/modules/xpwiki/?OpenSim%2FMoneyServer)
 * or [Gloebit module](http://dev.gloebit.com/opensim/configuration-instructions/)
 *
 * Includes portions of code from original DTLS/NLS Money Server, by:
 *   Melanie Thielker and Teravus Ovares (http://opensimulator.org/)
 *   Fumi.Iseki for CMS/LMS '09 5/31
 */

require_once('functions.php');

function env_set_money_transaction($sourceId, $destId, $amount, $type, $falgs, $desc, $prminvent, $nxtowner, $ip)
{
  if (!is_numeric($amount)) return;
	if (!isUUID($sourceId))  $sourceId = '00000000-0000-0000-0000-000000000000';
	if (!isUUID($destId))    $destId   = '00000000-0000-0000-0000-000000000000';

	$region = '00000000-0000-0000-0000-000000000000';
	$client = $sourceId;
	if ($client=='00000000-0000-0000-0000-000000000000') $client = $destId;

	$avt = opensim_get_avatar_session($client);
	if ($avt!=null) $region = $avt['regionID'];

	$db = null;
	if (defined('CURRENCY_DB_HOST')) {
		$db = new DB(CURRENCY_DB_HOST, CURRENCY_DB_NAME, CURRENCY_DB_USER, CURRENCY_DB_PASS, CURRENCY_DB_MYSQLI);
	}
	else {
		$db = new DB(OPENSIM_DB_HOST, OPENSIM_DB_NAME, OPENSIM_DB_USER, OPENSIM_DB_PASS, OPENSIM_DB_MYSQLI);
	}
	if ($db==null) return;


	$sql = "INSERT INTO ".CURRENCY_TRANSACTION_TBL." (sourceId,destId,amount,flags,".
                        "aggregatePermInventory,aggregatePermNextOwner,description,".
                        "transactionType,timeOccurred,RegionGenerated,ipGenerated) ".
			"VALUES ('".
				$sourceId."','".
				$destId."','".
				$amount."','".
				$db->escape($flags)."','".
				$db->escape($prminvent)."','".
				$db->escape($nxtowner)."','".
				$db->escape($desc)."','".
				$db->escape($type)."','".
				time()."','".
				$region."','".
				$db->escape($ip)."')";
	$db->query($sql);
	$db->close();
}



function env_get_money_balance($uuid)
{
	if (!isUUID($uuid)) return -1;

	$scash = 0;
	$dcash = 0;

	$db = null;
	if (defined('CURRENCY_DB_HOST')) {
		$db = new DB(CURRENCY_DB_HOST, CURRENCY_DB_NAME, CURRENCY_DB_USER, CURRENCY_DB_PASS, CURRENCY_DB_MYSQLI);
	}
	else {
		$db = new DB(OPENSIM_DB_HOST, OPENSIM_DB_NAME, OPENSIM_DB_USER, OPENSIM_DB_PASS, OPENSIM_DB_MYSQLI);
	}
	if ($db==null) return 0;


	$db->query("SELECT SUM(amount) FROM ".CURRENCY_TRANSACTION_TBL." WHERE destId='".  $uuid."'");
	if ($db->Errno==0) list($dcash) = $db->next_record();

	$db->query("SELECT SUM(amount) FROM ".CURRENCY_TRANSACTION_TBL." WHERE sourceId='".$uuid."'");
	if ($db->Errno==0) list($scash) = $db->next_record();

	$db->close();

	$cash = (integer)$dcash - (integer)$scash;
	return $cash;
}

function opensim_set_currency_transaction($sourceId, $destId, $amount, $type, $flags, $description, &$db=null)
{
	if (!is_numeric($amount)) return;
	if (!isUUID($sourceId))  $sourceId = '00000000-0000-0000-0000-000000000000';
	if (!isUUID($destId)) 	 $destId   = '00000000-0000-0000-0000-000000000000';

	if (!is_object($db)) $db = opensim_new_db();

	$handle   = 0;
	$secure   = '00000000-0000-0000-0000-000000000000';
	$client	  = $sourceId;
	$UUID	 = make_random_guid();
	$sourceID = $sourceId;
	$destID   = $destId;
	if ($client=='00000000-0000-0000-0000-000000000000') $client = $destId;

	$avt = opensim_get_avatar_session($client);
	if ($avt!=null) {
		$region = $avt['regionID'];
		$secure = $avt['secureID'];

		$rgn = opensim_get_region_info($region);
		if ($rgn!=null) $handle = $rgn["regionHandle"];
	}

	$sql = "INSERT INTO ".CURRENCY_TRANSACTION_TBL." (UUID,sender,receiver,amount,objectUUID,".
													"regionHandle,type,time,secure,status,description) ".
			"VALUES ('".
				$UUID."','".
				$sourceID."','".
				$destID."','".
				$amount."','".
				"00000000-0000-0000-0000-000000000000','".
				$handle."','".
				$db->escape($type)."','".
				time()."','".
				$secure."','".
				$db->escape($flags)."','".
				$db->escape($description)."')";
	$db->query($sql);
}

function opensim_set_currency_balance($agentid, $amount, &$db=null)
{
	if (!isUUID($agentid) or !is_numeric($amount)) return;

	if (!is_object($db)) $db = opensim_new_db();

	$userid = $db->escape($agentid);

	$db->lock_table(CURRENCY_MONEY_TBL);

	$db->query("SELECT balance FROM ".CURRENCY_MONEY_TBL." WHERE user='".$userid."'");
	if ($db->Errno==0) {
		list($cash) = $db->next_record();
		$balance = (integer)$cash + (integer)$amount;

		$db->query("UPDATE ".CURRENCY_MONEY_TBL." SET balance='".$balance."' WHERE user='".$userid."'");
		if ($db->Errno==0) $db->next_record();
	}

	$db->unlock_table();
}




function update_simulator_balance($agentID, $amount=-1, $secureID=null)
{
	if (!isUUID($agentID)) return false;

	if ($amount<0) {
		$amount = get_balance($agentID, $secureID);
		if ($amount<0) return false;
	}

	// XML RPC to Region Server
	if (!isUUID($secureID, true)) return false;

	$results = opensim_get_server_info($agentID);
	if (!$results) return false;
	$serverip  = $results["serverIP"];
	$httpport  = $results["serverHttpPort"];
	$serveruri = $results["serverURI"];

	$results = opensim_get_avatar_session($agentID);
	if (!$results) return false;
	$sessionID = $results["sessionID"];
	if ($secureID==null) $secureID = $results["secureID"];

	$req	  = array('clientUUID'=>$agentID, 'clientSessionID'=>$sessionID, 'clientSecureSessionID'=>$secureID, "Balance"=>$amount);
	$params   = array($req);
	$request  = xmlrpc_encode_request('UpdateBalance', $params);
	$response = do_call($serverip, $httpport, $serveruri, $request);

	return $response;
}

function  move_money($agentID, $destID, $amount, $type, $flags, $desc, $prminvent=0, $nxtowner=0, $ip="")
{
	if (!USE_CURRENCY_SERVER) {
  		env_set_money_transaction($agentID, $destID, $amount, $type, $flags, $desc, $prminvent, $nxtowner, $ip);
		return true;
	}


	// Direct DB access for security
	//$url = preg_split("/[:\/]/", USER_SERVER_URI);
	//$userip = $url[3];
 	opensim_set_currency_transaction($agentID, $destID, $amount, $type, $flags, $desc);

	if (isUUID($agentID) and $agentID!="00000000-0000-0000-0000-0000000000000") {
		opensim_set_currency_balance($agentID, -$amount);
	}

	if (isUUID($destID)  and $destID !="00000000-0000-0000-0000-0000000000000") {
		opensim_set_currency_balance($destID, $amount);
	}

	return true;
}



//
function  add_money($agentID, $amount, $secureID=null)
{
	if (!isUUID($agentID)) return false;

	//
	if (!USE_CURRENCY_SERVER) {
		env_set_money_transaction(null, $agentID, $amount, 5010, 0, "Add Money", 0, 0, "");
		$res["success"] = true;
		return $res;
	}

	//
	// XML RPC to Region Server
	//
	if (!isUUID($secureID, true)) return false;

	$results = opensim_get_server_info($agentID);
	$serverip  = $results["serverIP"];
	$httpport  = $results["serverHttpPort"];
	$serveruri = $results["serverURI"];
	if ($serverip=="") return false;

	$results = opensim_get_avatar_session($agentID);
	$sessionID = $results["sessionID"];
	//if ($sessionID=="")  return false;
	if ($secureID==null) $secureID = $results["secureID"];

	$req	  = array('clientUUID'=>$agentID, 'clientSessionID'=>$sessionID, 'clientSecureSessionID'=>$secureID, 'amount'=>$amount);
	$params   = array($req);
	$request  = xmlrpc_encode_request('AddBankerMoney', $params);

	$response = do_call($serverip, $httpport, $serveruri, $request);

	return $response;
}



//
// Send the money to avatar for bonus
// 										by Milo
//
function send_money($agentID, $amount, $secretCode=null)
{
    if (!isUUID($agentID)) return false;

    if (!USE_CURRENCY_SERVER) {
    	env_set_money_transaction(null, $agentID, $amount, 5003, 0, "Send Money", 0, 0, "");
    	$res["success"] = true;
    	return $res;
	}

	//
	// XML RPC to Region Server
	//
    $results = opensim_get_server_info($agentID);
	$serverip  = $results["serverIP"];
	$httpport  = $results["serverHttpPort"];
	$serveruri = $results["serverURI"];
	if ($serverip=="") return false;

	$serverip = gethostbyname($serverip);
	if ($secretCode!=null) {
		$secretCode = md5($secretCode."_".$serverip);
	}
	else {
		$secretCode = get_confirm_value($serverip);
	}

	$req 	  = array('clientUUID'=>$agentID, 'secretAccessCode'=>$secretCode, 'amount'=>$amount);
	$params   = array($req);
	$request  = xmlrpc_encode_request('SendMoneyBalance', $params);
	$response = do_call($serverip, $httpport, $serveruri, $request);

	return $response;
}



//
function  get_balance($agentID, $secureID=null)
{
	$cash = -1;
	if (!isUUID($agentID)) return (integer)$cash;

	//
	if (!USE_CURRENCY_SERVER) {
		$cash = env_get_money_balance($agentID);
		return (integer)$cash;
	}

	//
	// XML RPC to Region Server
	//
	if (!isUUID($secureID, true)) return (integer)$cash;

	$results = opensim_get_server_info($agentID);
	$serverip  = $results["serverIP"];
	$httpport  = $results["serverHttpPort"];
	$serveruri = $results["serverURI"];
	if ($serverip=="") return (integer)$cash;

	$results = opensim_get_avatar_session($agentID);
	$sessionID = $results["sessionID"];
	if ($sessionID=="")  return (integer)$cash;
	if ($secureID==null) $secureID = $results["secureID"];

	$req	  = array('clientUUID'=>$agentID, 'clientSessionID'=>$sessionID, 'clientSecureSessionID'=>$secureID);
	$params   = array($req);
	$request  = xmlrpc_encode_request('GetBalance', $params);
	$response = do_call($serverip, $httpport, $serveruri, $request);

	if ($response) $cash = $response["balance"];
	return (integer)$cash;
}


function  get_confirm_value($ipAddress)
{
	$key = CURRENCY_SCRIPT_KEY;
	if ($key=="") $key = "1234567883789";
	$confirmvalue = md5($key."_".$ipAddress);

	return $confirmvalue;
}

function process_transaction($avatarID, $cost, $ipAddress)
{
	# Do Credit Card Processing here!  Return False if it fails!
	# Remember, $amount is stored without decimal places, however it's assumed
	# that the transaction amount is in Cents and has two decimal places
	# 5 dollars will be 500
	# 15 dollars will be 1500

	//if ($avatarID==CURRENCY_BANKER) return true;
	//return false;

	return true;
}

function convert_to_real($amount)
{
	/*
	if($currency == 0) return 0;

	$db = new DB(CURRENCY_DB_HOST, CURRENCY_DB_NAME, CURRENCY_DB_USER, CURRENCY_DB_PASS, CURRENCY_DB_MYSQLI);

	# Get the currency conversion ratio in USD Cents per Money Unit
	# Actually, it's whatever currency your credit card processor uses

	$db->query("SELECT CentsPerMoneyUnit FROM ".CURRENCY_MONEY_TBL." limit 1");
	list($CentsPerMoneyUnit) = $db->next_record();
	$db->close();

	if (!$CentsPerMoneyUnit) $CentsPerMoneyUnit = 0;

	# Multiply the cents per unit times the requested amount

	$real = $CentsPerMoneyUnit * $currency;

	// Dealing in cents here. The XML requires an integer
	// so we have to ceil out any decimal places and cast as an integer

	$real = (integer)ceil($real);

	return $real;
	*/

	$cost = (integer)( CURRENCY_RATE / CURRENCY_RATE_PER * 100 * $amount );

	return $cost;
}


// XML RPC
function do_call($host, $port, $uri, $request)
{
	$url = "";
	if ($uri!="") {
		$dec = explode(":", $uri);
		if (!strncasecmp($dec[0], "http", 4)) $url = "$dec[0]:$dec[1]";
	}
	if ($url=="") $url ="http://$host";
	$url = "$url:$port/";

	$header[] = "Content-type: text/xml";
	$header[] = "Content-length: ".strlen($request);

	$ch = curl_init();
	curl_setopt($ch, CURLOPT_URL, $url);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
	curl_setopt($ch, CURLOPT_TIMEOUT, 3);
	curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
	curl_setopt($ch, CURLOPT_POSTFIELDS, $request);

	$data = curl_exec($ch);
	if (!curl_errno($ch)) curl_close($ch);

	$ret = false;
	if ($data) $ret = xmlrpc_decode($data);

	// for Debug
	/*
	ob_start();
	print_r($ret);
	$rt = ob_get_contents();
	ob_end_clean();
	error_log("[do_call] responce = ".$rt);
	*/

	return $ret;
}

function  opensim_get_avatar_session($uuid, &$db=null)
{
	global $OpenSimVersion;

	if (!isUUID($uuid)) return null;

	if (!is_object($db)) $db = opensim_new_db();
	if ($OpenSimVersion==null) opensim_get_db_version($db);

	$avssn = array();

	//
	if ($OpenSimVersion==OPENSIM_V07) {
		$sql = "SELECT RegionID,SessionID,SecureSessionID FROM Presence WHERE UserID='".$uuid."'";
		$db->query($sql);
		if ($db->Errno==0) list($RegionID, $SessionID, $SecureSessionID) = $db->next_record();
	}

	else if ($OpenSimVersion==OPENSIM_V06) {
		$sql = "SELECT currentRegion,sessionID,secureSessionID FROM agents WHERE UUID='".$uuid."'";
		$db->query($sql);
		if ($db->Errno==0) list($RegionID, $SessionID, $SecureSessionID) = $db->next_record();
	}

	else if ($OpenSimVersion==AURORASIM) {
		$sql = "SELECT CurrentRegionID,token FROM tokens,userinfo WHERE UUID='".$uuid."' AND UUID=UserID AND IsOnline='1'";
		$db->query($sql);
		if ($db->Errno==0) {
			while (list($rg, $ss) = $db->next_record()) {		// Get Last Record
				$RegionID  = $rg;
				$SessionID = null;
				$SecureSessionID = $ss;
			}
		}
	}

	else return $avssn;

	if ($db->Errno==0) {
		$avssn['regionID']  = $RegionID;
		$avssn['sessionID'] = $SessionID;
		$avssn['secureID']  = $SecureSessionID;
	}

	return $avssn;
}

function  opensim_set_current_region($uuid, $regionid, &$db=null)
{
	global $OpenSimVersion;

	if (!isUUID($uuid) or !isUUID($regionid)) return false;

	if (!is_object($db)) $db = opensim_new_db();
	if ($OpenSimVersion==null) opensim_get_db_version($db);

	//
	if ($OpenSimVersion==OPENSIM_V07) {
		$sql = "UPDATE Presence SET RegionID='".$regionid."' WHERE UserID='". $uuid."'";
	}

	else if ($OpenSimVersion==OPENSIM_V06) {
		$sql = "UPDATE agents SET currentRegion='".$regionid."' WHERE UUID='".$uuid."'";
	}

	else if ($OpenSimVersion==AURORASIM) {
		$sql = "UPDATE userinfo SET CurrentRegionID='".$regionid."' WHERE UserID='".$uuid."'";
	}

	else return false;

	$db->query($sql);
	if ($db->Errno!=0) return false;

	$db->next_record();
	return true;
}

function  opensim_get_server_info($userid, &$db=null)
{
	global $OpenSimVersion;

	if (!isUUID($userid)) return $ret;

	if (!is_object($db)) $db = opensim_new_db();
	if ($OpenSimVersion==null) opensim_get_db_version($db);

	$ret = array();

	//
	if ($OpenSimVersion==OPENSIM_V07) {
		$sql = "SELECT serverIP,serverHttpPort,serverURI,regionSecret FROM GridUser ";
		$sql.= "INNER JOIN regions ON regions.uuid=GridUser.LastRegionID WHERE GridUser.UserID='".$userid."'";
		$db->query($sql);
		if ($db->Errno==0) list($serverip, $httpport, $serveruri, $secret) = $db->next_record();
	}

	else if ($OpenSimVersion==OPENSIM_V06) {
		$sql = "SELECT serverIP,serverHttpPort,serverURI,regionSecret FROM agents ";
		$sql.= "INNRT JOIN regions ON regions.uuid=agents.currentRegion WHERE agents.UUID='".$userid."'";
		$db->query($sql);
		if ($db->Errno==0) list($serverip, $httpport, $serveruri, $secret) = $db->next_record();
	}

	else if ($OpenSimVersion==AURORASIM) {

		$sql = "SELECT gridregions.Info FROM userinfo,gridregions ";
		$sql.= "WHERE UserID='".$userid."' AND userinfo.CurrentRegionID=gridregions.RegionUUID";

		//$sql = "SELECT RegionInfo FROM userinfo,simulator ";
		//$sql.= "WHERE UserID='".$userid."' AND CurrentRegionID=simulator.RegionID";

		$db->query($sql);
		if ($db->Errno==0) {
			list($regioninfo) = $db->next_record();
			$info = split_key_value($regioninfo);		// from functions-opensim.php
			$serverip  = gethostbyname($info["serverIP"]);
			$httpport  = $info["serverHttpPort"];
			$serveruri = $info["serverURI"];
			$secret	= null;
		}
	}

	else return $ret;

	if ($db->Errno==0) {
		$ret["serverIP"] 	   = $serverip;
		$ret["serverHttpPort"] = $httpport;
		$ret["serverURI"] 	   = $serveruri;
		$ret["regionSecret"]   = $secret;
	}
	return $ret;
}

function  opensim_check_secure_session($uuid, $regionid, $secure, &$db=null)
{
	global $OpenSimVersion;

	if (!isUUID($uuid) or !isUUID($secure)) return false;

	if (!is_object($db)) $db = opensim_new_db();
	if ($OpenSimVersion==null) opensim_get_db_version($db);

	//
	if ($OpenSimVersion==OPENSIM_V07) {
		$sql = "SELECT UserID FROM Presence WHERE UserID='".$uuid."' AND SecureSessionID='".$secure."'";
		if (isUUID($regionid)) $sql = $sql." AND RegionID='".$regionid."'";
	}

	else if ($OpenSimVersion==OPENSIM_V06) {
		$sql = "SELECT UUID FROM agents WHERE UUID='".$uuid."' AND secureSessionID='".$secure."' AND agentOnline='1'";
		if (isUUID($regionid)) $sql = $sql." AND currentRegion='".$regionid."'";
	}

	else if ($OpenSimVersion==AURORASIM) {
		$sql = "SELECT UUID FROM tokens,userinfo WHERE UUID='".$uuid."' AND UUID=UserID AND token='".$secure."' AND IsOnline='1'";
		if (isUUID($regionid)) $sql = $sql." AND CurrentRegionID='".$regionid."'";
	}

	else return false;

	$db->query($sql);
	if ($db->Errno!=0) return false;

	list($UUID) = $db->next_record();
	if ($UUID!=$uuid) return false;
	return true;
}

function  opensim_check_region_secret($uuid, $secret, &$db=null)
{
	global $OpenSimVersion;

	if (!isUUID($uuid)) return false;

	if (!is_object($db)) $db = opensim_new_db();
	if ($OpenSimVersion==null) opensim_get_db_version($db);

	//
	if ($OpenSimVersion==OPENSIM_V07 or $OpenSimVersion==OPENSIM_V06) {
		$sql = "SELECT UUID FROM regions WHERE UUID='".$uuid."' AND regionSecret='".$db->escape($secret)."'";

		$db->query($sql);
		if ($db->Errno==0) {
			list($UUID) = $db->next_record();
			if ($UUID==$uuid) return true;
		}
	}

	else {
		$sql = "SELECT RegionInfo FROM userinfo,simulator ";
		$sql.= "WHERE UserID='".$userid."' AND CurrentRegionID=simulator.RegionID";

		$db->query($sql);
		if ($db->Errno==0) {
			list($regioninfo) = $db->next_record();
			$info = split_key_value($regioninfo);		// from functions-opensim.php
			if ($secret==$info["password"]) return true;
		}
	}

	return false;
}

function  make_random_hash()
{
 	$ret = sprintf('%04x%04x%04x%04x%04x%04x%04x%04x',mt_rand(0,0xffff),mt_rand(0,0xffff),mt_rand(0,0xffff),mt_rand(0,0xffff),
 													  mt_rand(0,0xffff),mt_rand(0,0xffff),mt_rand(0,0xffff),mt_rand(0,0xffff));
	return $ret;
}

function  make_random_guid()
{
	$uuid = sprintf( '%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
					  mt_rand( 0, 0xffff ), mt_rand( 0, 0xffff ), mt_rand( 0, 0xffff ),
					  mt_rand( 0, 0x0fff ) | 0x4000,
					  mt_rand( 0, 0x3fff ) | 0x8000,
		   			  mt_rand( 0, 0xffff ), mt_rand( 0, 0xffff ), mt_rand( 0, 0xffff ) );
	return $uuid;
}

///////////////////////////////////////////////////////////////////////////////////////////////////
//
// String Tools
//

// parse {"key1":"value1","key2":{"key3":"value3"}}
//
//    	--> [key1] => value1
//    		[key2] => Array
//        		(
//            		[key3] => value3
//        		)
//

function  split_key_value($str)
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
					$info[$key] = split_key_value($val);
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
				$info[$key] = split_key_value($val);
			}
			else $info[$key] = $val;
		}
	}

	return $info;
}

///////////////////////////////////////////////////////////////////////////////////////////////////
//
// Image
//

//
// Convert Image from JPEG2000 to TGA
//		file -> file.tga
//
function  j2k_to_tga($file, $iscopy=true)
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

function  get_j2k_to_tga_command()
{
	$command = find_command_path('j2k_to_image');
	return $command;
}

//
// Image Size Convert Command String
//
function  get_image_size_convert_command($xsize, $ysize)
{
	if (!is_numeric($xsize) or !is_numeric($ysize)) return '';

	$command = find_command_path('convert');
	if ($command=='') return '';

	$prog = $command.' - -geometry '.$xsize.'x'.$ysize.'! -';
	return $prog;
}

function  find_command_path($command)
{
	$path = '';
	if (file_exists('/usr/local/bin/'.$command))	  $path = '/usr/local/bin/';
	else if (file_exists('/usr/bin/'.$command))		  $path = '/usr/bin/';
	else if (file_exists('/usr/X11R6/bin/'.$command)) $path = '/usr/X11R6/bin/';
	else if (file_exists('/bin/'.$command))			  $path = '/bin/';
	else return '';

	return $path.$command;
}

function user_alert($agentID, $message, $secureID=null)
{
	$results = opensim_get_server_info($agentID);
	if (!$results) return false;
	$serverip  = $results["serverIP"];
	$httpport  = $results["serverHttpPort"];
	$serveruri = $results["serverURI"];

	$results = opensim_get_avatar_session($agentID);
	if (!$results) return false;
	$sessionID = $results["sessionID"];
	if ($secureID==null) $secureID = $results["secureID"];

	$req 	  = array('clientUUID'=>$agentID, 'clientSessionID'=>$sessionID, 'clientSecureSessionID'=>$secureID, 'Description'=>$message);
	$params   = array($req);
	$request  = xmlrpc_encode_request('UserAlert', $params);
	$response = do_call($serverip, $httpport, $serveruti, $request);

	return $response;
}

function  isAlphabetNumeric($str, $nullok=false)
{
	if ($str!='0' and $str==null) return $nullok;
	if (!preg_match('/^\w+$/', $str)) return false;
	return true;
}

function  isAlphabetNumericSpecial($str, $nullok=false)
{
	if ($str!='0' and $str==null) return $nullok;
	if (!preg_match('/^[_a-zA-Z0-9 &@%#\-\.]+$/', $str)) return false;
	return true;
}
