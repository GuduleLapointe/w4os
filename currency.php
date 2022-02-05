<?php
/*
 * currency.php
 *
 * Provides web tools for OpenSim currencies
 *
 * Part of "flexible_helpers_scripts" collection
 *   https://github.com/GuduleLapointe/flexible_helper_scripts
 *   by Gudule Lapointe <gudule@speculoos.world>
 *
 * Requires an OpenSimulator Money Server
 *    [DTL/NSL Money Server for OpenSim](http://www.nsl.tuis.ac.jp/xoops/modules/xpwiki/?OpenSim%2FMoneyServer)
 * or [Gloebit module](http://dev.gloebit.com/opensim/configuration-instructions/)
 *
 * Includes portions of code from
 *   Melanie Thielker and Teravus Ovares (http://opensimulator.org/)
 *   Fumi.Iseki for CMS/LMS '09 5/31
 */

// error_reporting(E_ERROR | E_WARNING | E_PARSE);

require_once('include/wp-config.php');
require_once('include/economy.php');

#
# The XMLRPC server object
#
$xmlrpc_server = xmlrpc_server_create();

#
# Viewer retrieves currency buy quote
#
xmlrpc_server_register_method($xmlrpc_server, "getCurrencyQuote", "get_currency_quote");
function get_currency_quote($method_name, $params, $app_data)
{
	$req	   = $params[0];
	$agentid   = $req['agentId'];
	$sessionid = $req['secureSessionId'];
	$amount	   = $req['currencyBuy'];
	$ipAddress = $_SERVER['REMOTE_ADDR'];

	$ret = opensim_check_secure_session($agentid, null, $sessionid);

	if ($ret) {
		$confirmvalue = get_confirm_value($ipAddress);
		switch(CURRENCY_PROVIDER) {
			case 'gloebit':
				$cost = 1; // default cost if no table;
				$conversion_table = GLOEBIT_CONVERSION_TABLE;
				while (list($key, $value) = each($conversion_table)) {
					$cost = $value;
					if(GLOEBIT_CONVERSION_THRESHOLD > 0) {
						$threshold = GLOEBIT_CONVERSION_THRESHOLD;
					} else {
						$threshold = 1;
					}
					if($key >= $amount / $threshold) {
						break;
					}
				}
				break;

			default:
			$cost = convert_to_real($amount);
			$realamount=$amount;
		}

		$currency = array('estimatedCost'=> $cost, 'currencyBuy'=> $realamount);
		$response_xml = xmlrpc_encode(array('success'	=> True,
		'currency'	=> $currency,
		'confirm'	=> $confirmvalue));
	}
	else {
		$response_xml = xmlrpc_encode(array('success'	  => False,
											'errorMessage'=> "Unable to Authenticate\n\nClick URL for more info.",
											'errorURI'	  => "".CURRENCY_HELPER_URL.""));
	}

	header("Content-type: text/xml");
	echo $response_xml;

	return "";
}

#
# Viewer buys currency
#
xmlrpc_server_register_method($xmlrpc_server, "buyCurrency", "buy_currency");
function buy_currency($method_name, $params, $app_data)
{
	$req	   = $params[0];
	$agentid   = $req['agentId'];
	$sessionid = $req['secureSessionId'];
	$amount	   = $req['currencyBuy'];
	$confim	   = $req['confirm'];
	$ipAddress = $_SERVER['REMOTE_ADDR'];

	//
	if ($confim!=get_confirm_value($ipAddress)) {
		$response_xml = xmlrpc_encode(array('success'	  => False,
											'errorMessage'=> "\n\nMissmatch Confirm Value!!",
											'errorURI'	  => "".CURRENCY_HELPER_URL.""));
		header("Content-type: text/xml");
		echo $response_xml;
		return "";
	}

	$checkSecure = opensim_check_secure_session($agentid, null, $sessionid);
	if (!$checkSecure) {
		$response_xml = xmlrpc_encode(array('success'	  => False,
											'errorMessage'=> "\n\nMissmatch Secure Session ID!!",
											'errorURI'	  => "".CURRENCY_HELPER_URL.""));
		header("Content-type: text/xml");
		echo $response_xml;
		return "";
	}

	$ret  = false;
	$cost = convert_to_real($amount);
	$transactionPermit = process_transaction($agentid, $cost, $ipAddress);

	if ($transactionPermit) {
		$res = add_money($agentid, $amount, $sessionid);
		if ($res["success"]) $ret = true;
	}

	if ($ret) {
		$response_xml = xmlrpc_encode(array('success' => True));
	}
	else {
		switch(CURRENCY_PROVIDER) {
			case 'podex':
			$errorMessage = get_option('w4os_podex_error_message');
			$errorURI = get_option('w4os_podex_redirect_url');
			break;

			case 'gloebit':
			if(defined(GLOEBIT_SANDBOX) && GLOEBIT_SANDBOX) {
        $baseurl="https://sandbox.gloebit.com/purchase";
      } else {
        $baseurl="https://www.gloebit.com/purchase";
      }
     	$server_info = opensim_get_server_info($agentid);
     	$serverip  = $server_info["serverIP"];
      $httpport  = $server_info["serverHttpPort"];

      $informurl="http://${serverip}:${httpport}/gloebit/buy_complete?agentId=${agentid}";
      $errorURI="${baseurl}?reset&r=&inform=$informurl";
			$errorMessage = "Click OK to finish the transaction on Gloebit website.";
			break;

			default:
			$errorMessage = 'Unable to process the transaction. The gateway denied your charge. Open help page?';
			$errorURI = empty(W4OS_GRID_INFO['help']) ? CURRENCY_HELPER_URL : W4OS_GRID_INFO['help'];
		}
		$response_xml = xmlrpc_encode(array('success'	  => False,
		'errorMessage'=> $errorMessage,
		'errorURI'	  => $errorURI));
	}

	header("Content-type: text/xml");
	echo $response_xml;

	return "";
}

#
# Region requests account balance
#
xmlrpc_server_register_method($xmlrpc_server, "simulatorUserBalanceRequest", "balance_request");

function balance_request($method_name, $params, $app_data)
{
	$req	   = $params[0];
	$agentid   = $req['agentId'];
	$sessionid = $req['secureSessionId'];

	$balance = get_balance($agentid, $sessionid);

	if ($balance>=0) {
		$response_xml = xmlrpc_encode(array('success' => True,
											'agentId' => $agentid,
											'funds'   => $balance));
	}
	else {
		$response_xml = xmlrpc_encode(array('success'	  => False,
											'errorMessage'=> "Could not authenticate your avatar. Money operations may be unavailable",
											'errorURI'	  => " "));
	}

	header("Content-type: text/xml");
	echo $response_xml;

	return "";
}

#
# Region initiates money transfer (Direct DB Operation for security)
#
xmlrpc_server_register_method($xmlrpc_server, "regionMoveMoney", "region_move_money");
function region_move_money($method_name, $params, $app_data)
{
	$req					= $params[0];
	$agentid				= $req['agentId'];
	$destid					= $req['destId'];
	$sessionid				= $req['secureSessionId'];
	$regionid				= $req['regionId'];
	$secret					= $req['secret'];
	$currencySecret			= $req['currencySecret'];
	$cash					= $req['cash'];
	$aggregatePermInventory = $req['aggregatePermInventory'];
	$aggregatePermNextOwner = $req['aggregatePermNextOwner'];
	$flags				 	= $req['flags'];
	$transactiontype		= $req['transactionType'];
	$description			= $req['description'];
	$ipAddress			  	= $_SERVER['REMOTE_ADDR'];

	$ret = opensim_check_region_secret($regionid, $secret);

	if ($ret) {
		$ret = opensim_check_secure_session($agentid, $regionid, $sessionid);

		if ($ret) {
			$balance = get_balance($agentid, $sessionid);
			if ($balance >= $cash) {
				move_money($agentid, $destid, $cash, $transactiontype, $flags, $description,
										$aggregatePermInventory, $aggregatePermNextOwner, $ipAddress);
				$sbalance = get_balance($agentid, $sessionid);
				$dbalance = get_balance($destid);

				$response_xml = xmlrpc_encode(array('success'		=> True,
													'agentId'		=> $agentid,
													'funds'		  	=> $balance,
													'funds2'		=> $balance,
													'currencySecret'=> " "));

				update_simulator_balance($agentid, $sbalance, $sessionid);
				update_simulator_balance($destid,  $dbalance);
			}
			else {
				$response_xml = xmlrpc_encode(array('success'	  => False,
													'errorMessage'=> "You do not have sufficient funds for this purchase",
													'errorURI'	  => " "));
			}
		}
		else {
			$response_xml = xmlrpc_encode(array('success'	  => False,
												'errorMessage'=> "Unable to authenticate avatar. Money operations may be unavailable",
												'errorURI'	  => " "));
		}
	}
	else {
		$response_xml = xmlrpc_encode(array('success'	  => False,
											'errorMessage'=> "This region is not authorized to manage your money.",
											'errorURI'	  => " "));
	}

	header("Content-type: text/xml");
	echo $response_xml;

	return "";
}

#
# Region claims user
#
xmlrpc_server_register_method($xmlrpc_server, "simulatorClaimUserRequest", "claimUser_func");
function claimUser_func($method_name, $params, $app_data)
{
	$req	   = $params[0];
	$agentid   = $req['agentId'];
	$sessionid = $req['secureSessionId'];
	$regionid  = $req['regionId'];
	$secret	   = $req['secret'];

	$ret = opensim_check_region_secret($regionid, $secret);

	if ($ret) {
		$ret = opensim_check_secure_session($agentid, null, $sessionid);

		if ($ret) {
			$ret = opensim_set_current_region($agentid, $regionid);

			if ($ret) {
				$balance = get_balance($agentid, $sessionid);
				$response_xml = xmlrpc_encode(array('success'		=> True,
													'agentId'		=> $agentid,
													'funds'		    => $balance,
													'currencySecret'=> " "));
			}
			else {
				$response_xml = xmlrpc_encode(array('success'	  => False,
													'errorMessage'=> "Error occurred, when DB was updated.",
													'errorURI'	  => " "));
			}
		}
		else {
			$response_xml = xmlrpc_encode(array('success'	  => False,
												'errorMessage'=> "Unable to authenticate avatar. Money operations may be unavailable.",
												'errorURI'	  => " "));
		}
	}
	else {
		$response_xml = xmlrpc_encode(array('success'	  => False,
											'errorMessage'=> "This region is not authorized to manage your money.",
											'errorURI'	  => " "));
	}

	header("Content-type: text/xml");
	echo $response_xml;

	return "";
}

#
# Process the request
#
$request_xml = file_get_contents("php://input");
//error_log(__FILE__ . ' '. $request_xml);

xmlrpc_server_call_method($xmlrpc_server, $request_xml, '');
xmlrpc_server_destroy($xmlrpc_server);
die();
