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

########################################################################
# This file enables buying currency in the client.
#
# For this to work, the all clients using currency need to add
#
#				-helperURI <WebpathToThisDirectory>
#
# to the commandline parameters when starting the client!
#
# Example:
#	client.exe -loginuri http://foo.com:8002/ -helperuri http://foo.com/
#
# Don't forget to change the currency conversion value in the wi_economy_money
# table!
#
# This requires PHP curl, XMLRPC, and MySQL extensions.
#
# If placed in the opensimwiredux web directory, it will share the db module
#


########################################################################
#
# Modified by Fumi.Iseki for XoopenSim/Modlos

########################################################################
#
# Modified by Olivier van Helden for modular optional currencies, including Gloebit
#
# To add a currency module, 
#    add "if()" section in  load_money_module to load the preferences
#       config file must be saved in as
#       ../flexible.helpers/yourcurrency.config.php.example to avoid 
#       overwriting it during updates
#    add "case ..." sections in get_currency_quote() and buy_currency()
#       to do specific function
# Do not remove other sections, to maintain the script as versatile as possible
#
# Please submit your changes for inclusion to
#    https://github.com/GuduleLapointe/flexible_helper_scripts

require_once('../include/env_interface.php');
require_once('./helpers.php');

#
# Money module arbitrage
# Check the current region money module and try to load the good one

function load_money_module($request_xml)
{
  global $conversion_table;

  $params=xmlrpc_decode($request_xml);
  $agentid=$params[0]["agentId"];

  if(file_exists('../config/gloebit.config.php')) {
    $server_info = opensim_get_server_info($agentid);
    $serverip  = $server_info["serverIP"];
    $httpport  = $server_info["serverHttpPort"];
    $informurl="http://${serverip}:${httpport}/gloebit/buy_complete?agentId=${agentid}";
    $headers = get_headers($informurl);
    $code = $headers[0];

    if($code != "HTTP/1.0 200 OK") return;
    
    include_once('../config/gloebit.config.php');
    return;
  }
/*
  if(file_exists('../config/yourcurrency.config.php')) {
    
    include_once('../config/yourcurrency.config.php');
    // always return, we can configure only one money module
    return;
  }
*/
}


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
  global $conversion_table;
  
	$req	   = $params[0];
	$agentid   = $req['agentId'];
	$sessionid = $req['secureSessionId'];
	$amount	   = $req['currencyBuy'];
	$ipAddress = $_SERVER['REMOTE_ADDR'];

//  check_money_module($agentid);

	$ret = opensim_check_secure_session($agentid, null, $sessionid);

	if ($ret) {
		$confirmvalue = get_confirm_value($ipAddress);
		switch(CURRENCY_MODULE) {
		  case "Gloebit":
		    $cost = 1; // default cost if no table;
      	while (list($key, $value) = each($conversion_table)) {
        	$cost = $value;
        	if(CURRENCY_FIXED_AMOUNTS) {
          	$realamount=$key;
         	} else {
           	$realamount=$amount;
        	}
        	if(CURRENCY_CONVERSION_THRESHOLD > 0) {
        	  $threshold = CURRENCY_CONVERSION_THRESHOLD;
        	} else {
        	  $threshold = 1;
        	}
          if($key >= $amount / $threshold) {
            break;
          }
        }
        break;
/*
      case "YourCurrency":
        // some code
        break;
*/
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
											'errorURI'	  => "".SYSURL.""));
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
											'errorURI'	  => "".SYSURL.""));
		header("Content-type: text/xml");
		echo $response_xml;
		return "";
	}

	$checkSecure = opensim_check_secure_session($agentid, null, $sessionid);
	if (!$checkSecure) {
		$response_xml = xmlrpc_encode(array('success'	  => False,
											'errorMessage'=> "\n\nMissmatch Secure Session ID!!",
											'errorURI'	  => "".SYSURL.""));
		header("Content-type: text/xml");
		echo $response_xml;
		return "";
	}

  switch(CURRENCY_MODULE) {
    case "Gloebit":
      if(CURRENCY_SANDBOX) {
        $baseurl="https://sandbox.gloebit.com/purchase";
      } else {
        $baseurl="https://www.gloebit.com/purchase";
      }
     	$server_info = opensim_get_server_info($agentid);
     	$serverip  = $server_info["serverIP"];
      $httpport  = $server_info["serverHttpPort"];

      $informurl="http://${serverip}:${httpport}/gloebit/buy_complete?agentId=${agentid}";
      $url="${baseurl}?reset&r=&inform=$informurl";
	   	$response_xml = xmlrpc_encode(array('success'	  => False,
	   	 							'errorMessage'=> "We need to bring you to Gloebit website do finish the transaction",
										'errorURI'	  => "".$url.""));
      break;
      
/*
      case "YourCurrency":
        // some code
        break;
*/

    default:
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
      	  	$response_xml = xmlrpc_encode(array('success'	  => False,
     	 										'errorMessage'=> "\n\nUnable to process the transaction. The gateway denied your charge",
    	 										'errorURI'	  => "".SYSURL.""));
    	  }
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

$request_xml = $HTTP_RAW_POST_DATA;
//error_log("currency.php: ".$request_xml);

load_money_module($request_xml);

xmlrpc_server_call_method($xmlrpc_server, $request_xml, '');
xmlrpc_server_destroy($xmlrpc_server);

