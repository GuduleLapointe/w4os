<?php
#
#  Copyright (c)Melanie Thielker and Teravus Ovares (http://opensimulator.org/)
#
#  Redistribution and use in source and binary forms, with or without
#  modification, are permitted provided that the following conditions are met:
#	  * Redistributions of source code must retain the above copyright
#		notice, this list of conditions and the following disclaimer.
#	  * Redistributions in binary form must reproduce the above copyright
#		notice, this list of conditions and the following disclaimer in the
#		documentation and/or other materials provided with the distribution.
#	  * Neither the name of the OpenSim Project nor the
#		names of its contributors may be used to endorse or promote products
#		derived FROM this software without specific prior written permission.
#
#  THIS SOFTWARE IS PROVIDED BY THE DEVELOPERS ``AS IS'' AND ANY
#  EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT LIMITED TO, THE IMPLIED
#  WARRANTIES OF MERCHANTABILITY AND FITNESS FOR A PARTICULAR PURPOSE ARE
#  DISCLAIMED. IN NO EVENT SHALL THE CONTRIBUTORS BE LIABLE FOR ANY
#  DIRECT, INDIRECT, INCIDENTAL, SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES
#  (INCLUDING, BUT NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES;
#  LOSS OF USE, DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND
#  ON ANY THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT
#  (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE OF THIS
#  SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.
#

#
# need external variable  by Fumi.Iseki
#	class DB
# 	CURRENCY_DB_HOST, CURRENCY_DB_NAME, CURRENCY_DB_USER, CURRENCY_DB_PASS
#	CURRENCY_MONEY_TBL,	CURRENCY_TRANSACTION_TBL
#
#

#$request_xml = file_get_contents("php://input");
#error_log("helper.php: ".$request_xml);

####################################################################

#
# User provided interface routine to interface with payment processor
#

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


###################### No user serviceable parts below #####################

#
# Helper routines
#

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



function update_simulator_balance($agentID, $amount=-1, $secureID=null)
{
	if (!isGUID($agentID)) return false;

	if ($amount<0) {
		$amount = get_balance($agentID, $secureID);
		if ($amount<0) return false;
	}

	// XML RPC to Region Server
	if (!isGUID($secureID, true)) return false;

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



//
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

	if (isGUID($agentID) and $agentID!="00000000-0000-0000-0000-0000000000000") {
		opensim_set_currency_balance($agentID, -$amount);
	}

	if (isGUID($destID)  and $destID !="00000000-0000-0000-0000-0000000000000") {
		opensim_set_currency_balance($destID, $amount);
	}

	return true;
}



//
function  add_money($agentID, $amount, $secureID=null)
{
	if (!isGUID($agentID)) return false;

	//
	if (!USE_CURRENCY_SERVER) {
		env_set_money_transaction(null, $agentID, $amount, 5010, 0, "Add Money", 0, 0, "");
		$res["success"] = true;
		return $res;
	}

	//
	// XML RPC to Region Server
	//
	if (!isGUID($secureID, true)) return false;

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
    if (!isGUID($agentID)) return false;

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
	if (!isGUID($agentID)) return (integer)$cash;

	//
	if (!USE_CURRENCY_SERVER) {
		$cash = env_get_money_balance($agentID);
		return (integer)$cash;
	}

	//
	// XML RPC to Region Server
	//
	if (!isGUID($secureID, true)) return (integer)$cash;

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



function  get_confirm_value($ipAddress)
{
	$key = env_get_config("currency_script_key");
	if ($key=="") $key = "1234567883789";
	$confirmvalue = md5($key."_".$ipAddress);

	return $confirmvalue;
}

?>
