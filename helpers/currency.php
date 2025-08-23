<?php
/**
 * currency.php
 *
 * Provides web tools for OpenSim currencies
 *
 * Requires an OpenSimulator Money Server
 *    [DTL/NSL Money Server for OpenSim](http://www.nsl.tuis.ac.jp/xoops/modules/xpwiki/?OpenSim%2FMoneyServer)
 * or [Gloebit module](http://dev.gloebit.com/opensim/configuration-instructions/)
 *
 * @package     magicoli/opensim-helpers
 * @author      Gudule Lapointe <gudule@speculoos.world>
 * @link            https://github.com/magicoli/opensim-helpers
 * @license     AGPLv3
 *
 * Includes portions of code from
 *   Melanie Thielker and Teravus Ovares (http://opensimulator.org/)
 *   Fumi.Iseki for CMS/LMS '09 5/31
 */

// error_reporting(E_ERROR | E_WARNING | E_PARSE);

require_once __DIR__ . '/bootstrap.php';
require_once OPENSIM_ENGINE_PATH . '/includes/functions-economy.php';

// OpenSim_Currency::process() returns an array, ready to be encoded in XMLRPC
$register = OpenSim_Currency::register('currencyEnpointCallback');
if(isError($register)) {
	$error_message = 'Failed to register currency xmlrpc';
	$error_code = 500;
	if(is_array($register)) {
		$error_message = $register['errorMessage'] ?? $register['message'] ?? $error_message;
		$error_code = $register['error_code'] ?? $error_code;
	}
	osXmlDie($error_message, $error_code);
}

function currencyEnpointCallback($response, $error_code = null) {
	error_log('[DEBUG] ' . __FUNCTION__ . ' received response: ' . print_r($response, true));
	// This function is called by the OpenSim_Currency class
	// It should return an XMLRPC encoded response
	if (isError($response)) {
		$error_code = $response['error_code'] ?? 500;
		$error_message = $response['errorMessage'] ?? $response['message'] ?? 'No message provided';
		osXmlDie($error_message, $error_code);
	}

	return xmlrpc_encode($response);
}

// Code below should be adapted for v3 and moved insine OpenSim_Currency class
// OpenSim_Currency class should return the 


// //
// // The XMLRPC server object
// //
// $xmlrpc_server = xmlrpc_server_create();

//
// Viewer retrieves currency buy quote
//
// xmlrpc_server_register_method( $xmlrpc_server, 'getCurrencyQuote', 'currency_xmlrpc_quote' );
// function currency_xmlrpc_quote( $method_name, $params, $app_data ) {
// 	$req       = $params[0];
// 	$agentid   = $req['agentId'];
// 	$sessionid = $req['secureSessionId'];
// 	$amount    = $req['currencyBuy'];
// 	$ipAddress = $_SERVER['REMOTE_ADDR'];

// 	$ret = opensim_check_secure_session( $agentid, null, $sessionid );

// 	if ( $ret ) {
// 		$confirmvalue = currency_get_confirm_value( $ipAddress );
// 		switch ( CURRENCY_PROVIDER ) {
// 			case 'gloebit':
// 				$cost             = 1; // default cost if no table;
// 				$conversion_table = GLOEBIT_CONVERSION_TABLE;
// 				foreach ( $conversion_table as $key => $value ) {
// 					$cost = $value;
// 					if ( GLOEBIT_CONVERSION_THRESHOLD > 0 ) {
// 						$threshold = GLOEBIT_CONVERSION_THRESHOLD;
// 					} else {
// 						$threshold = 1;
// 					}
// 					if ( $key >= $amount / $threshold ) {
// 						break;
// 					}
// 				}
// 				break;

// 			default:
// 				$cost       = currency_virtual_to_real( $amount );
// 				$realamount = $amount;
// 		}

// 		$currency     = array(
// 			'estimatedCost' => $cost,
// 			'currencyBuy'   => $realamount,
// 		);
// 		$response_xml = xmlrpc_encode(
// 			array(
// 				'success'  => true,
// 				'currency' => $currency,
// 				'confirm'  => $confirmvalue,
// 			)
// 		);
// 	} else {
// 		$response_xml = xmlrpc_encode(
// 			array(
// 				'success'      => false,
// 				'errorMessage' => "Unable to Authenticate\n\nClick URL for more info.",
// 				'errorURI'     => '' . CURRENCY_HELPER_URL . '',
// 			)
// 		);
// 	}

// 	header( 'Content-type: text/xml' );
// 	echo $response_xml;

// 	return '';
// }

// //
// // Viewer buys currency
// //
// xmlrpc_server_register_method( $xmlrpc_server, 'buyCurrency', 'currency_xmlrpc_buy' );
// function currency_xmlrpc_buy( $method_name, $params, $app_data ) {
// 	$req       = $params[0];
// 	$agentid   = $req['agentId'];
// 	$sessionid = $req['secureSessionId'];
// 	$amount    = $req['currencyBuy'];
// 	$confim    = $req['confirm'];
// 	$ipAddress = $_SERVER['REMOTE_ADDR'];

// 	if ( $confim != currency_get_confirm_value( $ipAddress ) ) {
// 		$response_xml = xmlrpc_encode(
// 			array(
// 				'success'      => false,
// 				'errorMessage' => "\n\nMissmatch Confirm Value!!",
// 				'errorURI'     => '' . CURRENCY_HELPER_URL . '',
// 			)
// 		);
// 		header( 'Content-type: text/xml' );
// 		echo $response_xml;
// 		return '';
// 	}

// 	$checkSecure = opensim_check_secure_session( $agentid, null, $sessionid );
// 	if ( ! $checkSecure ) {
// 		$response_xml = xmlrpc_encode(
// 			array(
// 				'success'      => false,
// 				'errorMessage' => "\n\nMissmatch Secure Session ID!!",
// 				'errorURI'     => '' . CURRENCY_HELPER_URL . '',
// 			)
// 		);
// 		header( 'Content-type: text/xml' );
// 		echo $response_xml;
// 		return '';
// 	}

// 	$ret               = false;
// 	$cost              = currency_virtual_to_real( $amount );
// 	$transactionPermit = currency_process_transaction( $agentid, $cost, $ipAddress );

// 	if ( $transactionPermit ) {
// 		$res = currency_add_money( $agentid, $amount, $sessionid );
// 		if ( $res['success'] ) {
// 			$ret = true;
// 		}
// 	}

// 	if ( $ret ) {
// 		$response_xml = xmlrpc_encode( array( 'success' => true ) );
// 	} else {
// 		switch ( CURRENCY_PROVIDER ) {
// 			case 'podex':
// 				$errorURI     = null; // opensim_format_tp(PODEX_REDIRECT_URL, TPLINK_HOP);
// 				$errorMessage = PODEX_ERROR_MESSAGE . ' ' . PODEX_REDIRECT_URL;
// 				break;

// 			case 'gloebit':
// 				if ( defined( GLOEBIT_SANDBOX ) && GLOEBIT_SANDBOX ) {
// 					$baseurl = 'https://sandbox.gloebit.com/purchase';
// 				} else {
// 					$baseurl = 'https://www.gloebit.com/purchase';
// 				}
// 				$server_info = opensim_get_server_info( $agentid );
// 				$serverip    = $server_info['serverIP'];
// 				$httpport    = $server_info['serverHttpPort'];

// 				$informurl    = "http://${serverip}:${httpport}/gloebit/buy_complete?agentId=${agentid}";
// 				$errorURI     = "${baseurl}?reset&r=&inform=$informurl";
// 				$errorMessage = 'Click OK to finish the transaction on Gloebit website.';
// 				break;

// 			default:
// 				$errorMessage = 'Unable to process the transaction. The gateway denied your charge. Open help page?';
// 				// TODO: return website help page URL
// 				$errorURI     = CURRENCY_HELPER_URL ?? null;
// 		}
// 		$response_xml = xmlrpc_encode(
// 			array(
// 				'success'      => false,
// 				'errorMessage' => $errorMessage,
// 				'errorURI'     => $errorURI,
// 			)
// 		);
// 	}

// 	header( 'Content-type: text/xml' );
// 	echo $response_xml;

// 	return '';
// }

// //
// // Region requests account balance
// //
// xmlrpc_server_register_method( $xmlrpc_server, 'simulatorUserBalanceRequest', 'currency_xmlrpc_balance' );
// function currency_xmlrpc_balance( $method_name, $params, $app_data ) {
// 	$req       = $params[0];
// 	$agentid   = $req['agentId'];
// 	$sessionid = $req['secureSessionId'];

// 	$balance = currency_get_balance( $agentid, $sessionid );

// 	if ( $balance >= 0 ) {
// 		$response_xml = xmlrpc_encode(
// 			array(
// 				'success' => true,
// 				'agentId' => $agentid,
// 				'funds'   => $balance,
// 			)
// 		);
// 	} else {
// 		$response_xml = xmlrpc_encode(
// 			array(
// 				'success'      => false,
// 				'errorMessage' => 'Could not authenticate your avatar. Money operations may be unavailable',
// 				'errorURI'     => ' ',
// 			)
// 		);
// 	}

// 	header( 'Content-type: text/xml' );
// 	echo $response_xml;

// 	return '';
// }

// //
// // Region initiates money transfer (Direct DB Operation for security)
// //
// xmlrpc_server_register_method( $xmlrpc_server, 'regionMoveMoney', 'currency_xmlrpc_regionMoveMoney' );
// function currency_xmlrpc_regionMoveMoney( $method_name, $params, $app_data ) {
// 	$req                    = $params[0];
// 	$agentid                = $req['agentId'];
// 	$destid                 = $req['destId'];
// 	$sessionid              = $req['secureSessionId'];
// 	$regionid               = $req['regionId'];
// 	$secret                 = $req['secret'];
// 	$currencySecret         = $req['currencySecret'];
// 	$cash                   = $req['cash'];
// 	$aggregatePermInventory = $req['aggregatePermInventory'];
// 	$aggregatePermNextOwner = $req['aggregatePermNextOwner'];
// 	$flags                  = $req['flags'];
// 	$transactiontype        = $req['transactionType'];
// 	$description            = $req['description'];
// 	$ipAddress              = $_SERVER['REMOTE_ADDR'];

// 	$ret = opensim_check_region_secret( $regionid, $secret );

// 	if ( $ret ) {
// 		$ret = opensim_check_secure_session( $agentid, $regionid, $sessionid );

// 		if ( $ret ) {
// 			$balance = currency_get_balance( $agentid, $sessionid );
// 			if ( $balance >= $cash ) {
// 				currency_move_money(
// 					$agentid,
// 					$destid,
// 					$cash,
// 					$transactiontype,
// 					$flags,
// 					$description,
// 					$aggregatePermInventory,
// 					$aggregatePermNextOwner,
// 					$ipAddress
// 				);
// 				$sbalance = currency_get_balance( $agentid, $sessionid );
// 				$dbalance = currency_get_balance( $destid );

// 				$response_xml = xmlrpc_encode(
// 					array(
// 						'success'        => true,
// 						'agentId'        => $agentid,
// 						'funds'          => $balance,
// 						'funds2'         => $balance,
// 						'currencySecret' => ' ',
// 					)
// 				);

// 				currency_update_simulator_balance( $agentid, $sbalance, $sessionid );
// 				currency_update_simulator_balance( $destid, $dbalance );
// 			} else {
// 				$response_xml = xmlrpc_encode(
// 					array(
// 						'success'      => false,
// 						'errorMessage' => 'You do not have sufficient funds for this purchase',
// 						'errorURI'     => ' ',
// 					)
// 				);
// 			}
// 		} else {
// 			$response_xml = xmlrpc_encode(
// 				array(
// 					'success'      => false,
// 					'errorMessage' => 'Unable to authenticate avatar. Money operations may be unavailable',
// 					'errorURI'     => ' ',
// 				)
// 			);
// 		}
// 	} else {
// 		$response_xml = xmlrpc_encode(
// 			array(
// 				'success'      => false,
// 				'errorMessage' => 'This region is not authorized to manage your money.',
// 				'errorURI'     => ' ',
// 			)
// 		);
// 	}

// 	header( 'Content-type: text/xml' );
// 	echo $response_xml;

// 	return '';
// }

// //
// // Region claims user
// //
// xmlrpc_server_register_method( $xmlrpc_server, 'simulatorClaimUserRequest', 'currency_xmlrpc_claimUserRequest' );
// function currency_xmlrpc_claimUserRequest( $method_name, $params, $app_data ) {
// 	$req       = $params[0];
// 	$agentid   = $req['agentId'];
// 	$sessionid = $req['secureSessionId'];
// 	$regionid  = $req['regionId'];
// 	$secret    = $req['secret'];

// 	$ret = opensim_check_region_secret( $regionid, $secret );

// 	if ( $ret ) {
// 		$ret = opensim_check_secure_session( $agentid, null, $sessionid );

// 		if ( $ret ) {
// 			$ret = opensim_set_current_region( $agentid, $regionid );

// 			if ( $ret ) {
// 				$balance      = currency_get_balance( $agentid, $sessionid );
// 				$response_xml = xmlrpc_encode(
// 					array(
// 						'success'        => true,
// 						'agentId'        => $agentid,
// 						'funds'          => $balance,
// 						'currencySecret' => ' ',
// 					)
// 				);
// 			} else {
// 				$response_xml = xmlrpc_encode(
// 					array(
// 						'success'      => false,
// 						'errorMessage' => 'Error occurred, when DB was updated.',
// 						'errorURI'     => ' ',
// 					)
// 				);
// 			}
// 		} else {
// 			$response_xml = xmlrpc_encode(
// 				array(
// 					'success'      => false,
// 					'errorMessage' => 'Unable to authenticate avatar. Money operations may be unavailable.',
// 					'errorURI'     => ' ',
// 				)
// 			);
// 		}
// 	} else {
// 		$response_xml = xmlrpc_encode(
// 			array(
// 				'success'      => false,
// 				'errorMessage' => 'This region is not authorized to manage your money.',
// 				'errorURI'     => ' ',
// 			)
// 		);
// 	}

// 	header( 'Content-type: text/xml' );
// 	echo $response_xml;

// 	return '';
// }

