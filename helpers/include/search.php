<?php
/*
 * search.php
 *
 * Provides database and functions required by OpenSimSearch scripts
 *
 * Part of "flexible_helpers_scripts" collection
 *   https://github.com/GuduleLapointe/flexible_helper_scripts
 *   by Gudule Lapointe <gudule@speculoos.world>
 *
 * Requires OpenSimulator Search module
 *   [OpenSimSearch](https://github.com/kcozens/OpenSimSearch)
 *
 * Includes portions of code from
 *   [OpenSimSearch](https://github.com/kcozens/OpenSimSearch)
 */

$SearchDB = new OSPDO('mysql:host=' . SEARCH_DB_HOST . ';dbname=' . SEARCH_DB_NAME, SEARCH_DB_USER, SEARCH_DB_PASS);

function OSSearchCreateTables($db) {
  $query = $db->prepare("CREATE TABLE IF NOT EXISTS `allparcels` (
    `regionUUID` char(36) NOT NULL,
    `parcelname` varchar(255) NOT NULL,
    `ownerUUID` char(36) NOT NULL default '00000000-0000-0000-0000-000000000000',
    `groupUUID` char(36) NOT NULL default '00000000-0000-0000-0000-000000000000',
    `landingpoint` varchar(255) NOT NULL,
    `parcelUUID` char(36) NOT NULL default '00000000-0000-0000-0000-000000000000',
    `infoUUID` char(36) NOT NULL default '00000000-0000-0000-0000-000000000000',
    `parcelarea` int(11) NOT NULL,
    PRIMARY KEY  (`parcelUUID`),
    KEY `regionUUID` (`regionUUID`)
  ) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

  CREATE TABLE IF NOT EXISTS `classifieds` (
    `classifieduuid` char(36) NOT NULL,
    `creatoruuid` char(36) NOT NULL,
    `creationdate` int(20) NOT NULL,
    `expirationdate` int(20) NOT NULL,
    `category` varchar(20) NOT NULL,
    `name` varchar(255) NOT NULL,
    `description` text NOT NULL,
    `parceluuid` char(36) NOT NULL,
    `parentestate` int(11) NOT NULL,
    `snapshotuuid` char(36) NOT NULL,
    `simname` varchar(255) NOT NULL,
    `posglobal` varchar(255) NOT NULL,
    `parcelname` varchar(255) NOT NULL,
    `classifiedflags` int(8) NOT NULL,
    `priceforlisting` int(5) NOT NULL,
    PRIMARY KEY  (`classifieduuid`)
  ) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

  CREATE TABLE IF NOT EXISTS `events` (
    `owneruuid` char(36) NOT NULL,
    `name` varchar(255) NOT NULL,
    `eventid` int(11) unsigned NOT NULL AUTO_INCREMENT,
    `creatoruuid` char(36) NOT NULL,
    `category` int(2) NOT NULL,
    `description` text NOT NULL,
    `dateUTC` int(10) NOT NULL,
    `duration` int(10) NOT NULL,
    `covercharge` tinyint(1) NOT NULL,
    `coveramount` int(10) NOT NULL,
    `simname` varchar(255) NOT NULL,
    `parcelUUID` char(36) NOT NULL,
    `globalPos` varchar(255) NOT NULL,
    `eventflags` int(1) NOT NULL,
    PRIMARY KEY (`eventid`)
  ) ENGINE=InnoDB DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;

  CREATE TABLE IF NOT EXISTS `hostsregister` (
    `host` varchar(255) NOT NULL,
    `port` int(5) NOT NULL,
    `register` int(10) NOT NULL,
    `nextcheck` int(10) NOT NULL,
    `checked` tinyint(1) NOT NULL,
    `failcounter` int(10) NOT NULL,
    PRIMARY KEY (`host`,`port`)
  ) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

  CREATE TABLE IF NOT EXISTS `objects` (
    `objectuuid` char(36) NOT NULL,
    `parceluuid` char(36) NOT NULL,
    `location` varchar(255) NOT NULL,
    `name` varchar(255) NOT NULL,
    `description` varchar(255) NOT NULL,
    `regionuuid` char(36) NOT NULL default '',
    PRIMARY KEY  (`objectuuid`,`parceluuid`)
  ) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

  CREATE TABLE IF NOT EXISTS `parcels` (
    `regionUUID` char(36) NOT NULL,
    `parcelname` varchar(255) NOT NULL,
    `parcelUUID` char(36) NOT NULL,
    `landingpoint` varchar(255) NOT NULL,
    `description` varchar(255) NOT NULL,
    `searchcategory` varchar(50) NOT NULL,
    `build` enum('true','false') NOT NULL,
    `script` enum('true','false') NOT NULL,
    `public` enum('true','false') NOT NULL,
    `dwell` float NOT NULL default '0',
    `infouuid` varchar(36) NOT NULL default '',
    `mature` varchar(10) NOT NULL default 'PG',
    PRIMARY KEY  (`regionUUID`,`parcelUUID`),
    KEY `name` (`parcelname`),
    KEY `description` (`description`),
    KEY `searchcategory` (`searchcategory`),
    KEY `dwell` (`dwell`)
  ) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

  CREATE TABLE IF NOT EXISTS `parcelsales` (
    `regionUUID` char(36) NOT NULL,
    `parcelname` varchar(255) NOT NULL,
    `parcelUUID` char(36) NOT NULL,
    `area` int(6) NOT NULL,
    `saleprice` int(11) NOT NULL,
    `landingpoint` varchar(255) NOT NULL,
    `infoUUID` char(36) NOT NULL default '00000000-0000-0000-0000-000000000000',
    `dwell` int(11) NOT NULL,
    `parentestate` int(11) NOT NULL default '1',
    `mature` varchar(10) NOT NULL default 'PG',
    PRIMARY KEY  (`regionUUID`,`parcelUUID`)
  ) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

  CREATE TABLE IF NOT EXISTS `popularplaces` (
    `parcelUUID` char(36) NOT NULL,
    `name` varchar(255) NOT NULL,
    `dwell` float NOT NULL,
    `infoUUID` char(36) NOT NULL,
    `has_picture` tinyint(1) NOT NULL,
    `mature` varchar(10) COLLATE utf8_unicode_ci NOT NULL,
    PRIMARY KEY  (`parcelUUID`)
  ) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
  ");

  // CREATE TABLE IF NOT EXISTS `regions` (
  //   `regionname` varchar(255) NOT NULL,
  //   `regionUUID` char(36) NOT NULL,
  //   `regionhandle` varchar(255) NOT NULL,
  //   `url` varchar(255) NOT NULL,
  //   `owner` varchar(255) NOT NULL,
  //   `owneruuid` char(36) NOT NULL,
  //   PRIMARY KEY  (`regionUUID`)
  // ) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

  $result = $query->execute();
}

if( ! tableExists($SearchDB, [ 'parcels', 'parcelsales', 'allparcels', 'objects', 'popularplaces', 'events', 'classifieds', 'hostsregister' ] )) {
  error_log("Creating missing OpenSimSearch tables in " . SEARCH_DB_NAME);
  OSSearchCreateTables($SearchDB);
}


function join_terms($glue, $terms, $deprecated = true) {
  if(empty($terms)) return "";
  return "(" . join($glue, $terms) . ")";
}

function buildMatureConditions($flags)
{
    $terms = array();
    if ($flags & pow(2, 24)) $terms[] = "mature = 'PG'";
    if ($flags & pow(2, 25)) $terms[] = "mature = 'Mature'";
    if ($flags & pow(2, 26)) $terms[] = "mature = 'Adult'";

    return join_terms(" OR ", $terms);
}

function hostUnregister($hostname, $port) {
  global $SearchDB;

  $SearchDB->prepareAndExecute("DELETE FROM hostsregister
    WHERE host = :host AND port = :port",
    array(
      'host' => $hostname,
      'port' => $port,
    )
  );

  $formatCheck = $SearchDB->query("SHOW COLUMNS FROM regions LIKE 'uuid'");
  if($formatCheck->rowCount() == 0) $saveregions = true;
  if($saveregions) {
    $query = $SearchDB->prepareAndExecute("SELECT regionUUID FROM regions WHERE url = ?",
    [ "http://$hostname:$port/"] );
    // TODO: make similar request on robust database if $saveregions == false or nothing found
    if($query) {
      $regions=$query->fetchAll();
      foreach($regions as $region) {
        $regionUUID = $region[0];
        // TODO: query parcels and delete related popularplaces
        // $SearchDB->prepareAndExecute("DELETE FROM popularplaces WHERE parcelUUID = ?", [$parcelUUID] );
        $SearchDB->prepareAndExecute("DELETE FROM parcels WHERE regionUUID = ?", [$regionUUID] );
        $SearchDB->prepareAndExecute("DELETE FROM allparcels WHERE regionUUID = ?", [$regionUUID] );
        $SearchDB->prepareAndExecute("DELETE FROM parcelsales WHERE regionUUID = ?", [$regionUUID] );
        $SearchDB->prepareAndExecute("DELETE FROM objects WHERE regionuuid = ?", [$regionUUID] );
        if($saveregions) $SearchDB->prepareAndExecute("DELETE FROM regions WHERE regionUUID = ?", [$regionUUID] );
      }
    }
  }
}
