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

require("config/config.php");

$DB_HOST=OPENSIM_DB_HOST;
$DB_NAME=OPENSIM_DB_NAME;
$DB_USER=OPENSIM_DB_USER;
$DB_PASSWORD=OPENSIM_DB_PASS;

$host = $_GET['host'];
$port = $_GET['port'];
$service = $_GET['service'];

if ($host == "" || $port == "")
{
  header("HTTP/1.0 404 Bad Request");
  echo "Missing region host and/or port\n";
  exit;
}

// Attempt to connect to the database
try {
  $db = new PDO("mysql:host=$DB_HOST;dbname=$DB_NAME", $DB_USER, $DB_PASSWORD);
  $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
}
catch(PDOException $e)
{
  echo "Error connecting to database\n";
  file_put_contents('PDOErrors.txt', $e->getMessage() . "\n-----\n", FILE_APPEND);
  exit;
}

if ($service == "online")
{
  // Check if there is already a database row for this host
  $query = $db->prepare("SELECT register FROM hostsregister WHERE host = ? AND port = ?");
  $query->execute( array($host, $port) );

  // Get the request time as a timestamp for later
  $timestamp = $_SERVER['REQUEST_TIME'];

  // If a database row was returned check the nextcheck date
  if ($query->rowCount() > 0)
  {
    $query = $db->prepare("UPDATE hostsregister SET " .
    "register = ?, " .
    "nextcheck = 0, checked = 0, failcounter = 0 " .
    "WHERE host = ? AND port = ?");
    $query->execute( array($timestamp, $host, $port) );
  }
  else
  {
    // The SELECT did not return a result. Insert a new record.
    $query = $db->prepare("INSERT INTO hostsregister VALUES (?, ?, ?, 0, 0, 0)");
    $query->execute( array($host, $port, $timestamp) );
  }
}

if ($service == "offline")
{
  $query = $db->prepare("DELETE FROM hostsregister WHERE host = ? AND port = ?");
  $query->execute( array($host, $port) );
}

$db = NULL;

if (is_array($otherRegistrars) && $hostname != "" && $port != "" && $service != "")
{
	$querystring=getenv('QUERY_STRING');
	foreach ($otherRegistrars as $registrar) {
		$result=file_get_contents("$registrar?$querystring");
	}
}
