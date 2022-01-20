<?php
/*
 * parseevents.php
 *
 * Part of "flexible_helpers_scripts" collection
 * https://github.com/GuduleLapointe/flexible_helper_scripts
 *
 * This script parses data from registered hosts to feed the search database.
 * It must be run regularly by a cron task for the search to work properly.
 */

require_once('include/config.php');
require_once('include/ossearch_db.php');

function checkpoint($message = 'Check point') {
  $message = basename(__FILE__) . ': ' . $message;
  echo $message;
  error_log($message);
}

$json_url = HYPEVENTS_URL . '/events.json';
$json = json_decode(file_get_contents($json_url), true);
if (! $json ) {
  error_log("Invalid json received from $json_url");
  die;
}

$categories = array(
  'discussion' => 18,
  'sports' => 19,
  'live music' => 20,
  'commercial' => 22,
  'nightlife/entertainment' => 23,
  'games/contests' => 24,
  'pageants' => 25,
  'education' => 26,
  'arts and culture' => 27,
  'charity/support groups' => 28,
  'miscellaneous' => 29,

  // From HYPEvents code:
  'art' => 27, // Art & Culture
  'education' => 26, // Education
  'fair' => 23, // ambiguous, could be 23 Nightlife, or 27 Art, or 28 Charity
  'lecture' => 27, // Art & Culture
  'litterature' => 27, // Art & Culture
  'music' => 20, // Live Music
  'roleplay' => 24, // Games/Contests
  'social' => 28, // Charity / Support Groups
);

function getEventCategory($values) {
  global $categories;
  if(empty($values)) return 0; // Undefined
  if(!is_array($values)) $values = $array($values);
  foreach($values as $value) {
    if(is_int($value)) return $value;
    $key = strtolower($value);
    if(isset($categories[$key])) return $categories[$key];
  }
  return 29; // Not undefined, but unknown, so we return Miscellaneous
}
$notbefore = time() - 3600;
// print_r($json);

define('EVENTS_NULL_KEY', '00000000-0000-0000-0000-000000000001');

$events=array();
foreach($json as $json_event) {
  if(!isset($json_event['owneruuid'])) $json_event['owneruuid'] = EVENTS_NULL_KEY;
  $start = strtotime($json_event['start']);
  if($start < $notbefore) continue;
  $end = strtotime($json_event['end']);
  $duration = ($end > $start) ? round((strtotime($json_event['end']) - $start) / 60) : 60;
  $duration = ($duration > 0) ? $duration : 60;
  $description = strip_tags(html_entity_decode($json_event['description']));
  $description .= "\n\n" . $json_event['hgurl'];
  $slurl = $json_event['hgurl']; // TODO format as valid slurl
  if(preg_match('!.*[:/]([0-9]+)/([0-9]+)/([0-9]+)/?$!', $json_event['hgurl']))
  $pos = preg_replace('!.*[:/]([0-9]+)/([0-9]+)/([0-9]+)/?$!', '$1,$2,$3', $json_event['hgurl']);
  else $pos = '128,128,25';

  $fields = array(
    'owneruuid' => EVENTS_NULL_KEY, // Not implemented
    'name' => $json_event['title'],
    // 'eventid' => $json_event['eventid'],
    'creatoruuid' => EVENTS_NULL_KEY, // Not implemented
    'category' => getEventCategory($json_event['categories']),
    'description' => $description,
    'dateUTC' => $start,
    'duration' => $duration,
    'covercharge' => 0, // Not implemented
    'coveramount' => 0, // Not implemented
    'simname' => $slurl,
    'parcelUUID' => EVENTS_NULL_KEY, // Not implemented
    'globalPos' => $pos,
    'eventflags' => 0, // Not implemented
    // 'hash' => $json_event['hash'], // Not implemented, though
  );
  $events[] = $fields;
}

$SearchDB->query("DELETE FROM events");
foreach($events as $event) {
  $query = $SearchDB->prepare("INSERT INTO events
    (owneruuid, name, creatoruuid, category, description, dateUTC, duration, covercharge, coveramount, simname, parcelUUID, globalPos, eventflags )
    VALUES (:owneruuid, :name, :creatoruuid, :category, :description, :dateUTC, :duration, :covercharge, :coveramount, :simname, :parcelUUID, :globalPos, :eventflags )"
  );
  $result = $query->execute($event);
  if(!$result) error_log("error while insert(ing new events)");
}
