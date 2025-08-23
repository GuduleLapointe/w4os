<?php
/**
 * Grid class for OpenSimulator Helpers
 */
if( ! defined( 'OSHELPERS' ) ) {
    exit;
}

require_once( dirname(__DIR__) . '/includes/functions.php' );

class OpenSim_Grid {
    private static $grid_stats;
    private $grid_info;
    private $grid_info_card;
    private $grid_stats_card;
    private static $labels = array();

    public function __construct() {
        $this->constants();
        // $this->grid_stats = $this->get_grid_stats();
        // $this->grid_info = $this->get_grid_info();
        // $this->grid_info_card = $this->get_grid_info_card();
        // $this->grid_stats_card = $this->get_grid_stats_card();
    }

    public function constants() {
        self::$labels = array(
            'status' => _('Status'),
            'members' => _('Members'),
            'active_members' => _('Active members (30 days)'),
            'members_in_world' => _('Members in world'),
            'active_users' => _('Active users (30 days)'),
            'total_users' => _('Total users in world'),
            'regions' => _('Regions'),
            'total_area' => _('Total area'),
        );
    }

    public static function get_grid_info( $grid_uri = false, $args = array() ) {
        // global $oshelpers_cache;

        $key = __METHOD__ . md5( serialize( func_get_args() ) );
        $grids_info = os_cache_get( 'grids_info', array() );
        if( isset( $grids_info[$key] ) ) {
            $info = $grids_info[$key];
            if( ! empty( $info['gridname'] ) ) {
                return $grids_info[$key];
            }
        }

        $HomeURI = OpenSim::get_option( 'Hypergrid.HomeURI' );
        $is_local_grid = ( ! $grid_uri || $grid_uri === $HomeURI );
        if( $is_local_grid ) {
            $grid_uri = $HomeURI;
            if( empty( $grid_uri ) ) {
                return false;
            }
        }

        // If gridname not found, only try once to fetch grid info again
        $info = os_cache_get( 'grid_info_' . $key, false );
        if( $info ) {
            return $info;
        }

        $info = array();

        // Fetch live info from grid using $login_uri/get_grid_info and parse xml result in array
        // Example xml result:
        $xml_url = $grid_uri . '/get_grid_info';
        try {
            $xml = @simplexml_load_file( $xml_url );
        } catch( Exception $e ) {
            $xml = false;
        }
        if( ! $xml ) {
            $info = array(
                'online' => false,
                'login' => $grid_uri,
            );
        } else {
            $info['online'] = true;
            try {
                $array = (array) $xml;
                if( ! $array ) {
                    throw new Exception( 'Error parsing grid info.' );
                }
                $info = array_merge( $info, $array );
            } catch( Exception $e ) {
                return $e;
            }
        }

        if( $is_local_grid ) {
            $config_info = OpenSim::get_option( 'GridInfoService' );
            if( is_array( $config_info ) ) {
                $info = array_merge( $config_info, $info );
            }
        }

        if(empty($info)) {
            $info = false;
        }

        // $oshelpers_cache[$key] = $info;
        $grids_info[$key] = $info;
        os_cache_set( 'grids_info', $grids_info );
        if( is_array( $info ) && empty( $info['gridname'] ) ) {
            $info['gridname'] = opensim_format_tp( $grid_uri, TPLINK_TXT );
        }
        os_cache_set( 'grid_info_' . $key, $info );
        return $info;
    }

    public static function array_to_card( $id, $info, $args = array() ) {
        if( empty( $info ) ) {
            return;
        }
        $hide_first = '';
        if( ! empty( $args['title'] ) && is_string( $args['title'] ) ) {
            $title = $args['title'];
        } else {
            $title = array_values( $info )[0];
            if( is_numeric( array_keys( $info )[0] ) || ( isset($args['hide_first']) && $args['hide_first'] === true ) ) {
                $hide_first = 'hidden d-none';
            }
        }

        $collapse_head = '';
        $collapse_class = '';
        $collapse_data = '';
        $html = sprintf(
            '<div id="card-%1$s" class="accordion flex-fill">
                <div class="accordion-item card card-$1$s bg-primary">
                    <h5 class="card-title p-0 m-0 accordion-header" %4$s>
                        <button class="accordion-button p-3" data-bs-toggle="collapse" href="#card-list-%1$s" aria-expanded="true" aria-controls="collapse-card-list-%1$s">
                            %2$s
                        </button>
                    </h5>
                <ul id="card-list-%s" class="list-group list-group-flush accordion-collapse collapse show" data-bs-parent="#card-%1$s">',
            $id,
            $title,
            $collapse_head,
            $collapse_class,
            $collapse_data
        );

        foreach( $info as $key => $value ) {
            $html .= sprintf(
                '
                <li class="list-group-item %2$s">
                %3$s %4$s
                </li>',
                $id,
                $hide_first,
                is_numeric( $key ) ? '' : $key . ':',
                $value
            );
            $hide_first = '';
            $class="";
        }
        $html .= '</ul>';
        $html .= '</div>';
        $html .= '</div>';
        return $html;
    }

    /**
     * Get grid information as a card
     */
    public static function grid_info_card( $grid_uri = false, $args = array() ) {
        $grid_info = self::get_grid_info( $grid_uri, $args );
        if( ! $grid_info || OpenSim::is_error( $grid_info ) ) {
            return false;
        }

        $info = array(
            _('Grid Name') => $grid_info['gridname'],
            _('Login URI') => OpenSim::hop( $grid_info['login'] ),
        );

        $title = false;
        if( ! empty( $args['title'])) {
            $title = $args['title'] === true ? _( 'Grid Information' ) : $args['title'];
        } else {
            $title = _( 'Grid Information' );
        }
        return self::array_to_card( 'grid-info', $info, array(
            'title' => $title,
        ) );
    }

    public static function grid_stats_card( $args = null ) {
        $grid_stats = self::get_grid_stats( $args );

        if( ! $grid_stats || OpenSim::is_error( $grid_stats ) ) {
            return false;
        }

        $title = false;
        if( ! empty( $args['title'])) {
            $title = $args['title'] === true ? _( 'Grid Information' ) : $args['title'];
        } else {
            $title = _( 'Grid Status' );
        }

        return self::array_to_card( 'grid-status', $grid_stats, array(
            'title' => $title,
        ) );
    }

    private static function array_to_xml($array, $xml) {
        foreach($array as $key => $value) {
            if(is_array($value)) {
                $subnode = $xml->addChild($key);
                self::array_to_xml($value, $subnode);
            } else {
                $xml->addChild($key, htmlspecialchars($value));
            }
        }
    }

    public static function get_grid_stats( $args = null ) {
        $grid_info = self::get_grid_info();
        if( empty( $grid_info ) ) {
            return false;
        }

        $grid_uri = $grid_info['login'];

        $args = array_merge(array(
            'output' => 'array',
            'title' => true,
        ));

        $stats = array(
            'status' => $grid_info['online'] ? _('Online') : _('Offline'),
        );

        $robust_db = OpenSim::$robust_db;
        if ( ! $robust_db || OpenSim::is_error($robust_db) ) {
            $stats['error'] = _('Database not connected.');
        } else {
            $lastmonth = time() - 30 * 86400;
            $gridonline = $grid_info['online'] ? _('Yes') : _('No');
            
            $filter = '';
            // if ( get_option( 'w4os_exclude_models' ) ) {
            //     $filter .= "u.FirstName != '" . get_option( 'w4os_model_firstname' ) . "'
            //     AND u.LastName != '" . get_option( 'w4os_model_lastname' ) . "'";
            // }
            // if ( get_option( 'w4os_exclude_nomail' ) ) {
            //     $filter .= " AND u.Email != ''";
            // }
            if ( ! empty( $filter ) ) {
                $filter = "$filter AND ";
            }

            $stats = array(
                'status' => $grid_info['online'] ? _('Online') : _('Offline'),
                'members' => $robust_db->get_var( "SELECT COUNT(*)
                    FROM UserAccounts as u WHERE $filter active=1"
                ),
                'active_members' => $robust_db->get_var( "SELECT COUNT(*)
                    FROM GridUser as g, UserAccounts as u 
                    WHERE $filter PrincipalID = UserID AND g.Login > :lastmonth",
                    array(
                        'lastmonth' => $lastmonth,
                    )
                ),
                'members_in_world' => $robust_db->get_var( "SELECT COUNT(*)
                    FROM Presence AS p, UserAccounts AS u
                    WHERE $filter RegionID != '00000000-0000-0000-0000-000000000000'
                    AND p.UserID = u.PrincipalID;"
                ),
                'active_users' => $robust_db->get_var( "SELECT COUNT(*)
                    FROM GridUser WHERE Login > :lastmonth",
                    array(
                        'lastmonth' => $lastmonth,
                    )
                ),
                'total_users' => $robust_db->get_var( "SELECT COUNT(*)
                    FROM Presence WHERE RegionID != '00000000-0000-0000-0000-000000000000';"
                ),
                'regions' => $robust_db->get_var( "SELECT COUNT(*)
                    FROM regions"
                ),
                'total_area' => $robust_db->get_var( "SELECT round(sum(sizex * sizey / 1000000),2)
                    FROM regions" 
                ) . '&nbsp;km²',
            );

            // Replace keys with values of self::$labels
            $labels = self::$labels;
            $labels = array_intersect_key( self::$labels, $stats );
            $stats = array_combine( $labels, $stats );
    
        }

        switch( $args['output'] ) {
            case 'xml':
                $xml = new SimpleXMLElement('<gridstatus/>');
                self::array_to_xml($stats, $xml);
                return $xml->asXML();
            case 'array':
                return $stats;
            default:
                return $stats;
        }   

        return $stats;
    }
}
