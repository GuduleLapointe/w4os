<?php
//
// Mute_List 
//										by Fumi.Iseki (NSL)
//

require_once('../include/env_interface.php');


//$request_xml = $HTTP_RAW_POST_DATA;
//error_log("mute.php: ".$request_xml);



// Access Check
if (!opensim_is_access_from_region_server()) {
	$remote_addr = $_SERVER["REMOTE_ADDR"];
	error_log("mute.php: Illegal access from ".$remote_addr);
	exit;
}



$DbLink = new DB(MUTE_DB_HOST, MUTE_DB_NAME, MUTE_DB_USER, MUTE_DB_PASS);


$method = $_SERVER["PATH_INFO"];

if ($method == "/UpdateList/") {
	$parms = $HTTP_RAW_POST_DATA;
	$start = strpos($parms, "?>");

	if ($start != -1) {
		$start += 2;
		$parms = substr($parms, $start);
		//$parts = split("[<>]", $parms);
		$parts = preg_split("/[<>]/", $parms);
		$agent_id   = $parts[4];
		$mute_id    = $parts[8];
		$mute_name  = $parts[12];
		$mute_type  = $parts[16];
		$mute_flags = $parts[20];
		$timestamp  = $parts[24];

		if (isGUID($agent_id) and isGUID($mute_id) and isNumeric($mute_type) and isNumeric($mute_flags) and isNumeric($timestamp)) {
			$query_str = "INSERT INTO ".MUTE_LIST_TBL." (agentID,muteID,muteName,muteType,muteFlags,timestamp) ".
							"VALUES ('".$agent_id."','".$mute_id."','".mysql_escape_string($mute_name)."','".$mute_type."','".$mute_flags."','".$timestamp."')";
			$DbLink->query($query_str);

			//error_log("mute.php: UpdateList Query = ".$query_str);
			echo '<?xml version="1.0" encoding="utf-8"?><boolean>true</boolean>';
			exit;
		}
	}

	echo '<?xml version="1.0" encoding="utf-8"?><boolean>false</boolean>';
	exit;
}


if ($method == "/DeleteList/") {
	$parms = $HTTP_RAW_POST_DATA;
	$start = strpos($parms, "?>");

	if ($start != -1) {
		$start += 2;
		$parms = substr($parms, $start);
		//$parts = split("[<>]", $parms);
		$parts = preg_split("/[<>]/", $parms);
		$agent_id   = $parts[4];
		$mute_id    = $parts[8];
		$mute_name  = $parts[12];

		if (isGUID($agent_id) and isGUID($mute_id)) {
			$query_str = "DELETE FROM ".MUTE_LIST_TBL.
							" WHERE agentID='".$agent_id."' and muteID='".$mute_id."' and muteName='".mysql_escape_string($mute_name)."'";
			$DbLink->query($query_str);

			//error_log("mute.php: DeleteList Query = ".$query_str);
			if ($DbLink->Errno==0) {
				echo '<?xml version="1.0" encoding="utf-8"?><boolean>true</boolean>';
				exit;
			}
		}
	}

	echo '<?xml version="1.0" encoding="utf-8"?><boolean>false</boolean>';
	exit;
}


if ($method == "/RequestList/") {
	$parms = $HTTP_RAW_POST_DATA;
	//$parts = split("[<>]", $parms);
	$parts = preg_split("/[<>]/", $parms);
	$agent_id = $parts[6];
	$query_str = "";
	$errno = -1;

	if (isGUID($agent_id)) {
		$query_str = "SELECT agentID,muteID,muteName,muteType,muteFlags,timestamp FROM ".MUTE_LIST_TBL." WHERE agentID='".$agent_id."'";
		$DbLink->query($query_str);
		$errno = $DbLink->Errno;
	}
	//error_log("mute.php: RequestList Query = ".$query_str);

	echo '<?xml version="1.0" encoding="utf-8"?>';
	echo '<ArrayOfGridMuteList xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xmlns:xsd="http://www.w3.org/2001/XMLSchema">';

	if ($errno==0) {
		while(list($agentID, $muteID, $muteName, $muteType, $muteFlags, $timestamp) = $DbLink->next_record()) {
 			echo '<GridMuteList xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xmlns:xsd="http://www.w3.org/2001/XMLSchema">';
			echo   '<agentID>'.  $agentID.  '</agentID>';
			echo   '<muteID>'.   $muteID.   '</muteID>';
			echo   '<muteName>'. $muteName. '</muteName>';
			echo   '<muteType>'. $muteType. '</muteType>';
			echo   '<muteFlags>'.$muteFlags.'</muteFlags>';
			echo   '<timestamp>'.$timestamp.'</timestamp>';
			echo '</GridMuteList>';
		}
	}

	echo '</ArrayOfGridMuteList>';
	exit;
}


?>
