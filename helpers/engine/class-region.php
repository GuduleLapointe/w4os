<?php
/**
 * OpenSimulator Region Class - Framework Agnostic
 * 
 * Core region functionality without framework dependencies
 */

class OpenSim_Region {
    protected $uuid;
    protected $item;
    protected $data;
    protected $db;
    protected $server;
    
    // Region properties
    protected $name;
    protected $owner_name;
    protected $owner_uuid;
    protected $serverURI;
    protected $sizeX;
    protected $sizeY;
    protected $flags;
    protected $last_seen;
    protected $presence;
    
    // Connection status
    protected $console_connected = false;
    protected $db_connected = false;

    public function __construct($args = null, $database_connection = null) {
        $this->db = $database_connection;
        
        if (!OpenSim::empty($args)) {
            $this->fetch_region_data($args);
        }
        
        $this->init_server_connection();
    }

    /**
     * Initialize server connection if serverURI is available
     */
    protected function init_server_connection() {
        if (!empty($this->item->serverURI)) {
            // This will be implemented by framework-specific classes
            // as server connection classes may be framework-dependent
        }
    }

    /**
     * Fetch region data from database
     */
    public function fetch_region_data($args) {
        if (OpenSim::empty($args)) {
            return;
        }

        if (is_object($args)) {
            $this->uuid = $args->uuid;
            $this->item = $args;
        } elseif (is_string($args) && is_uuid($args)) {
            $this->uuid = $args;
            if ($this->db) {
                $query = $this->get_main_query() . ' WHERE uuid = ?';
                $this->item = $this->db->get_row($query, [$this->uuid]);
            }
        }
        
        if ($this->item) {
            $this->populate_properties();
        }
    }

    /**
     * Get the main query for fetching region data
     */
    protected function get_main_query() {
        return "SELECT regions.*, 
                CONCAT(UserAccounts.FirstName, ' ', UserAccounts.LastName) AS owner_name,
                sizeX * sizeY AS size,
                (SELECT COUNT(*) FROM Presence WHERE Presence.RegionID = regions.uuid) AS presence
                FROM regions
                LEFT JOIN UserAccounts ON regions.owner_uuid = UserAccounts.PrincipalID";
    }

    /**
     * Populate object properties from item data
     */
    protected function populate_properties() {
        if (!$this->item) return;
        
        $this->name = $this->item->regionName ?? null;
        $this->owner_name = $this->item->owner_name ?? null;
        $this->owner_uuid = $this->item->owner_uuid ?? null;
        $this->serverURI = $this->item->serverURI ?? null;
        $this->sizeX = $this->item->sizeX ?? 256;
        $this->sizeY = $this->item->sizeY ?? 256;
        $this->flags = $this->item->flags ?? 0;
        $this->last_seen = $this->item->last_seen ?? 0;
        $this->presence = $this->item->presence ?? 0;
    }

    /**
     * Get region name
     */
    public function get_name() {
        return $this->name;
    }

    /**
     * Get region UUID
     */
    public function get_uuid() {
        return $this->uuid;
    }

    /**
     * Get region item data
     */
    public function get_item() {
        return $this->item;
    }

    /**
     * Get region size as formatted string
     */
    public function get_size_formatted() {
        if (empty($this->sizeX) || empty($this->sizeY)) {
            return null;
        }
        return $this->sizeX . '×' . $this->sizeY;
    }

    /**
     * Get region flags array
     */
    public function get_flags() {
        return $this->match_flags($this->flags);
    }

    /**
     * Match bitwise flags to labels
     */
    public static function match_flags($bitwise, $flag_definitions = null) {
        if ($flag_definitions === null) {
            // Default OpenSimulator flags
            $flag_definitions = [
                1    => 'Default Region',
                1024 => 'Default HG Region', 
                2    => 'Fallback Region',
                256  => 'Authenticate',
                512  => 'Hyperlink',
                32   => 'Locked Out',
                8    => 'No Direct Login',
                64   => 'No Move',
                16   => 'Persistent',
                128  => 'Reservation',
            ];
        }

        $matches = [];
        foreach ($flag_definitions as $flag => $label) {
            if ($bitwise & $flag) {
                $matches[$flag] = $label;
            }
        }
        return $matches;
    }

    /**
     * Check if region is online by testing server connection
     */
    public function is_online() {
        if (empty($this->serverURI)) {
            return false;
        }

        // Simple HTTP check - can be overridden by framework-specific implementations
        $url = rtrim($this->serverURI, '/') . '/';
        $context = stream_context_create([
            'http' => [
                'timeout' => 5,
                'method' => 'GET'
            ]
        ]);
        
        $result = @file_get_contents($url, false, $context);
        return $result !== false;
    }

    /**
     * Get formatted server URI (hostname:port)
     */
    public function get_server_uri_formatted($use_dns = true) {
        if (empty($this->serverURI)) {
            return null;
        }

        $parts = parse_url($this->serverURI);
        $hostname = $parts['host'];

        // Use DNS if hostname is an IP address
        if ($use_dns && filter_var($hostname, FILTER_VALIDATE_IP)) {
            $resolved = gethostbyaddr($parts['host']);
            if ($resolved !== $parts['host']) {
                $dot_count = substr_count($resolved, '.');
                if ($dot_count > 0 && $dot_count <= 4) {
                    $hostname = $resolved;
                }
            }
        }

        return $hostname . ':' . ($parts['port'] ?? '80');
    }

    /**
     * Get region teleport URI format
     */
    public function get_tp_uri($gateway = null) {
        if (!$this->name || !$gateway) {
            return null;
        }
        
        // Strip protocol from gateway
        $gateway = preg_replace('/^https?:\/\//', '', $gateway);
        $gateway = rtrim($gateway, '/') . '/';
        
        return $gateway . $this->name;
    }

    /**
     * Format last seen timestamp
     */
    public function get_last_seen_formatted() {
        if ($this->last_seen === 0) {
            return 'Never';
        }
        return date('Y-m-d H:i:s', $this->last_seen);
    }

    /**
     * Get owner name
     */
    public function get_owner_name() {
        return $this->owner_name;
    }

    /**
     * Get presence count
     */
    public function get_presence() {
        return $this->presence ?? 0;
    }
}
