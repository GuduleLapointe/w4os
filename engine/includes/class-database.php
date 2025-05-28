<?php
/**
 * W4OS Database Engine
 * 
 * Core database functionality that can be used by both WordPress and helpers.
 * This will contain all the database connection and query logic.
 */

class OSPDO extends PDO {
    public $connected = false;
    
    public function __construct($dsn, $username = null, $password = null, $driver_options = null) {
        try {
            @parent::__construct($dsn, $username, $password, $driver_options);
            $this->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $this->connected = true;
        } catch (PDOException $e) {
            error_log("Could not connect to database $dsn as $username");
            $this->connected = false;
        }
    }

    /**
     * Prepare SQL query, execute with params and log error if any
     *
     * @param  string $query
     * @param  array  $params     substitute markers passed to execute()
     * @param  array  $options    options passed to prepare()
     * @return PDOstatement if success, false on error
     */
    public function prepareAndExecute($query, $params = null, $options = array()) {
        $trace = debug_backtrace()[0];
        $trace = $trace['file'] . ':' . $trace['line'];

        $statement = $this->prepare($query, $options);
        $result = $statement->execute($params);

        if ($result) {
            return $statement;
        }

        error_log('Error ' . $statement->errorCode() . ' ' . $statement->errorInfo()[2] . ' ' . $trace);
        return false;
    }

    public function insert($table, $values) {
        $markers = [];
        foreach ($values as $field => $value) {
            $markers[] = ':' . $field;
        }
        $markers = implode(',', $markers);
        $fields = implode(',', array_keys($values));
        $sql = "INSERT INTO $table ($fields) VALUES ($markers)";
        $statement = $this->prepare($sql);
        return $statement->execute($values);
    }

    /**
     * Prepare query and return single value
     */
    public function get_var($query, $params = array()) {
        $statement = $this->prepareAndExecute($query, $params);
        if ($statement) {
            $result = $statement->fetchColumn();
            $statement->closeCursor();
            return $result;
        }
        return false;
    }

    /**
     * WordPress wpdb compatibility method - get single row
     */
    public function get_row($query, $params = array(), $output = OBJECT) {
        $statement = $this->prepareAndExecute($query, $params);
        if ($statement) {
            $result = ($output === ARRAY_A) ? $statement->fetch(PDO::FETCH_ASSOC) : $statement->fetch(PDO::FETCH_OBJ);
            $statement->closeCursor();
            return $result;
        }
        return null;
    }

    /**
     * WordPress wpdb compatibility method - get multiple rows
     */
    public function get_results($query, $params = array(), $output = OBJECT) {
        $statement = $this->prepareAndExecute($query, $params);
        if ($statement) {
            $results = ($output === ARRAY_A) ? $statement->fetchAll(PDO::FETCH_ASSOC) : $statement->fetchAll(PDO::FETCH_OBJ);
            $statement->closeCursor();
            return $results;
        }
        return array();
    }

    /**
     * WordPress wpdb compatibility method - prepare query with placeholders
     */
    public function prepare($query, ...$args) {
        if (empty($args)) {
            return parent::prepare($query);
        }
        
        // Simple WordPress-style placeholder replacement
        $query = str_replace('%s', '?', $query);
        $query = str_replace('%d', '?', $query);
        $query = str_replace('%f', '?', $query);
        
        return parent::prepare($query);
    }

    /**
     * Check if table exists
     */
    public function table_exists($table) {
        if (!$this->connected) {
            return false;
        }
        
        try {
            $result = $this->query("SELECT 1 FROM $table LIMIT 1");
            return $result !== false;
        } catch (Exception $e) {
            return false;
        }
    }

    /**
     * Check if multiple tables exist
     */
    public function tables_exist($tables) {
        if (is_string($tables)) {
            $tables = array($tables);
        }
        
        foreach ($tables as $table) {
            if (!$this->table_exists($table)) {
                return false;
            }
        }
        return true;
    }
}
