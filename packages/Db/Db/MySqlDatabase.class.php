<?
/**
 * Db_MySqlDatabase
 *
 * @category   ToolsAndUtilities
 * @package    DatabaseDrivers
 * @subpackage MySQL
 */

class MySqlDatabase
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
	 * @var resource
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
	public function __construct($server, $username, $password, $db_name = null, $persistency = true)
	{
		if($persistency){
			$this->link = @mysql_pconnect($server, $username, $password);
		}
		else{
			$this->link = @mysql_connect($server, $username, $password, true);
		}

		if(!$this->link){
			if(mysql_errno()){
				throw new MySqlException(mysql_error(), mysql_errno());
			}
			else{
				throw new MySqlException("Can't connect to MySQL server on '$server'", 2003);
			}
		}

		if($db_name !== null){
			if(!$this->selectDatabase($db_name)){
				if(mysql_errno()){
					$error = mysql_error();
					$errno = mysql_errno();
				}
				else{
					$error = "Error selecting database '$db_name' on host '$server'";
					$errno = 1049;
				}
	
				@mysql_close($this->link);
				throw new MySqlException($error, $errno);
			}
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
			mysql_close($this->link);
		}
	}

	
    public function selectDatabase($dbName){
    	if(!is_resource($this->link)){
			throw new MySqlException(3, "There is no connection to the server");
    	}
    	return mysql_select_db($dbName, $this->link);
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


	/**
	 * Returns current database name
	 *
	 * @access public
	 * @return string
	*/
	public function getName(){
		return $this->name;
	}


	public function setConnectionEncoding($encoding){
		if(empty($encoding)){
			throw new InvalidArgumentException("\$encoding have to be non empty string.");
		}
		@mysql_query("SET NAMES $encoding", $this->link);
	}

	/**
	 * Starts a new transaction
	 *
	 * @access public
	 * @throws DB_Exception
	 * @return boolean
	*/
	public function startTransaction(){
		if(!$this->transaction_started){
			if(@mysql_query("START TRANSACTION", $this->link)){
				$this->transaction_started = true;
				return true;
			}
			else{
				return false;
			}
		}
		else{
			return false;
		}

	}

	/**
	 * Commits a last started transaction
	 *
	 * @access public
	 * @return boolean
	*/
	public function commit(){
		if($this->transaction_started){
			if(@mysql_query("COMMIT", $this->link)){
				return true;
			}
			else{
				return false;
			}
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
			if(@mysql_query("SAVEPOINT " . $identifier, $this->link)){
				return true;
			}
			else{
				return false;
			}
		}
		else{
			return false;
		}
	}


	/**
	 * Rolls back to last savePoint
	 *
	 * @param string $savepoint_identifier = ""
	 *
	 * @access public
	 * @return boolean
	*/
	public function rollBack($savepoint_identifier = ""){
		if($this->transaction_started){
			if($savepoint_identifier != ""){
				if(@mysql_query("ROLLBACK TO SAVEPOINT " . $savepoint_identifier, $this->link)){
					return true;
				}
				else{
					return false;
				}
			}
			else{
				if(@mysql_query("ROLLBACK", $this->link)){
					return true;
				}
				else{
					return false;
				}
			}
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
		$tables_result = @mysql_query("SHOW TABLES", $this->link);

		while(($current_table = mysql_fetch_row($tables_result))){
			$tables_names[] = $current_table[0];
		}
		mysql_free_result($tables_result);

		return $tables_names;
	}


	/**
	 * Returns table's name with $offset id
	 *
	 * @param integer $offset
	 *
	 * @access public
	 * @return string or boolean
	 *
	 * @todo change return false to smth else
	*/
	public function getTableName($offset){
		$tables_result = @mysql_query("SHOW TABLES", $this->link);

		if($offset >= 0 and $offset < mysql_num_rows($tables_result)){
			if(@mysql_data_seek($tables_result, $offset)){
				$table_row = mysql_fetch_array($tables_result);
				return $table_row[0];
			}
			else{
				return false;
			}
		}
		else{
			return false;
		}
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
	 * @example $table = "table", $type = "r" (READ)
	 * @example $table = "table", $type = "w" (WRITE)
	 *
	 * @access public
	 * @return boolean
	*/
	public function lockTables($tables, $type="r"){
		if(is_array($tables)){
			$lock_query = "LOCK TABLES ";
			foreach($tables as $table_name => $current_type){
				$lock_query .= $table_name . " ";
				if($current_type == "w"){
					$lock_query .= " WRITE";
				}
				else{
					$lock_query .= " READ";
				}
				$lock_query .= ", ";
			}
			$lock_query = substr($lock_query, 0, strlen($lock_query)-2);
		}
		elseif(is_string($tables)){
			$table_name = $tables;
			if($table_name == ""){
				return false;
			}
			$lock_query = "LOCK TABLES " . $table_name;
			if($type == "w"){
				$lock_query .= " WRITE";
			}
			else{
				$lock_query .= " READ";
			}

		}

		if($lock_query != ""){
			if(@mysql_query($lock_query, $this->link)){
				return true;
			}
			else{
				return false;
			}
		}
		else{
			return false;
		}
	}


	/**
	 * Unlocks tables that were locked by current thread
	 *
	 * @access public
	 * @return boolean
	*/
	public function unlockTables(){
		if(@mysql_query('UNLOCK TABLES', $this->link)){
			return true;
		}
		else{
			return false;
		}
	}



	/**
	 * Drops table or tables
	 *
	 * @param array $tables
	 *
	 * or
	 *
	 * @param string $table
	 *
	 * @access public
	 * @return boolean
	*/
	public function dropTables($table_name){
		if(is_array($table_name)){
			$tables = $table_name;
			$drop_query = "DROP TABLE ";

			foreach ($tables as $name){
				$drop_query .= $name . ", ";
			}

			$drop_query = substr($drop_query, 0, strlen($drop_query)-3);
		}
		elseif(is_string($table_name)){
			$drop_query = "DROP TABLE $table_name";
		}

		if($drop_query != ""){
			if(@mysql_query($drop_query, $this->link)){
				return true;
			}
			else{
				return false;
			}
		}
		else{
			return false;
		}
	}


	/**
	 * Renames table
	 *
	 * @param string $table_name
	 * @param string $new_name
	 *
	 * @access public
	 * @return boolean
	*/
	public function renameTable($table_name, $new_name){
		if($table_name != "" && $new_name != ""){
			if(@mysql_query("RENAME TABLE $table_name TO $new_name", $this->link)){
				return true;
			}
			else{
				return false;
			}
		}
		else{
			return false;
		}
	}
}