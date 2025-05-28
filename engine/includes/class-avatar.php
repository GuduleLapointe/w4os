<?php
/**
 * OpenSimulator Avatar Engine
 * 
 * Core avatar functionality moved from W4OS3_Avatar (non-WordPress specific)
 */

class OpenSim_Avatar {
    public $UUID;
    public $FirstName;
    public $LastName;

    protected $db;
    protected $data;    
    protected static $base_query = "SELECT * FROM (
        SELECT *, CONCAT(FirstName, ' ', LastName) AS avatarName, GREATEST(Login, Logout) AS last_seen
        FROM UserAccounts 
        LEFT JOIN userprofile ON PrincipalID = userUUID 
        LEFT JOIN GridUser ON PrincipalID = UserID
    ) AS subquery";

    public function __construct() {
        // Initialize the custom database connection with credentials
        if (class_exists('W4OS_WPDB')) {
            $this->db = new W4OS_WPDB( W4OS_DB_ROBUST );
        }

        $args = func_get_args();
        if ( ! empty( $args[0] ) ) {
            $this->initialize_avatar( $args[0] );
        }
    }

    /**
     * Initialize the avatar object.
     */
    private function initialize_avatar( $args ) {
        if ( ! $this->db ) {
            return false;
        }
        if( empty( $args ) ) {
            return false;
        }

        $query = self::$base_query;
        
        if( W4OS3::is_uuid($args ) ) {
            $uuid = $args;
        } else if ( is_array( $args ) ) {
            $uuid = ( W4OS3::is_uuid( $args['uuid'] ) ? $args['uuid'] : false );
        } else if( is_object( $args )) {
            $user = $args;
            $avatars = self::get_avatars_by_email( $user->user_email );
            if( count( $avatars ) > 0 ) {
                $uuid = key( $avatars );
            } else {
                $uuid = false;
            }
        } else {
            $uuid = false;
        }

        if( $uuid !== false ) {
            // $uuid = $args;
            $query .= " WHERE PrincipalID = %s";
            $sql = $this->db->prepare( $query, array( $uuid ) );
            $avatar_row = $this->db->get_row( $sql );
        } else if ( is_string( $args ) ) {
            $parts = explode( '@', $args );
            $grid = $parts[1] ?? null;
            $name = preg_replace('/\s+/', '.', $parts[0]);
            $parts = explode('.', $name);
            if ( count($parts) < 2 ) {
                return false;
            }
            $firstname = $parts[0];
            $lastname = $parts[1];
            if( isset( $grid ) ) {
                $grid_info = W4OS3::grid_info( $grid );
                // $avatar_row = new stdClass();
                $this->UUID = W4OS_NULL_KEY;
                $this->FirstName = $firstname;
                $this->LastName = $lastname;
                $this->AvatarName = trim( "$this->FirstName $this->LastName" );
                $this->AvatarHGName = "$firstname.$lastname@$grid";
                $this->externalProfileURL = self::get_profile_url();
                $this->grid_info = $grid_info;
                return;
            } else {
                $query .= " WHERE FirstName = %s AND LastName = %s";
                $sql = $this->db->prepare( $query, array ( $firstname, $lastname ) );
                $avatar_row = $this->db->get_row( $sql );
            }
        } else {
            return false;
        }

        if ( $avatar_row ) {
            $this->UUID = $avatar_row->PrincipalID;
            $this->FirstName = $avatar_row->FirstName;
            $this->LastName  = $avatar_row->LastName;
            $this->AvatarName = trim( "$this->FirstName $this->LastName" );
            $this->Created = $avatar_row->Created;

            // $this->Created = esc_attr(get_the_author_meta( 'w4os_created', $id ));
            $this->AvatarSlug         = strtolower( "$this->FirstName.$this->LastName" );
            $this->AvatarHGName       = $this->AvatarSlug . '@' . esc_attr( get_option( 'w4os_login_uri' ) );
            $this->ProfilePictureUUID = $avatar_row->ProfilePictureUUID ?? W4OS_NULL_KEY;
            $this->profileLanguages   = $avatar_row->profileLanguages;
            $this->profileAboutText   = $avatar_row->profileAboutText;
            $this->profileImage	   	  = $avatar_row->profileImage;
            $this->profileFirstImage  = $avatar_row->profileFirstImage;
            $this->profileFirstText   = $avatar_row->profileFirstText;
            $this->profilePartner     = $avatar_row->profilePartner;
            $this->Email			  = $avatar_row->Email;

            $this->data      = $avatar_row; // Dev only, shoudn't be use once the class is fully implemented
        }
    }

    public function get_data() {
        return $this->data;
    }

    public function uuid() {
        return $this->UUID ?? false;
    }

    public function FirstName() {
        return $this->FirstName ?? '';
    }

    public function LastName() {
        return $this->LastName ?? '';
    }

    public function Name() {
        return trim( $this->FirstName . ' ' . $this->LastName );
    }

    public function Email() {
        return $this->Email ?? '';
    }

    public static function get_name( $item ) {
        if ( is_object( $item ) ) {
            $uuid = $item->PrincipalID;
            if ( isset( $item->avatarName ) ) {
                return trim( $avatarName = $item->avatarName );
            } elseif ( isset( $item->FirstName ) && isset( $item->LastName ) ) {
                return trim( $item->FirstName . ' ' . $item->LastName );
            }
            return __( 'Invalid Avatar Object', 'w4os' );
        } elseif ( opensim_isuuid( $item ) ) {
            $uuid = $item;
            global $w4osdb;
            $query  = "SELECT CONCAT(FirstName, ' ', LastName) AS Name FROM UserAccounts WHERE PrincipalID = %s";
            $result = $w4osdb->get_var( $w4osdb->prepare( $query, $uuid ) );
            if ( $result && ! is_wp_error( $result ) ) {
                return esc_html( $result );
            }
        }
        return __( 'Unknown Avatar', 'w4os' );
    }

    static function get_user_avatar( $user_id = null ) {
        if ( empty( $user_id ) ) {
            $user = wp_get_current_user();
        } else {
            $user = get_user_by( 'ID', $user_id );
        }

        $avatars = self::get_avatars( array( 'Email' => $user->user_email ) );
        if ( empty( $avatars ) ) {
            return false;
        }
        $key    = key( $avatars );
        $avatar = new static( $key );

        return $avatar;
    }

    static function get_avatars_by_email( $email ) {
        if ( empty( $email ) ) {
            return array();
        }
        return self::get_avatars( array( 'Email' => $email ) );
    }

    static function get_avatars( $args = array(), $format = OBJECT ) {
        global $w4osdb;
        if ( empty( $w4osdb ) ) {
            return false;
        }

        if( ! isset ( $args['active'] ) ) {
            $args['active'] = true;
        }

        foreach( $args as $arg => $value ) {
            switch( $arg ) {
                case 'Email':
                    $conditions[] = $w4osdb->prepare( 'Email = %s', $value );
                    break;
                case 'active':
                    $conditions[] = 'active = ' . ( $value ? 'true' : 'false' );
                    break;
            }
        }

        $avatars = array();
        $sql    = 'SELECT PrincipalID, FirstName, LastName FROM UserAccounts';
        if( ! empty( $conditions )) {
            $sql .= ' WHERE ' . implode( ' AND ', $conditions );
        }

        $result = $w4osdb->get_results( $sql, $format );
        if ( is_array( $result ) ) {
            foreach ( $result as $avatar ) {
                $avatars[ $avatar->PrincipalID ] = trim( "$avatar->FirstName $avatar->LastName" );
            }
        }
        return $avatars;
    }

    public static function user_level( $item ) {
        if ( is_numeric( $item ) ) {
            $level = intval( $item );
        } else {
            $level = intval( $item->UserLevel );
        }
        if ( $level >= 200 ) {
            return __( 'God', 'w4os' );
        } elseif ( $level >= 150 ) {
            return __( 'Liaison', 'w4os' );
        } elseif ( $level >= 100 ) {
            return __( 'Customer Service', 'w4os' );
        } elseif ( $level >= 1 ) {
            return __( 'God-like', 'w4os' );
        }
    }
}
