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
 * Verify if given string is an UUID
 * @param  [type]  $uuid                 string to verify
 * @param  boolean $nullok               accept null value or null key as valid (default false)
 * @param  boolean $strict               apply strict UUID v4 implentation (default true)
 * @return boolean
 */
function isUUID($uuid, $nullok=false, $strict = false)
{
	if ($uuid==null) return $nullok;
  if(defined('NULL_KEY') && $uuid == NULL_KEY) return $nullok;
  // Official V4 uuid (xxxxxxxx-xxxx-4xxx-[89AB]xxx-xxxxxxxxxxxx), should be
  if($strict)
  return (preg_match('/^[0-9A-F]{8}-[0-9A-F]{4}-4[0-9A-F]{3}-[89AB][0-9A-F]{3}-[0-9A-F]{12}$/i', $uuid));
  else
  return (preg_match('/^[0-9A-F]{8,8}-[0-9A-F]{4,4}-[0-9A-F]{4,4}-[0-9A-F]{4,4}-[0-9A-F]{12,12}$/i', $uuid));
}

/**
 * Format destination uri as a valid local or hypergrid link url
 *
 * @param  string $uri      Destination uri, as "host:port:Region Name" or already formatted URL
 * @param  integer $format  The disired format as binary flags. Several values can be specified with an addition
 *                          e.g. LINK_FORMAT_V3HG + LINK_FORMAT_APPTP
 *                          LINK_FORMAT_LOCAL or 1:   secondlife://Region Name/x/y/z
 *                          LINK_FORMAT_HG or 2:      original HG format (obsolete?)
 *                          LINK_FORMAT_V3HG or 4:    v3 HG format (Singularity)
 *                          LINK_FORMAT_HOP or 8:     hop:// format (FireStorm)
 *                          LINK_FORMAT_TXT or 16:    host:port:Region Name
 *                          LINK_FORMAT_APPTP or 32:  secondlife:///app/teleport link
 *                          LINK_FORMAT_MAP or 64:    (not implemented)
 *                          127:                      output all formats
 * @param  string $sep      Separator for multiple formats, default new line
 * @return string
 */
function destinationLink($uri, $format = LINK_FORMAT, $sep = "\n") {
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
  if(count($parts) >=3 && is_numeric($parts[0]) && is_numeric($parts[1]) && is_numeric($parts[2]) ) {
    $posparts = array($parts[0],$parts[1],$parts[2]);
    $pos = join('/', $posparts);
    $pos_sl = ($parts[0]>=256 || $parts[0]>=256) ? "" : $pos;
  }
  $pos_mandatory = (empty($pos)) ? "128/128/25" : $pos;
  $links = array();
  if ($format & LINK_FORMAT_TXT)    $links[LINK_FORMAT_TXT] = "$host:$port/$region/$pos";
  if ($format & LINK_FORMAT_LOCAL)  $links[LINK_FORMAT_LOCAL] = "secondlife://$region/$pos";
  if ($format & LINK_FORMAT_HG)     $links[LINK_FORMAT_HG] = "secondlife://$host:$port/$region/$pos";
  if ($format & LINK_FORMAT_V3HG)     $links[LINK_FORMAT_V3HG] = "secondlife://http|!!$host|$port+$region";
  if ($format & LINK_FORMAT_HOP)    $links[LINK_FORMAT_HOP] = "hop://$host:$port/$regionencoded/$pos_mandatory";
  if ($format & LINK_FORMAT_APPTP)     $links[LINK_FORMAT_APPTP] = "secondlife:///app/teleport/$host:$port:$regionencoded/" . ((!empty($pos_sl)) ? "$pos_sl/" : "");
  if ($format & LINK_FORMAT_MAP)     $links[LINK_FORMAT_MAP] = "secondlife:///app/map/$host:$port:$regionencoded/$pos";

  return join($sep, $links);
}

function xmlResponse($success = true, $errorMessage = false, $data = false) {
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

function xmlDie($message = "") {
	xmlResponse(false, $message, []);
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
