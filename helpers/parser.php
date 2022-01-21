<?php
/*
 * parser.php
 *
 * Part of "flexible_helpers_scripts" collection
 * https://github.com/GuduleLapointe/flexible_helper_scripts
 *
 * This script parses data from registered hosts to feed the search database.
 * It must be run regularly by a cron task for the search to work properly.
 */

require_once('include/config.php');
require_once('include/ossearch_db.php');

$now = time();

function GetURL($host, $port, $url)
{
    $url = "http://$host:$port/$url";

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);

    $data = curl_exec($ch);
    if (curl_errno($ch) == 0)
    {
        curl_close($ch);
        return $data;
    }

    curl_close($ch);
    return "";
}

function CheckHost($hostname, $port)
{
    global $SearchDB, $now;

    $xml = GetURL($hostname, $port, "?method=collector");
    if (empty($xml)) $failcounter = "failcounter + 1";
    else $failcounter = "0";

    //Update nextcheck to be 10 minutes from now. The current OS instance
    //won't be checked again until at least this much time has gone by.
    $next = $now + 600;

    $query = $SearchDB->prepare( "UPDATE hostsregister
      SET nextcheck = ?, checked = 1, failcounter = $failcounter
      WHERE host = ? AND port = ?"
    );
    $query->execute( array($next, $hostname, $port) );

    if (!empty($xml)) parse($hostname, $port, $xml);
}

function parse($hostname, $port, $xml)
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

    //Don't try and parse if XML is invalid or we got an HTML 404 error.
    if ($objDOM->loadXML($xml) == False)
        return;

    //
    // Get the region data to update
    //
    $regiondata = $objDOM->getElementsByTagName("regiondata");

    //If returned length is 0, collector method may have returned an error
    if ($regiondata->length == 0)
        return;

    $regiondata = $regiondata->item(0);

    //
    // Update nextcheck so this host entry won't be checked again until after
    // the DataSnapshot module has generated a new set of data to be parsed.
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
        $regioncategory = $region->getAttributeNode("category")->nodeValue;

        //
        // Start reading the Region info
        //
        $info = $region->getElementsByTagName("info")->item(0);

        $regionuuid = $info->getElementsByTagName("uuid")->item(0)->nodeValue;

        $regionname = $info->getElementsByTagName("name")->item(0)->nodeValue;

        $regionhandle = $info->getElementsByTagName("handle")->item(0)->nodeValue;

        $url = $info->getElementsByTagName("url")->item(0)->nodeValue;

        //
        // First, check if we already have a region that is the same
        //
        // $check = $SearchDB->prepare("SELECT * FROM search_regions WHERE regionUUID = ?");
        // $check->execute( array($regionuuid) );
        //
        // if ($check->rowCount() > 0)
        // {
            // $query = $SearchDB->prepare("DELETE FROM search_regions WHERE regionUUID = ?");
            // $query->execute( array($regionuuid) );
            $query = $SearchDB->prepare("DELETE FROM parcels WHERE regionUUID = ?");
            $query->execute( array($regionuuid) );
            $query = $SearchDB->prepare("DELETE FROM allparcels WHERE regionUUID = ?");
            $query->execute( array($regionuuid) );
            $query = $SearchDB->prepare("DELETE FROM parcelsales WHERE regionUUID = ?");
            $query->execute( array($regionuuid) );
            $query = $SearchDB->prepare("DELETE FROM objects WHERE regionuuid = ?");
            $query->execute( array($regionuuid) );
        // }

        $data = $region->getElementsByTagName("data")->item(0);
        $estate = $data->getElementsByTagName("estate")->item(0);
        $estateid = $estate->getElementsByTagName("id")->item(0)->nodeValue;
        $username = $estate->getElementsByTagName("name")->item(0)->nodeValue;
        $useruuid = $estate->getElementsByTagName("uuid")->item(0)->nodeValue;

        //
        // Second, add the new info to the database
        //
        // $query = $SearchDB->prepare("INSERT INTO search_regions VALUES(:r_name, :r_uuid, " .
        //                       ":r_handle, :url, :u_name, :u_uuid)");
        // $query->execute( array("r_name" => $regionname, "r_uuid" => $regionuuid,
        //                         "r_handle" => $regionhandle, "url" => $url,
        //                         "u_name" => $username, "u_uuid" => $useruuid) );

        //
        // Start reading the parcel info
        //
        $parcel = $data->getElementsByTagName("parcel");

        foreach ($parcel as $value)
        {
            $parcelname = $value->getElementsByTagName("name")->item(0)->nodeValue;
            $parceluuid = $value->getElementsByTagName("uuid")->item(0)->nodeValue;
            $infouuid = $value->getElementsByTagName("infouuid")->item(0)->nodeValue;
            $parcellanding = $value->getElementsByTagName("location")->item(0)->nodeValue;
            $parceldescription = $value->getElementsByTagName("description")->item(0)->nodeValue;
            $parcelarea = $value->getElementsByTagName("area")->item(0)->nodeValue;
            $parcelcategory = $value->getAttributeNode("category")->nodeValue;
            $parcelsaleprice = $value->getAttributeNode("salesprice")->nodeValue;
            $dwell = $value->getElementsByTagName("dwell")->item(0)->nodeValue;

            //The image tag will only exist if the parcel has a snapshot image
            $has_pic = 0;
            $image_node = $value->getElementsByTagName("image");
            if ($image_node->length > 0) {
                $image = $image_node->item(0)->nodeValue;
                if ($image != NULL_KEY) $has_pic = 1;
            }

            $owner = $value->getElementsByTagName("owner")->item(0);
            $owneruuid = $owner->getElementsByTagName("uuid")->item(0)->nodeValue;

            // Adding support for groups
            $group = $value->getElementsByTagName("group")->item(0);
            if ($group != "") $groupuuid = $group->getElementsByTagName("groupuuid")->item(0)->nodeValue;
            else $groupuuid = NULL_KEY;

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
            $query->execute( array($parceluuid) );

            //
            // Save
            //
            $query = $SearchDB->prepare("INSERT INTO allparcels VALUES(:r_uuid, :p_name, :o_uuid, :g_uuid, :landing, :p_uuid, :i_uuid, :area)");
            $query->execute( array(
              "r_uuid"  => $regionuuid,
              "p_name"  => $parcelname,
              "o_uuid"  => $owneruuid,
              "g_uuid"  => $groupuuid,
              "landing" => $parcellanding,
              "p_uuid"  => $parceluuid,
              "i_uuid"  => $infouuid,
              "area"    => $parcelarea)
            );

            if ($parceldirectory == "true")
            {
                $query = $SearchDB->prepare("INSERT INTO parcels
                  VALUES(:r_uuid, :p_name, :p_uuid, :landing, :desc, :cat, :build, :script, :public, :dwell, :i_uuid, :r_cat)"
                );
                $query->execute( array(
                  "r_uuid"  => $regionuuid,
                  "p_name"  => $parcelname,
                  "p_uuid"  => $parceluuid,
                  "landing" => $parcellanding,
                  "desc"    => $parceldescription,
                  "cat"     => $parcelcategory,
                  "build"   => $parcelbuild,
                  "script"  => $parcelscript,
                  "public"  => $parcelpublic,
                  "dwell"   => $dwell,
                  "i_uuid"  => $infouuid,
                  "r_cat"   => $regioncategory)
                );

                $query = $SearchDB->prepare("INSERT INTO popularplaces VALUES(:p_uuid, :p_name, :dwell, :i_uuid, :has_pic, :r_cat)");
                $query->execute( array(
                  "p_uuid"  => $parceluuid,
                  "p_name"  => $parcelname,
                  "dwell"   => $dwell,
                  "i_uuid"  => $infouuid,
                  "has_pic" => $has_pic,
                  "r_cat"   => $regioncategory)
                );
            }

            if ($parcelforsale == "true")
            {
                $query = $SearchDB->prepare("INSERT INTO parcelsales VALUES(:r_uuid, :p_name, :p_uuid, :area, :price, :landing, :i_uuid, :dwell, :e_id, :r_cat)");
                $query->execute( array(
                  "r_uuid"  => $regionuuid,
                  "p_name"  => $parcelname,
                  "p_uuid"  => $parceluuid,
                  "area"    => $parcelarea,
                  "price"   => $parcelsaleprice,
                  "landing" => $parcellanding,
                  "i_uuid"  => $infouuid,
                  "dwell"   => $dwell,
                  "e_id"    => $estateid,
                  "r_cat"   => $regioncategory)
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
            $regionuuid = $value->getElementsByTagName("regionuuid")->item(0)->nodeValue;
            $parceluuid = $value->getElementsByTagName("parceluuid")->item(0)->nodeValue;
            $location = $value->getElementsByTagName("location")->item(0)->nodeValue;
            $title = $value->getElementsByTagName("title")->item(0)->nodeValue;
            $description = $value->getElementsByTagName("description")->item(0)->nodeValue;
            $flags = $value->getElementsByTagName("flags")->item(0)->nodeValue;

            $query = $SearchDB->prepare("INSERT INTO objects VALUES(:uuid, :p_uuid, :location, :title, :descr, :r_uuid)");
            $query->execute( array(
              "uuid"     => $uuid,
              "p_uuid"   => $parceluuid,
              "location" => $location,
              "title"    => $title,
              "descr"     => $description,
              "r_uuid"   => $regionuuid)
            );
        }
    }
}

$sql = "SELECT host, port FROM hostsregister WHERE nextcheck<$now AND checked=0 AND failcounter<10 LIMIT 0,100";
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
    CheckHost($jobs[0], $jobs[1]);

$SearchDB = NULL;
die();
