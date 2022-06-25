<?php

/**
 * New database class using PDO, replaces DB class using mysqli
 */
class OSPDO extends PDO {
	public function __construct($dsn, $username=null, $password=null, $driver_options=null)
	{
		try {
			@parent::__construct($dsn, $username, $password, $driver_options);
			$this->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
			$this->connected = true;
		}
		catch(PDOException $e)
		{
			error_log("Could not connect to database $dsn as $username");
		  // error_log($e);
			$this->connected = false;
		}
	}

	/**
	 * Prepare SQL query, execute with params and log error if any
	 * @param  string $query
	 * @param  array  $options  options passed to prepare()
	 * @param  array 	$params		substitute markers passed to execute()
	 * @return PDOstatement if success, false on error
	 */
	public function prepareAndExecute($query, $params = NULL, $options = []) {
		$statement = $this->prepare($query, $options);
		$result = $statement->execute($params);

		if($result) return $statement;

		$trace = debug_backtrace()[0];
		$trace = $trace['file'] . ':' . $trace['line'];
		error_log('Error ' . $statement->errorCode() . ' ' . $statement->errorInfo()[2] . ' ' . $trace);
		return false;
	}

	public function insert($table, $values) {
		foreach ($values as $field => $value) {
			$markers[] = ':' . $field;
		}
		$markers = implode(',', $markers);
		$fields = implode(',', array_keys($values));
		$sql = "INSERT INTO $table ($fields) VALUES ($markers)";
		$statement = $this->prepare($sql);
		return $statement->execute($values);
	}
}

function tableExists($pdo, $tables) {
	if(!is_object($pdo)) return false;
	if(!$pdo->connected) return false;
	// error_log("pdo " . print_r($pdo, true));

  if(is_string($tables)) $tables=array($tables);
  foreach($tables as $table) {
    // Try a select statement against the table
    // Run it in try/catch in case PDO is in ERRMODE_EXCEPTION.
    try {
      $result = $pdo->query("SELECT 1 FROM $table LIMIT 1");
    } catch (Exception $e) {
      error_log(__FILE__ . ": " . SEARCH_DB_NAME . " is missing table $table" );
      // We got an exception == table not found
      return false;
    }
    if($result == false) {
      error_log(__FILE__ . ": " . SEARCH_DB_NAME . " is missing table $table" );
      return false;
    }
  }
  return true;
}

if(defined('OPENSIM_DB') && OPENSIM_DB === true)
$OpenSimDB = new OSPDO('mysql:host=' . OPENSIM_DB_HOST . ';dbname=' . OPENSIM_DB_NAME, OPENSIM_DB_USER, OPENSIM_DB_PASS);
