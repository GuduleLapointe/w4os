<?php
/*
 * register.php
 *
 * Script called by the simulator to register on the search engine. Actual data
 * fetch is processed by parser.php.
 *
 * Part of "flexible_helpers_scripts" collection
 *   https://github.com/GuduleLapointe/flexible_helper_scripts
 *   by Gudule Lapointe <gudule@speculoos.world>
 *
 * Requires OpenSimulator Search module
 *   [OpenSimSearch](https://github.com/kcozens/OpenSimSearch)
 * Events are fetched from 2do HYPEvents or any other HYPEvents implementation
 *   [2do HYPEvents](https://2do.pm)
 *
 * Includes portions of code from
 *   [OpenSimSearch](https://github.com/kcozens/OpenSimSearch)
 */

require_once 'includes/config.php';
require_once 'includes/search.php';

$host    = $_GET['host'];
$port    = $_GET['port'];
$service = $_GET['service'];

if ( $host == '' || $port == '' ) {
	header( 'HTTP/1.0 400 Bad Request' );
	echo "400 Bad Request: missing region host and/or port\n";
	exit;
}

switch ( $service ) {
	case 'online':
		// Check if there is already a database row for this host
		$query = $SearchDB->prepare( 'SELECT register FROM hostsregister WHERE host = :host AND port = :port' );
		$query->execute(
			array(
				':host' => $host,
				':port' => $port,
			)
		);

		// Get the request time as a timestamp for later
		$timestamp = $_SERVER['REQUEST_TIME'];

		// If a database row was returned check the nextcheck date
		if ( $query->rowCount() > 0 ) {
			$query = $SearchDB->prepare(
				'UPDATE hostsregister
      SET register = :timestamp, nextcheck = 0, checked = 0, failcounter = 0
      WHERE host = :host AND port = :port'
			);
		} else {
			// The SELECT did not return a result. Insert a new record.
			$query = $SearchDB->prepare( "INSERT INTO hostsregister VALUES (:host, :port, :timestamp, 0, 0, 0, '')" );
			// $query->execute( array($host, $port, $timestamp) );
		}
		$query->execute(
			array(
				':host'      => $host,
				':port'      => $port,
				':timestamp' => $timestamp,
			)
		);

		// Trigger parser for new host
		dontwait(); // make sure simulator doesn't pause start process
		sleep( 2 ); // leave simulator start process some time before querying it
		$parser = "{$_SERVER['REQUEST_SCHEME']}://{$_SERVER['HTTP_HOST']}" . dirname( $_SERVER['REQUEST_URI'] ) . '/parser.php';
		$result = file_get_contents( $parser );
		break;

	case 'offline':
		ossearch_hostUnregister( $host, $port );
		break;
}

if ( is_array( SEARCH_REGISTRARS ) & ! empty( $hostname ) & ! empty( $port ) & ! empty( $service ) ) {
	$querystring = getenv( 'QUERY_STRING' );
	foreach ( SEARCH_REGISTRARS as $registrar ) {
		$result = file_get_contents( "$registrar?$querystring" );
	}
}
die;
