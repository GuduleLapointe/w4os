<?php
/*
* parser.php
*
* Parse data from registered hosts to feed the search database.
* If in a standalone helpers implementation, it must be run on a regular basis
* (with a cron job) for the search to work.
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

require_once('include/wp-config.php');
require_once('include/search.php');
dontWait();

$now = time();

function hostCheck($hostname, $port)
{
  global $SearchDB, $now;

  $failcounter = 0;
  $interval = 600; // Wait at least 10 minutes before scanning the same host

  $xml = file_get_contents("http://$hostname:$port/?method=collector");
  if (empty($xml)) {
    error_log("$hostname:$port unreachable");
    $fails = $SearchDB->prepareAndExecute("SELECT failcounter FROM hostsregister
      WHERE host = :host AND port = :port",
      array(
        'host' => $hostname,
        'port' => $port,
      )
    );
    $failcounter = $fails->fetch()[0] + 1;
    $interval = $interval * pow(2, $failcounter); // extend scanning interval for inactive hosts
    if($failcounter > 10) {
      hostUnregister($hostname, $port);
      return;
    }
  }
  $nextcheck = time() + $interval;

  // Update nextcheck time. The next check interval is multiplied by the number
  // of fails to minimize useless requests
  $query = $SearchDB->prepareAndExecute( "UPDATE hostsregister
    SET failcounter = :failcounter, nextcheck = :nextcheck, checked = 1
    WHERE host = :host AND port = :port",
    array(
      'failcounter' => $failcounter,
      'nextcheck' => 0,
      'host' => $hostname,
      'port' => $port,
    )
  );

  if (!empty($xml)) hostScan($hostname, $port, $xml);
}

function hostScan($hostname, $port, $xml)
{
  global $SearchDB, $now;
  ///////////////////////////////////////////////////////////////////////
  //
  // Search engine sim scanner
  //

  //
  // Load XML doc from URL
  //
  $objDOM = new DOMDocument();
  $objDOM->resolveExternals = false;

  //Don't try and scan if XML is invalid or we got an HTML 404 error.
  if ($objDOM->loadXML($xml) == False) return;

  //
  // Get the region data to update
  //
  $regiondata = $objDOM->getElementsByTagName("regiondata");

  //If returned length is 0, collector method may have returned an error
  if ($regiondata->length == 0) return;

  $regiondata = $regiondata->item(0);

  //
  // Update nextcheck so this host entry won't be checked again until after
  // the DataSnapshot module has generated a new set of data to be scanned.
  //
  $expire = $regiondata->getElementsByTagName("expire")->item(0)->nodeValue;
  $next = $now + $expire;

  $query = $SearchDB->prepare("UPDATE hostsregister SET nextcheck = ? WHERE host = ? AND port = ?");
  $query->execute( array($next, $hostname, $port) );

  //
  // Get the region data to be saved in the database
  //
  $regionlist = $regiondata->getElementsByTagName("region");

  foreach ($regionlist as $region)
  {
    $mature = $region->getAttributeNode("category")->nodeValue;

    //
    // Start reading the Region info
    //
    $info = $region->getElementsByTagName("info")->item(0);
    $regionUUID = $info->getElementsByTagName("uuid")->item(0)->nodeValue;
    $regionname = $info->getElementsByTagName("name")->item(0)->nodeValue;
    $regionhandle = $info->getElementsByTagName("handle")->item(0)->nodeValue;
    $url = $info->getElementsByTagName("url")->item(0)->nodeValue;

    /*
     * First, check if we already have a region that is the same
     *
     * "region" table conflicts with OpenSim region table. This table could be
     * renamed, but as for now it's not used anywhere else, so we ignore it.
     */

    // $check = $SearchDB->prepare("SELECT * FROM search_regions WHERE regionUUID = ?");
    // $check->execute( array($regionUUID) );
    //
    // if ($check->rowCount() > 0)
    // {
    // $query = $SearchDB->prepare("DELETE FROM search_regions WHERE regionUUID = ?");
    // $query->execute( array($regionUUID) );
    $query = $SearchDB->prepare("DELETE FROM parcels WHERE regionUUID = ?");
    $query->execute( array($regionUUID) );
    $query = $SearchDB->prepare("DELETE FROM allparcels WHERE regionUUID = ?");
    $query->execute( array($regionUUID) );
    $query = $SearchDB->prepare("DELETE FROM parcelsales WHERE regionUUID = ?");
    $query->execute( array($regionUUID) );
    $query = $SearchDB->prepare("DELETE FROM objects WHERE regionuuid = ?");
    $query->execute( array($regionUUID) );
    // }

    $data = $region->getElementsByTagName("data")->item(0);
    $estate = $data->getElementsByTagName("estate")->item(0);
    $parentestate = $estate->getElementsByTagName("id")->item(0)->nodeValue;
    $username = $estate->getElementsByTagName("name")->item(0)->nodeValue;
    $useruuid = $estate->getElementsByTagName("uuid")->item(0)->nodeValue;

    /*
     * Second, add the new info to the database
     *
     * (same as above, regions table conflict, ignore it)
     */
    // $query = $SearchDB->prepare("INSERT INTO search_regions VALUES(:r_name, :regionUUID, " .
    //                       ":r_handle, :url, :u_name, :u_uuid)");
    // $query->execute( array("r_name" => $regionname, 'regionUUID' => $regionUUID,
    //                         "r_handle" => $regionhandle, 'url' => $url,
    //                         "u_name" => $username, "u_uuid" => $useruuid) );

    /*
     * Read parcel info
     */

    $parcel = $data->getElementsByTagName("parcel");
    foreach ($parcel as $value)
    {
      $parcelname = $value->getElementsByTagName("name")->item(0)->nodeValue;
      $parcelUUID = $value->getElementsByTagName("uuid")->item(0)->nodeValue;
      $infoUUID = $value->getElementsByTagName("infouuid")->item(0)->nodeValue;
      $landingpoint = $value->getElementsByTagName("location")->item(0)->nodeValue;
      $parceldescription = $value->getElementsByTagName("description")->item(0)->nodeValue;
      $parcelarea = $value->getElementsByTagName("area")->item(0)->nodeValue;
      $searchcategory = $value->getAttributeNode("category")->nodeValue;
      $saleprice = $value->getAttributeNode("salesprice")->nodeValue;
      $dwell = $value->getElementsByTagName("dwell")->item(0)->nodeValue;

      //The image tag will only exist if the parcel has a snapshot image
      $has_picture = 0;
      $image_node = $value->getElementsByTagName("image");
      if ($image_node->length > 0) {
        $image = $image_node->item(0)->nodeValue;
        if ($image != NULL_KEY) $has_picture = 1;
      }

      $owner = $value->getElementsByTagName("owner")->item(0);
      $ownerUUID = $owner->getElementsByTagName("uuid")->item(0)->nodeValue;

      // Adding support for groups
      $group = $value->getElementsByTagName("group")->item(0);
      if ($group != "") $groupUUID = $group->getElementsByTagName("groupUUID")->item(0)->nodeValue;
      else $groupUUID = NULL_KEY;

      //
      // Check bits on Public, Build, Script
      //
      $parcelforsale = $value->getAttributeNode("forsale")->nodeValue;
      $parceldirectory = $value->getAttributeNode("showinsearch")->nodeValue;
      $parcelbuild = $value->getAttributeNode("build")->nodeValue;
      $parcelscript = $value->getAttributeNode("scripts")->nodeValue;
      $parcelpublic = $value->getAttributeNode("public")->nodeValue;

      //Prepare for the insert of data in to the popularplaces table. This gets
      //rid of any obsolete data for parcels no longer set to show in search.
      $query = $SearchDB->prepare("DELETE FROM popularplaces WHERE parcelUUID = ?");
      $query->execute( array($parcelUUID) );

      /*
       * Save
       *
       * Sometimes, the parcel is inserted more than once, which causes a fatal
       * issue. Delete it first (quick workaround, should be investigated).
       */

      $query = $SearchDB->prepare("DELETE FROM allparcels WHERE parcelUUID = :parcelUUID");
      $query->execute( array( 'parcelUUID' => $parcelUUID ));
      $query = $SearchDB->prepare("INSERT INTO allparcels VALUES(:regionUUID, :parcelname, :ownerUUID, :groupUUID, :landingpoint, :parcelUUID, :infoUUID, :parcelarea)");
      $query->execute(array(
        'regionUUID' => $regionUUID,
        'parcelname' => $parcelname,
        'ownerUUID' => $ownerUUID,
        'groupUUID' => $groupUUID,
        'landingpoint' => $landingpoint,
        'parcelUUID' => $parcelUUID,
        'infoUUID' => $infoUUID,
        'parcelarea' => $parcelarea,
      ));

      if ($parceldirectory == "true")
      {
        $query = $SearchDB->prepare( "INSERT INTO parcels
          VALUES(:regionUUID, :parcelname, :parcelUUID, :landingpoint, :description, :searchcategory, :build, :script, :public, :dwell, :infoUUID, :mature)"
        );
        $query->execute(array(
          'regionUUID' => $regionUUID,
          'parcelname' => $parcelname,
          'parcelUUID' => $parcelUUID,
          'landingpoint' => $landingpoint,
          'description' => $parceldescription,
          'searchcategory' => $searchcategory,
          'build' => $parcelbuild,
          'script' => $parcelscript,
          'public' => $parcelpublic,
          'dwell' => $dwell,
          'infoUUID' => $infoUUID,
          "mature"   => $mature,
        ));

        $query = $SearchDB->prepare("INSERT INTO popularplaces VALUES(:parcelUUID, :parcelname, :dwell, :infoUUID, :has_picture, :mature)");
        $query->execute( array(
          'parcelUUID' => $parcelUUID,
          'parcelname' => $parcelname,
          'dwell' => $dwell,
          'infoUUID' => $infoUUID,
          "has_picture" => $has_picture,
          "mature"   => $mature,
        ));
      }

      if ($parcelforsale == "true")
      {
        $query = $SearchDB->prepare("INSERT INTO parcelsales VALUES(:regionUUID, :parcelname, :parcelUUID, :parcelarea, :saleprice, :landingpoint, :infoUUID, :dwell, :parentestate, :mature)");
        $query->execute( array(
        'regionUUID' => $regionUUID,
        'parcelname' => $parcelname,
        'parcelUUID' => $parcelUUID,
        'parcelarea' => $parcelarea,
        'saleprice' => $saleprice,
        'landingpoint' => $landingpoint,
        'infoUUID' => $infoUUID,
        'dwell' => $dwell,
        'parentestate' => $parentestate,
        'mature' => $mature)
        );
      }
    }

    //
    // Handle objects
    //
    $objects = $data->getElementsByTagName("object");

    foreach ($objects as $value)
    {
      $uuid = $value->getElementsByTagName("uuid")->item(0)->nodeValue;
      $regionUUID = $value->getElementsByTagName("regionuuid")->item(0)->nodeValue;
      $parcelUUID = $value->getElementsByTagName("parcelUUID")->item(0)->nodeValue;
      $location = $value->getElementsByTagName("location")->item(0)->nodeValue;
      $title = $value->getElementsByTagName("title")->item(0)->nodeValue;
      $description = $value->getElementsByTagName("description")->item(0)->nodeValue;
      $flags = $value->getElementsByTagName("flags")->item(0)->nodeValue;

      $query = $SearchDB->prepare("INSERT INTO objects VALUES(:uuid, :parcelUUID, :location, :title, :descr, :regionUUID)");
      $query->execute( array(
      'uuid' => $uuid,
      'parcelUUID' => $parcelUUID,
      'location' => $location,
      'title' => $title,
      'descr' => $description,
      'regionUUID' => $regionUUID)
      );
    }
  }
}

// $sql = "SELECT host, port FROM hostsregister WHERE nextcheck<$now AND checked=0 AND failcounter<10 LIMIT 0,100";
$sql = "SELECT host, port FROM hostsregister WHERE nextcheck<$now AND checked=0 LIMIT 0,100";
$jobsearch = $SearchDB->query($sql);

//
// If the sql query returns no rows, all entries in the hostsregister
// table have been checked. Reset the checked flag and re-run the
// query to select the next set of hosts to be checked.
//

if ($jobsearch->rowCount() == 0)
{
  $jobsearch = $SearchDB->query("UPDATE hostsregister SET checked = 0");
  $jobsearch = $SearchDB->query($sql);
}

while ($jobs = $jobsearch->fetch(PDO::FETCH_NUM))
hostCheck($jobs[0], $jobs[1]);

die();
