<?php
/**
 * economy.php
 *
 * Provides functions required by helpers
 *
 * @package     magicoli/opensim-helpers
 * @author      Gudule Lapointe <gudule@speculoos.world>
 * @link            https://github.com/magicoli/opensim-helpers
 * @license     AGPLv3
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
function is_uuid( $uuid, $nullok = false, $strict = false ) {
	if(! is_string( $uuid ) ) {
		return false;
	}
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
 * Sanitize a URL, removing spaces and invalid characters.
 * For any king of URL. For OpenSim teleport links, use opensim_sanitize_uri() instead.
 *
 * @param  string $url  The URL to sanitize.
 * @return string       The sanitized URL.
 */
if(! function_exists( 'sanitize_url' ) ) {
	// If sanitize_url is not defined, define it
	// This is to avoid conflicts with WordPress or other frameworks that may have their own sanitize_url function
	function sanitize_url( $url ) {
		$url = trim( $url );
		$url = str_replace( ' ', '+', $url );
		$url = filter_var( $url, FILTER_SANITIZE_URL );
		return $url;
	}
}

/**
 * Escape URL for safe output in HTML
 * 
 * @param string $url URL to escape
 * @return string Escaped URL
 */
function escape_url($url) {
    return htmlspecialchars($url, ENT_QUOTES, 'UTF-8');
}

/**
 * Does nothing, just returns the string unchanged. Meant to replace dozens of 
 * unnecessary calls to htmlspecialchars() in the code.
 */
function do_not_sanitize($string, $foo='', $bar='', $john='', $doe='' ) {
	return $string;
}

/**
 * Sanitize a string to be used as an ID or slug, like for html container id, css class, etc.
 */
function sanitize_id( $string ) {
	if( empty( $string ) ) {
		return false;
	}
	
	$id = $string;
	try {
		// Transliterate to Latin, remove diacritics, lowercase, but keep dashes and underscores
		$id = @transliterator_transliterate("Any-Latin; NFD; [:Nonspacing Mark:] Remove; NFC; Lower();", $id );
	} catch ( Error $e ) {
		error_log( 'Warning (php-intl missing) ' . $e->getMessage() );
		$id = strtolower( $id );
	}
	// Remove all punctuation except dashes and underscores
	$id = preg_replace('/[^\p{L}\p{N}_\-\s]/u', '-', $id);
	// Replace multiple separators with a single one
	$sep="[-_\s]";
	$id = preg_replace("/$sep* $sep*/", '_', $id );
	$id = preg_replace("/$sep*_$sep*/", '_', $id );
	$id = preg_replace("/$sep*-$sep*/", '-', $id );
	// Remove leading/trailing dashes
	$id = trim($id, '-');
		
	return $id;
}

function sanitize_version( $string ) {
	if( empty( $string ) ) {
		return false;
	}
	
	$id = $string;
	try {
		// Transliterate to Latin, remove diacritics, lowercase, but keep dashes and underscores
		$id = @transliterator_transliterate("Any-Latin; NFD; [:Nonspacing Mark:] Remove; NFC; Lower();", $id );
	} catch ( Error $e ) {
		error_log( 'Warning (php-intl missing) ' . $e->getMessage() );
		$id = strtolower( $id );
	}
	// Remove all punctuation except dashes and underscores
	$id = preg_replace('/[^\p{L}\p{N}_\-\s\.]/u', '-', $id);
	// Replace multiple separators with a single one
	$sep="[-_\s]";
	$id = preg_replace("/$sep* $sep*/", '_', $id );
	$id = preg_replace("/$sep*_$sep*/", '_', $id );
	$id = preg_replace("/$sep*-$sep*/", '-', $id );
	// Remove leading/trailing dashes
	$id = trim($id, '-');
		
	return $id;
}

/**
 * Sanitize a destination URI or URL, specifically for OpenSim teleport links.
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
	} elseif ( preg_match( '/[a-z].*\.[a-z]/', $split[0] ) ) {
			$host = array_shift( $split );
		if ( preg_match( '/^[0-9]+$/', $split[0] ) ) {
			$port = array_shift( $split );
		} else {
			$port = 8002;
		}
			$region = preg_replace( ':^/*:', '', @$split[0] );
	} elseif ( function_exists( 'w4os_grid_login_uri' ) ) {
		$host = parse_url( w4os_grid_login_uri(), PHP_URL_HOST );
		$port = parse_url( w4os_grid_login_uri(), PHP_URL_HOST );
		if ( preg_match( '/^[0-9]+$/', $split[0] ) ) {
			array_shift( $split );
		}
		$region = preg_replace( ':^/*:', '', @$split[0] );
	} else {
		if ( empty( $gatekeeperURL ) ) {
			return false;
		}
		$region = $split[2];
		$split  = explode( ':', preg_replace( '#.*://([^/]+)/?.*#', '$1', $gatekeeperURL ) );
		$host   = $split[0];
		$port   = $split[1];
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
	$region = trim( str_replace( '_', ' ', $region ) );
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
	$parts = parse_url( $uri );

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
	$region_hop    = str_replace( ' ', '%20', $region );
	$pos_mandatory = ( empty( $pos ) ) ? '128/128/25' : $pos;
	$links         = array();
	if ( $format & TPLINK_TXT ) {
		$links[ TPLINK_TXT ] = "$host:$port $region/$pos";
	}
	if ( $format & TPLINK_LOCAL || ( $format & TPLINK_HG && empty( $host ) ) ) {
															$links[ TPLINK_LOCAL ] = "secondlife://$region/$pos";
	}
	if ( $format & TPLINK_HG ) {
		$links[ TPLINK_HG ] = "secondlife://$host:$port $region/$pos";
	}
	if ( $format & TPLINK_V3HG ) {
		$links[ TPLINK_V3HG ] = "secondlife://http|!!$host|$port+$region";
	}
	if ( $format & TPLINK_HOP ) {
		$links[ TPLINK_HOP ] = "hop://$host:$port/$region_hop" . ( empty( $pos ) ? '' : "/$pos" );
	}
	if ( $format & TPLINK_APPTP ) {
		$links[ TPLINK_APPTP ] = "secondlife:///app/teleport/$host:$port+$regionencoded/" . ( ( ! empty( $pos_sl ) ) ? "$pos_sl/" : '' );
	}
	// if ($format & TPLINK_MAP)        $links[TPLINK_MAP]      = "secondlife:///app/map/$host:$port+$regionencoded/$pos";
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

/**
 * Build region URL from array
 *
 * @param  array $region sanitized region array
 * @return string
 */
function opensim_region_url( $region ) {
	if ( ! is_array( $region ) ) {
		return false;
	}
	return $region['gatekeeper'] . ( empty( $region['region'] ) ? '' : ':' . $region['region'] ) . ( empty( $region['pos'] ) ? '' : '/' . $region['pos'] );
}

function opensim_get_region( $region_uri, $var = null ) {
	if ( empty( $region_uri ) ) {
		return array();
	}
	global $OSSEARCH_CACHE;
	$region = opensim_sanitize_uri( $region_uri, '', true );

	$gatekeeper = $region['gatekeeper'];

	$link_region = opensim_link_region( $region );

	$uuid = @$link_region['uuid'];
	if ( ! is_uuid( $uuid ) ) {
		// error_log( "opensim_get_region $region_uri invalid uuid $uuid" );
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

	// Check if self-signed certificates should be accepted
	$beta_options = w4os_get_option('beta', array());
	$enable_self_signed = $beta_options['enable_self_signed'] ?? false;

	$options = array(
		'http' => array(
			'method'  => 'POST',
			'header'  => 'Content-Type: text/xml' . "\r\n",
			'timeout' => 3, // most of the time below 1 sec, but leave some time for slow ones
			'content' => $xml_request,
		),
	);

	// Add SSL context options if self-signed certificates should be accepted
	if ($enable_self_signed) {
		$options['ssl'] = array(
			'verify_peer'      => false,
			'verify_peer_name' => false,
			'allow_self_signed' => true,
		);
	}

	$context = stream_context_create($options);

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

function get_xml_response_data( $requestURL, $request ) {
	if(empty($request)) {
		return array();
	}

	// Check if self-signed certificates should be accepted
	$beta_options = w4os_get_option('beta', array());
	$enable_self_signed = $beta_options['enable_self_signed'] ?? false;

	$options = array(
		'http' => array(
			'method'  => 'POST',
			'header'  => 'Content-Type: text/xml' . "\r\n",
			'content' => $request,
		),
	);

	// Add SSL context options if self-signed certificates should be accepted
	if ($enable_self_signed) {
		$options['ssl'] = array(
			'verify_peer'      => false,
			'verify_peer_name' => false,
			'allow_self_signed' => true,
		);
	}

	$context = stream_context_create($options);
	$response  = xmlrpc_decode( file_get_contents( $requestURL, false, $context ) );
	if ( is_array( $response ) && ! xmlrpc_is_fault( $response ) && ! empty( $response ) && isset( $response['data'] ) ) {
		return $response['data'];
	} else {
		return array();
	}
}

function osXmlResponse( $success = true, $errorMessage = false, $data = false ) {
	global $request_key;

	// Data given, output as xmlrpc
	if ( is_array( $data ) ) {
		if( ! $success && ! empty( $errorMessage ) ) {
			# Avoid duplicate error messages
			# TODO: improve to make sure we don't cache error messages for different requests
			#	(currently $request_key only identifies search arguments, client IP and gateway url,
			# 	but client IP is the simulator, not the actual user, so in theory, the key could 
			# 	be identical for two simultaneous requests by different users in the same region, 
			#	although this is unlikely to happen)

			$tmp_dir = get_writable_tmp_dir();
			$cache = $tmp_dir . '/cache-' . $request_key;
			# Check if file exists and is not older than 5 seconds
			if ( file_exists( $cache ) && ( time() - filemtime( $cache ) < 1.5 ) ) {
				$errorMessage = '';
			} else {
				file_put_contents( $cache, $errorMessage );
			}
		}

		$array = array(
			'success'      => $success,
			'errorMessage' => $errorMessage,
			'data'		 => $data,
		);

		$response_xml = xmlrpc_encode( $array );
		
		echo $response_xml;
		return;
	}

	// No data given, output simple boolean or error message, no change here
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

function osNotice( $message ) {
	echo $message . "\n";
}

function osAdminNotice( $message, $error_code = 0, $die = false ) {
	// get calling function and file
	$trace = debug_backtrace();

	if ( isset( $trace[1] ) ) {
		$caller = $trace[1];
	} else {
		$caller = $trace[0];
	}
	$file     = empty( $caller['file'] ) ? '' : $caller['file'];
	$function = $caller['function'] . '()' ?? 'main';
	$line     = $caller['line'] ?? 0;
	$class    = $caller['class'] ?? 'main';
	$type     = $caller['type'] ?? '::';
	if ( $class != 'main' ) {
		$function = $class . $type . $function;
	}
	$file    = $file . ':' . $line;
	$message = sprintf(
		'%s%s: %s in %s',
		$function,
		empty( $error_code ) ? '' : " Error $error_code",
		$message,
		$file
	);
	error_log( $message );
	if ( $die == true ) {
		die( $error_code );
	}
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
		error_log( '[DEBUG] ' . $message );
		echo $message . "\n";
	}
}

function set_helpers_locale( $locale = null, $domain = 'messages' ) {
	mb_internal_encoding( 'UTF-8' );
	$encoding = mb_internal_encoding();

	if ( isset( $_GET['l'] ) ) {
		$locale = $_GET['l'];
	}
	$languages = array_filter( array_merge( array( $locale ), explode( ',', $_SERVER['HTTP_ACCEPT_LANGUAGE'] ) ) );

	// $results = putenv("LC_ALL=$locale");
	// if (!$results) {
	// exit ('putenv failed');
	// }

	// $currentLocale = setlocale(LC_ALL, 0);
	$user_locales = array_unique( array( $locale, $locale . ".$encoding", $locale . '.UTF-8', $locale . '.utf8', $locale, 0 ) );

	$user_locales = array_map(
		function ( $code ) {
			return preg_replace(
				array( '/;.*/', '/-/' ),
				array( '', '_' ),
				$code
			);
		},
		$languages
	);

	// Generate variants with different encodings appended
	$variants = array();
	foreach ( $user_locales as $lang ) {
		$variants[] = $lang;
		$variants[] = "$lang.$encoding";
		// $variants[] = "$lang.UTF-8";
	}

	$variants = array_unique( $variants );
	if ( ! setlocale( LC_ALL, $variants ) ) {
		// error_log( "setlocale() failed: none of  '" . join( ', ', $variants ) . "' does exist in this environment or setlocale() is not available on this platform" );
		setlocale( LC_ALL, 0 );
		return 0;
	}

	bindtextdomain( $domain, './locales' );
	textdomain( $domain );
}

function get_writable_tmp_dir() {
	if(isset($_GLOBALS['tmp_dir'])) {
		return $_GLOBALS['tmp_dir'];
	}
	$dirs = array( sys_get_temp_dir(), ini_get('upload_tmp_dir'), '/tmp', '/var/tmp', '/usr/tmp', '.' );
	foreach ( $dirs as $dir ) {
		if ( @is_writable( $dir ) ) {
			$_GLOBALS['tmp_dir'] = $dir;
			return $dir;
		}
	}
	error_log( __FILE__ . ':' . __LINE__ . ' ERROR - could not find a writable temporary directory, check web server and PHP config' );
	return false;
	// return '/tmp';
}

if( ! defined( 'NULL_KEY') ) define( 'NULL_KEY', '00000000-0000-0000-0000-000000000000' );
if( ! defined( 'TPLINK_LOCAL') ) {
	define( 'TPLINK_LOCAL', 1 ); // seconlife://Region/x/y/z
	define( 'TPLINK_HG', 2 ); // seconlife://yourgrid.org:8002 Region/x/y/z
	define( 'TPLINK_V3HG', 4 ); // the overcomplicated stuff!
	define( 'TPLINK_HOP', 8 ); // hop://yourgrid.org:8002:Region/x/y/z
	define( 'TPLINK_TXT', 16 ); // yourgrid.org:8002:Region/x/y/z
	define( 'TPLINK_APPTP', 32 ); // secondlife:///app/teleport/yourgrid.org:8002:Region/x/y/z
	define( 'TPLINK_MAP', 64 ); // secondlife:///app/map/yourgrid.org:8002:Region/x/y/z
	define( 'TPLINK', pow( 2, 8 ) - 1 ); // all formats
	define( 'TPLINK_DEFAULT', TPLINK_HOP ); // default
}

define( 'HELPERS_LOCALE_DIR', dirname( __DIR__ ) . '/languages' );

/**
 * Safe sprintf function to avoid fatal errors
 * Returns unchanged format string if error occurs
 * 
 * @param string $format
 * @param mixed ...$args
 * @return string
 */
function sprintf_safe($format, ...$args) {
	try {
		$result = sprintf($format, ...$args);
		restore_error_handler();
		return $result;
	} catch (Throwable $e) {
		error_log("Error sprintf_safe( $format, " . join(', ', $args) . '): ' . $e->getMessage());
		restore_error_handler();
		return $format;
	}
}

/**
 * Convert a string to PascalCase.
 * Preserves PascalCase-like words (e.g. "OpenSim", "OSHelper") as they are.
 * 
 * @param string $string
 * @return string
 */
function pascal_case($string) {
	// Convert to PascalCase
	$string = preg_replace('/[^a-zA-Z0-9]+/', ' ', $string); // Replace non-alphanumeric characters with space
	$string = trim(preg_replace('/\s+/', ' ', $string)); // Remove extra spaces
	$words = explode(' ', $string);
	$pascal_words = array();
	foreach ($words as $$key => $word) {
		if(preg_match('/^[A-Z].*[a-z]/', $word)) {
			continue;
		}
		$words[$key] = ucfirst(strtolower($word));
	}
	$string = implode('', $pascal_words);
	return $string;
}


/**
 * parse_ini_file_decode
 * Extended parse_ini_file to handle json_encoded values.
 */
function parse_ini_file_decode( $filename, $process_sections = false, $scanner_mode = INI_SCANNER_NORMAL ) {
	$ini_array = parse_ini_file( $filename, $process_sections, $scanner_mode );
	if ( ! is_array( $ini_array ) ) {
		return false;
	}

	foreach ( $ini_array as $section => $values ) {
		// By abundant precaution, as main sections are not supposed to be json-encoded
		if ( is_string( $values ) && preg_match( '/^\{.*\}$/', $values ) ) {
			$valuse = json_decode( $value, true );
			$ini_array[ $section ] = json_decode( $values, true );
		}

		if(is_array( $values ) ) {
			// Decode json-encoded values in sections
			foreach ( $values as $key => $value ) {
				// Json-decode value if needed
				if ( is_string( $value ) && preg_match( '/^\{.*\}$/', $value ) ) {
					$ini_array[ $section ][ $key ] = json_decode( $value, true );
				}
				// Decode dotnet ConnectionString format
				if ( is_string( $value ) && preg_match( '/^(Data Source=.*;|Initial Catalog=.*;User ID=.*;)/', $value ) ) {
					$ini_array[ $section ][ $key ] = Engine_Settings::connectionstring_to_array( $value );
				}

			}
		}
	}

	return $ini_array;
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

require_once(__DIR__ . '/functions-escaping.php');
