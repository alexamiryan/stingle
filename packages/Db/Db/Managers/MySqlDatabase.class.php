<?php
/**
 * Db_MySqlDatabase
 *
 * @category   ToolsAndUtilities
 * @package    DatabaseDrivers
 * @subpackage MySQL
 */

define('DEFAULT_DATETIME_FORMAT', "Y-m-d H:i:s");
define('DEFAULT_DATE_FORMAT', "Y-m-d");

class MySqlDatabase extends Model
{

	const JOIN_STANDART = "JOIN";
	const JOIN_LEFT = "LEFT JOIN";
	const JOIN_RIGHT = "RIGHT JOIN";
	const JOIN_INNER = "INNER JOIN";
	const JOIN_OUTER = "OUTER JOIN";
	const JOIN_STRAIGTH = "STRAIGTH_JOIN";

	const ORDER_ASC = "ASC";
	const ORDER_DESC = "DESC";

	/**
	 * Database connection link
	 * @access private
	 * @var mysqli
	 */
	protected $link;

	/**
	 * Flag, determines whether the transaction is started
	 * @access private
	 * @var boolean
	 */
	protected $transaction_started;

	/**
     * Multiton pattern implementation makes "new" unavailable
     *
     * @return void
     */
/**
	 *
	 * @param $server
	 * @param $username
	 * @param $password
	 * @param $db_name
	 * @param $persistency
	 * @return unknown_type
	 */
	public function __construct($server, $username, $password, $db_name = null, $persistency = false)
	{
		if($persistency){
			$server = 'p:' . $server;
		}
		
		$this->link = new mysqli($server, $username, $password, $db_name);
		if ($this->link->connect_errno) {
			throw new MySqlException($this->link->connect_error, $this->link->connect_errno);
		}
	}

	/**
	 * Multiton pattern implementation makes "clone" unavailable
	 *
	 * @return void
	 */
	protected function __clone()
	{}

	/**
	 * Destructor
	 *
	 * @access public
	 * @return void
	*/
	public function __destruct(){
		if(is_resource($this->link)){
			$this->link->close();
		}
	}

	
    public function selectDatabase($dbName){
    	if (!$this->link->ping()) {
		throw new MySqlException(3, "There is no connection to the server");
	}
    	return $this->link->select_db($dbName);;
    }
    
	/**
	 * Returns link variable for current database
	 *
	 * @access public
	 * @return resource
	*/
	public function getLink(){
		return $this->link;
	}


	public function setConnectionEncoding($encoding){
		if(empty($encoding)){
			throw new InvalidArgumentException("\$encoding have to be non empty string.");
		}
		return $this->link->query("SET NAMES $encoding");
	}

	/**
	 * Starts a new transaction
	 *
	 * @access public
	 * @throws DB_Exception
	 * @return boolean
	*/
	public function startTransaction($withSnapshot = false, $name = null){
		if(!$this->transaction_started){
			if($name !== null){
				if($this->link->begin_transaction(($withSnapshot ? MYSQLI_TRANS_START_WITH_CONSISTENT_SNAPSHOT : null), $name)){
					$this->transaction_started = true;
					return true;
				}
			}
			else{
				if($this->link->begin_transaction(($withSnapshot ? MYSQLI_TRANS_START_WITH_CONSISTENT_SNAPSHOT : null))){
					$this->transaction_started = true;
					return true;
				}
			}
		}
		return false;
	}

	/**
	 * Commits a last started transaction
	 *
	 * @access public
	 * @return boolean
	*/
	public function commit($name = null){
		if($this->transaction_started){
			return $this->link->commit(null, $name);
		}
		else{
			return false;
		}
	}


	/**
	 * Saves a last started transaction
	 *
	 * @param string $identifier savePoint identifier
	 *
	 * @access public
	 * @return boolean
	*/
	public function savePoint($identifier){
		if($this->transaction_started && $identifier != ""){
			return $this->link->savepoint($identifier);
		}
		else{
			return false;
		}
	}


	/**
	 * Rolls back to last savePoint
	 *
	 * @param string $savepointIdentifier
	 *
	 * @access public
	 * @return boolean
	*/
	public function rollBack($savepointIdentifier = null){
		if($this->transaction_started){
			return $this->link->rollback(null, $savepointIdentifier);
		}			
		else{
			return false;
		}
	}


	/**
	 * Returns array with names of database tables
	 *
	 * @access public
	 * @return array
	*/
	public function getTablesNames(){
		$tablesResult = $this->link->query("SHOW TABLES");
		
		$tableNames = array();
		while($currentTable = $tablesResult->fetch_row()){
			array_push($tableNames, $currentTable[0]);
		}
		$tablesResult->free();

		return $tableNames;
	}


	/**
	 * Locks tables from given array
	 *
	 * @param array $tables
	 *
	 * @example $tables = Array("table_name_1" => "r", "table_name_2" => "w", "table_name_3" => "");
	 *
	 * or
	 *
	 * @param string $table
	 * @param string $type
	 *
	 * @example $tables = "table", $type = "r" (READ)
	 * @example $tables = "table", $type = "w" (WRITE)
	 *
	 * @access public
	 * @return boolean
	*/
	public function lockTables($tables, $type="r"){
		if(empty($tables)){
			return false;
		}
		
		$lockQuery = "LOCK TABLES ";
		if(is_array($tables)){
			$lockQueriesArr = array();
			foreach($tables as $table_name => $current_type){
				$query .= $table_name . " ";
				if($current_type == "w"){
					$query .= " WRITE";
				}
				else{
					$query .= " READ";
				}
				array_push($lockQueriesArr, $query);
			}
			$lockQuery .= implode(", ", $lockQueriesArr);
		}
		elseif(is_string($tables)){
			$lockQuery .= $tables;
			if($type == "w"){
				$lockQuery .= " WRITE";
			}
			else{
				$lockQuery .= " READ";
			}

		}

		return $this->link->query($lockQuery);
	}


	/**
	 * Unlocks tables that were locked by current thread
	 *
	 * @access public
	 * @return boolean
	*/
	public function unlockTables(){
		return $this->link->query('UNLOCK TABLES');
	}



	/**
	 * Drops table or tables
	 *
	 * @param array $tableName
	 *
	 * or
	 *
	 * @param string $tableName
	 *
	 * @access public
	 * @return boolean
	*/
	public function dropTables($tableName){
		if(empty($tableName)){
			return false;
		}
		
		$dropQuery = "DROP TABLE ";
		if(is_array($tableName)){
			$dropQuery .= implode(",", $tableName);
		}
		elseif(is_string($tableName)){
			$dropQuery .= $tableName;
		}

		return $this->link->query($dropQuery);
	}


	/**
	 * Renames table
	 *
	 * @param string $oldName
	 * @param string $newName
	 *
	 * @access public
	 * @return boolean
	*/
	public function renameTable($oldName, $newName){
		if(!empty($oldName) && !empty($newName)){
			return $this->link->query("RENAME TABLE $oldName TO $newName");
		}
		else{
			return false;
		}
	}
}