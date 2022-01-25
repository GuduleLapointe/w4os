<?php

/**
 * New database class using PDO, replaces DB class using mysqli
 */
class OSPDO extends PDO {
	public function __construct($dsn, $username=null, $password=null, $driver_options=null)
	{
		try {
			parent::__construct($dsn, $username, $password, $driver_options);
		  $this->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
		}
		catch(PDOException $e)
		{
			// $message = "Could not connect to the database";
		  header("HTTP/1.0 500 Internal Server Error");
			// echo "500 Internal Server Error\n";
		  error_log($e);
		  die();
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

$OpenSimDB = new OSPDO('mysql:host=' . OPENSIM_DB_HOST . ';dbname=' . OPENSIM_DB_NAME, OPENSIM_DB_USER, OPENSIM_DB_PASS);

/**
 * deprecated, use OSPDO class instead
 */
class DB
{
	var $Host 	  = null;				// Hostname of our MySQL server
	var $Database = null;				// Logical database name on that server
	var $User 	  = null;				// Database user
	var $Password = null;				// Database user's password
	var $Link_ID  = null;				// Result of mysql_connect()/mysqli_connect()
	var $Query_ID = null;				// Result of most recent mysql_query()/mysqli_query()
	var $Record	  = array();			// Current mysql_fetch_array()/mysqli_fetch_array() -result
	var $Row;							// Current row number
	var $Errno    = 0;					// Error state of query
	var $Error    = '';

	var $UseMySQLi= false;
	var $Timeout;						// not implement yet

	function DB($dbhost=null, $dbname=null, $dbuser=null, $dbpass=null, $usemysqli=false, $timeout=60)
	{
		$this->Host 	= $dbhost;
		$this->Database = $dbname;
		$this->User 	= $dbuser;
		$this->Password = $dbpass;

		$this->UseMySQLi= $usemysqli;
		$this->Timeout  = $timeout;
		ini_set('mysql.connect_timeout', $timeout);
	}

	function set_DB($dbhost, $dbname, $dbuser, $dbpass, $usemysqli=false)
	{
		$this->Host 	= $dbhost;
		$this->Database = $dbname;
		$this->User 	= $dbuser;
		$this->Password = $dbpass;
		$this->UseMySQLi= $usemysqli;
	}

	function halt($msg)
	{
		error_log(__FILE__ . ' MySQL ERROR : ' . $msg);
		// die('Session Halted.');
	}

	function connect()
	{
		if ($this->Link_ID==null) {
			//
			if (!$this->UseMySQLi) {
				$this->Link_ID = mysql_connect($this->Host, $this->User, $this->Password);
				if (!$this->Link_ID) {
					$this->Errno = 999;
					return;
				}
				mysql_set_charset('utf8');
				$SelectResult = mysql_select_db($this->Database, $this->Link_ID);
				if (!$SelectResult) {
					$this->Errno = mysql_errno($this->Link_ID);
					$this->Error = mysql_error($this->Link_ID);
					$this->Link_ID = null;
					$this->halt('cannot select database <i>'.$this->Database.'</i>');
				}
			}
			//
			else {
				$this->Link_ID = mysqli_connect($this->Host, $this->User, $this->Password, $this->Database);
				if (!$this->Link_ID) {
					$this->Errno = 999;
					$this->Error = mysqli_connect_error();
					$this->halt('cannot select database <i>'.$this->Database.'</i>');
				}
			}
		}
	}

 	function escape($String)
 	{
		$this->connect();

		if (!$this->UseMySQLi) return mysql_real_escape_string($String);
 		return mysqli_real_escape_string($this->Link_ID, $String);
 	}

	function query($Query_String)
	{
		$this->connect();
		if ($this->Errno!=0) return 0;

		if (!$this->UseMySQLi) {
			$this->Query_ID = mysql_query($Query_String, $this->Link_ID);
			$this->Errno = mysql_errno($this->Link_ID);
			$this->Error = mysql_error($this->Link_ID);
		}
		else {
			$this->Query_ID = mysqli_query($this->Link_ID, $Query_String);
			$this->Errno = mysqli_errno($this->Link_ID);
			$this->Error = mysqli_error($this->Link_ID);
		}
		$this->Row = 0;
		//
		if (!$this->Query_ID) {
			$this->halt('Invalid SQL: '.$Query_String);
		}
		return $this->Query_ID;
	}

	function next_record()
	{
		if (!$this->UseMySQLi) {
			$this->Record = @mysql_fetch_array($this->Query_ID);
			$this->Row += 1;
			$this->Errno = mysql_errno($this->Link_ID);
			$this->Error = mysql_error($this->Link_ID);
			$stat = is_array($this->Record);
			if (!$stat) {
				@mysql_free_result($this->Query_ID);
				$this->Query_ID = null;
			}
		}
		else {
			$this->Record = @mysqli_fetch_array($this->Query_ID);
			$this->Row += 1;
			$this->Errno = mysqli_errno($this->Link_ID);
			$this->Error = mysqli_error($this->Link_ID);
			$stat = is_array($this->Record);
			if (!$stat) {
				@mysqli_free_result($this->Query_ID);
				$this->Query_ID = null;
			}
		}

		return $this->Record;
	}

	function num_rows()
	{
		if (!$this->UseMySQLi) return mysql_num_rows($this->Query_ID);
		return mysqli_num_rows($this->Query_ID);
	}

	function affected_rows()
	{
		if (!$this->UseMySQLi) return mysql_affected_rows($this->Link_ID);
		return mysqli_affected_rows($this->Link_ID);
	}

	function optimize($tbl_name)
	{
		$this->connect();
		if ($this->Errno!=0) return;

		if (!$this->UseMySQLi) {
			$this->Query_ID = @mysql_query('OPTIMIZE TABLE '.$tbl_name, $this->Link_ID);
		}
		else {
			$this->Query_ID = @mysqli_query($this->Link_ID, 'OPTIMIZE TABLE '.$tbl_name);
		}
	}

	function clean_results()
	{
		if ($this->Query_ID!=null) {
			if (!$this->UseMySQLi) {
				mysql_freeresult($this->Query_ID);
			}
			else {
				mysqli_freeresult($this->Query_ID);
			}
			$this->Query_ID = null;
		}
	}

	function close()
	{
	/*
		if ($this->Link_ID) {
			if (!$this->UseMySQLi) mysql_close($this->Link_ID);
			mysqli_close($this->Link_ID);
			$this->Link_ID = null;
		}
	*/
	}

	function exist_table($table, $lower_case=true)
	{
		$ret = false;

		if ($lower_case) $table = strtolower($table);

		$this->query('SHOW TABLES');
		if ($this->Errno==0) {
			while (list($db_tbl) = $this->next_record()) {
				if ($lower_case) $db_tbl = strtolower($db_tbl);
				if ($db_tbl==$table) {
					$ret = true;
					break;
				}
			}
		}

		return $ret;
	}

	function exist_field($table, $field, $lower_case=true)
	{
		$ret1 = false;
		$ret2 = false;

		if ($lower_case) $cmp_table = strtolower($table);
		else             $cmp_table = $table;

		$this->query('SHOW TABLES');
		if ($this->Errno==0) {
			while (list($db_tbl) = $this->next_record()) {
				if ($lower_case) $db_tbl = strtolower($db_tbl);
				if ($db_tbl==$cmp_table) {
					$ret1 = true;
					break;
				}
			}
		}

		if ($ret1) {
			$this->query('SHOW COLUMNS FROM '.$table);
			if ($this->Errno==0) {
				while (list($db_fld) = $this->next_record()) {
					if ($db_fld==$field) {
						$ret2 = true;
						break;
					}
				}
			}
		}

		return $ret2;
	}

	//
	// InnoDB では Update_time は NULL になる!
	//
	function get_update_time($table, $unixtime=true)
	{
		$update = '';
		if ($unixtime) $update = 0;

		$this->query("SHOW TABLE STATUS WHERE name='$table'");

		if ($this->Errno==0) {
			$table_status = $this->next_record();
			$update = $table_status['Update_time'];
			if ($unixtime) {
				if ($update!='') $update = strtotime($update);
				else $update = 0;
			}
		}

		return $update;
	}

	//
	// Lock
	//
	function lock_table($table, $mode='write')
	{
		$this->query("LOCK TABLES ".$table." ".$mode);
	}

	function unlock_table()
	{
		$this->query("UNLOCK TABLES");
	}

	//
	// Timeout
	//
	function set_default_timeout($tm)
	{
    	ini_set('mysql.connect_timeout', $tm);
		$this->Timeout = $tm;
	}

	function set_temp_timeout($tm)
	{
    	ini_set('mysql.connect_timeout', $tm);
	}

	function reset_timeout()
	{
    	ini_set('mysql.connect_timeout', $this->Timeout);
	}
}

function tableExists($pdo, $tables) {
	if(!is_object($pdo)) return false;
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
