<?php
/**
 * W4OS Search Engine
 * 
 * Core search functionality for avatars, regions, events, etc.
 */

class OpenSim_Search
{
    private static $instance = null;
    private static $db;
    private static $db_creds = null;
    
    private static $events_table;
    private static $regions_table;
    private static $hypevents_url;
    private static $tables;

    private function __construct() {
        // Initialize database connection
        self::db();

        // Set custom table names from settings
        self::$events_table = Engine_Settings::get('engine.Search.SearchEventsTable', 'events');
        $this->fix_region_table_name();

        self::$tables = array(
            'allparcels',
            'classifieds',
            self::$events_table,
            'objects',
            'hostsregister',
            'parcels',
            'parcelsales',
            'popularplaces',
            self::$regions_table,
        );

        // Make sure tables exist
        if ( ! self::$db->tables_exist( self::$tables ) ) {
            error_log( 'Creating missing OpenSimSearch tables in search database' );
            $this->create_tables();
        }

        // DB update 1 add gatekeeperURL to existing tables
        if ( ! count( self::$db->query( "SHOW COLUMNS FROM `parcels` LIKE 'gatekeeperURL'" )->fetchAll() ) ) {
            $this->db_update_1();
        }

        // DB update 2 add imageUUID to parcels table
        if ( ! count( self::$db->query( "SHOW COLUMNS FROM `parcels` LIKE 'imageUUID'" )->fetchAll() ) ) {
            $this->db_update_2();
        }
    }

    /**
     * Fixes the region table name based on the database structure.
     * 
     * This method checks if the 'regions' table exists and has a 'uuid' column.
     * If it does, it uses 'regionsregister' as the table name to avoid conflicts
     * with OpenSim's robust regions table.
     * If the 'regions' table does not exist or does not have the 'uuid' column,
     * it uses 'regions' as the table name.
     */
    private function fix_region_table_name() {
        if(! self::db()) {
            error_log('[ERROR] ' . __METHOD__ . ' Database connection is not established.');
            return false;
        }

        $regions_table = Engine_Settings::get('engine.Search.SearchRegionsTable', 'regions');

        if($regions_table == 'regions' && self::$db->tables_exist( array( 'regions' ) ) ) {
            $formatCheck   = self::$db->query( "SHOW COLUMNS FROM regions LIKE 'uuid'" );
            $regions_table = ( $formatCheck->rowCount() == 0 ) ? $regions_table : 'regionsregister';
        }

        self::$regions_table = $regions_table;
        return $regions_table;
    }

    public static function getInstance()
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }    
        return self::$instance;
    }    
    
    public static function db() {
        if( self::$db ) {
            return self::$db;
        }
        if( self::$db === false ) {
            // Don't check again if already failed
            return false;
        }

        self::$db = false; // Reset to false to avoid multiple checks

        // Get SearchDB credentials from settings, fallback to main robust db
        self::$db_creds = Engine_Settings::get('engine.Search.SearchDB');
        
        if (self::$db_creds) {
            self::$db = new OpenSim_Database(self::$db_creds);
        } else {
            self::$db = OpenSim_Robust::db(); // Fallback to Robust database if SearchDB not configured
        }

        if (self::$db) {
            return self::$db;
        }

        error_log('[ERROR] ' . __METHOD__ . ' Database connection failed');
        self::$db = false; // Set to false if connection fails

        return self::$db;
    }

    private function create_tables() {
        if(! self::db() ) {
            error_log('[ERROR] ' . __METHOD__ . ' Database connection failed.');
            return;
        }
        error_log('[NOTICE] ' . __METHOD__ . ' Update schema: creating missing search tables');

        $db = self::$db;

        $table_events = self::$events_table;
        $table_regions = self::$regions_table;

        // Split the large SQL into individual statements
        $sql_statements = [
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
            ) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci",

            "CREATE TABLE IF NOT EXISTS `classifieds` (
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
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci",

            "CREATE TABLE IF NOT EXISTS `$table_events` (
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
                `landingpoint` varchar(35) DEFAULT NULL,
                `parcelName` varchar(255) DEFAULT NULL,
                `mature` enum('true','false') NOT NULL,
                PRIMARY KEY (`eventid`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8 AUTO_INCREMENT=1",

            "CREATE TABLE IF NOT EXISTS `hostsregister` (
                `host` varchar(255) NOT NULL,
                `port` int(5) NOT NULL,
                `register` int(10) NOT NULL,
                `nextcheck` int(10) NOT NULL,
                `checked` tinyint(1) NOT NULL,
                `failcounter` int(10) NOT NULL,
                `gatekeeperURL` varchar(255),
                PRIMARY KEY (`host`,`port`)
            ) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci",

            "CREATE TABLE IF NOT EXISTS `objects` (
                `objectuuid` char(36) NOT NULL,
                `parceluuid` char(36) NOT NULL,
                `location` varchar(255) NOT NULL,
                `name` varchar(255) NOT NULL,
                `description` varchar(255) NOT NULL,
                `regionuuid` char(36) NOT NULL default '',
                `gatekeeperURL` varchar(255),
                PRIMARY KEY  (`objectuuid`,`parceluuid`)
            ) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci",

            "CREATE TABLE IF NOT EXISTS `parcels` (
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
            ) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci",

            "CREATE TABLE IF NOT EXISTS `parcelsales` (
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
            ) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci",

            "CREATE TABLE IF NOT EXISTS `popularplaces` (
                `parcelUUID` char(36) NOT NULL,
                `name` varchar(255) NOT NULL,
                `dwell` float NOT NULL,
                `infoUUID` char(36) NOT NULL,
                `has_picture` tinyint(1) NOT NULL,
                `mature` varchar(10) COLLATE utf8_unicode_ci NOT NULL,
                `gatekeeperURL` varchar(255),
                PRIMARY KEY  (`parcelUUID`)
            ) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci",

            "CREATE TABLE IF NOT EXISTS `$table_regions` (
                `regionname` varchar(255) NOT NULL,
                `regionUUID` char(36) NOT NULL,
                `regionhandle` varchar(255) NOT NULL,
                `url` varchar(255) NOT NULL,
                `owner` varchar(255) NOT NULL,
                `owneruuid` char(36) NOT NULL,
                `gatekeeperURL` varchar(255),
                PRIMARY KEY  (`regionUUID`)
            ) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci"
        ];

        foreach ($sql_statements as $sql) {
            try {
                $result = $db->exec($sql);
                if ($result === false) {
                    error_log('[ERROR] ' . __METHOD__ . ' Failed to execute SQL: ' . $sql);
                    return false;
                }
            } catch (Exception $e) {
                error_log('[ERROR] ' . __METHOD__ . ' Exception executing SQL: ' . $e->getMessage());
                return false;
            }
        }

        return true;
    }

    private function db_update_1() {
        if(! self::db() ) {
            error_log('[ERROR] ' . __METHOD__ . ' Database connection failed.');
            return;
        }
        error_log('[NOTICE] ' . __METHOD__ . ' Update schema: add gatekeeperURL column to existing tables');

        foreach ( self::$tables as $table ) {
            if ( ! count( self::$db->query( "SHOW COLUMNS FROM `$table` LIKE 'gatekeeperURL'" )->fetchAll() ) ) {
                self::$db->query( "ALTER TABLE $table ADD gatekeeperURL varchar(255)" );
            }
        }
    }

    function ossearch_db_update_2() {
        if(! self::db() ) {
            error_log('[ERROR] ' . __METHOD__ . ' Database connection failed.');
            return;
        }
        error_log('[NOTICE] ' . __METHOD__ . ' Update schema: add imageUUID column to parcels table');

        if ( ! count( self::$db->query( "SHOW COLUMNS FROM `parcels` LIKE 'imageUUID'" )->fetchAll() ) ) {
            self::$db->query( 'ALTER TABLE parcels ADD imageUUID char(36)' );
        }
    }

    static function rating_flags_to_query( $flags, $table = '' ) {
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

        return OSPDO::join_query_conditions($terms, 'OR');
    }

    public static function unregister_host( $hostname, $port ) {
        // Ensure database connection is established
        if(! self::db()) {
            error_log('[ERROR] ' . __METHOD__ . ' Database connection failed.');
            return;
        }
        error_log('[DEBUG] ' . __METHOD__ . " Unregistering host: $hostname:$port");

        self::$db->prepareAndExecute(
            'DELETE FROM hostsregister
        WHERE host = :host AND port = :port',
            array(
                'host' => $hostname,
                'port' => $port,
            )
        );

        $query = self::$db->prepareAndExecute( 'SELECT regionUUID FROM ' . self::$regions_table . ' WHERE url = ?', array( "http://$hostname:$port/" ) );
        if ( $query ) {
            $regions = $query->fetchAll();
            foreach ( $regions as $region ) {
                $regionUUID = $region[0];
                self::$db->prepareAndExecute( 'DELETE pop FROM popularplaces AS pop INNER JOIN parcels AS par ON pop.parcelUUID = par.parcelUUID WHERE regionUUID = ?', array( $regionUUID ) );
                self::$db->prepareAndExecute( 'DELETE FROM parcels WHERE regionUUID = ?', array( $regionUUID ) );
                self::$db->prepareAndExecute( 'DELETE FROM allparcels WHERE regionUUID = ?', array( $regionUUID ) );
                self::$db->prepareAndExecute( 'DELETE FROM parcelsales WHERE regionUUID = ?', array( $regionUUID ) );
                self::$db->prepareAndExecute( 'DELETE FROM objects WHERE regionuuid = ?', array( $regionUUID ) );
                self::$db->prepareAndExecute( 'DELETE FROM ' . self::$regions_table . ' WHERE regionUUID = ?', array( $regionUUID ) );
            }
        }
    }
}
