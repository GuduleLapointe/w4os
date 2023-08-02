<?php
/**
 * parseevents.php
 *
 * This script parses data from registered hosts to feed the search database.
 * It must be run regularly by a cron task for the search to work properly.
 *
 * @package     magicoli/opensim-helpers
 * @author      Gudule Lapointe <gudule@speculoos.world>
 * @link            https://github.com/magicoli/opensim-helpers
 * @license     AGPLv3
 */

require_once 'includes/config.php';
require_once 'includes/search.php';
define( 'EVENTS_NULL_KEY', '00000000-0000-0000-0000-000000000001' );

dontWait();

$json_url = HYPEVENTS_URL . '/events.json';
$json     = json_decode( file_get_contents( $json_url ), true );
if ( ! $json ) {
	error_log( "Invalid json received from $json_url" );
	die;
}

$categories = array(
	'discussion'              => 18,
	'sports'                  => 19,
	'live music'              => 20,
	'commercial'              => 22,
	'nightlife/entertainment' => 23,
	'games/contests'          => 24,
	'pageants'                => 25,
	'education'               => 26,
	'arts and culture'        => 27,
	'charity/support groups'  => 28,
	'miscellaneous'           => 29,

	// From HYPEvents code:
	'art'                     => 27, // Art & Culture
	'education'               => 26, // Education
	'fair'                    => 23, // ambiguous, could be 23 Nightlife, or 27 Art, or 28 Charity
	'lecture'                 => 27, // Art & Culture
	'litterature'             => 27, // Art & Culture
	'music'                   => 20, // Live Music
	'roleplay'                => 24, // Games/Contests
	'social'                  => 28, // Charity / Support Groups
);

function getEventCategory( $values ) {
	global $categories;
	if ( empty( $values ) ) {
		return 0; // Undefined
	}
	if ( ! is_array( $values ) ) {
		$values = $array( $values );
	}
	foreach ( $values as $value ) {
		if ( is_int( $value ) ) {
			return $value;
		}
		$key = strtolower( $value );
		if ( isset( $categories[ $key ] ) ) {
			return $categories[ $key ];
		}
	}
	return 29; // Not undefined, but unknown, so we return Miscellaneous
}
$notbefore = time() - 3600;

$events = array();
foreach ( $json as $json_event ) {
	if ( ! isset( $json_event['owneruuid'] ) ) {
		$json_event['owneruuid'] = EVENTS_NULL_KEY;
	}
	$start = strtotime( $json_event['start'] );
	if ( $start < $notbefore ) {
		continue;
	}
	$end      = strtotime( $json_event['end'] );
	$duration = ( $end > $start ) ? round( ( strtotime( $json_event['end'] ) - $start ) / 60 ) : 60;
	$duration = ( $duration > 0 ) ? $duration : 60;

	$online = opensim_region_is_online( $json_event['hgurl'] );
	if ( ! $online ) {
		continue; // ignore event if region is malformatted or unreachable
	}

	$region     = opensim_sanitize_uri( $json_event['hgurl'], '', true );
	$get_region = opensim_get_region( $json_event['hgurl'] );
	$pos        = ( empty( $region['pos'] ) ) ? array( 128, 128, 25 ) : explode( '/', $region['pos'] );
	if ( ! empty( $get_region['x'] ) & ! empty( $get_region['y'] ) ) {
		$pos[0] += $get_region['x'];
		$pos[1] += $get_region['y'];
	}
	$pos = implode( ',', $pos );

	$slurl       = opensim_format_tp( $json_event['hgurl'], TPLINK_TXT );
	$links       = opensim_format_tp( $json_event['hgurl'], TPLINK_APPTP + TPLINK_HOP );
	$description = strip_tags( html_entity_decode( utf8_encode( utf8_decode( $json_event['description'] ) ) ) );
	$description = "$links\n\n$description";
	// $title = utf8_encode(utf8_decode($json_event['title']));
	$title = strip_tags( html_entity_decode( utf8_encode( utf8_decode( $json_event['title'] ) ) ) );

	$fields = array(
		'owneruuid'     => EVENTS_NULL_KEY, // Not implemented
		'name'          => $title,
		// 'eventid' => $json_event['eventid'],
		'creatoruuid'   => EVENTS_NULL_KEY, // Not implemented
		'category'      => getEventCategory( $json_event['categories'] ),
		'description'   => $description,
		'dateUTC'       => $start,
		'duration'      => $duration,
		'covercharge'   => 0, // Not implemented
		'coveramount'   => 0, // Not implemented
		'simname'       => $slurl,
		'parcelUUID'    => EVENTS_NULL_KEY, // Not implemented
		'globalPos'     => $pos,
		'eventflags'    => 0, // Not implemented
		'gatekeeperURL' => $region['gatekeeper'],
	// 'hash' => $json_event['hash'], // Not implemented, though
	);
	$events[] = $fields;
}

if ( is_object( $SearchDB ) && $SearchDB->connected ) {
	$SearchDB->query( 'DELETE FROM events' );
	foreach ( $events as $event ) {
		$result = $SearchDB->insert( 'events', $event );
		if ( ! $result ) {
			error_log( 'error while inserting new events)' );
		}
	}
}
