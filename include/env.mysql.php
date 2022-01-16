<?php
/*********************************************************************************
 * env.mysql.php v1.0.0 for OpenSim 	by Fumi.Iseki  2011 1/27
 *
 * 			Copyright (c) 2011   http://www.nsl.tuis.ac.jp/
 *
 *			supported versions of OpenSim are 0.6.7, 0.6.8, 0.6.9, 0.7 and 0.7.1Dev
 *			tools.func.php is needed
 *			mysql.func.php is needed
 *
 *********************************************************************************/


/*********************************************************************************
 * Function List

// for Currency
 function env_set_money_transaction($sourceId, $destId, $amount, $type, $falgs, $desc, $prminvent, $nxtowner, $ip)
 function env_get_money_balance($uuid)

**********************************************************************************/




/////////////////////////////////////////////////////////////////////////////////////
//
// Load Function
//

require_once('tools.func.php');
require_once('mysql.func.php');



/////////////////////////////////////////////////////////////////////////////////////
//
// for Currency

function env_set_money_transaction($sourceId, $destId, $amount, $type, $falgs, $desc, $prminvent, $nxtowner, $ip)
{
    if (!isNumeric($amount)) return;
	if (!isGUID($sourceId))  $sourceId = '00000000-0000-0000-0000-000000000000';
	if (!isGUID($destId))    $destId   = '00000000-0000-0000-0000-000000000000';

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
	if (!isGUID($uuid)) return -1;

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
