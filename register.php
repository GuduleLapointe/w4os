<?php
/*
 * register.php
 *
 * Part of "flexible_helpers_scripts" collection
 * https://github.com/GuduleLapointe/flexible_helper_scripts
 *
 * This this script allow hosts to register on the search engine. It is called
 * by the simulator upon start and on regular intervals defined in OpenSim.ini.
 *
 * Actual search data are fetched by parser.php for registered hosts with
 * past nextcheck date.
 */

require_once('include/config.php');
require_once('include/ossearch_db.php');

$host = $_GET['host'];
$port = $_GET['port'];
$service = $_GET['service'];

if ($host == "" || $port == "")
{
  header("HTTP/1.0 400 Bad Request");
  echo "400 Bad Request: missing region host and/or port\n";
  exit;
}


// Attempt to connect to the database
try {
  $SearchDB = new PDO('mysql:host=' . SEARCH_DB_HOST . ';dbname=' . SEARCH_DB_NAME, SEARCH_DB_USER, SEARCH_DB_PASS);
  $SearchDB->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
}
catch(PDOException $e)
{
  header("HTTP/1.0 500 Internal Server Error");
  error_log(__FILE__ . " Could not connect to the database");
  die();
}

switch($service) {
  case 'online':
  // Check if there is already a database row for this host
  $query = $SearchDB->prepare("SELECT register FROM hostsregister WHERE host = :host AND port = :port");
  $query->execute( array( ':host' => $host, ':port' => $port ) );

  // Get the request time as a timestamp for later
  $timestamp = $_SERVER['REQUEST_TIME'];

  // If a database row was returned check the nextcheck date
  if ($query->rowCount() > 0)
  {
    $query = $SearchDB->prepare("UPDATE hostsregister SET register = :timestamp, nextcheck = 0, checked = 0, failcounter = 0 WHERE host = :host AND port = :port");
  }
  else
  {
    // The SELECT did not return a result. Insert a new record.
    $query = $SearchDB->prepare("INSERT INTO hostsregister VALUES (:host, :port, :timestamp, 0, 0, 0)");
    // $query->execute( array($host, $port, $timestamp) );
  }
  $query->execute( array( ':host' => $host, ':port' => $port, ':timestamp' => $timestamp ) );
  break;

  case 'offline':
  $query = $SearchDB->prepare("DELETE FROM hostsregister WHERE host = :host AND port = :port");
  $query->execute( array( ':host' => $host, ':port' => $port ) );
  break;

  // default:
  // error_log(__FILE__ . " bad request " . getenv('QUERY_STRING') . " raw data " . $HTTP_RAW_POST_DATA);
}

$SearchDB = NULL;

if (is_array($otherRegistrars) && $hostname != "" && $port != "" && $service != "")
{
	$querystring=getenv('QUERY_STRING');
	foreach ($otherRegistrars as $registrar) {
		$result=file_get_contents("$registrar?$querystring");
	}
}
die;
