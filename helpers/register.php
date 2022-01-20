<?php
/*
 * register.php
 *
 * Part of "flexible_helpers_scripts" collection
 * Source: https://git.magiiic.com/opensimulator/flexible_helper_scripts
 *
 * This file contains the registration of a simulator to the database and checks
 * if the simulator is new in the database or a reconnected one
 *
 * If the simulator is old, check if the nextcheck date > registration When the
 * date is older, make a request to the Parser to grab new data
 */


require("include/config.php");

$DB_HOST=OPENSIM_DB_HOST;
$DB_NAME=OPENSIM_DB_NAME;
$DB_USER=OPENSIM_DB_USER;
$DB_PASSWORD=OPENSIM_DB_PASS;

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
  $db = new PDO('mysql:host=' . OPENSIM_DB_HOST . ';dbname=' . OPENSIM_DB_NAME, OPENSIM_DB_USER, OPENSIM_DB_PASS);
  $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
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
  $query = $db->prepare("SELECT register FROM hostsregister WHERE host = :host AND port = :port");
  $query->execute( array( ':host' => $host, ':port' => $port ) );

  // Get the request time as a timestamp for later
  $timestamp = $_SERVER['REQUEST_TIME'];

  // If a database row was returned check the nextcheck date
  if ($query->rowCount() > 0)
  {
    $query = $db->prepare("UPDATE hostsregister SET register = :timestamp, nextcheck = 0, checked = 0, failcounter = 0 WHERE host = :host AND port = :port");
  }
  else
  {
    // The SELECT did not return a result. Insert a new record.
    $query = $db->prepare("INSERT INTO hostsregister VALUES (:host, :port, :timestamp, 0, 0, 0)");
    // $query->execute( array($host, $port, $timestamp) );
  }
  $query->execute( array( ':host' => $host, ':port' => $port, ':timestamp' => $timestamp ) );
  break;

  case 'offline':
  $query = $db->prepare("DELETE FROM hostsregister WHERE host = :host AND port = :port");
  $query->execute( array( ':host' => $host, ':port' => $port ) );
  break;

  default:
  error_log(__FILE__ . " request not understood " . getenv('QUERY_STRING') . " raw data " . $HTTP_RAW_POST_DATA);
}

$db = NULL;

if (is_array($otherRegistrars) && $hostname != "" && $port != "" && $service != "")
{
	$querystring=getenv('QUERY_STRING');
	foreach ($otherRegistrars as $registrar) {
		$result=file_get_contents("$registrar?$querystring");
	}
}
die;
