<?php
/*
 * economy.php
 *
 * Provides functions required by helpers
 *
 * Part of "flexible_helpers_scripts" collection
 *   https://github.com/GuduleLapointe/flexible_helper_scripts
 *   by Gudule Lapointe <gudule@speculoos.world>
 */

/**
 * Verify if given string is an UUID.
 * In theory, we would check want v4-compliant uuids
 * (xxxxxxxx-xxxx-4xxx-[89AB]xxx-xxxxxxxxxxxx) but OpenSimulator seems to have
 * lot of non v4-compliant uuids left, so stict defaults to false.
 * @param  [type]  $uuid                 string to verify
 * @param  boolean $nullok               accept null value or null key as valid (default false)
 * @param  boolean $strict               apply strict UUID v4 implentation (default false)
 * @return boolean
 */
function opensim_isuuid($uuid, $nullok=false, $strict = false)
{
	if ($uuid==null) return $nullok;
  if(defined('NULL_KEY') && $uuid == NULL_KEY) return $nullok;

  if($strict) return (preg_match('/^[0-9A-F]{8}-[0-9A-F]{4}-4[0-9A-F]{3}-[89AB][0-9A-F]{3}-[0-9A-F]{12}$/i', $uuid));
	else return (preg_match('/^[0-9A-F]{8,8}-[0-9A-F]{4,4}-[0-9A-F]{4,4}-[0-9A-F]{4,4}-[0-9A-F]{12,12}$/i', $uuid));
}

/**
 * Format destination uri as a valid local or hypergrid link url
 *
 * @param  string $uri      Destination uri, as "host:port:Region Name" or already formatted URL
 * @param  integer $format  The desired format as binary flags. Several values can be specified with an addition
 *                          e.g. TPLINK_V3HG + TPLINK_APPTP
 *                          TPLINK_LOCAL or 1:   secondlife://Region Name/x/y/z
 *                          TPLINK_HG or 2:      original HG format (obsolete?)
 *                          TPLINK_V3HG or 4:    v3 HG format (Singularity)
 *                          TPLINK_HOP or 8:     hop:// format (FireStorm)
 *                          TPLINK_TXT or 16:    host:port:Region Name
 *                          TPLINK_APPTP or 32:  secondlife:///app/teleport link
 *                          TPLINK_MAP or 64:    (not implemented)
 *                          127:                      output all formats
 * @param  string $sep      Separator for multiple formats, default new line
 * @return string
 */
function opensim_format_tp($uri, $format = TPLINK, $sep = "\n") {
  if(empty($uri)) return;
  $uri = preg_replace('#!#', '', $uri);
  $uri = preg_replace('#.*://+#', '', $uri);
  $uri = preg_replace('#[\|:]#', '/', $uri);
  $uri = preg_replace('#^([^/]+)/([0-9]+)/#', '$1:$2/', $uri);
  $uri = preg_replace('#^[[:blank:]]*#', '', $uri);
  // $uri = preg_replace('#(\d{4}):#', '$1/', $uri);
  $parts = explode("/", $uri);
  $loginuri = array_shift($parts);
  $hostparts = explode(":", $loginuri);
  $host = $hostparts[0];
  $port = (empty($hostparts[1])) ? 80 : $hostparts[1];
  $region = urldecode(array_shift($parts));
  $regionencoded = urlencode($region);
	$pos="";
  if(count($parts) >=3 && is_numeric($parts[0]) && is_numeric($parts[1]) && is_numeric($parts[2]) ) {
    $posparts = array($parts[0],$parts[1],$parts[2]);
    $pos = join('/', $posparts);
    $pos_sl = ($parts[0]>=256 || $parts[0]>=256) ? "" : $pos;
  }
  $pos_mandatory = (empty($pos)) ? "128/128/25" : $pos;
  $links = array();
  if ($format & TPLINK_TXT)    $links[TPLINK_TXT] = "$host:$port/$region/$pos";
  if ($format & TPLINK_LOCAL)  $links[TPLINK_LOCAL] = "secondlife://$region/$pos";
  if ($format & TPLINK_HG)     $links[TPLINK_HG] = "secondlife://$host:$port/$region/$pos";
  if ($format & TPLINK_V3HG)     $links[TPLINK_V3HG] = "secondlife://http|!!$host|$port+$region";
  if ($format & TPLINK_HOP)    $links[TPLINK_HOP] = "hop://$host:$port/$regionencoded/$pos_mandatory";
  if ($format & TPLINK_APPTP)     $links[TPLINK_APPTP] = "secondlife:///app/teleport/$host:$port:$regionencoded/" . ((!empty($pos_sl)) ? "$pos_sl/" : "");
  if ($format & TPLINK_MAP)     $links[TPLINK_MAP] = "secondlife:///app/map/$host:$port:$regionencoded/$pos";

  return join($sep, $links);
}

function opensim_user_alert($agentID, $message, $secureID=null)
{
	$agentServer = opensim_get_server_info($agentID);
	if (!$agentServer) return false;
	$serverip  = $agentServer["serverIP"];
	$httpport  = $agentServer["serverHttpPort"];
	$serveruri = $agentServer["serverURI"];

	$avatarSession = opensim_get_avatar_session($agentID);
	if (!$avatarSession) return false;
	$sessionID = $avatarSession["sessionID"];
	if ($secureID==null) $secureID = $avatarSession["secureID"];

  $request  = xmlrpc_encode_request(
    'UserAlert', array(array('clientUUID'=>$agentID,
    'clientSessionID'=>$sessionID,
    'clientSecureSessionID'=>$secureID,
    'Description'=>$message,
  )));
	$response = currency_xmlrpc_call($serverip, $httpport, $serveruti, $request);

	return $response;
}

function osXmlResponse($success = true, $errorMessage = false, $data = false) {
	if( is_array($data) ) {
		$array = array(
			'success'      => $success,
			'errorMessage' => $errorMessage,
		);
		if(!empty($data)) $array['data'] = $data;
		array_filter($array);
		$response_xml = xmlrpc_encode($array);
		echo $response_xml;
		return;
	}
	if($success) {
		$answer = new SimpleXMLElement("<boolean>true</boolean>");
	} else {
		$answer = new SimpleXMLElement("<error>$errorMessage</error>");
	}
	echo $answer->asXML();
}

function osXmlDie($message = "") {
	osXmlResponse(false, $message, []);
  die;
}

/**
 * Flush output and free client so following commands are executed in background
 * @return void
 */
function dontWait() {
	$size = ob_get_length();

	header("Content-Length:$size");
	header("Connection:close");
	header("Content-Encoding: none");
	header("Content-Type: text/html; charset=utf-8");

	ob_flush();
	ob_end_flush();
	flush();
}
