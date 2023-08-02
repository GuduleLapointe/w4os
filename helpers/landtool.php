<?php
/**
 * landtool.php
 *
 * Provides web tools for land sales operations
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

require_once 'includes/config.php';
require_once 'includes/economy.php';

// No user serviceable parts below #####################
//
// The XMLRPC server object
//

$xmlrpc_server = xmlrpc_server_create();

//
// Land purchase sections
//
// Functions are called by the viewer directly.
//

//
// Land buying functions
//

xmlrpc_server_register_method( $xmlrpc_server, 'preflightBuyLandPrep', 'buy_land_prep' );

function buy_land_prep( $method_name, $params, $app_data ) {
	$req          = $params[0];
	$agentid      = $req['agentId'];
	$sessionid    = $req['secureSessionId'];
	$amount       = $req['currencyBuy'];
	$billableArea = $req['billableArea'];
	$ipAddress    = $_SERVER['REMOTE_ADDR'];

	$ret = opensim_check_secure_session( $agentid, null, $sessionid );

	if ( $ret ) {
		$confirmvalue      = currency_get_confirm_value( $ipAddress );
		$membership_levels = array(
			'levels' => array(
				'id'          => '00000000-0000-0000-0000-000000000000',
				'description' => 'some level',
			),
		);
		$landUse           = array(
			'upgrade' => false,
			'action'  => '' . CURRENCY_HELPER_URL . '',
		);
		$currency          = array( 'estimatedCost' => currency_virtual_to_real( $amount ) );
		$membership        = array(
			'upgrade' => false,
			'action'  => '' . CURRENCY_HELPER_URL . '',
			'levels'  => $membership_levels,
		);
		$response_xml      = xmlrpc_encode(
			array(
				'success'    => true,
				'currency'   => $currency,
				'membership' => $membership,
				'landUse'    => $landUse,
				'currency'   => $currency,
				'confirm'    => $confirmvalue,
			)
		);
	} else {
		$response_xml = xmlrpc_encode(
			array(
				'success'      => false,
				'errorMessage' => "Unable to Authenticate\n\nClick URL for more info.",
				'errorURI'     => '' . CURRENCY_HELPER_URL . '',
			)
		);
	}

	header( 'Content-type: text/xml' );
	echo $response_xml;

	return '';
}


//
// Perform the buy (所持金が足りないとき)
//

xmlrpc_server_register_method( $xmlrpc_server, 'buyLandPrep', 'buy_land' );

function buy_land( $method_name, $params, $app_data ) {
	 $req         = $params[0];
	$agentid      = $req['agentId'];
	$sessionid    = $req['secureSessionId'];
	$amount       = $req['currencyBuy'];
	$cost         = $req['estimatedCost'];
	$billableArea = $req['billableArea'];
	$confim       = $req['confirm'];
	$ipAddress    = $_SERVER['REMOTE_ADDR'];

	if ( $confim != currency_get_confirm_value( $ipAddress ) ) {
		$response_xml = xmlrpc_encode(
			array(
				'success'      => false,
				'errorMessage' => "\n\nMissmatch Confirm Value!!",
				'errorURI'     => '' . CURRENCY_HELPER_URL . '',
			)
		);
		header( 'Content-type: text/xml' );
		echo $response_xml;
		return '';
	}

	$ret = opensim_check_secure_session( $agentid, null, $sessionid );

	if ( $ret ) {
		if ( $amount >= 0 ) {
			if ( ! $cost ) {
				$cost = currency_virtual_to_real( $amount );
			}
			if ( ! currency_process_transaction( $agentid, $cost, $ipAddress ) ) {
				$response_xml = xmlrpc_encode(
					array(
						'success'      => false,
						'errorMessage' => "\n\nThe gateway has declined your transaction. Please update your payment method AND try again later.",
						'errorURI'     => '' . CURRENCY_HELPER_URL . '',
					)
				);
			}
						$enough_money = false;
			$res                      = currency_add_money( $agentid, $amount, $sessionid );
			if ( $res['success'] ) {
				$enough_money = true;
			}

			if ( $enough_money ) {
				$amount += currency_get_balance( $agentid );
				currency_move_money( $agentid, null, $amount, 5002, 0, 'Land Purchase', 0, 0, $ipAddress );
				currency_update_simulator_balance( $agentid, -1, $sessionid );
				$response_xml = xmlrpc_encode( array( 'success' => true ) );
			} else {
				$response_xml = xmlrpc_encode(
					array(
						'success'      => false,
						'errorMessage' => "\n\nYou do not have sufficient funds for this purchase",
						'errorURI'     => '' . CURRENCY_HELPER_URL . '',
					)
				);
			}
		}
	} else {
		$response_xml = xmlrpc_encode(
			array(
				'success'      => false,
				'errorMessage' => "\n\nUnable to Authenticate\n\nClick URL for more info.",
				'errorURI'     => '' . CURRENCY_HELPER_URL . '',
			)
		);
	}

	header( 'Content-type: text/xml' );
	echo $response_xml;

	return '';
}




//
// Process XMLRPC request
//

$request_xml = file_get_contents( 'php://input' );
// error_log("landtool.php: ".$request_xml);

xmlrpc_server_call_method( $xmlrpc_server, $request_xml, '' );
xmlrpc_server_destroy( $xmlrpc_server );
