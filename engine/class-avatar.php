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
    public $Email;
    public $AvatarName;
    public $AvatarHGName;
    public $AvatarSlug;
    public $ProfilePictureUUID;
    protected static $slug;
    protected static $profile_page_url;

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
		$this->db = new OSPDO( W4OS_DB_ROBUST );
		self::$slug     = get_option( 'w4os_profile_slug', 'profile' );
		self::$profile_page_url = get_home_url( null, self::$slug );

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
        
        if( is_uuid($args ) ) {
            $uuid = $args;
        } else if ( is_array( $args ) ) {
            $uuid = ( is_uuid( $args['uuid'] ) ? $args['uuid'] : false );
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
            $query .= " WHERE PrincipalID = ?";
            $stmt = $this->db->prepare( $query );
            $stmt->execute( array( $uuid ) );
            $avatar_row = $stmt->fetch();
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
                // External grid avatar - set basic properties
                $this->UUID = '00000000-0000-0000-0000-000000000000'; // W4OS_NULL_KEY equivalent
                $this->FirstName = $firstname;
                $this->LastName = $lastname;
                $this->AvatarName = trim( "$this->FirstName $this->LastName" );
                $this->AvatarHGName = "$firstname.$lastname@$grid";
                // Note: grid_info lookup would need to be handled by calling code
                return;
            } else {
                $query .= " WHERE FirstName = ? AND LastName = ?";
                $stmt = $this->db->prepare( $query );
                $stmt->execute( array( $firstname, $lastname ) );
                $avatar_row = $stmt->fetch();
            }
        } else {
            return false;
        }

        if ( $avatar_row ) {
            $this->UUID = $avatar_row['PrincipalID'];
            $this->FirstName = $avatar_row['FirstName'];
            $this->LastName  = $avatar_row['LastName'];
            $this->AvatarName = trim( "$this->FirstName $this->LastName" );
            $this->Created = $avatar_row['Created'];
            $this->AvatarSlug = strtolower( "$this->FirstName.$this->LastName" );
            
            // Set grid-specific properties (would need grid info from calling code)
            $this->AvatarHGName = $this->AvatarSlug . '@' . (defined('OPENSIM_GRID_NAME') ? OPENSIM_GRID_NAME : 'localhost');
            
            $this->ProfilePictureUUID = $avatar_row['ProfilePictureUUID'] ?? '00000000-0000-0000-0000-000000000000';
            $this->profileLanguages   = $avatar_row['profileLanguages'] ?? '';
            $this->profileAboutText   = $avatar_row['profileAboutText'] ?? '';
            $this->profileImage       = $avatar_row['profileImage'] ?? '';
            $this->profileFirstImage  = $avatar_row['profileFirstImage'] ?? '';
            $this->profileFirstText   = $avatar_row['profileFirstText'] ?? '';
            $this->profilePartner     = $avatar_row['profilePartner'] ?? '';
            $this->Email              = $avatar_row['Email'] ?? '';

            $this->data = (object) $avatar_row; // Convert array to object for compatibility
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
        } elseif ( is_uuid( $item ) ) {
            return _('get_name missing DB implementation');
            
            // TODO: adapt for OSPDO database connection

            // $uuid = $item;
            // global $w4osdb;
            // $query  = "SELECT CONCAT(FirstName, ' ', LastName) AS Name FROM UserAccounts WHERE PrincipalID = %s";
            // $result = $w4osdb->get_var( $w4osdb->prepare( $query, $uuid ) );
            // if ( $result && ! is_wp_error( $result ) ) {
            //     return esc_html( $result );
            // }
        }
        return _( 'Unknown Avatar' );
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
		$avatar = new W4OS3_Avatar( $key );

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
            return _('God');
        } elseif ( $level >= 150 ) {
            return _('Liaison');
        } elseif ( $level >= 100 ) {
            return _('Customer Service');
        } elseif ( $level >= 1 ) {
            return _('God-like');
        }
        return '';
    }
}
