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
 *
 * @param  [type]  $uuid                 string to verify
 * @param  boolean $nullok               accept null value or null key as valid (default false)
 * @param  boolean $strict               apply strict UUID v4 implentation (default false)
 * @return boolean
 */
function opensim_isuuid( $uuid, $nullok = false, $strict = false ) {
	if ( $uuid == null ) {
		return $nullok;
	}
	if ( defined( 'NULL_KEY' ) && $uuid == NULL_KEY ) {
		return $nullok;
	}

	if ( $strict ) {
		return ( preg_match( '/^[0-9A-F]{8}-[0-9A-F]{4}-4[0-9A-F]{3}-[89AB][0-9A-F]{3}-[0-9A-F]{12}$/i', $uuid ) );
	} else {
		return ( preg_match( '/^[0-9A-F]{8,8}-[0-9A-F]{4,4}-[0-9A-F]{4,4}-[0-9A-F]{4,4}-[0-9A-F]{12,12}$/i', $uuid ) );
	}
}

/**
 * Sanitize a destination URI or URL
 *
 * @param  string  $url                             url or uri (secondlife:// url, hop:// url, region name...)
 * @param  string  $gatekeeperURL           default login uri to add to urls sithout host:port
 * @param  boolean $array_outout            output as array
 * @return string       (default)                   $host:$port $region/$pos
 *           or array                                           array($host, $port, $region, $pos)
 */
function opensim_sanitize_uri( $url, $gatekeeperURL = null, $array_outout = false ) {
	// $normalized = opensim_format_tp($uri, TPLINK_TXT);
	$host   = null;
	$port   = null;
	$region = null;
	$pos    = null;
	$uri    = urldecode( trim( $url ) );
	$uri    = preg_replace( '#^(.*://)?(([A-Za-z0-9_-]+\.[A-Za-z0-9\._-]+)([:/ ]+)?)?(([0-9]+)([ /:]))?([^/]+)(/|$)(.*)#', '$3:$6:$8/$10', "$uri" );
	$uri    = preg_replace( '/^([^:]+)::([0-9]+)/', '$1:$2', $uri );
	$uri    = preg_replace( '+[:/]*$+', '', $uri );
	$split  = explode( '/', $uri );
	$uri    = array_shift( $split );
	if ( count( $split ) == 2 || count( $split ) == 3 ) {
		$pos = implode( '/', $split );
	} else {
		$pos = '';
	}
	// $pos = preg_replace('+[^0-9/]+e', '', $pos);
	$split = explode( ':', $uri );
	if ( count( $split ) == 1 ) {
		$region = $split[0];
	} elseif ( count( $split ) == 2 && preg_match( '/ /', $split[1] ) ) {
		// could probably improve the preg_replace to avoid this
		$host   = $split[0];
		$split  = explode( ' ', $split[1] );
		$port   = $split[0];
		$region = $split[1];
	} else {
		$host   = $split[0];
		$port   = @$split[1];
		$region = @$split[2];
	}
	if ( empty( $host ) & ! empty( $gatekeeperURL ) ) {
		$split = explode( ':', preg_replace( '#.*://([^/]+)/?.*#', '$1', $gatekeeperURL ) );
		$host  = $split[0];
		$port  = $split[1];
	}
	if ( empty( $port ) & ! empty( $host ) ) {
		$port = 80;
	}
	$host   = strtolower( trim( $host ) );
	$region = trim( $region );
	if ( is_numeric( $region ) ) {
		$pos    = "$region/$pos";
		$region = '';
	}
	if ( $array_outout ) {
		return array(
			'host'       => $host,
			'port'       => $port,
			'region'     => $region,
			'pos'        => $pos,
			'gatekeeper' => "http://$host:$port",
			'key'        => strtolower( "$host:$port/$region" ),
		);
	} else {
		return trim(
			$host
			. ( empty( $port ) ? '' : ":$port" )
			. ( empty( $region ) ? '' : " $region" )
			. ( empty( $pos ) ? '' : "/$pos" ),
			":/ \n\r\t\v\x00"
		);
	}

	// trim(string $string, string $characters = " \n\r\t\v\x00"): string
	// return preg_replace('#^[: ]*(.*)/*$#', '$1', "$host:$port $region" . ((empty($pos)) ? '' : "/$pos"));
}

/**
 * Format destination uri as a valid local or hypergrid link url
 *
 * @param  string  $uri      Destination uri, as "host:port:Region Name" or already formatted URL
 * @param  integer $format  The desired format as binary flags. Several values can be specified with an addition
 *                          e.g. TPLINK_V3HG + TPLINK_APPTP
 *                          TPLINK_LOCAL or 1:   secondlife://Region Name/x/y/z
 *                          TPLINK_HG or 2:      original HG format (obsolete?)
 *                          TPLINK_V3HG or 4:    v3 HG format (Singularity)
 *                          TPLINK_HOP or 8:     hop:// format (FireStorm)
 *                          TPLINK_TXT or 16:    host:port Region Name
 *                          TPLINK_APPTP or 32:  secondlife:///app/teleport link
 *                          TPLINK_MAP or 64:    (not implemented)
 *                          127:                      output all formats
 * @param  string  $sep      Separator for multiple formats, default new line
 * @return string
 */
function opensim_format_tp( $uri, $format = TPLINK, $sep = "\n" ) {
	if ( empty( $uri ) ) {
		return;
	}
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
	// $posparts = array($parts[0],$parts[1],$parts[2]);
	// $pos = join('/', $posparts);
	// $pos_sl = ($parts[0]>=256 || $parts[0]>=256) ? "" : $pos;
	// }
	$uri_parts = opensim_sanitize_uri( $uri, '', true );
	extract( $uri_parts );

	$regionencoded = urlencode( $region );
	$pos_mandatory = ( empty( $pos ) ) ? '128/128/25' : $pos;
	$links         = array();
	if ( $format & TPLINK_TXT ) {
		$links[ TPLINK_TXT ] = "$host:$port $region/$pos";
	}
	if ( $format & TPLINK_LOCAL || ( $format & TPLINK_HG && empty( $host ) ) ) {
															$links[ TPLINK_LOCAL ] = "secondlife://$region/$pos";
	}
	if ( $format & TPLINK_HG ) {
		$links[ TPLINK_HG ] = "secondlife://$host:$port+$region/$pos";
	}
	if ( $format & TPLINK_V3HG ) {
		$links[ TPLINK_V3HG ] = "secondlife://http|!!$host|$port+$region";
	}
	if ( $format & TPLINK_HOP ) {
		$links[ TPLINK_HOP ] = "hop://$host:$port/$regionencoded/$pos_mandatory";
	}
	if ( $format & TPLINK_APPTP ) {
		$links[ TPLINK_APPTP ] = "secondlife:///app/teleport/$host:$port+$regionencoded/" . ( ( ! empty( $pos_sl ) ) ? "$pos_sl/" : '' );
	}
	// if ($format & TPLINK_MAP)		$links[TPLINK_MAP]		= "secondlife:///app/map/$host:$port+$regionencoded/$pos";
	$links = preg_replace( '#^[^[:alnum:]]*|[^[:alnum:]]+$#', '', $links );

	return join( $sep, $links );
}

/**
 * Use xmlrpc link_region method to request link_region data from robust
 *
 * @param  mixed  $args   region uri or sanitized region array
 * @param  string $var      output a single variable value
 * @return array (or string if var specified)
 */
function opensim_link_region( $args, $var = null ) {
	if ( empty( $args ) ) {
		return array();
	}
	global $OSSEARCH_CACHE;

	if ( is_array( $args ) ) {
		$region_array = $args;
	} else {
		$region_array = opensim_sanitize_uri( $args, '', true );
	}
	extract( $region_array ); // $host, $port, $region, $pos, $gatekeeper, $key

	if ( isset( $OSSEARCH_CACHE['link_region'][ $key ] ) ) {
		$link_region = $OSSEARCH_CACHE['link_region'][ $key ];
	} else {
		$link_region                           = oxXmlRequest( $gatekeeper, 'link_region', array( 'region_name' => "$region" ) );
		$OSSEARCH_CACHE['link_region'][ $key ] = $link_region;
	}
	if ( $link_region ) {
		if ( $var ) {
			return $link_region[ $var ];
		} else {
			return $link_region;
		}
	}
	return array();
}

function opensim_get_region( $region_uri, $var = null ) {
	if ( empty( $region_uri ) ) {
		return array();
	}
	global $OSSEARCH_CACHE;

	$region     = opensim_sanitize_uri( $region_uri, '', true );
	$gatekeeper = $region['gatekeeper'];

	$link_region = opensim_link_region( $region );
	$uuid        = @$link_region['uuid'];
	if ( ! opensim_isuuid( $uuid ) ) {
		return array();
	}

	if ( isset( $OSSEARCH_CACHE['get_region'][ $uuid ] ) ) {
		$get_region = $OSSEARCH_CACHE['get_region'][ $uuid ];
	} else {
		$get_region = oxXmlRequest( $gatekeeper, 'get_region', array( 'region_uuid' => "$uuid" ) );
		// $get_region = oxXmlRequest('http://dev.w4os.org:8402/grid', 'get_region_by_name', ['scopeid' => NULL_KEY,'name'=>"$region"]);
		$OSSEARCH_CACHE['get_region'][ $uuid ] = $get_region;
	}
	$get_region['link_region'] = $link_region;

	if ( $get_region ) {
		if ( $var ) {
			return $get_region[ $var ];
		} else {
			return $get_region;
		}
	}
	return array();
}

/**
 * Check if region is online
 *
 * @param  mixed $region   region uri or sanitized region array
 * @return boolean                  true if online
 */
function opensim_region_is_online( $region ) {
	$data = opensim_link_region( $region );
	return ( $data && $data['result'] == 'True' );
}

function opensim_user_alert( $agentID, $message, $secureID = null ) {
	$agentServer = opensim_get_server_info( $agentID );
	if ( ! $agentServer ) {
		return false;
	}
	$serverip  = $agentServer['serverIP'];
	$httpport  = $agentServer['serverHttpPort'];
	$serveruri = $agentServer['serverURI'];

	$avatarSession = opensim_get_avatar_session( $agentID );
	if ( ! $avatarSession ) {
		return false;
	}
	$sessionID = $avatarSession['sessionID'];
	if ( $secureID == null ) {
		$secureID = $avatarSession['secureID'];
	}

	$request  = xmlrpc_encode_request(
		'UserAlert',
		array(
			array(
				'clientUUID'            => $agentID,
				'clientSessionID'       => $sessionID,
				'clientSecureSessionID' => $secureID,
				'Description'           => $message,
			),
		)
	);
	$response = currency_xmlrpc_call( $serverip, $httpport, $serveruti, $request );

	return $response;
}

/**
 * [oxXmlRequest description]
 *
 * @param  string $gatekeeper               [description]
 * @param  string $method                   [description]
 * @param  array  $request                  [description]
 * @return array             received xml response
 */
function oxXmlRequest( $gatekeeper, $method, $request ) {
	$xml_request = xmlrpc_encode_request( $method, array( $request ) );

	$context = stream_context_create(
		array(
			'http' => array(
				'method'  => 'POST',
				'header'  => 'Content-Type: text/xml' . "\r\n",
				'timeout' => 3, // most of the time below 1 sec, but leave some time for slow ones
				'content' => $xml_request,
			),
		)
	);

	$response = @file_get_contents( $gatekeeper, false, $context );
	if ( $response === false ) {
		return false;
	}

	$xml_array = xmlrpc_decode( $response );
	if ( empty( $xml_array ) ) {
		return;
	}
	if ( is_array( $xml_array ) & ! xmlrpc_is_fault( $xml_array ) ) {
		return $xml_array;
	}

	return false;
}

function osXmlResponse( $success = true, $errorMessage = false, $data = false ) {
	if ( is_array( $data ) ) {
		$array = array(
			'success'      => $success,
			'errorMessage' => $errorMessage,
		);
		if ( ! empty( $data ) ) {
			$array['data'] = $data;
		}
		array_filter( $array );
		$response_xml = xmlrpc_encode( $array );
		echo $response_xml;
		return;
	}
	if ( $success ) {
		$answer = new SimpleXMLElement( '<boolean>true</boolean>' );
	} else {
		$answer = new SimpleXMLElement( "<error>$errorMessage</error>" );
	}
	echo $answer->asXML();
}

function osXmlDie( $message = '' ) {
	osXmlResponse( false, $message, array() );
	die;
}

/**
 * Flush output and free client so following commands are executed in background
 *
 * @return void
 */
function dontWait() {
	$size = ob_get_length();

	header( "Content-Length:$size" );
	header( 'Connection:close' );
	header( 'Content-Encoding: none' );
	header( 'Content-Type: text/html; charset=utf-8' );

	ob_flush();
	ob_end_flush();
	flush();
}

if ( ! function_exists( 'osdebug' ) ) {
	function osdebug( $message = '' ) {
		if ( empty( $message ) ) {
			return;
		}
		if ( ! is_string( $message ) ) {
			$message = print_r( $message, true );
		}
		error_log( 'osdebug ' . $message );
		echo $message . "\n";
	}
}

/**
 * OpenSim source to help further attempts to allow Hypergrid search results.
 * Infouuid is a fake parcelid resolving to region handle and (region-level?)
 * pos which might (or not) give enough information to allow hg results.
 * 1. Link region locally with link-region (or directly in db?)
 * 2. Use local link region handle (instead of remote one) to generate infouuid
 * 3. Use local link Global pos instead of remote one
 */
//
// public static UUID BuildFakeParcelID(ulong regionHandle, uint x, uint y)
// {
// byte[] bytes =
// {
// (byte)regionHandle, (byte)(regionHandle >> 8), (byte)(regionHandle >> 16), (byte)(regionHandle >> 24),
// (byte)(regionHandle >> 32), (byte)(regionHandle >> 40), (byte)(regionHandle >> 48), (byte)(regionHandle >> 56),
// (byte)x, (byte)(x >> 8), 0, 0,
// (byte)y, (byte)(y >> 8), 0, 0 };
// return new UUID(bytes, 0);
// }
//
// public static UUID BuildFakeParcelID(ulong regionHandle, uint x, uint y, uint z)
// {
// byte[] bytes =
// {
// (byte)regionHandle, (byte)(regionHandle >> 8), (byte)(regionHandle >> 16), (byte)(regionHandle >> 24),
// (byte)(regionHandle >> 32), (byte)(regionHandle >> 40), (byte)(regionHandle >> 48), (byte)(regionHandle >> 56),
// (byte)x, (byte)(x >> 8), (byte)z, (byte)(z >> 8),
// (byte)y, (byte)(y >> 8), 0, 0 };
// return new UUID(bytes, 0);
// }
