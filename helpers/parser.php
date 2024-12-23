<?php
/**
 * parser.php
 *
 * Parse data from registered hosts to feed the search database.
 * If in a standalone helpers implementation, it must be run on a regular basis
 * (with a cron job) for the search to work.
 *
 * Requires OpenSimulator Search module
 *   [OpenSimSearch](https://github.com/kcozens/OpenSimSearch)
 * Events are fetched from 2do HYPEvents or any other HYPEvents implementation
 *   [2do HYPEvents](https://2do.directory)
 *
 * @package     magicoli/opensim-helpers
 * @author      Gudule Lapointe <gudule@speculoos.world>
 * @link            https://github.com/magicoli/opensim-helpers
 * @license     AGPLv3
 *
 * Includes portions of code from
 *   [OpenSimSearch](https://github.com/kcozens/OpenSimSearch)
 **/

require_once 'includes/config.php';
require_once 'includes/search.php';
dontWait();

$now = time();

function hostCheck( $hostname, $port ) {
	global $SearchDB, $now;

	$failcounter = 0;
	$interval    = 600; // Wait at least 10 minutes before scanning the same host

	@$xml = file_get_contents( "http://$hostname:$port/?method=collector" );
	if ( empty( $xml ) ) {
		// error_log( "$hostname:$port unreachable" );
		$fails       = $SearchDB->prepareAndExecute(
			'SELECT failcounter FROM hostsregister
      WHERE host = :host AND port = :port',
			array(
				'host' => $hostname,
				'port' => $port,
			)
		);
		$failcounter = $fails->fetch()[0] + 1;
		$interval    = $interval * pow( 2, $failcounter ); // extend scanning interval for inactive hosts
		if ( $failcounter > 10 ) {
			ossearch_hostUnregister( $hostname, $port );
			return;
		}
	}
	$nextcheck = time() + $interval;

	// Update nextcheck time. The next check interval is multiplied by the number
	// of fails to minimize useless requests
	$query = $SearchDB->prepareAndExecute(
		'UPDATE hostsregister
    SET failcounter = :failcounter, nextcheck = :nextcheck, checked = 1
    WHERE host = :host AND port = :port',
		array(
			'failcounter' => $failcounter,
			'nextcheck'   => 0,
			'host'        => $hostname,
			'port'        => $port,
		)
	);

	if ( ! empty( $xml ) ) {
		hostScan( $hostname, $port, $xml );
	}
}

function hostScan( $hostname, $port, $xmlcontent ) {
	global $SearchDB, $now;
	//
	//
	// Search engine sim scanner
	//

	//
	// Load XML doc from URL
	//
	$objDOM                   = new DOMDocument();
	$objDOM->resolveExternals = false;

	// Don't try and scan if XML is invalid or we got an HTML 404 error.
	if ( $objDOM->loadXML( $xmlcontent ) == false ) {
		return;
	}

	$gatekeeperURL = ( ! empty( $objDOM->getElementsByTagName( 'gatekeeperURL' ) ) ) ? $objDOM->getElementsByTagName( 'gatekeeperURL' )->item( 0 )->nodeValue : '';

	//
	// Get the region data to update
	//
	$regiondata = $objDOM->getElementsByTagName( 'regiondata' );

	// If returned length is 0, collector method may have returned an error
	if ( $regiondata->length == 0 ) {
		return;
	}

	$regiondata = $regiondata->item( 0 );

	//
	// Update nextcheck so this host entry won't be checked again until after
	// the DataSnapshot module has generated a new set of data to be scanned.
	//
	$expire = $regiondata->getElementsByTagName( 'expire' )->item( 0 )->nodeValue;
	$next   = $now + $expire;

	$query = $SearchDB->prepare( 'UPDATE hostsregister SET nextcheck = ?, gatekeeperURL = ? WHERE host = ? AND port = ?' );
	$query->execute( array( $next, $gatekeeperURL, $hostname, $port ) );

	//
	// Get the region data to be saved in the database
	//
	$regionlist = $regiondata->getElementsByTagName( 'region' );

	foreach ( $regionlist as $region ) {
		$mature = $region->getAttributeNode( 'category' )->nodeValue;

		//
		// Start reading the Region info
		//
		$info         = $region->getElementsByTagName( 'info' )->item( 0 );
		$regionUUID   = $info->getElementsByTagName( 'uuid' )->item( 0 )->nodeValue;
		$regionname   = $info->getElementsByTagName( 'name' )->item( 0 )->nodeValue;
		$regionhandle = $info->getElementsByTagName( 'handle' )->item( 0 )->nodeValue;
		$url          = $info->getElementsByTagName( 'url' )->item( 0 )->nodeValue;

		/*
		* First, check if we already have a region that is the same
		*/

		$SearchDB->prepareAndExecute( 'DELETE FROM ' . SEARCH_REGION_TABLE . ' WHERE regionUUID = ?', array( $regionUUID ) );
		$SearchDB->prepareAndExecute( 'DELETE FROM parcels WHERE regionUUID = ?', array( $regionUUID ) );
		$SearchDB->prepareAndExecute( 'DELETE FROM allparcels WHERE regionUUID = ?', array( $regionUUID ) );
		$SearchDB->prepareAndExecute( 'DELETE FROM parcelsales WHERE regionUUID = ?', array( $regionUUID ) );
		$SearchDB->prepareAndExecute( 'DELETE FROM objects WHERE regionuuid = ?', array( $regionUUID ) );

		$data         = $region->getElementsByTagName( 'data' )->item( 0 );
		$estate       = $data->getElementsByTagName( 'estate' )->item( 0 );
		$parentestate = $estate->getElementsByTagName( 'id' )->item( 0 )->nodeValue;
		$username     = $estate->getElementsByTagName( 'name' )->item( 0 )->nodeValue;
		$useruuid     = $estate->getElementsByTagName( 'uuid' )->item( 0 )->nodeValue;
		if ( empty( $useruuid ) ) {
			$useruuid = NULL_KEY;
		}

		/*
		* Second, add the new info to the database again
		*/

		$SearchDB->insert(
			SEARCH_REGION_TABLE,
			array(
				'regionname'    => $regionname,
				'regionUUID'    => $regionUUID,
				'regionhandle'  => $regionhandle,
				'url'           => $url,
				'owner'         => $username,
				'ownerUUID'     => $useruuid,
				'gatekeeperURL' => $gatekeeperURL,
			)
		);

		/*
		* Read parcel info
		*/

		$parcels = $data->getElementsByTagName( 'parcel' );
		foreach ( $parcels as $parcel ) {
			$parcelname        = $parcel->getElementsByTagName( 'name' )->item( 0 )->nodeValue;
			$parcelUUID        = $parcel->getElementsByTagName( 'uuid' )->item( 0 )->nodeValue;
			$infoUUID          = $parcel->getElementsByTagName( 'infouuid' )->item( 0 )->nodeValue;
			$landingpoint      = $parcel->getElementsByTagName( 'location' )->item( 0 )->nodeValue;
			$parceldescription = $parcel->getElementsByTagName( 'description' )->item( 0 )->nodeValue;
			$parcelarea        = $parcel->getElementsByTagName( 'area' )->item( 0 )->nodeValue;
			$searchcategory    = $parcel->getAttributeNode( 'category' )->nodeValue;
			$saleprice         = $parcel->getAttributeNode( 'salesprice' )->nodeValue;
			$dwell             = $parcel->getElementsByTagName( 'dwell' )->item( 0 )->nodeValue;

			// The image tag will only exist if the parcel has a snapshot image
			$has_picture = 0;
			$image_node  = $parcel->getElementsByTagName( 'image' );
			if ( $image_node->length > 0 ) {
				$image = $image_node->item( 0 )->nodeValue;
				if ( $image != NULL_KEY ) {
					$has_picture = 1;
				}
			}
			if ( empty( $image ) ) {
				$image = NULL_KEY;
			}

			$owner     = $parcel->getElementsByTagName( 'owner' )->item( 0 );
			$ownerUUID = $owner->getElementsByTagName( 'uuid' )->item( 0 )->nodeValue;

			// Adding support for groups
			$group = $parcel->getElementsByTagName( 'group' )->item( 0 );
			if ( $group ) {
				$groupUUID = $group->getElementsByTagName( 'groupuuid' )->item( 0 )->nodeValue;
			}
			if ( empty( $groupUUID ) ) {
				$groupUUID = NULL_KEY;  // empty group, should it happen?
			}

			//
			// Check bits on Public, Build, Script
			//
			$parcelforsale   = $parcel->getAttributeNode( 'forsale' )->nodeValue;
			$parceldirectory = $parcel->getAttributeNode( 'showinsearch' )->nodeValue;
			$parcelbuild     = $parcel->getAttributeNode( 'build' )->nodeValue;
			$parcelscript    = $parcel->getAttributeNode( 'scripts' )->nodeValue;
			$parcelpublic    = $parcel->getAttributeNode( 'public' )->nodeValue;

			// Prepare for the insert of data in to the popularplaces table. This gets
			// rid of any obsolete data for parcels no longer set to show in search.
			$query = $SearchDB->prepare( 'DELETE FROM popularplaces WHERE parcelUUID = ?' );
			$query->execute( array( $parcelUUID ) );

			/*
			 * Save
			 *
			 * Sometimes, the parcel is inserted more than once, which causes a fatal
			 * issue. Delete it first (quick workaround, should be investigated).
			 */

			$query = $SearchDB->prepare( 'DELETE FROM allparcels WHERE parcelUUID = :parcelUUID' );
			$query->execute( array( 'parcelUUID' => $parcelUUID ) );

			$SearchDB->insert(
				'allparcels',
				array(
					'regionUUID'    => $regionUUID,
					'parcelname'    => $parcelname,
					'ownerUUID'     => $ownerUUID,
					'groupUUID'     => $groupUUID,
					'landingpoint'  => $landingpoint,
					'parcelUUID'    => $parcelUUID,
					'infoUUID'      => $infoUUID,
					'parcelarea'    => $parcelarea,
					'gatekeeperURL' => $gatekeeperURL,
				)
			);

			if ( $parceldirectory == 'true' ) {
				$SearchDB->insert(
					'parcels',
					array(
						'regionUUID'     => $regionUUID,
						'parcelname'     => $parcelname,
						'parcelUUID'     => $parcelUUID,
						'landingpoint'   => $landingpoint,
						'description'    => $parceldescription,
						'searchcategory' => $searchcategory,
						'build'          => $parcelbuild,
						'script'         => $parcelscript,
						'public'         => $parcelpublic,
						'dwell'          => $dwell,
						'infouuid'       => $infoUUID,
						'mature'         => $mature,
						'gatekeeperURL'  => $gatekeeperURL,
						'imageUUID'      => $image,
					)
				);

					// We don't want land for sale in popular places.
				if ( $parcelforsale == 'false' ) {
					$SearchDB->insert(
						'popularplaces',
						array(
							'parcelUUID'    => $parcelUUID,
							'name'          => $parcelname,
							'dwell'         => $dwell,
							'infoUUID'      => $infoUUID,
							'has_picture'   => $has_picture,
							'mature'        => $mature,
							'gatekeeperURL' => $gatekeeperURL,
						)
					);
				}
			}

			if ( $parcelforsale == 'true' ) {
				$SearchDB->insert(
					'parcelsales',
					array(
						'regionUUID'    => $regionUUID,
						'parcelname'    => $parcelname,
						'parcelUUID'    => $parcelUUID,
						'area'          => $parcelarea,
						'saleprice'     => $saleprice,
						'landingpoint'  => $landingpoint,
						'infoUUID'      => $infoUUID,
						'dwell'         => $dwell,
						'parentestate'  => $parentestate,
						'mature'        => $mature,
						'gatekeeperURL' => $gatekeeperURL,
					)
				);
			}
		}

		//
		// Handle objects
		//
		$objects = $data->getElementsByTagName( 'object' );

		foreach ( $objects as $object ) {
			$objectname = $object->getElementsByTagName( 'title' )->item( 0 )->nodeValue;
			$location   = $object->getElementsByTagName( 'location' )->item( 0 )->nodeValue;
			$parcelUUID = $object->getElementsByTagName( 'parceluuid' )->item( 0 )->nodeValue;
			$regionUUID = $object->getElementsByTagName( 'regionuuid' )->item( 0 )->nodeValue;
			// $flags = $object->getElementsByTagName("flags")->item(0)->nodeValue; // not implemented
			// $image = $object->getElementsByTagName("image")->item(0)->nodeValue; // not implemented
			$SearchDB->insert(
				'objects',
				array(
					'objectuuid'    => $object->getElementsByTagName( 'uuid' )->item( 0 )->nodeValue,
					'parceluuid'    => $parcelUUID,
					'location'      => $location,
					'name'          => $location,
					'description'   => $object->getElementsByTagName( 'description' )->item( 0 )->nodeValue,
					'regionuuid'    => $object->getElementsByTagName( 'regionuuid' )->item( 0 )->nodeValue,
					'gatekeeperURL' => $gatekeeperURL,
				)
			);
		}
	}
}

if ( $SearchDB && $SearchDB->connected ) {
	// $sql = "SELECT host, port FROM hostsregister WHERE nextcheck<$now AND checked=0 AND failcounter<10 LIMIT 0,100";
	$sql = "SELECT host, port FROM hostsregister WHERE nextcheck<$now AND checked=0 LIMIT 0,100";
	// $sql = "SELECT host, port FROM hostsregister WHERE checked=0 LIMIT 0,100";
	$jobsearch = $SearchDB->query( $sql );

	//
	// If the sql query returns no rows, all entries in the hostsregister
	// table have been checked. Reset the checked flag and re-run the
	// query to select the next set of hosts to be checked.
	//

	if ( $jobsearch->rowCount() == 0 ) {
		$jobsearch = $SearchDB->query( 'UPDATE hostsregister SET checked = 0' );
		$jobsearch = $SearchDB->query( $sql );
	}

	while ( $jobs = $jobsearch->fetch( PDO::FETCH_NUM ) ) {
		hostCheck( $jobs[0], $jobs[1] );
	}
}

die();
