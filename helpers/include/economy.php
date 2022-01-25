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

if (defined('CURRENCY_DB_HOST')) {
  error_log(CURRENCY_DB_HOST . ';dbname=' . CURRENCY_DB_NAME . ',' . CURRENCY_DB_USER. ',' . CURRENCY_DB_PASS);

  $CurrencyDB = new OSPDO('mysql:host=' . CURRENCY_DB_HOST . ';dbname=' . CURRENCY_DB_NAME, CURRENCY_DB_USER, CURRENCY_DB_PASS);
} else {
  $CurrencyDB = &$OpenSimDB;
}
if ($OpenSimVersion==null) opensim_get_db_version();

function noserver_save_transaction($sourceId, $destId, $amount, $type, $flags, $desc, $prminvent, $nxtowner, $ip)
{
  global $CurrencyDB;

  if (!is_numeric($amount)) return;
	if (!isUUID($sourceId))  $sourceId = NULL_KEY;
	if (!isUUID($destId))    $destId   = NULL_KEY;

	$region = NULL_KEY;
	$client = $sourceId;
	if ($client==NULL_KEY) $client = $destId;

	$avt = opensim_get_avatar_session($client);
	if ($avt!=null) $region = $avt['regionID'];

	$CurrencyDB->insert(CURRENCY_TRANSACTION_TBL, array(
    'sourceId' => $sourceId,
    'destId' => $destId,
    'amount' => $amount,
    'flags' => $flags,
    'aggregatePermInventory' => $prminvent,
    'aggregatePermNextOwner' => $nxtowner,
    'description' => $desc,
    'transactionType' => $type,
    'timeOccurred' => time(),
    'RegionGenerated' => $region,
    'ipGenerated' => $ip,
  ));
}

function noserver_get_balance($agentID)
{
  global $CurrencyDB;

	if (!isUUID($agentID)) return -1;

	$sent_sum = 0;
	$received_sum = 0;

  $credits = $CurrencyDB->prepareAndExecute("SELECT SUM(amount) FROM ".CURRENCY_TRANSACTION_TBL." WHERE destId = :destId",
  array('destId' => $agentID,));
  if($credits) list($received_sum) = $CurrencyDB->fetch();

  $debits = $CurrencyDB->prepareAndExecute("SELECT SUM(amount) FROM ".CURRENCY_TRANSACTION_TBL." WHERE sourceId = :sourceId",
  array('sourceId' => $agentID));
  if ($debits) list($sent_sum) = $CurrencyDB->fetch();

	$cash = (integer)$received_sum - (integer)$sent_sum;
	return $cash;
}

function opensim_save_transaction($sourceId, $destId, $amount, $type, $flags, $description, &$deprecated=null)
{
  global $CurrencyDB;

	if (!is_numeric($amount)) return;
	if (!isUUID($sourceId))  $sourceId = NULL_KEY;
	if (!isUUID($destId)) 	 $destId   = NULL_KEY;

	$handle   = 0;
	$secure   = NULL_KEY;
	$client	  = $sourceId;
	$UUID	 = make_random_guid();
	$sourceID = $sourceId;
	$destID   = $destId;
	if ($client==NULL_KEY) $client = $destId;

	$avt = opensim_get_avatar_session($client);
	if ($avt!=null) {
		$region = $avt['regionID'];
		$secure = $avt['secureID'];

		$rgn = opensim_get_region_info($region);
		if ($rgn!=null) $handle = $rgn["regionHandle"];
	}

  $CurrencyDB->insert(CURRENCY_TRANSACTION_TBL, array(
    'UUID' => $UUID,
    'sender' => $sourceID,
    'receiver' => $destID,
    'amount' => $amount,
    'objectUUID' => NULL_KEY,
    'regionHandle' => $handle,
    'type' => $type,
    'time' => time(),
    'secure' => $secure,
    'status' => $flags,
    'description' => $description,
  ));
}

function opensim_set_currency_balance($agentID, $amount, &$deprecated=null)
{
  if (!isUUID($agentID) or !is_numeric($amount)) return false;

  global $CurrencyDB;
  $balances_table = CURRENCY_MONEY_TBL;

	$CurrencyDB->query("LOCK TABLES $balances_table");
	$currentbalance = $CurrencyDB->prepareAndExecute("SELECT balance FROM $balances_table WHERE user = :user", array(
    'user' => $agentID,
  ));
	if ($currentbalance) {
		list($cash) = $currentbalance->fetch();
		$balance = (integer)$cash + (integer)$amount;
		$result = $CurrencyDB->prepareAndExecute("UPDATE $balances_table SET balance = :balance WHERE user = :user", array(
      'balance' => $balance,
      'user' => $agentID,
    ));
  } else {
    $result = false;
  }
  $CurrencyDB->query("UNLOCK TABLES $balances_table");
  return $result;
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

	$agentServer = opensim_get_server_info($agentID);
	if (!$agentServer) return false;
	$serverip  = $agentServer["serverIP"];
	$httpport  = $agentServer["serverHttpPort"];
	$serveruri = $agentServer["serverURI"];

	$avatarSession = opensim_get_avatar_session($agentID);
	if (!$avatarSession) return false;
	$sessionID = $avatarSession["sessionID"];
	if ($secureID==null) $secureID = $avatarSession["secureID"];

	$request  = xmlrpc_encode_request('UpdateBalance', array(array(
    'clientUUID'=>$agentID,
    'clientSessionID'=>$sessionID,
    'clientSecureSessionID'=>$secureID,
    "Balance"=>$amount,
  )));
	$response = do_call($serverip, $httpport, $serveruri, $request);

	return $response;
}

function move_money($agentID, $destID, $amount, $type, $flags, $desc, $prminvent=0, $nxtowner=0, $ip="")
{
	if (!USE_CURRENCY_SERVER) {
    noserver_save_transaction($agentID, $destID, $amount, $type, $flags, $desc, $prminvent, $nxtowner, $ip);
    return true;
	}

	// Direct DB access for security
	//$url = preg_split("/[:\/]/", USER_SERVER_URI);
	//$userip = $url[3];
 	opensim_save_transaction($agentID, $destID, $amount, $type, $flags, $desc);

  // TODO: Shouldn't we execute both balance updates only if all of the four
  // conditions are met and none of them if any of the checks fails?
	if (isUUID($agentID) and $agentID!=NULL_KEY) {
		opensim_set_currency_balance($agentID, -$amount);
	}
	if (isUUID($destID)  and $destID !=NULL_KEY) {
		opensim_set_currency_balance($destID, $amount);
	}

	return true;
}

//
function add_money($agentID, $amount, $secureID=null)
{
	if (!isUUID($agentID)) return false;

	//
	if (!USE_CURRENCY_SERVER) {
		noserver_save_transaction(null, $agentID, $amount, 5010, 0, "Add Money", 0, 0, "");
		$response = [ "success" => true ];
		return $response;
	}

	//
	// XML RPC to Region Server
	//
	if (!isUUID($secureID, true)) return false;

	$agentServer = opensim_get_server_info($agentID);
	$serverip  = $agentServer["serverIP"];
	$httpport  = $agentServer["serverHttpPort"];
	$serveruri = $agentServer["serverURI"];
	if ($serverip=="") return false;

	$avatarSession = opensim_get_avatar_session($agentID);
	$sessionID = $avatarSession["sessionID"];
	//if ($sessionID=="")  return false;
	if ($secureID==null) $secureID = $avatarSession["secureID"];

	$request  = xmlrpc_encode_request('AddBankerMoney', array(array(
    'clientUUID'=>$agentID,
    'clientSessionID'=>$sessionID,
    'clientSecureSessionID'=>$secureID,
    'amount'=>$amount,
  )));
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
    noserver_save_transaction(null, $agentID, $amount, 5003, 0, "Send Money", 0, 0, "");
    $response = [ "success" => true ];
    return $response;
  }

	//
	// XML RPC to Region Server
	//
  $agentServer = opensim_get_server_info($agentID);
	$serverip  = $agentServer["serverIP"];
	$httpport  = $agentServer["serverHttpPort"];
	$serveruri = $agentServer["serverURI"];
	if ($serverip=="") return false;
  $serverip = gethostbyname($serverip);

	if ($secretCode!=null) {
		$secretCode = md5($secretCode."_".$serverip);
	} else {
		$secretCode = get_confirm_value($serverip);
	}

  $request  = xmlrpc_encode_request('SendMoneyBalance', array(array(
    'clientUUID'=>$agentID,
    'secretAccessCode'=>$secretCode,
    'amount'=>$amount,
  )));
	$response = do_call($serverip, $httpport, $serveruri, $request);

	return $response;
}

function get_balance($agentID, $secureID=null)
{
	$cash = -1;
	if (!isUUID($agentID)) return (integer)$cash;

	if (!USE_CURRENCY_SERVER) {
		$cash = noserver_get_balance($agentID);
		return (integer)$cash;
	}

	if (!isUUID($secureID, true)) return (integer)$cash;

	$agentServer = opensim_get_server_info($agentID);
	$serverip  = $agentServer["serverIP"];
	$httpport  = $agentServer["serverHttpPort"];
	$serveruri = $agentServer["serverURI"];
	if ($serverip=="") return (integer)$cash;

	$avatarSession = opensim_get_avatar_session($agentID);
	$sessionID = $avatarSession["sessionID"];
	if ($sessionID=="")  return (integer)$cash;
	if ($secureID==null) $secureID = $avatarSession["secureID"];

  $request  = xmlrpc_encode_request('GetBalance', array(array(
    'clientUUID'=>$agentID,
    'clientSessionID'=>$sessionID,
    'clientSecureSessionID'=>$secureID,
  )));
	$response = do_call($serverip, $httpport, $serveruri, $request);

	if ($response) $cash = $response["balance"];
	return (integer)$cash;
}

function get_confirm_value($ipAddress)
{
  // TODO:
  // Option to force key to be something else than default
	$key = ($key=="") ? "1234567883789" : CURRENCY_SCRIPT_KEY;
	$confirmvalue = md5($key."_".$ipAddress);

	return $confirmvalue;
}

function process_transaction($avatarID, $cost, $ipAddress)
{
	# Do external processing here! (credit card, paypal, any money system)
	# Return False if it fails!
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
  global $CurrencyDB;
	if($currency == 0) return 0;

	$CurrencyDB = new DB(CURRENCY_DB_HOST, CURRENCY_DB_NAME, CURRENCY_DB_USER, CURRENCY_DB_PASS, CURRENCY_DB_MYSQLI);

	# Get the currency conversion ratio in USD Cents per Money Unit
	# Actually, it's whatever currency your credit card processor uses

	$CurrencyDB->query("SELECT CentsPerMoneyUnit FROM ".CURRENCY_MONEY_TBL." limit 1");
	list($CentsPerMoneyUnit) = $CurrencyDB->fetch();
	$CurrencyDB->close();

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

  // TODO: use file_get_contents() instead of over complicate curl procedure
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

	return $ret;
}

function opensim_get_avatar_session($agentID, &$deprecated=null)
{
  global $CurrencyDB;
  if (!isUUID($agentID)) return null;

  $query = $CurrencyDB->query("SELECT RegionID,SessionID,SecureSessionID FROM Presence WHERE UserID='$agentID'");
  if ($query) list($RegionID, $SessionID, $SecureSessionID) = $query->fetch();
  else return array();

  $av_session['regionID']  = $RegionID;
  $av_session['sessionID'] = $SessionID;
  $av_session['secureID']  = $SecureSessionID;

	return $av_session;
}

function opensim_set_current_region($agentID, $regionid, &$deprecated=null)
{
  global $OpenSimDB;

	if (!isUUID($agentID) or !isUUID($regionid)) return false;

  $sql = "UPDATE Presence SET RegionID='$regionid' WHERE UserID='$agentID'";
  $result = $OpenSimDB->query($sql);
	if (!$result) return false;
	return true;
}

function opensim_get_server_info($userid, &$deprecated=null)
{
  global $OpenSimDB;


  if (!isUUID($userid)) return array();

  switch($OpenSimVersion) {
    case OPENSIM_V07:
    $result = $OpenSimDB->query("SELECT serverIP,serverHttpPort,serverURI,regionSecret
      FROM GridUser INNER JOIN regions ON regions.uuid=GridUser.LastRegionID
      WHERE GridUser.UserID='$userid'"
    );
    if ($result) list($serverip, $httpport, $serveruri, $secret) = $OpenSimDB->fetch();
    else return array();
    break;

    case OPENSIM_V06:
    $sql = "SELECT serverIP,serverHttpPort,serverURI,regionSecret FROM agents
    INNER JOIN regions ON regions.uuid=agents.currentRegion WHERE agents.UUID='$userid'";
    $result = $OpenSimDB->query($sql);
    if ($result) list($serverip, $httpport, $serveruri, $secret) = $OpenSimDB->fetch();
    else return array();
    break;

    case AURORASIM:
    $sql = "SELECT gridregions.Info FROM userinfo,gridregions
    WHERE UserID='$userid' AND userinfo.CurrentRegionID=gridregions.RegionUUID";
    $result = $OpenSimDB->query($sql);
    if ($result) {
      list($regioninfo) = $OpenSimDB->fetch();
      $info = aurora_split_key_value($regioninfo);		// from functions-opensim.php
      $serverip  = gethostbyname($info["serverIP"]);
      $httpport  = $info["serverHttpPort"];
      $serveruri = $info["serverURI"];
      $secret	= null;
    }
    else return array();
    break;

    default:
    return array();
  }

  $serverinfo["serverIP"] 	   = $serverip;
  $serverinfo["serverHttpPort"] = $httpport;
  $serverinfo["serverURI"] 	   = $serveruri;
  $serverinfo["regionSecret"]   = $secret;
	return $serverinfo;
}

function opensim_check_secure_session($agentID, $regionid, $secure, &$deprecated=null)
{
  global $OpenSimDB;


	if (!isUUID($agentID) or !isUUID($secure)) return false;

	switch($OpenSimVersion) {
    case OPENSIM_V07:
		$sql = "SELECT UserID FROM Presence WHERE UserID='$agentID' AND SecureSessionID='$secure'";
		if (isUUID($regionid)) $sql = $sql." AND RegionID='$regionid'";
    break;

    case OPENSIM_V06:
		$sql = "SELECT UUID FROM agents WHERE UUID='$agentID' AND secureSessionID='$secure' AND agentOnline='1'";
		if (isUUID($regionid)) $sql = $sql." AND currentRegion='$regionid'";
    break;

    case AURORASIM:
		$sql = "SELECT UUID FROM tokens,userinfo WHERE UUID='$agentID' AND UUID=UserID AND token='$secure' AND IsOnline='1'";
		if (isUUID($regionid)) $sql = $sql." AND CurrentRegionID='$regionid'";
    break;

    default:
    return false;
  }

	$query = $OpenSimDB->query($sql);
	if (!$query) return false;

	list($UUID) = $query->fetch();
	if ($UUID!=$agentID) return false;
	return true;
}

function opensim_check_region_secret($regionID, $secret, &$deprecated=null)
{
  global $OpenSimDB, $CurrencyDB;


	if (!isUUID($regionID)) return false;

	switch($OpenSimVersion) {
    case OPENSIM_V07:
    case OPENSIM_V06:
		$result = $OpenSimDB->prepareAndExecute("SELECT UUID FROM regions WHERE UUID=:uuid AND regionSecret=:regionSecret", array(
      'uuid' => $regionID,
      'regionSecret' => $secret,
    ));
		if ($result) {
			list($UUID) = $result->fetch();
			if ($UUID==$regionID) return true;
		}
    break;

    case AURORASIM:
		$result = $CurrencyDB->query("SELECT RegionInfo FROM userinfo,simulator WHERE UserID=:UserID AND CurrentRegionID=simulator.RegionID", array(
      'UserID' => $userid,
    ));
		if ($result) {
			list($regioninfo) = $result->fetch();
			$info = aurora_split_key_value($regioninfo);		// from functions-opensim.php
			if ($secret==$info["password"]) return true;
		}
    break;
	}

	return false;
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

function user_alert($agentID, $message, $secureID=null)
{
	$agentServer = opensim_get_server_info($agentID);
	if (!$agentServer) return false;
	$serverip  = $agentServer["serverIP"];
	$httpport  = $agentServer["serverHttpPort"];
	$serveruri = $agentServer["serverURI"];

	$avatarSession = opensim_get_avatar_session($agentID);
	if (!$avatarSession) return false;
	$sessionID = $avatarSession["sessionID"];
	if ($secureID==null) $secureID = $avatarSession["secureID"];

  $request  = xmlrpc_encode_request(
    'UserAlert', array(array('clientUUID'=>$agentID,
    'clientSessionID'=>$sessionID,
    'clientSecureSessionID'=>$secureID,
    'Description'=>$message,
  )));
	$response = do_call($serverip, $httpport, $serveruti, $request);

	return $response;
}
