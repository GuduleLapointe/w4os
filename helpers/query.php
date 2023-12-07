<?php
/**
 * query.php
 *
 * Script called by the viewer, provides search results for places, land sales end events
 *
 * Requires OpenSimulator Search module
 *   [OpenSimSearch](https://github.com/kcozens/OpenSimSearch)
 * Events need to be fetched with a separate script, from an HYPEvents server
 *
 * @package     magicoli/opensim-helpers
 * @author      Gudule Lapointe <gudule@speculoos.world>
 * @link            https://github.com/magicoli/opensim-helpers
 * @license     AGPLv3
 *
 * Includes portions of code from
 *   [OpenSimSearch](https://github.com/kcozens/OpenSimSearch)
 */

require_once 'includes/config.php';
require_once 'includes/search.php';

function ossearch_get_gatekeeperURL( $args = array() ) {
	$gatekeeperURL = false;
	if ( ! empty( $args['gatekeeper_url'] ) ) {
		$gatekeeperURL = $args['gatekeeper_url'];
	} elseif ( ! empty( $_REQUEST['gk'] ) ) {
		$gatekeeperURL = $_REQUEST['gk'];
	} else {
		return false;
	}

	if ( ! empty( $gatekeeperURL ) ) {
		$gatekeeperURL = preg_match( '#https?://#', $gatekeeperURL ) ? $gatekeeperURL : 'http://' . $gatekeeperURL;
	}
	return $gatekeeperURL;
}

//
// The XMLRPC server object
//

$xmlrpc_server = xmlrpc_server_create();

//
// Places Query
//
xmlrpc_server_register_method( $xmlrpc_server, 'dir_places_query', 'dir_places_query' );
function dir_places_query( $method_name, $params, $app_data ) {
	global $SearchDB;
	$req = $params[0];

	$flags       = $req['flags'];
	$text        = $req['text'];
	$category    = isset($req['category']) ? $req['category'] : null;
	$query_start = $req['query_start'];
	if ( ! is_int( $query_start ) ) {
		$query_start = 0;
	}

	$pieces = explode( ' ', $text );
	array_filter( $pieces );
	$text = join( '%', $pieces );
	$text = "%$text%";
	if ( empty( $text ) || $text == '%%%' ) {
		osXmlDie( 'Invalid search terms' );
	}

	$terms   = array();
	$sqldata = array();

	// order by traffic or by parcename
	$order = ( $flags & 1024 ) ? 'dwell DESC, parcelname' : 'parcelname';

	$terms[] = '(parcelname LIKE :text OR description LIKE :text)';
	$type    = ossearch_terms_build_rating( $flags );
	if ( ! empty( $type ) ) {
		$terms[] = "$type";
	}
	if ( $category > 0 ) {
		$terms[] = 'searchcategory = :cat';
	}

	$values        = array(
		':text'  => $text,
		':order' => $order,
		':cat'   => $category,
	);
	$gatekeeperURL = ossearch_get_gatekeeperURL();
	if ( $gatekeeperURL ) {
		$terms[]                 = 'parcels.gatekeeperURL = :gatekeeperURL';
		$values['gatekeeperURL'] = $gatekeeperURL;
	}
	$query = $SearchDB->prepareAndExecute(
		'SELECT * FROM parcels
		INNER JOIN ' . SEARCH_REGION_TABLE . ' AS r ON parcels.regionUUID = r.regionUUID
    WHERE ' . join( ' AND ', $terms ) . " ORDER BY :order LIMIT $query_start,101",
		$values
	);

	$data = array();
	while ( $row = $query->fetch( PDO::FETCH_ASSOC ) ) {

		$data[] = array_merge($row, array(
			'parcel_id' => $row['infouuid'],
			'name'      => $row['parcelname'],
			'for_sale'  => 'False',
			'auction'   => 'False',
			'dwell'     => $row['dwell'],
		));
	}

	if ( empty( $data ) ) {
		// osXmlDie( 'Nothing found' );
		osXmlResponse( true, 'No results', $data );
	} else {
		osXmlResponse( true, '', $data );
	}
	die();
}

//
// Popular Places Query
//
xmlrpc_server_register_method( $xmlrpc_server, 'dir_popular_query', 'dir_popular_query' );
function dir_popular_query( $method_name, $params, $app_data ) {
	global $SearchDB;

	$req               = $params[0];
	$text              = $req['text'];
	$flags             = $req['flags'];
	$query_start       = $req['query_start'];
	$include_hypergrid = ( isset( $req['include_hypergrid'] ) && $req['include_hypergrid'] == 'false' ) ? false : true;
	$include_landsales = ( isset( $req['include_landsales'] ) && $req['include_landsales'] == 'true' ) ? true : false;

	$terms   = array();
	$sqldata = array();

	if ( $flags & pow( 2, 12 ) ) {
		$terms[] = 'has_picture = 1';
	}
	// if ($flags & pow(2,11)) $terms[] = "pop.mature = 0";     //PgSimsOnly (1 << 11)
	$typeCondition = ossearch_terms_build_rating( $flags, 'pop' );
	if ( ! empty( $typeCondition ) ) {
		$terms[] = $typeCondition;
	}

	if ( $text != '' ) {
		$terms[]         = '(name LIKE :text)';
		$sqldata['text'] = "%$text%";
	}

	$gatekeeperURL = ossearch_get_gatekeeperURL( $req );
	if ( ! $include_hypergrid && ! empty( $gatekeeperURL ) ) {
		$terms[]                  = 'pop.gatekeeperURL = :gatekeeperURL';
		$sqldata['gatekeeperURL'] = $gatekeeperURL;
	}
	$left_join = null;
	if ( ! $include_landsales ) {
		$left_join = 'LEFT JOIN parcelsales AS sales ON sales.parcelUUID = pop.parcelUUID';
		$terms[]   = 'sales.regionUUID IS NULL';
	}
	if ( count( $terms ) > 0 ) {
		$where = ' WHERE ' . join( ' AND ', $terms );
	} else {
		$where = '';
	}

	// Prevent SQL injection by checking that $query_start is a number
	if ( ! is_int( $query_start ) ) {
		$query_start = 0;
	}

	$sql    = 'SELECT pop.infoUUID, pop.name, pop.dwell, pop.gatekeeperURL, r.regionname, r.regionUUID, par.landingpoint, par.imageUUID FROM popularplaces as pop
	INNER JOIN parcels as par ON pop.parcelUUID = par.parcelUUID
	INNER JOIN ' . SEARCH_REGION_TABLE . ' as r ON par.regionUUID = r.regionUUID
	' . $left_join . '
	' . $where . "
	ORDER BY pop.dwell DESC, par.parcelname LIMIT $query_start,100";
	$query  = $SearchDB->prepare( $sql );
	$result = $query->execute( $sqldata );

	$data = array();
	while ( $row = $query->fetch( PDO::FETCH_ASSOC ) ) {
		$data[] = array(
			'parcel_id'     => $row['infoUUID'],
			'name'          => $row['name'],
			'dwell'         => $row['dwell'],
			'gatekeeperURL' => $row['gatekeeperURL'],
			'regionname'    => $row['regionname'],
			'regionUUID'    => $row['regionUUID'],
			'landingpoint'  => $row['landingpoint'],
			'imageUUID'     => $row['imageUUID'],
		);
	}

	osXmlResponse( true, '', $data );
}

//
// Land Query
//
xmlrpc_server_register_method( $xmlrpc_server, 'dir_land_query', 'dir_land_query' );
function dir_land_query( $method_name, $params, $app_data ) {
	global $SearchDB;
	$req = $params[0];

	$flags       = $req['flags'];
	$type        = $req['type'];
	$price       = $req['price'];
	$area        = $req['area'];
	$query_start = $req['query_start'];

	$terms   = array();
	$sqldata = array();

	if ( $type != 4294967295 ) {
		// Do this check first so we can bail out quickly on Auction search
		if ( ( $type & 26 ) == 2 ) {
			osXmlDie( 'No auctions listed' ); // Auction (from SearchTypeFlags enum)
		}

		if ( ( $type & 24 ) == 8 ) {
			$terms[] = 'parentestate = 1'; // Mainland (24=0x18 [bits 3 & 4])
		}
		if ( ( $type & 24 ) == 16 ) {
			$terms[] = 'parentestate <> 1'; // Estate (24=0x18 [bits 3 & 4])
		}
	}

	$typeCondition = ossearch_terms_build_rating( $flags );
	if ( ! empty( $typeCondition ) ) {
		$terms[] = $typeCondition;
	}
	if ( $flags & pow( 2, 20 ) ) {
		$terms[]          = 'saleprice <= :price';
		$sqldata['price'] = $price;
	}
	if ( $flags & pow( 2, 21 ) ) {
		$terms[]         = 'area >= :area';
		$sqldata['area'] = $area;
	}
	$gatekeeperURL = ossearch_get_gatekeeperURL();
	if ( $gatekeeperURL ) {
		$terms[]                  = 'gatekeeperURL = :gatekeeperURL';
		$sqldata['gatekeeperURL'] = $gatekeeperURL;
	}

	// The PerMeterSort flag is always passed from a map item query.
	// It doesn't hurt to have this as the default search order.
	$order = 'lsq';     // PerMeterSort (1 << 17)

	if ( $flags & pow( 2, 19 ) ) {
		$order = 'parcelname';
	}
	if ( $flags & pow( 2, 16 ) ) {
		$order = 'saleprice';
	}
	if ( $flags & pow( 2, 18 ) ) {
		$order = 'area';
	}
	if ( ! ( $flags & pow( 2, 15 ) ) ) {
		$order .= ' DESC';
	}

	if ( count( $terms ) > 0 ) {
		$where = ' WHERE ' . join( ' AND ', $terms );
	} else {
		$where = '';
	}

	// Prevent SQL injection by checking that $query_start is a number
	if ( ! is_int( $query_start ) ) {
		$query_start = 0;
	}

	$sql    = "SELECT *,saleprice/area AS lsq FROM parcelsales $where ORDER BY " . $order . " LIMIT $query_start,101";
	$query  = $SearchDB->prepare( $sql );
	$result = $query->execute( $sqldata );

	$data = array();
	while ( $row = $query->fetch( PDO::FETCH_ASSOC ) ) {
		$data[] = array(
			'parcel_id'     => $row['infoUUID'],
			'name'          => $row['parcelname'],
			'auction'       => 'false',
			'for_sale'      => 'true',
			'sale_price'    => $row['saleprice'],
			'landing_point' => $row['landingpoint'],
			'region_UUID'   => $row['regionUUID'],
			'area'          => $row['area'],
		);
	}

	osXmlResponse( true, '', $data );
}

//
// Events Query
//

xmlrpc_server_register_method( $xmlrpc_server, 'dir_events_query', 'dir_events_query' );
function dir_events_query( $method_name, $params, $app_data ) {
	global $SearchDB;
	$req = $params[0];

	$text        = $req['text'];
	$flags       = $req['flags'];
	$query_start = $req['query_start'];

	if ( $text == '%%%' ) {
		$response_xml = xmlrpc_encode(
			array(
				'success'      => false,
				'errorMessage' => 'Invalid search terms',
			)
		);

		print $response_xml;

		return;
	}

	$pieces = explode( '|', $text );

	$day      = $pieces[0];
	$category = $pieces[1];
	if ( count( $pieces ) < 3 ) {
		$search_text = '';
	} else {
		$search_text = $pieces[2];
	}

	$terms   = array();
	$sqldata = array();

	// Event times are in UTC so we need to get the current time in UTC.
	$now = time();

	if ( $day == 'u' ) {
		// This condition will include upcoming and in-progress events
		$terms[] = 'dateUTC+duration*60 >= ' . $now;
	} else {
		// For events in a given day we need to determine the days start time
		$now -= idate( 'Z' );     // Adjust for timezone
		$now -= ( $now % 86400 ); // Adjust to start of day

		// Is $day a number of days before or after current date?
		if ( $day != 0 ) {
			$now += $day * 86400;
		}

		$then = $now + 86400;   // Time for end of day

		// This condition will include any in-progress events
		$terms[] = "(dateUTC+duration*60 >= $now AND dateUTC < $then)";
	}

	if ( $category > 0 ) {
		$terms[] = 'category = :category';

		$sqldata['category'] = $category;
	}

	$type = array();
	if ( $flags & pow( 2, 24 ) ) {
		$type[] = 'eventflags = 0'; // IncludePG (1 << 24)
	}
	if ( $flags & pow( 2, 25 ) ) {
		$type[] = 'eventflags = 1'; // IncludeMature (1 << 25)
	}
	if ( $flags & pow( 2, 26 ) ) {
		$type[] = 'eventflags = 2'; // IncludeAdult (1 << 26)
	}
	if ( count( $type ) > 0 ) {
		$terms[] = ossearch_terms_join( ' OR ', $type );
	}

	if ( $search_text != '' ) {
		$terms[] = '(name LIKE :text1 OR ' .
		'description LIKE :text2)';

		$search_text      = "%$search_text%";
		$sqldata['text1'] = $search_text;
		$sqldata['text2'] = $search_text;
	}

	if ( count( $terms ) > 0 ) {
		$where = ' WHERE ' . join( ' AND ', $terms );
	} else {
		$where = '';
	}

	// Prevent SQL injection by checking that $query_start is a number
	if ( ! is_int( $query_start ) ) {
		$query_start = 0;
	}

	$sql    = 'SELECT owneruuid,name,eventid,dateUTC,eventflags,globalPos' .
	' FROM events' . $where . " LIMIT $query_start,101";
	$query  = $SearchDB->prepare( $sql );
	$result = $query->execute( $sqldata );

	$data = array();
	while ( $row = $query->fetch( PDO::FETCH_ASSOC ) ) {
		$date = new DateTime( '@' . $row['dateUTC'] );
		$date->setTimezone( new DateTimeZone( 'America/Los_Angeles' ) );
		$LSTDate = $date->format( 'm/d h:i A' );

		// The landing point is only needed when this event query is
		// called to allow placement of event markers on the world map.
		$data[] = array(
			'owner_id'      => $row['owneruuid'],
			'name'          => $row['name'],
			'event_id'      => $row['eventid'],
			'date'          => $LSTDate,
			'unix_time'     => $row['dateUTC'],
			'event_flags'   => $row['eventflags'],
			'landing_point' => $row['globalPos'],
		);
	}
	osXmlResponse( true, '', $data );
}

//
// Classifieds Query
//
xmlrpc_server_register_method( $xmlrpc_server, 'dir_classified_query', 'dir_classified_query' );
function dir_classified_query( $method_name, $params, $app_data ) {
	global $OpenSimDB;
	if ( ! tableExists( $OpenSimDB, array( 'classifieds' ) ) ) {
		osXmlResponse( false );
		die();
	}

	$req = $params[0];

	$text        = $req['text'];
	$flags       = $req['flags'];
	$category    = $req['category'];
	$query_start = $req['query_start'];

	if ( $text == '%%%' ) {
		osXmlResponse( false, 'Invalid search terms', array() );
		return;
	}

	$terms   = array();
	$sqldata = array();

	// Renew Weekly flag is bit 5 (32) in $flags.
	$f = array();
	if ( $flags & 4 ) {
		$f[] = 'classifiedflags & 4'; // PG (1 << 2)
	}
	if ( $flags & 8 ) {
		$f[] = 'classifiedflags & 8'; // Mature (1 << 3)
	}
	if ( $flags & 64 ) {
		$f[] = 'classifiedflags & 64'; // Adult (1 << 6)
	}
	if ( count( $f ) > 0 ) {
		$terms[] = ossearch_terms_join( ' OR ', $f );
	}

	// Only restrict results based on category if it is not 0 (Any Category)
	if ( $category > 0 ) {
		$terms[] = "category = $category";
	}

	if ( $text != '' ) {
		$terms[]         = '(name LIKE :text OR description LIKE :text)';
		$sqldata['text'] = "%$text%";
	}

	// Was there at least condition for the search?
	if ( count( $terms ) > 0 ) {
		$where = ' WHERE ' . join( ' AND ', $terms );
	} else {
		$where = '';
	}

	// Prevent SQL injection by checking that $query_start is a number
	if ( ! is_int( $query_start ) ) {
		$query_start = 0;
	}

	$sql   = "SELECT * FROM classifieds $where ORDER BY priceforlisting DESC LIMIT $query_start,101";
	$query = $OpenSimDB->prepareAndExecute( $sql, $sqldata );

	$data = array();
	while ( $row = $query->fetch( PDO::FETCH_ASSOC ) ) {
		$data[] = array(
			'classifiedid'    => $row['classifieduuid'],
			'name'            => $row['name'],
			'classifiedflags' => $row['classifiedflags'],
			'creation_date'   => $row['creationdate'],
			'expiration_date' => $row['expirationdate'],
			'priceforlisting' => $row['priceforlisting'],
		);
	}

	osXmlResponse( true, '', $data );
}

//
// Events Info Query
//

xmlrpc_server_register_method( $xmlrpc_server, 'event_info_query', 'event_info_query' );
function event_info_query( $method_name, $params, $app_data ) {
	global $SearchDB;

	$req = $params[0];

	$eventID = $req['eventID'];

	$query  = $SearchDB->prepare( 'SELECT * FROM events WHERE eventID = ?' );
	$result = $query->execute( array( $eventID ) );

	$data = array();
	while ( $row = $query->fetch( PDO::FETCH_ASSOC ) ) {
		$date = strftime( '%G-%m-%d %H:%M:%S', $row['dateUTC'] );

		$category = '*Unspecified*';
		if ( $row['category'] == 18 ) {
			$category = 'Discussion';
		}
		if ( $row['category'] == 19 ) {
			$category = 'Sports';
		}
		if ( $row['category'] == 20 ) {
			$category = 'Live Music';
		}
		if ( $row['category'] == 22 ) {
			$category = 'Commercial';
		}
		if ( $row['category'] == 23 ) {
			$category = 'Nightlife/Entertainment';
		}
		if ( $row['category'] == 24 ) {
			$category = 'Games/Contests';
		}
		if ( $row['category'] == 25 ) {
			$category = 'Pageants';
		}
		if ( $row['category'] == 26 ) {
			$category = 'Education';
		}
		if ( $row['category'] == 27 ) {
			$category = 'Arts and Culture';
		}
		if ( $row['category'] == 28 ) {
			$category = 'Charity/Support Groups';
		}
		if ( $row['category'] == 29 ) {
			$category = 'Miscellaneous';
		}

		// debug($row);
		if ( isset( $_REQUEST['gk'] ) & ! empty( $_REQUEST['gk'] ) && $_REQUEST['gk'] == $row['gatekeeperURL'] ) {
			$simname = preg_replace( '/^[^ ]*/', '', $row['simname'] );
		} else {
			$simname = $row['simname'];
		}

		$data[] = array(
			'event_id'       => $row['eventid'],
			'creator'        => $row['creatoruuid'],
			'name'           => $row['name'],
			'category'       => $category,
			'description'    => $row['description'],
			'date'           => $date,
			'dateUTC'        => $row['dateUTC'],
			'duration'       => $row['duration'],
			'covercharge'    => $row['covercharge'],
			'coveramount'    => $row['coveramount'],
			'simname'        => $simname,
			'globalposition' => $row['globalPos'],
			'eventflags'     => $row['eventflags'],
		);
	}

	osXmlResponse( true, '', $data );
}

//
// Classifieds Info Query
//
xmlrpc_server_register_method( $xmlrpc_server, 'classifieds_info_query', 'classifieds_info_query' );
function classifieds_info_query( $method_name, $params, $app_data ) {
	global $OpenSimDB;
	if ( ! tableExists( $OpenSimDB, array( 'classifieds' ) ) ) {
		osXmlResponse( false );
		die();
	}

	$req = $params[0];

	$classifiedID = $req['classifiedID'];

	$query  = $OpenSimDB->prepare( 'SELECT * FROM classifieds WHERE classifieduuid = ?' );
	$result = $query->execute( array( $classifiedID ) );

	$data = array();
	while ( $row = $query->fetch( PDO::FETCH_ASSOC ) ) {
		$data[] = array(
			'classifieduuid'  => $row['classifieduuid'],
			'creatoruuid'     => $row['creatoruuid'],
			'creationdate'    => $row['creationdate'],
			'expirationdate'  => $row['expirationdate'],
			'category'        => $row['category'],
			'name'            => $row['name'],
			'description'     => $row['description'],
			'parceluuid'      => $row['parceluuid'],
			'parentestate'    => $row['parentestate'],
			'snapshotuuid'    => $row['snapshotuuid'],
			'simname'         => $row['simname'],
			'posglobal'       => $row['posglobal'],
			'parcelname'      => $row['parcelname'],
			'classifiedflags' => $row['classifiedflags'],
			'priceforlisting' => $row['priceforlisting'],
		);
	}

	osXmlResponse( true, '', $data );
}

//
// Process the request
//
$request_xml = file_get_contents( 'php://input' );
xmlrpc_server_call_method( $xmlrpc_server, $request_xml, '' );

xmlrpc_server_destroy( $xmlrpc_server );
die();
