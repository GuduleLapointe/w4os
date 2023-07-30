<?php
/**
 * search.php
 *
 * Provides database and functions required by OpenSimSearch scripts
 *
 * Requires OpenSimulator Search module
 *   [OpenSimSearch](https://github.com/kcozens/OpenSimSearch)
 *
 * @package		magicoli/opensim-helpers
 * @author 		Gudule Lapointe <gudule@speculoos.world>
 * @link 			https://github.com/magicoli/opensim-helpers
 * @license		AGPLv3
 *
 * Includes portions of code from
 *   [OpenSimSearch](https://github.com/kcozens/OpenSimSearch)
 */

function ossearch_db_tables( $db ) {
	if ( ! $db->connected ) {
		return false;
	}

	$query = $db->prepare(
		"CREATE TABLE IF NOT EXISTS `allparcels` (
    `regionUUID` char(36) NOT NULL,
    `parcelname` varchar(255) NOT NULL,
    `ownerUUID` char(36) NOT NULL default '00000000-0000-0000-0000-000000000000',
    `groupUUID` char(36) NOT NULL default '00000000-0000-0000-0000-000000000000',
    `landingpoint` varchar(255) NOT NULL,
    `parcelUUID` char(36) NOT NULL default '00000000-0000-0000-0000-000000000000',
    `infoUUID` char(36) NOT NULL default '00000000-0000-0000-0000-000000000000',
    `parcelarea` int(11) NOT NULL,
    `gatekeeperURL` varchar(255),
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
    `gatekeeperURL` varchar(255),
    PRIMARY KEY (`eventid`)
  ) ENGINE=InnoDB DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;

  CREATE TABLE IF NOT EXISTS `hostsregister` (
    `host` varchar(255) NOT NULL,
    `port` int(5) NOT NULL,
    `register` int(10) NOT NULL,
    `nextcheck` int(10) NOT NULL,
    `checked` tinyint(1) NOT NULL,
    `failcounter` int(10) NOT NULL,
    `gatekeeperURL` varchar(255),
    PRIMARY KEY (`host`,`port`)
  ) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

  CREATE TABLE IF NOT EXISTS `objects` (
    `objectuuid` char(36) NOT NULL,
    `parceluuid` char(36) NOT NULL,
    `location` varchar(255) NOT NULL,
    `name` varchar(255) NOT NULL,
    `description` varchar(255) NOT NULL,
    `regionuuid` char(36) NOT NULL default '',
    `gatekeeperURL` varchar(255),
    PRIMARY KEY  (`objectuuid`,`parceluuid`)
  ) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

  CREATE TABLE IF NOT EXISTS `parcels` (
    `parcelUUID` char(36) NOT NULL,
    `regionUUID` char(36) NOT NULL,
    `parcelname` varchar(255) NOT NULL,
    `landingpoint` varchar(255) NOT NULL,
    `description` varchar(255) NOT NULL,
    `searchcategory` varchar(50) NOT NULL,
    `build` enum('true','false') NOT NULL,
    `script` enum('true','false') NOT NULL,
    `public` enum('true','false') NOT NULL,
    `dwell` float NOT NULL default '0',
    `infouuid` varchar(36) NOT NULL default '',
    `mature` varchar(10) NOT NULL default 'PG',
    `gatekeeperURL` varchar(255),
    `imageUUID` char(36),
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
    `gatekeeperURL` varchar(255),
    PRIMARY KEY  (`regionUUID`,`parcelUUID`)
  ) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

  CREATE TABLE IF NOT EXISTS `popularplaces` (
    `parcelUUID` char(36) NOT NULL,
    `name` varchar(255) NOT NULL,
    `dwell` float NOT NULL,
    `infoUUID` char(36) NOT NULL,
    `has_picture` tinyint(1) NOT NULL,
    `mature` varchar(10) COLLATE utf8_unicode_ci NOT NULL,
    `gatekeeperURL` varchar(255),
    PRIMARY KEY  (`parcelUUID`)
  ) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

  CREATE TABLE IF NOT EXISTS `" . SEARCH_REGION_TABLE . '` (
    `regionname` varchar(255) NOT NULL,
    `regionUUID` char(36) NOT NULL,
    `regionhandle` varchar(255) NOT NULL,
    `url` varchar(255) NOT NULL,
    `owner` varchar(255) NOT NULL,
    `owneruuid` char(36) NOT NULL,
    `gatekeeperURL` varchar(255),
    PRIMARY KEY  (`regionUUID`)
  ) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
  '
	);

	$result = $query->execute();
}

function ossearch_db_update_1() {
	global $SearchDB;
	if ( ! $SearchDB ) {
		return false;
	}

	$tables = array( 'allparcels', 'classifieds', 'events', 'hostsregister', 'objects', 'parcels', 'parcelsales', 'popularplaces', 'regions' );
	foreach ( $tables as $table ) {
		if ( ! count( $SearchDB->query( "SHOW COLUMNS FROM `$table` LIKE 'gatekeeperURL'" )->fetchAll() ) ) {
			$SearchDB->query( "ALTER TABLE $table ADD gatekeeperURL varchar(255)" );
		}
	}
}

function ossearch_db_update_2() {
	global $SearchDB;
	if ( ! $SearchDB ) {
		return false;
	}

	if ( ! count( $SearchDB->query( "SHOW COLUMNS FROM `parcels` LIKE 'imageUUID'" )->fetchAll() ) ) {
		$SearchDB->query( 'ALTER TABLE parcels ADD imageUUID char(36)' );
	}
}

function ossearch_terms_join( $glue, $terms, $deprecated = true ) {
	if ( empty( $terms ) ) {
		return '';
	}
	return '(' . join( $glue, $terms ) . ')';
}

function ossearch_terms_build_rating( $flags, $table = '' ) {
	if ( ! empty( $table ) ) {
		$table = "$table.";
	}
	$terms = array();
	if ( $flags & pow( 2, 24 ) ) {
		$terms[] = "${table}mature = 'PG'";
	}
	if ( $flags & pow( 2, 25 ) ) {
		$terms[] = "${table}mature = 'Mature'";
	}
	if ( $flags & pow( 2, 26 ) ) {
		$terms[] = "${table}mature = 'Adult'";
	}
	return ossearch_terms_join( ' OR ', $terms );
}

function ossearch_hostUnregister( $hostname, $port ) {
	global $SearchDB;

	$SearchDB->prepareAndExecute(
		'DELETE FROM hostsregister
    WHERE host = :host AND port = :port',
		array(
			'host' => $hostname,
			'port' => $port,
		)
	);

	$query = $SearchDB->prepareAndExecute( 'SELECT regionUUID FROM ' . SEARCH_REGION_TABLE . ' WHERE url = ?', array( "http://$hostname:$port/" ) );
	if ( $query ) {
		$regions = $query->fetchAll();
		foreach ( $regions as $region ) {
			$regionUUID = $region[0];
			$SearchDB->prepareAndExecute( 'DELETE pop FROM popularplaces AS pop INNER JOIN parcels AS par ON pop.parcelUUID = par.parcelUUID WHERE regionUUID = ?', array( $regionUUID ) );
			$SearchDB->prepareAndExecute( 'DELETE FROM parcels WHERE regionUUID = ?', array( $regionUUID ) );
			$SearchDB->prepareAndExecute( 'DELETE FROM allparcels WHERE regionUUID = ?', array( $regionUUID ) );
			$SearchDB->prepareAndExecute( 'DELETE FROM parcelsales WHERE regionUUID = ?', array( $regionUUID ) );
			$SearchDB->prepareAndExecute( 'DELETE FROM objects WHERE regionuuid = ?', array( $regionUUID ) );
			$SearchDB->prepareAndExecute( 'DELETE FROM ' . SEARCH_REGION_TABLE . ' WHERE regionUUID = ?', array( $regionUUID ) );
		}
	}
}

try {
	$SearchDB = new OSPDO( 'mysql:host=' . SEARCH_DB_HOST . ';dbname=' . SEARCH_DB_NAME, SEARCH_DB_USER, SEARCH_DB_PASS );
} catch ( PDOException $e ) {
	// error_log("could not connect to " . SEARCH_DB_HOST);
	error_log( $e );
	$SearchDB = null;
}

if ( $SearchDB && $SearchDB->connected ) {
	// <tl;tr> To avoid data loss, fatal errors or conflicts, we use regionsregister
	// table instead of regions if it seems to be a robust database. For obscure and
	// historical reasons, search regions table has the same name as robust regions
	// table, although it has a different structure and a different purpose. It
	// could be renamed but some developers are reluctant to do it, so we keep the
	// original name for backward compatibility when in a separate database.
	if ( tableExists( $SearchDB, array( 'regions' ) ) ) {
		$formatCheck   = $SearchDB->query( "SHOW COLUMNS FROM regions LIKE 'uuid'" );
		$regions_table = ( $formatCheck->rowCount() == 0 ) ? 'regions' : 'regionsregister';
	} else {
		$regions_table = 'regions';
	}
	define( 'SEARCH_REGION_TABLE', $regions_table );

	if ( ! tableExists( $SearchDB, array( SEARCH_REGION_TABLE, 'parcels', 'parcelsales', 'allparcels', 'objects', 'popularplaces', 'events', 'classifieds', 'hostsregister' ) ) ) {
		error_log( 'Creating missing OpenSimSearch tables in ' . SEARCH_DB_NAME );
		ossearch_db_tables( $SearchDB );
	}

	if ( ! count( $SearchDB->query( "SHOW COLUMNS FROM `parcels` LIKE 'gatekeeperURL'" )->fetchAll() ) ) {
		ossearch_db_update_1();
	}
	if ( ! count( $SearchDB->query( "SHOW COLUMNS FROM `parcels` LIKE 'imageUUID'" )->fetchAll() ) ) {
		ossearch_db_update_2();
	}
}
