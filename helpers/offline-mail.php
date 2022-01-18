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

function tmplog($string) {
	global $PGM;
	exec("echo \"$PGM: $string\" >> /tmp/offline.log");
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

$PGM=getenv('SCRIPT_NAME');

$DbLink = new DB(OFFLINE_DB_HOST, OFFLINE_DB_NAME, OFFLINE_DB_USER, OFFLINE_DB_PASS, OFFLINE_DB_MYSQLI);

$method = $_SERVER["PATH_INFO"];
if(empty($method)) $method = '/' . basename(getenv('REDIRECT_URL')) . '/';

if ($method == "/SaveMessage/")
{
	$msg = $HTTP_RAW_POST_DATA;
	$start = strpos($msg, "?>");


	if ($start != -1)
	{
		$start+=2;
		$msg = substr($msg, $start);
		$parts = preg_split("/[<>]/", $msg);
		$from_agent = $parts[4];
		$to_agent = $parts[12];

		// Save for in-world delivery
		$DbLink->query("insert into ". OFFLINE_MESSAGE_TBL ." (uuid, message) values ('" .
		$DbLink->escape($to_agent) . "', '" .
		$DbLink->escape($msg) . "')");
		echo "<?xml version=\"1.0\" encoding=\"utf-8\"?><boolean>true</boolean>";

		// Save to mail queue if mail forwarding is set
		$query="select imviaemail, email from usersettings where useruuid='" .
		$DbLink->escape($to_agent) . "'";
		$DbLink->query($query);
		if($DbLink) {
			list($sendmail, $email) = $DbLink->next_record();
			if(empty($email)) {
				exit;
			} else if($sendmail =='false') {
				exit;
			} else {
				$xml = new SimpleXMLElement($HTTP_RAW_POST_DATA);
				// tmplog("$HTTP_RAW_POST_DATA");
				$data = simpleXMLToArray($xml);

				//while(list($key, $value)=each($data)) {
				//	tmplog("$key: $value");
				//}

				// To do: mail queue
				// Check for each imSessionID
				//	if last message older than 60 sec, send all & purge queue
				//	or first message older than 10 min
				// 		-> send all & purge queue
				// CREATE TABLE IF NOT EXISTS `offlinemail` (
				//  `sentDate` timestamp NOT NULL,
				//  `imSessionID` varchar(36) NOT NULL,
				//  `message` text NOT NULL,
				//   KEY `sentDate` (`sentDate`),
				//   KEY `imSessionID` (`imSessionID`)
				// ) ENGINE=MyISAM DEFAULT CHARSET=latin1;
				if($data['fromAgentName'] == "Server") $data[fromAgentName] = GRID_NAME;

				$headers .= "From: $data[fromAgentName] <" . OFFLINE_SENDER_MAIL . ">\r\n";
				// $body = $data['message']
				// 	. "\r\n"
				// 	. "\r\n------"
				// 	. "\r\nSpeculoos.net Instant Messages mail forwarding. Alpha version"
				// 	. "\r\nPlease do not answer to this mail, log in-world instead";
				$headers .= "MIME-Version: 1.0\r\n";
				$headers .= "Content-type: text/html; charset=UTF-8\r\n";
				$parts = explode('|', $data['message']);
				if(count($parts) > 1) {
					$subject=$parts[0];
					$body="<h4>$subject</h4>" . $parts[1];
				} else {
					$body = $data['message'];
				}
				$body = str_replace("\n", "\n<br>", $body);
				if(!empty(GRID_NAME)) {
					$in=" in " . GRID_NAME;
				}

				switch($data['dialog']) {
					// To complete from http://wiki.secondlife.com/wiki/ImprovedInstantMessage
					case "32":
					$subject="Group notice: $subject";
					$intro = "$data[fromAgentName] sent a group notice$in:";
					break;

					case "3":
					$subject = "Group invitation from $data[fromAgentName]";
					$intro = $body;
					$body = "Log in-world to accept or decline";
					break;

					case "4":
					$subject = "Inventory offer from $data[fromAgentName]";
					$intro = "$data[fromAgentName] returned you " . $body;
					$body = "Log in-world to accept or decline";
					break;

					// case "19":
					// $subject = "Message from $data[fromAgentName]";
					// $intro = $body;
					// break;

					case "38":
					$subject = "Friendship offer from $data[fromAgentName]";
					$intro = $body;
					$body = "Log in-world to accept or decline.";
					break;

					default:
					$subject = "Message from $data[fromAgentName]";
					$intro = "$data[fromAgentName] sent you a message$in:";
				}

				$body = "<html><body>"
					. "<p>$intro</p>"
					. "<blockquote>$body</blockquote>"
					. "\r\n"
					. "\r\n"
					. "<hr>"
					. "<p style='font-size:small'><b>$gridName</b> Instant Messages mail forwarding by speculoos.world (alpha version)."
						. "<br>Please log in-world to answer to this message. Emails to the sender address will not be procesed."
						. "<br>To disable mail notifications, uncheck option \"Send IM to mail\" in your viewer preferences (tab \"Chat\" or \"Communications\")."
						. "</p></body></html>";

						mail($email, $subject, $body, $headers);
					}
				}
			}
			else
			{
				echo "<?xml version=\"1.0\" encoding=\"utf-8\"?><boolean>false</boolean>";
			}
			exit;
		}

		if ($method == "/RetrieveMessages/")
		{
			$parms = $HTTP_RAW_POST_DATA;
			$parts = preg_split("/[<>]/", $parms);
			$agent_id = $parts[6];

			$DbLink->query("select message from ".OFFLINE_MESSAGE_TBL." where uuid='" .
			$DbLink->escape($agent_id) . "'");

			echo "<?xml version=\"1.0\" encoding=\"utf-8\"?><ArrayOfGridInstantMessage xmlns:xsi=\"http://www.w3.org/2001/XMLSchema-instance\" xmlns:xsd=\"http://www.w3.org/2001/XMLSchema\">";

				while(list($message) = $DbLink->next_record())
				{
					echo $message;
				}

				echo "</ArrayOfGridInstantMessage>";

				$DbLink->query("delete from ".OFFLINE_MESSAGE_TBL." where uuid='" .
				$DbLink->escape($agent_id) . "'");
				exit;
			}

			?>
