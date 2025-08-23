<?php
/**
 * HYPEvents class
 *
 * This class handles all processing related to HYPEvents, including
 * fetching, parsing, and formatting events output.
 * 
 * TODO: return errors in a structured way for eventsparser.php to handle them
 *       (e.g. as an array of errors, or as an exception).
 * TODO: let server breath, make sure we take pauses or do whatever is needed
 *       to avoid overloading the server with too many requests.
 * TODO: prevent unauthorized external access (with IP, secret key, etc.)
 * TODO: prevent concurrent processing (keep track of running processes
 *       and prevent multiple instances from running at the same time).
 * TODO: In a medium term, this class will also include development made 
 *       in 2do directory and other projects related to HYPEvents.
 *
 * @package     magicoli/opensim-helpers
 * @author      Gudule Lapointe <gudule@speculoos.world>
 * @link            https://github.com/magicoli/opensim-helpers
 * @license     AGPLv3
 */

// $db is obtained with OpenSim_Search::db() method, which handles connection
// and returns the search database object or false on failure.
// 
// No globals or global constants used (except Helpers::HypEvents::NULL_KEY).
// Settings are obtained from Engine_Settings::get() method exclusively.

// The class will be called specifically for parsing events, or can be used
// by other scripts for more advanced processing. So the class does not
// parse the events on its own, but provides methods to do so.

class OpenSim_HypEvents {
    private static $enabled;

    private static $db;
    private static $hypevents_url;
    private static $categories;
    private static $events_table;

    public const NULL_KEY = '00000000-0000-0000-0000-000000000001';
    private const THRESHOLD =  3600; // 1 hour

    public function __construct() {
        $this->init();
    }

    /**
     * Set properties for the HYPEvents class.
     *
     * This method initializes the categories and other properties
     * needed for processing HYPEvents.
     */
    private static function init() {
        self::db(); // Initialize the search database connection
        if ( ! self::$db ) {
            error_log( '[ERROR] Failed to connect to the search database.' );
            self::$enabled = false;
        }

        self::$hypevents_url = Engine_Settings::get( 'engine.Search.HypeventsUrl' );
        if (empty( self::$hypevents_url )) {
            error_log( '[ERROR] HYPEvents URL is not set.' );
            self::$enabled = false;
        }

        self::$events_table = Engine_Settings::get('engine.Search.SearchEventsTable', 'events');

        // If nothing above has set Enabled to false, we are good to set it to true.
        if( self::$enabled !== false ) {
            self::$enabled = true;
        }

        // Initialize categories
        self::$categories = array(
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
            'fair'                    => 23, // ambiguous, could mean 23 Nightlife, or 27 Art, or 28 Charity
            'lecture'                 => 27, // Art & Culture
            'litterature'             => 27, // Art & Culture
            'music'                   => 20, // Live Music
            'roleplay'                => 24, // Games/Contests
            'social'                  => 28, // Charity / Support Groups
        );
    }

    /**
     * Get the search database connection.
     *
     * This method returns the search database OpenSim_Database object (PDO).
     * If the connection fails, it returns false.
     *
     * @return OpenSim_Search|false The search database connection or false on failure.
     */
    private static function db() {
        if( self::$db || self::$db === false ) {
            return self::$db;
        }

        self::$db = OpenSim_Search::db();
        if ( self::$db ) {
            return self::$db;
        }

        self::$db = false;
        return false;
    }

    /**
     * Parse and store events from HYPEvents.
     *
     * This method fetches events from the HYPEvents URL, parses them,
     * and stores them in the search database.
     * 
     * @return array Standardized response with success/error status and data
     */
    public static function parse() {
        self::init();
        
        if( ! self::$enabled ) {
            return osError('HYPEvents is not enabled - check database connection and HypeventsUrl setting', 503); // Service Unavailable
        }

        $json_url = self::$hypevents_url . '/events.json';
        error_log( '[NOTICE] ' . __METHOD__ . ' fetching events from ' . $json_url );
        $json = file_get_contents( $json_url );
        if ( $json === false ) {
            return osError('Failed to fetch events from ' . $json_url, 502); // Bad Gateway
        }
        
        $raw_events = json_decode( $json, true );
        if ( ! $raw_events ) {
            return osError('Invalid JSON received from ' . $json_url, 502); // Bad Gateway
        }    
 
        // Processing can be long, free the http connection and continue in the background.
        dontWait();

        error_log( '[NOTICE] ' . __METHOD__ . ' fetched ' . count( $raw_events ) . ' events.' );
        
        // Dev only: shrink for testing
        // $raw_events = array_slice( $raw_events, 0, 5 );
        // error_log('[WARNING] ' . __METHOD__ . ' processing only ' . count( $raw_events ) . ' events for testing purposes.' );

        // Process each event (this is the most time-consuming part)
        $start_time = microtime( true );
        $hypevents = array();
        foreach ( $raw_events as $hypevent ) {
            $processed_event = self::processEvent( $hypevent );
            if ($processed_event) {
                $hypevents[] = $processed_event;
            }
        }
        
        $hypevents = array_filter( $hypevents );
        
        // TODO: instead of deleting all events, we should mark them
        // as being processed, and only delete those that are not left
        // in the new list.
        $delete_result = self::$db->query( 'DELETE FROM ' . self::$events_table );
        if ( $delete_result === false ) {
            return osError('Failed to clear existing events from database', 500); // Internal Server Error
        }

        // TODO: use a prepared statement to avoid SQL injection
        // TODO: use BEGIN TRANSACTION to allow rollback in case of error
        $inserted_count = 0;
        $failed_count = 0;
        foreach ( $hypevents as $event ) {
            $result = self::$db->insert( self::$events_table, $event );
            if ( $result ) {
                $inserted_count++;
            } else {
                $failed_count++;
                error_log( '[ERROR] Failed to insert event: ' . print_r($event, true) );
            }
        }
        
        // Only fail if no events were inserted
        if ( $failed_count > 0 && $inserted_count === 0 ) {
            return osError("Failed to insert $failed_count out of " . count($hypevents) . " events", 500);
        }
        
        $end_time = microtime( true );
        $total_time = round( $end_time - $start_time, 2 ); // in seconds
        $average_time = count($hypevents) > 0 ? $total_time / count( $hypevents ) : 0;
        
        $summary = array(
            'fetched_count' => count( $raw_events ),
            'processed_count' => count( $hypevents ),
            'inserted_count' => $inserted_count,
            'failed_count' => $failed_count,
            'total_time' => $total_time,
            'average_time' => $average_time
        );
        
        error_log( '[NOTICE] ' . __METHOD__ . ' processed ' . count( $hypevents ) . ' events in ' . $total_time . ' seconds (' . $average_time . '/event).' );
        
        return osSuccess('Events parsed successfully', $summary);
    }

    /**
     * Get Event Category
     * 
     * Returns the category ID for a given event first known category ID
     * from a category short name or a list of category names.
     * 
     * @param string|array $category The category name or an array of category names.
     * @return int The category ID or 0 if no valid category is found.
     */
    public static function get_category( $values ) {
        if ( empty( $values ) ) {
            return 0; // Undefined
        }
        if ( ! is_array( $values ) ) {
            $values = array( $values );
        }
        foreach ( $values as $value ) {
            if( is_numeric( $value ) ) {
                // If it's a number, return it directly if valid
                if( in_array( $value, self::$categories ) ) {
                    return (int) $value;
                }
                continue; // Skip to next value
            }

            $value = strtolower( trim( $value ) );
            if ( isset( self::$categories[ $value ] ) ) {
                return self::$categories[ $value ];
            }
        }
        return 29; // Miscellaneous as default category
    }

    /**
     * Process a single event.
     *
     * This method processes a single event, extracting relevant information
     * and storing it in the search database.
     *
     * @param array $hypevent The event data to process.
     */
    private static function processEvent( $hypevent ) {
        $now = time();
        $notbefore = $now - self::THRESHOLD;
        $start = strtotime( $hypevent['start'] );
        if ( ! $start || $start < $notbefore ) {
            // Ignore ongoing events that started too long ago
            return;
        }

        $end      = strtotime( $hypevent['end'] );
        if( $end < $now ) {
            // Ignore past events
            return;
        }
        $duration = ( $end > $start ) ? round( ( strtotime( $hypevent['end'] ) - $start ) / 60 ) : 60;
        $duration = ( $duration > 0 ) ? $duration : 60;

        $online = opensim_region_is_online( $hypevent['hgurl'] );
        if ( ! $online ) {
            return; // ignore event if region is malformatted or unreachable
        }

        $region     = opensim_sanitize_uri( $hypevent['hgurl'], '', true );
        $get_region = opensim_get_region( $hypevent['hgurl'] );
        $pos        = ( empty( $region['pos'] ) ) ? array( 128, 128, 25 ) : explode( '/', $region['pos'] );
        if ( ! empty( $get_region['x'] ) & ! empty( $get_region['y'] ) ) {
            $pos[0] += $get_region['x'];
            $pos[1] += $get_region['y'];
        }
        $pos = implode( ',', $pos );

        $slurl       = opensim_format_tp( $hypevent['hgurl'], TPLINK_TXT );
        $links       = opensim_format_tp( $hypevent['hgurl'], TPLINK_APPTP + TPLINK_HOP );
        $description = strip_tags( html_entity_decode( utf8_encode( utf8_decode( $hypevent['description'] ) ) ) );
        $description = "$links\n\n$description";
        // $title = utf8_encode(utf8_decode($hypevent['title']));
        $title = strip_tags( utf8_encode( utf8_decode( $hypevent['title'] ) ) );

        $fields = array(
            'owneruuid'     => self::NULL_KEY, // Not implemented
            'name'          => $title,
            // 'eventid' => $hypevent['eventid'],
            'creatoruuid'   => self::NULL_KEY, // Not implemented
            'category'      => self::get_category( $hypevent['categories'] ),
            'description'   => $description,
            'dateUTC'       => $start,
            'duration'      => $duration,
            'covercharge'   => 0, // Not implemented
            'coveramount'   => 0, // Not implemented
            'simname'       => $slurl,
            'parcelUUID'    => self::NULL_KEY, // Not implemented
            'globalPos'     => $pos,
            'eventflags'    => 0, // Not implemented
            'gatekeeperURL' => $region['gatekeeper'],
        // 'hash' => $hypevent['hash'], // Not implemented, though
        );

        return $fields;
    }
}
