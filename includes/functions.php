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
 * Sanitize a destination URI or URL
 * @param  string  $url								url or uri (secondlife:// url, hop:// url, region name...)
 * @param  string  $gatekeeperURL			default login uri to add to urls sithout host:port
 * @param  boolean $array_outout			output as array
 * @return string		(default)					$host:$port $region/$pos
 *			 or array											array($host, $port, $region, $pos)
 */
function opensim_sanitize_uri($url, $gatekeeperURL = NULL, $array_outout = false) {
  // $normalized = opensim_format_tp($uri, TPLINK_TXT);
  $host = NULL;
	$port = NULL;
	$region = NULL;
	$pos = NULL;
  $uri = urldecode($url);
  $uri = preg_replace('#.*://(([a-z0-9_-]+\.[a-z0-9\._-]+)([:/ ]+)?)?(([0-9]+?)([:/  ]+))?([^/]+)(/|$)#', '$2:$5:$7/', $uri);
  $split = explode('/', $uri);
  $uri = array_shift($split);
  if(count($split) == 2 || count($split) == 3) $pos = implode('/', $split);
  else $pos = "";
  $split = explode(':', $uri);
  if(count($split) == 1) {
    $region = $split[0];
  } else if (count($split) == 2 && preg_match('/ /', $split[1])) {
    // could probably improve the preg_replace to avoid this
    $host = $split[0];
    $split = explode(' ', $split[1]);
    $port = $split[0];
    $region = $split[1];
  } else {
    $host = $split[0];
    $port = $split[1];
    $region = $split[2];
  }
  if(empty($host) &! empty($gatekeeperURL)) {
    $split = explode(":", preg_replace('#.*://([^/]+)/?.*#', '$1', $gatekeeperURL));
    $host = $split[0];
    $port = $split[1];
  }
  if(empty($port) &! empty($host)) $port = 80;
	$host=strtolower(trim($host));
	$region = trim($region);
	if(is_numeric($region)) {
		$pos = "$region/$pos";
		$region = "";
	}
  if($array_outout) {
    return array(
      'host' => $host,
      'port' => $port,
      'region' => $region,
      'pos' => $pos
    );
  } else return trim(
		$host
		. (empty($port) ? '' : ":$port")
		. (empty($region) ? '' : " $region")
		. (empty($pos) ? '' : "/$pos")
	);

	// trim(string $string, string $characters = " \n\r\t\v\x00"): string
	// return preg_replace('#^[: ]*(.*)/*$#', '$1', "$host:$port $region" . ((empty($pos)) ? '' : "/$pos"));
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
  // $uri = preg_replace('#!#', '', $uri);
  // $uri = preg_replace('#.*://+#', '', $uri);
  // $uri = preg_replace('#[\|:]#', '/', $uri);
  // $uri = preg_replace('#^([^/]+)/([0-9]+)/#', '$1:$2/', $uri);
  // $uri = preg_replace('#^[[:blank:]]*#', '', $uri);
	// echo "$uri ";
  // // $uri = preg_replace('#(\d{4}):#', '$1/', $uri);
  // $parts = explode("/", $uri);
  // $loginuri = array_shift($parts);
  // $hostparts = explode(":", $loginuri);
  // $host = $hostparts[0];
  // $port = (empty($hostparts[1])) ? 80 : $hostparts[1];
  // $region = urldecode(array_shift($parts));
	// $pos="";
  // if(count($parts) >=3 && is_numeric($parts[0]) && is_numeric($parts[1]) && is_numeric($parts[2]) ) {
  //   $posparts = array($parts[0],$parts[1],$parts[2]);
  //   $pos = join('/', $posparts);
  //   $pos_sl = ($parts[0]>=256 || $parts[0]>=256) ? "" : $pos;
  // }
	$uri_parts = opensim_sanitize_uri($uri, '', true);
	debug($uri_parts);

	$regionencoded = urlencode($region);
  $pos_mandatory = (empty($pos)) ? "128/128/25" : $pos;
  $links = array();
  if ($format & TPLINK_TXT)		$links[TPLINK_TXT]		= "$host:$port $region/$pos";
  if ($format & TPLINK_LOCAL || ($format & TPLINK_HG && empty($host)) )
															$links[TPLINK_LOCAL]	= "secondlife://$region/$pos";
  if ($format & TPLINK_HG)		$links[TPLINK_HG]			= "secondlife://$host:$port+$region/$pos";
  if ($format & TPLINK_V3HG)	$links[TPLINK_V3HG]		= "secondlife://http|!!$host|$port+$region";
  if ($format & TPLINK_HOP)		$links[TPLINK_HOP]		= "hop://$host:$port/$regionencoded/$pos_mandatory";
  if ($format & TPLINK_APPTP)	$links[TPLINK_APPTP]	= "secondlife:///app/teleport/$host:$port+$regionencoded/" . ((!empty($pos_sl)) ? "$pos_sl/" : "");
  // if ($format & TPLINK_MAP)		$links[TPLINK_MAP]		= "secondlife:///app/map/$host:$port+$regionencoded/$pos";
	$links = preg_replace('#^[^[:alnum:]]*|[^[:alnum:]]+$#', '', $links);

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
