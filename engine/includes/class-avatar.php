<?php
/**
 * OpenSimulator Avatar Engine
 * 
 * Framework-agnostic avatar functionality including profile management, 
 * database operations, and core avatar logic.
 */

if (!defined('ABSPATH') && !defined('OPENSIM_ENGINE')) {
    exit('Direct access not allowed');
}

class Avatar
{
    private $db;
    public $UUID;
    public $FirstName;
    public $LastName;
    public $AvatarName;
    public $Created;
    public $Email;
    public $ProfilePictureUUID;
    public $profileLanguages;
    public $profileAboutText;
    public $profileImage;
    public $profileFirstImage;
    public $profileFirstText;
    public $profilePartner;
    private $data;
    
    private static $base_query = "SELECT * FROM (
        SELECT *, CONCAT(FirstName, ' ', LastName) AS avatarName, GREATEST(Login, Logout) AS last_seen
        FROM UserAccounts 
        LEFT JOIN userprofile ON PrincipalID = userUUID 
        LEFT JOIN GridUser ON PrincipalID = UserID
    ) AS subquery";

    public function __construct($args = null) {
        // Use global database connection if available
        global $OpenSimDB;
        if (!empty($OpenSimDB) && $OpenSimDB->connected) {
            $this->db = $OpenSimDB;
        }
        
        if (!empty($args)) {
            $this->initialize_avatar($args);
        }
    }

    /**
     * Initialize the avatar object from various input types
     */
    private function initialize_avatar($args) {
        if (!$this->db || !$this->db->connected) {
            return false;
        }
        
        if (empty($args)) {
            return false;
        }

        $query = self::$base_query;
        
        if (OpenSim::is_uuid($args)) {
            $uuid = $args;
        } elseif (is_array($args)) {
            $uuid = (OpenSim::is_uuid($args['uuid']) ? $args['uuid'] : false);
        } elseif (is_object($args)) {
            // Handle user object - would need email lookup
            if (isset($args->user_email)) {
                $avatars = self::get_avatars_by_email($args->user_email);
                if (count($avatars) > 0) {
                    $uuid = key($avatars);
                } else {
                    $uuid = false;
                }
            } else {
                $uuid = false;
            }
        } else {
            $uuid = false;
        }

        if ($uuid !== false) {
            $query .= " WHERE PrincipalID = ?";
            $statement = $this->db->prepare($query);
            $statement->execute([$uuid]);
            $avatar_row = $statement->fetch(PDO::FETCH_OBJ);
        } elseif (is_string($args)) {
            $parts = explode('@', $args);
            $grid = $parts[1] ?? null;
            $name = preg_replace('/\s+/', '.', $parts[0]);
            $parts = explode('.', $name);
            
            if (count($parts) < 2) {
                return false;
            }
            
            $firstname = $parts[0];
            $lastname = $parts[1];
            
            if (isset($grid)) {
                // External grid avatar
                $this->UUID = OpenSim::NULL_KEY;
                $this->FirstName = $firstname;
                $this->LastName = $lastname;
                $this->AvatarName = trim("$this->FirstName $this->LastName");
                // Handle external grid info if needed
                return;
            } else {
                $query .= " WHERE FirstName = ? AND LastName = ?";
                $statement = $this->db->prepare($query);
                $statement->execute([$firstname, $lastname]);
                $avatar_row = $statement->fetch(PDO::FETCH_OBJ);
            }
        } else {
            return false;
        }

        if ($avatar_row) {
            $this->UUID = $avatar_row->PrincipalID;
            $this->FirstName = $avatar_row->FirstName;
            $this->LastName = $avatar_row->LastName;
            $this->AvatarName = trim("$this->FirstName $this->LastName");
            $this->Created = $avatar_row->Created;
            $this->Email = $avatar_row->Email;
            $this->ProfilePictureUUID = $avatar_row->ProfilePictureUUID ?? OpenSim::NULL_KEY;
            $this->profileLanguages = $avatar_row->profileLanguages;
            $this->profileAboutText = $avatar_row->profileAboutText;
            $this->profileImage = $avatar_row->profileImage;
            $this->profileFirstImage = $avatar_row->profileFirstImage;
            $this->profileFirstText = $avatar_row->profileFirstText;
            $this->profilePartner = $avatar_row->profilePartner;
            $this->data = $avatar_row;
        }
    }

    // Getter methods
    public function get_data() {
        return $this->data;
    }

    public function uuid() {
        return $this->UUID ?? false;
    }

    public function first_name() {
        return $this->FirstName ?? '';
    }

    public function last_name() {
        return $this->LastName ?? '';
    }

    public function name() {
        return trim($this->FirstName . ' ' . $this->LastName);
    }

    public function email() {
        return $this->Email ?? '';
    }

    /**
     * Get avatar name from various input types
     */
    public static function get_name($item) {
        if (is_object($item)) {
            if (isset($item->avatarName)) {
                return trim($item->avatarName);
            } elseif (isset($item->FirstName) && isset($item->LastName)) {
                return trim($item->FirstName . ' ' . $item->LastName);
            }
            return 'Invalid Avatar Object';
        } elseif (OpenSim::is_uuid($item)) {
            global $OpenSimDB;
            if (!$OpenSimDB || !$OpenSimDB->connected) {
                return 'Unknown Avatar';
            }
            
            $query = "SELECT CONCAT(FirstName, ' ', LastName) AS Name FROM UserAccounts WHERE PrincipalID = ?";
            $statement = $OpenSimDB->prepare($query);
            $statement->execute([$item]);
            $result = $statement->fetchColumn();
            
            if ($result) {
                return $result;
            }
        }
        return 'Unknown Avatar';
    }

    /**
     * Determine avatar type (user, model, service)
     */
    public function avatar_type($item = null) {
        if (empty($item)) {
            $item = $this->data;
        }

        // Check if it's a model (this would need to be implemented)
        // if (Model::is_model($item)) {
        //     return 'model';
        // }
        
        if (empty($item->Email)) {
            return 'service';
        }
        
        return 'user';
    }

    /**
     * Get user level description
     */
    public static function user_level($item) {
        if (is_numeric($item)) {
            $level = intval($item);
        } else {
            $level = intval($item->UserLevel);
        }
        
        if ($level >= 200) {
            return 'God';
        } elseif ($level >= 150) {
            return 'Liaison';
        } elseif ($level >= 100) {
            return 'Customer Service';
        } elseif ($level >= 1) {
            return 'God-like';
        }
        
        return null;
    }

    /**
     * Get avatars by email address
     */
    public static function get_avatars_by_email($email) {
        if (empty($email)) {
            return array();
        }
        return self::get_avatars(array('Email' => $email));
    }

    /**
     * Get avatars with various filters
     */
    public static function get_avatars($args = array(), $format = PDO::FETCH_OBJ) {
        global $OpenSimDB;
        if (!$OpenSimDB || !$OpenSimDB->connected) {
            return false;
        }

        if (!isset($args['active'])) {
            $args['active'] = true;
        }

        $conditions = array();
        $params = array();
        
        foreach ($args as $arg => $value) {
            switch ($arg) {
                case 'Email':
                    $conditions[] = 'Email = ?';
                    $params[] = $value;
                    break;
                case 'active':
                    $conditions[] = 'active = ' . ($value ? 'true' : 'false');
                    break;
            }
        }

        $avatars = array();
        $sql = 'SELECT PrincipalID, FirstName, LastName FROM UserAccounts';
        
        if (!empty($conditions)) {
            $sql .= ' WHERE ' . implode(' AND ', $conditions);
        }

        $statement = $OpenSimDB->prepare($sql);
        $statement->execute($params);
        $result = $statement->fetchAll($format);
        
        if (is_array($result)) {
            foreach ($result as $avatar) {
                $avatars[$avatar->PrincipalID] = trim("$avatar->FirstName $avatar->LastName");
            }
        }
        
        return $avatars;
    }

    /**
     * Parse wants mask into readable array
     */
    public function wants($item = null, $mask = null, $additionalvalue = null) {
        if (empty($item) && !empty($this->data)) {
            $item = $this->data;
        }
        if (empty($mask)) {
            $mask = $item->profileWantToMask ?? null;
        }
        if (empty($additionalvalue)) {
            $additionalvalue = $item->profileWantToText ?? null;
        }

        return OpenSim::demask(
            $mask,
            array(
                'Build',
                'Explore', 
                'Meet',
                'Group',
                'Buy',
                'Sell',
                'Be Hired',
                'Hire',
            ),
            $additionalvalue
        );
    }

    /**
     * Parse skills mask into readable array
     */
    public function skills($item = null, $mask = null, $additionalvalue = null) {
        if (empty($item) && !empty($this->data)) {
            $item = $this->data;
        }
        if (empty($mask)) {
            $mask = $item->profileSkillsMask ?? null;
        }
        if (empty($additionalvalue)) {
            $additionalvalue = $item->profileSkillsText ?? null;
        }

        return OpenSim::demask(
            $mask,
            array(
                'Textures',
                'Architecture',
                'Event Planning',
                'Modeling',
                'Scripting',
                'Custom Characters',
            ),
            $additionalvalue
        );
    }

    /**
     * Check if avatar is online
     */
    public function is_online() {
        if (empty($this->data->Online)) {
            return false;
        }
        return OpenSim::is_true($this->data->Online);
    }

    /**
     * Check if avatar is active
     */
    public function is_active() {
        return intval($this->data->active ?? 0) === 1;
    }
}
