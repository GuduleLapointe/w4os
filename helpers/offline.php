<?php
/*
 * Mail forwarding enabled version @ 2010-2018 gudule.lapointe@speculoos.world
 *
 * Based on http://opensimulator.org/wiki/Offline_Messaging
 * Copyright (c) 2007, 2008 Contributors, http://opensimulator.org/
 * See CONTRIBUTORS for a full list of copyright holders.
 *
 * This looks like its lifted from http://www.weberdev.com/get_example-4372.html
 * I'd contact the original developer for licensing info, but his website is broken.
 *
 * See LICENSE for the full licensing terms of this file.
 *
*/

require_once('include/env_interface.php');

function xmlSuccess($boolean = true) {
	$result = ($boolean) ? 'true' : 'false';
	$answer = new SimpleXMLElement("<boolean>$result</boolean>");
	echo $answer->asXML();
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

$DbLink = new DB(OFFLINE_DB_HOST, OFFLINE_DB_NAME, OFFLINE_DB_USER, OFFLINE_DB_PASS, OFFLINE_DB_MYSQLI);

$method = $_SERVER['PATH_INFO)'];
if(empty($method)) $method = '/' . basename(getenv('REDIRECT_URL')) . '/';

switch($method) {
	case "/SaveMessage/":
	if (strpos($HTTP_RAW_POST_DATA, "?>") == -1) {
		xmlSuccess(false);
		die;
	}

	$xml = new SimpleXMLElement($HTTP_RAW_POST_DATA);

	// Save for in-world delivery
	$query = sprintf(
		"INSERT INTO %s (PrincipalID, FromID, Message) VALUES ('%s', '%s', '%s');",
		OFFLINE_MESSAGE_TBL,
		$DbLink->escape($xml->toAgentID),
		$DbLink->escape($xml->fromAgentID),
		$DbLink->escape(preg_replace('/>\n/', '>', $xml->asXML())),
	);
	$DbLink->query($query);
	// Send in-world save result, send by mail is bonus
	xmlSuccess($DbLink->Errno==0);

	// Save to mail queue if mail forwarding is set
	$query = sprintf(
		"SELECT imviaemail, email FROM usersettings WHERE useruuid='%s';",
		$DbLink->escape($xml->toAgentID),
	);
	$DbLink->query($query);

	if($DbLink) {
		list($sendmail, $email) = $DbLink->next_record();
		if(empty($email) || $sendmail =='false') exit;

		if($xml->fromAgentName == "Server") $xml->fromAgentName = GRID_NAME;

		$headers = "From: $xml->fromAgentName <" . OFFLINE_SENDER_MAIL . ">\r\n";
		$headers .= "MIME-Version: 1.0\r\n";
		$headers .= "Content-type: text/html; charset=UTF-8\r\n";
		$parts = explode('|', $xml->message);
		if(count($parts) > 1) {
			$subject=$parts[0];
			$body="<h4>$subject</h4>" . $parts[1];
		} else {
			$body = $xml->message;
		}
		$body = str_replace("\n", "\n<br>", $body);
		if(!empty(GRID_NAME)) {
			$in=" in " . GRID_NAME;
		}

		switch($xml->dialog) {
			// To complete from http://wiki.secondlife.com/wiki/ImprovedInstantMessage
			case "32":
			$subject="Group notice: $subject";
			$intro = "$xml->fromAgentName sent a group notice$in:";
			break;

			case "3":
			$subject = "Group invitation from $xml->fromAgentName";
			$intro = $body;
			$body = "Log in-world to accept or decline";
			break;

			case "4":
			$subject = "Inventory offer from $xml->fromAgentName";
			$intro = "$xml->fromAgentName returned you " . $body;
			$body = "Log in-world to accept or decline";
			break;

			// case "19":
			// $subject = "Message from $xml->fromAgentName";
			// $intro = $body;
			// break;

			case "38":
			$subject = "Friendship offer from $xml->fromAgentName";
			$intro = $body;
			$body = "Log in-world to accept or decline.";
			break;

			default:
			$subject = "Message from $xml->fromAgentName";
			$intro = "$xml->fromAgentName sent you a message$in:";
		}

		$body = "<html><body>"
		. "<p>$intro</p>"
		. "<blockquote>$body</blockquote>"
		. "\r\n"
		. "\r\n"
		. "<hr>"
		. "<p style='font-size:small'><b>" . GRID_NAME . "</b> Instant Messages mail forwarding by w4os."
		. "<br>Please log in-world to answer to this message. Emails to the sender address will not be processed."
		. "<br>To disable mail notifications, uncheck option \"Send IM to mail\" in your viewer preferences (tab \"Chat\" or \"Communications\")."
		. "</p></body></html>";

		if(defined( 'WPINC' )) {
			// We're inside WordPress, use wp_mail()
			add_action('plugins_loaded', function() use ($email, $subject, $body, $headers) {
				$result = wp_mail($email, $subject, $body, $headers);
				if(! $result) error_log(__FILE__ . "error $result sending IM notification to $email.");
				die();
			}, 99 );
		} else {
			// use standard PHP mail, might need a local smtp server
			$result = mail($email, $subject, $body, $headers);
			if(! $result) error_log(__FILE__ . "error $result sending IM notification to $email.");
			die();
		}
	}
	break;

 	case "/RetrieveMessages/"):
	$xml = new SimpleXMLElement($HTTP_RAW_POST_DATA);

	$errno = -1;

	if (isGUID($xml->Guid)) {
		$query = sprintf(
			"SELECT ID, Message FROM %s WHERE PrincipalID='%s'",
			OFFLINE_MESSAGE_TBL,
			$xml->Guid,
		);
		$DbLink->query($query);
		$result = $DbLink->Errno;
		$delete_query = '';
		if ($result==0) {
			echo '<?xml version="1.0" encoding="utf-8"?>';
			echo '<ArrayOfGridInstantMessage xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xmlns:xsd="http://www.w3.org/2001/XMLSchema">';
			while(list($id, $message) = $DbLink->next_record()) {
				$start = strpos($message, "?>");
				if ($start != -1) $message = substr($message, $start + 2);
				echo $message;
				$delete_query .= sprintf(
					"DELETE FROM %s WHERE ID = %d; ",
					OFFLINE_MESSAGE_TBL,
					$id,
				);
			}
			echo '</ArrayOfGridInstantMessage>';
			if(!empty($delete_query)) {
				$DbLink->query($delete_query);
				$result = $DbLink->Errno;
				if ($result!=0) error_log("DB error while deleting sent messages");
			}
		} else {
			error_log("DB error while retrieving messages $result");
		}
	}
	exit;
	break;

	default:
	error_log("Offline messages: method $method not implemented, please configure OfflineMessageModule = OfflineMessageModule in OpenSim.ini");
	die();
}
