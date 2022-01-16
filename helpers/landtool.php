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
#		derived from this software without specific prior written permission.
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


//////////////////////////////////////////////////////////////////////////////
//
// Modified by Fumi.Iseki for CMS/LMS '09 5/31
//

require_once('include/env_interface.php');
require_once('helpers.php');



###################### No user serviceable parts below #####################
#
# The XMLRPC server object
#

$xmlrpc_server = xmlrpc_server_create();

#
# Land purchase sections
#
# Functions are called by the viewer directly.
#

#
# Land buying functions
#

xmlrpc_server_register_method($xmlrpc_server, "preflightBuyLandPrep", "buy_land_prep");

function buy_land_prep($method_name, $params, $app_data)
{
	$req		  = $params[0];
	$agentid	  = $req['agentId'];
	$sessionid	  = $req['secureSessionId'];
	$amount		  = $req['currencyBuy'];
	$billableArea = $req['billableArea'];
	$ipAddress 	  = $_SERVER['REMOTE_ADDR'];

	$ret = opensim_check_secure_session($agentid, null, $sessionid);

	if($ret) {
		$confirmvalue = get_confirm_value($ipAddress);
		$membership_levels = array('levels' => array('id' => "00000000-0000-0000-0000-000000000000", 'description' => "some level"));
		$landUse	= array('upgrade' => False, 'action' => "".SYSURL."");
		$currency   = array('estimatedCost' => convert_to_real($amount));
		$membership = array('upgrade' => False, 'action' => "".SYSURL."", 'levels' => $membership_levels);
		$response_xml = xmlrpc_encode(array('success'	=> True,
											'currency'  => $currency,
											'membership'=> $membership,
											'landUse'	=> $landUse,
											'currency'  => $currency,
											'confirm'	=> $confirmvalue));
	}
	else {
		$response_xml = xmlrpc_encode(array( 'success'	  	=> False,
											 'errorMessage'	=> "Unable to Authenticate\n\nClick URL for more info.",
											 'errorURI'		=> "".SYSURL.""));
	}

	header("Content-type: text/xml");
	echo $response_xml;

	return "";
}


#
# Perform the buy (所持金が足りないとき)
#

xmlrpc_server_register_method($xmlrpc_server, "buyLandPrep", "buy_land");

function buy_land($method_name, $params, $app_data)
{
	$req		  = $params[0];
	$agentid	  = $req['agentId'];
	$sessionid	  = $req['secureSessionId'];
	$amount		  = $req['currencyBuy'];
	$cost		  = $req['estimatedCost'];
	$billableArea = $req['billableArea'];
    $confim		  = $req['confirm'];
	$ipAddress	  = $_SERVER['REMOTE_ADDR'];

	//
	if ($confim!=get_confirm_value($ipAddress)) {
		$response_xml = xmlrpc_encode(array('success'     => False,
											'errorMessage'=> "\n\nMissmatch Confirm Value!!",
											'errorURI'    => "".SYSURL.""));
		header("Content-type: text/xml");
		echo $response_xml;
		return "";
	}

	$ret = opensim_check_secure_session($agentid, null, $sessionid);

	if ($ret) {
		if($amount>=0) {
		 	if (!$cost) $cost = convert_to_real($amount);
			if(!process_transaction($agentid, $cost, $ipAddress)) {
				$response_xml = xmlrpc_encode(array(
						'success'	   => False,
						'errorMessage' => "\n\nThe gateway has declined your transaction. Please update your payment method AND try again later.",
						'errorURI'	   => "".SYSURL.""));
			}
			//
			$enough_money = false;
			$res = add_money($agentid, $amount, $sessionid);
			if ($res["success"]) $enough_money = true;

			if ($enough_money) {
				$amount += get_balance($agentid);
				move_money($agentid, null, $amount, 5002, 0, "Land Purchase", 0, 0, $ipAddress);
				update_simulator_balance($agentid, -1, $sessionid);
				$response_xml = xmlrpc_encode(array('success' => True));
			}
			else {
				$response_xml = xmlrpc_encode(array('success'     => False,
												'errorMessage'=> "\n\nYou do not have sufficient funds for this purchase",
												'errorURI'	  => "".SYSURL.""));
			}
		}
	}
	else {
		$response_xml = xmlrpc_encode(array('success'	   => False,
											'errorMessage' => "\n\nUnable to Authenticate\n\nClick URL for more info.",
											'errorURI'	   => "".SYSURL.""));
	}

	header("Content-type: text/xml");
	echo $response_xml;

	return "";
}




#
# Process XMLRPC request
#

$request_xml = file_get_contents("php://input");
//error_log("landtool.php: ".$request_xml);

xmlrpc_server_call_method($xmlrpc_server, $request_xml, '');
xmlrpc_server_destroy($xmlrpc_server);
