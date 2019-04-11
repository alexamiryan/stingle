<?php

class MySqlQuery extends Model {

	protected $link = null;
	
	protected $instanceName = MySqlDbManager::DEFAULT_INSTANCE_NAME;

	protected $result = null;
	
	////////counter vars/////////////
	protected $lastFetchType = null;
	protected $lastRecordPosition = 0;
	protected $lastFieldPosition = 0;
	protected $isTransactionStarted = false;
	////////////////////////////////

	protected $logger = null;
	protected $log = false;
	protected $nonExitentTables = array();

	const FETCH_TYPE_RECORD = 'record';
	const FETCH_TYPE_FIELD = 'field';
	const RECORD_TYPE_ARRAY = 0;
	const RECORD_TYPE_ASSOC = 1;
	const RECORD_TYPE_OBJECT = 2;
	const LOGGER_NAME = 'MysqlQuery';

	/**
	 * Class constructor
	 *
	 * @param Db_MySqlDatabase db
	 *
	 */
	public function __construct($instanceName = null, Logger $logger = null) {
		if($instanceName !== null){
			$this->instanceName = $instanceName;
		}
		
		if ($logger === null) {
			$this->setLogger(new SessionLogger());
		}
		else {
			$this->setLogger($logger);
		}
		
		$this->link = MySqlDbManager::getDbObject($instanceName)->getLink();
	}
	
	protected function chooseDbEndpoint($query){
		$type = (self::isSelect($query) ? MySqlDbManager::INSTANCE_TYPE_RO : MySqlDbManager::INSTANCE_TYPE_RW);
		$this->link = MySqlDbManager::getDbObject($this->instanceName, $type)->getLink();
	}
	
	protected function switchToRWEndpoint(){
		$this->link = MySqlDbManager::getDbObject($this->instanceName, MySqlDbManager::INSTANCE_TYPE_RW)->getLink();
	}
	
	public static function isSelect($query) {
		// Default trim()'s mask plus left parentheses
		$ltrimMask = "( \t\n\r\0\x0B";

		return 'SELECT' === strtoupper(
			substr(
				ltrim($query, $ltrimMask), 0, 6
			)
		);
	}
	
	public function getInstanceName(){
		return $this->instanceName;
	}
	public function setInstanceName($instanceName){
		$this->instanceName = $instanceName;
	}

	public function setLogger(Logger $logger) {
		$this->logger = $logger;
	}

	public function setLogging($bool) {
		if (!is_bool($bool)) {
			return false;
		}
		$this->log = $bool;
	}

	public function getLogging() {
		return $this->log;
	}

	/**
	 * Get current error messgae from database
	 *
	 * @return bool
	 */
	public function errorMessage() {
		return $this->link->error;
	}

	/**
	 * Get current error code from database
	 *
	 * @return unknown
	 */
	public function errorCode() {
		return $this->link->errno;
	}

	/**
	 * Execute SQL query
	 *
	 * @param string $sqlStatement
	 * @return MysqlQuery
	 */
	public function exec($sqlStatement) {
		if(!$this->isTransactionStarted){
			$this->chooseDbEndpoint($sqlStatement);
		}
		
		if (empty($sqlStatement)) {
			throw new EmptyArgumentException();
		}

		if ($this->log) {
			$this->logger->log(static::LOGGER_NAME, $sqlStatement);
		}
		
		if (($this->result = $this->link->query($sqlStatement)) !== false) {
			$this->lastFetchType = null;
			$this->lastFieldPosition = 0;
			$this->lastRecordPosition = 0;
			return $this;
		}
		else {
			$errorCode = $this->errorCode();
			$errorMessage = $this->errorMessage();
			if ($errorCode == 1146) {
				preg_match("/Table \'.*?\.(.+?)\' doesn\'t exist/", $errorMessage, $matches);

				if (isset($matches[1])) {
					$nonExistantTableName = $matches[1];

					if (!in_array($nonExistantTableName, $this->nonExitentTables)) {

						$sqlFiles = Tbl::getPluginSQLFilePathsByTableName($nonExistantTableName);
						if ($sqlFiles !== false) {
							$this->startTransaction();
							foreach ($sqlFiles as $sqlFilePath) {
								self::executeSQLFile($sqlFilePath, ';');
							}

							if ($this->commit()) {
								array_push($this->nonExitentTables, $nonExistantTableName);
								return $this->exec($sqlStatement);
							}
							else {
								$this->rollBack();
							}
						}
					}
				}
			}
			throw new MySqlException("MySQL Error: $errorCode: $errorMessage in query `$sqlStatement`", $errorCode);
		}
	}

	/**
	 * Rows affected by query
	 *
	 * @return bool
	 */
	public function affected() {
		return $this->link->affected_rows;
	}

	/**
	 * Get last insert id
	 *
	 * @return int $insert_id
	 */
	public function getLastInsertId() {
		return $this->link->insert_id;
	}

	/**
	 * Analog of mysql_num_rows()
	 *
	 * @return int $number
	 */
	public function countRecords() {
		if ($this->result) {
			return $this->result->num_rows;
		}
		else {
			return false;
		}
	}

	/**
	 * Analog of mysql_num_fields()
	 *
	 * @return int $number
	 */
	public function countFields() {
		if ($this->result) {
			return $this->result->field_count;
		}
		else {
			return false;
		}
	}

	/**
	 * Fetch one row and move cursor to nex row
	 *
	 * @param int $type (0-Normal Array, 1-Associative Array, 2-Object)
	 * @return array
	 */
	public function fetchRecord($type = self::RECORD_TYPE_ASSOC) {
		if ($this->countRecords() == 0) {
			return false;
		}

		if (!$this->result) {
			return array();
		}

		if ($this->lastFetchType != self::FETCH_TYPE_RECORD) {
			$this->result->data_seek($this->lastRecordPosition);
			$this->lastFetchType = self::FETCH_TYPE_RECORD;
		}
		else {
			++$this->lastRecordPosition;
		}

		switch ($type) {
			case self::RECORD_TYPE_ARRAY:
				return $this->result->fetch_row();
			case self::RECORD_TYPE_ASSOC:
				return $this->result->fetch_assoc();
			case self::RECORD_TYPE_OBJECT:
				return $this->result->fetch_object();
			default:
				return array();
		}
	}

	/**
	 * Fetch one fileld from row
	 *
	 * @param string $fieldName
	 * @param int $isNumeric (false - $field_identifier is name of the field, true - $field_identifier is number of the field)
	 * @return string
	 */
	public function fetchField($fieldName, $isNumeric = false) {
		if ($this->countRecords() == 0) {
			return false;
		}

		if (!$this->result) {
			return false;
		}

		if ($this->lastFetchType != self::FETCH_TYPE_FIELD) {
			$this->result->data_seek($this->lastFieldPosition);
			$this->lastFetchType = self::FETCH_TYPE_FIELD;
		}
		else {
			++$this->lastFieldPosition;
		}

		if ($isNumeric) {
			$record = $this->result->fetch_row();
		}
		else {
			$record = $this->result->fetch_assoc();
		}

		if ($record) {
			return $record[$fieldName];
		}
		else {
			return false;
		}
	}

	/**
	 * Get array of the query
	 *
	 * @param int $offset
	 * @param int $len
	 * @param int $fieldsType (0-Normal,1-Assoc, 2-Object)
	 * @param int $rowsType (0-Normal,1-Assoc, 2-Object)
	 * @param string $rowsTypeField (name of the field to become index for Assoc $rowsType)
	 * @return array
	 */
	public function fetchRecords($offset = 0, $len = 0, $fieldsType = self::RECORD_TYPE_ASSOC, $rowsType = self::RECORD_TYPE_ARRAY, $rowsTypeField = null) {
		$returnArray = array();
		$counter = 0;
		$numRecords = $this->countRecords();

		if (abs($offset) > $numRecords || $numRecords == 0 || !$this->result) {
			return array();
		}

		if ($this->lastFetchType == self::FETCH_TYPE_FIELD) {
			$resultLastPosition = $this->lastFieldPosition;
		}
		elseif ($this->lastFetchType == self::FETCH_TYPE_RECORD) {
			$resultLastPosition = $this->lastRecordPosition;
		}
		else {
			$resultLastPosition = 0;
		}

		$flagToReverceArray = false;

		if ($len < 0) {
			$flagToReverceArray = true;
		}

		if ($len > 0) {
			if ($offset < 0) {
				$offset = $numRecords - abs($offset);
			}
		}
		elseif ($len < 0) {
			if ($offset > 0) {
				if (abs($len) > abs($offset)) {
					$len = abs($offset);
					$offset = 0;
				}
				else {
					$offset = $offset - abs($len) + 1;
					$len = abs($len);
				}
			}
			elseif ($offset < 0) {
				if (abs($len) > abs($offset)) {
					$len = $numRecords - abs($offset) + 1;
					$offset = 0;
				}
				else {
					$offset = $numRecords - abs($offset) - abs($len) + 1;
					$len = abs($len);
				}
			}
		}
		elseif ($len == 0) {
			$len = $numRecords;
		}

		$this->result->data_seek($offset);

		if ($rowsType == self::RECORD_TYPE_ARRAY) {
			if ($fieldsType == self::RECORD_TYPE_ARRAY) {
				while ($currentResult = $this->result->fetch_row()) {
					$returnArray[$counter] = $currentResult;
					$counter++;
					if ($counter == abs($len)) {
						break;
					}
				}
			}
			elseif ($fieldsType == self::RECORD_TYPE_ASSOC) {
				while ($currentResult = $this->result->fetch_assoc()) {
					$returnArray[$counter] = $currentResult;
					$counter++;
					if ($counter == abs($len)) {
						break;
					}
				}
			}
			elseif ($fieldsType == self::RECORD_TYPE_OBJECT) {
				while ($currentResult = $this->result->fetch_object()) {
					$returnArray[$counter] = $currentResult;
					$counter++;
					if ($counter == abs($len)) {
						break;
					}
				}
			}
			else {
				return array();
			}
		}
		elseif ($rowsType == self::RECORD_TYPE_ASSOC and ! empty($rowsTypeField)) {
			$numFieldType = 0;
			if ($fieldsType == self::RECORD_TYPE_ARRAY) {
				for ($i = 0; $i < $this->countFields(); $i++) {
					if ($this->fieldName($i) == $rowsTypeField) {
						$numFieldType = $i;
						break;
					}
				}
				while ($currentResult = $this->result->fetch_row()) {
					$returnArray[$currentResult[$numFieldType]] = $currentResult;
					$counter++;
					if ($counter == abs($len)) {
						break;
					}
				}
			}
			elseif ($fieldsType == self::RECORD_TYPE_ASSOC) {
				while ($currentResult = $this->result->fetch_assoc()) {
					$returnArray[$currentResult[$rowsTypeField]] = $currentResult;
					$counter++;
					if ($counter == abs($len)) {
						break;
					}
				}
			}
			elseif ($fieldsType == self::RECORD_TYPE_OBJECT) {
				while ($currentResult = $this->result->fetch_object()) {
					$returnArray[$currentResult->$rowsTypeField] = $currentResult;
					$counter++;
					if ($counter == abs($len)) {
						break;
					}
				}
			}
		}
		else {
			return array();
		}
		if ($resultLastPosition < $numRecords) {
			$this->result->data_seek($resultLastPosition);
		}
		if ($flagToReverceArray) {
			$returnArray = array_reverse($returnArray);
		}

		return $returnArray;
	}

	/**
	 * Get array of only one field
	 *
	 * @param string $fieldName
	 * @param bool $isNumeric (true-$field_identifier is numeric, false-$field_identifier is a name)
	 * @param int $offset
	 * @param int $len
	 * @return array
	 */
	public function fetchFields($fieldName, $isNumeric = false, $offset = 0, $len = 0) {
		$returnArray = array();
		$counter = 0;
		$numRecords = $this->countRecords();

		if (abs($offset) > $numRecords || $numRecords == 0 || !$this->result) {
			return array();
		}
		
		if ($this->lastFetchType == self::FETCH_TYPE_FIELD) {
			$resultLastPosition = $this->lastFieldPosition;
		}
		elseif ($this->lastFetchType == self::FETCH_TYPE_RECORD) {
			$resultLastPosition = $this->lastRecordPosition;
		}
		else {
			$resultLastPosition = 0;
		}

		$flagToReverceArray = false;

		if ($len < 0) {
			$flagToReverceArray = true;
		}

		if ($len > 0) {
			if ($offset < 0) {
				$offset = $numRecords - abs($offset);
			}
		}
		elseif ($len < 0) {
			if ($offset > 0) {
				if (abs($len) > abs($offset)) {
					$len = abs($offset);
					$offset = 0;
				}
				else {
					$offset = $offset - abs($len) + 1;
					$len = abs($len);
				}
			}
			elseif ($offset < 0) {
				if (abs($len) > abs($offset)) {
					$len = $numRecords - abs($offset) + 1;
					$offset = 0;
				}
				else {
					$offset = $numRecords - abs($offset) - abs($len) + 1;
					$len = abs($len);
				}
			}
		}
		elseif ($len == 0) {
			$len = $numRecords;
		}

		$this->result->data_seek($offset);

		if ($isNumeric == false) {
			while ($currentResult = $this->result->fetch_assoc()) {
				$returnArray[$counter] = $currentResult[$fieldName];
				$counter++;
				if ($counter == abs($len)) {
					break;
				}
			}
		}
		elseif ($isNumeric == true) {
			while ($currentResult = $this->result->fetch_row()) {
				$returnArray[$counter] = $currentResult[$fieldName];
				$counter++;
				if ($counter == abs($len)) {
					break;
				}
			}
		}
		else {
			return array();
		}


		if ($resultLastPosition < $numRecords) {
			$this->result->data_seek($resultLastPosition);
		}
		if ($flagToReverceArray) {
			$returnArray = array_reverse($returnArray);
		}
		
		return $returnArray;
	}

	/**
	 * Get name of specified field
	 *
	 * @param int $offset
	 * @return string
	 */
	public function fieldName($offset) {
		return $this->fieldInfo($offset)->name;
	}

	/**
	 * Get type of specified field
	 *
	 * @param int $offset
	 * @return string
	 */
	public function fieldType($offset) {
		return $this->fieldInfo($offset)->type;
	}

	/**
	 * Get length of specified field
	 *
	 * @param int $offset
	 * @return int
	 */
	public function fieldLen($offset) {
		return $this->fieldInfo($offset)->length;
	}
	

	/**
	 * Get flags of specified field
	 *
	 * @param int $offset
	 * @return array
	 */
	public function fieldFlags($offset) {
		return $this->fieldInfo($offset)->flags;
	}

	
	/**
	 * 
	 * @param int $offset
	 * @return mysqli_fetch_field_direct
	 */
	public function fieldInfo($offset){
		if ($this->result and $offset >= 0 and $offset < $this->countFields()) {
			return $this->result->fetch_field_direct($offset);
		}
		return false;
	}

	/**
	 * Get found rows count from select query which
	 * uses SQL_CALC_FOUND_ROWS parameter
	 *
	 * @return integer
	 */
	public function getFoundRowsCount() {
		$this->exec("SELECT FOUND_ROWS() AS `cnt`");
		return $this->fetchField('cnt');
	}
	
	public function escapeString($string){
		return $this->link->real_escape_string($string);
	}
	
	
	/**
	 * Starts a new transaction
	 *
	 * @access public
	 * @throws DB_Exception
	 * @return boolean
	 */
	public function startTransaction($withSnapshot = false, $name = null) {
		$this->switchToRWEndpoint();
		if (!$this->isTransactionStarted) {
			if ($this->link->begin_transaction(($withSnapshot ? MYSQLI_TRANS_START_WITH_CONSISTENT_SNAPSHOT : null), $name)) {
				$this->isTransactionStarted = true;
				return true;
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
	public function commit($name = null) {
		if ($this->isTransactionStarted) {
			$result = $this->link->commit(null, $name);
			$this->isTransactionStarted = false;
			return $result;
		}
		else {
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
	public function savePoint($identifier) {
		if ($this->isTransactionStarted && !empty($identifier)) {
			return $this->link->savepoint($identifier);
		}
		else {
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
	public function rollBack($savepointIdentifier = null) {
		if ($this->isTransactionStarted) {
			$result = $this->link->rollback(null, $savepointIdentifier);
			$this->isTransactionStarted = false;
			return $result;
		}
		else {
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
	 * @example $tables = "table", $type = "r" (READ)
	 * @example $tables = "table", $type = "w" (WRITE)
	 *
	 * @access public
	 * @return boolean
	 */
	public function lockTables($tables, $type = "r") {
		$this->switchToRWEndpoint();
		
		if (empty($tables)) {
			return false;
		}

		$lockQuery = "LOCK TABLES ";
		if (is_array($tables)) {
			$lockQueriesArr = array();
			foreach ($tables as $table_name => $current_type) {
				$query .= $table_name . " ";
				if ($current_type == "w") {
					$query .= " WRITE";
				}
				else {
					$query .= " READ";
				}
				array_push($lockQueriesArr, $query);
			}
			$lockQuery .= implode(", ", $lockQueriesArr);
		}
		elseif (is_string($tables)) {
			$lockQuery .= $tables;
			if ($type == "w") {
				$lockQuery .= " WRITE";
			}
			else {
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
	public function unlockTables() {
		$this->switchToRWEndpoint();
		
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
	public function dropTables($tableName) {
		if (empty($tableName)) {
			return false;
		}
		$this->switchToRWEndpoint();

		$dropQuery = "DROP TABLE ";
		if (is_array($tableName)) {
			$dropQuery .= implode(",", $tableName);
		}
		elseif (is_string($tableName)) {
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
	public function renameTable($oldName, $newName) {
		$this->switchToRWEndpoint();
		
		if (!empty($oldName) && !empty($newName)) {
			return $this->link->query("RENAME TABLE $oldName TO $newName");
		}
		else {
			return false;
		}
	}

	public function executeSQLFile($file, $delimiter = ';') {
		$this->switchToRWEndpoint();
		
		$matches = array();
		$otherDelimiter = false;
		if (is_file($file) === true) {
			$file = fopen($file, 'r');
			if (is_resource($file) === true) {
				$query = array();
				while (feof($file) === false) {
					$query[] = fgets($file);
					if (preg_match('~' . preg_quote('delimiter', '~') . '\s*([^\s]+)$~iS', end($query), $matches) === 1) {
						//DELIMITER DIRECTIVE DETECTED
						array_pop($query); //WE DON'T NEED THIS LINE IN SQL QUERY
						if ($otherDelimiter = ( $matches[1] != $delimiter )) {
							
						}
						else {
							// THIS IS THE DEFAULT DELIMITER, DELETE THE LINE BEFORE THE LAST (THAT SHOULD BE THE NOT DEFAULT DELIMITER) AND WE SHOULD CLOSE THE STATEMENT
							array_pop($query);
							$query[] = $delimiter;
						}
					}
					if (!$otherDelimiter && preg_match('~' . preg_quote($delimiter, '~') . '\s*$~iS', end($query)) === 1) {
						$query = trim(implode('', $query));

						$this->exec($query);
					}
					if (is_string($query) === true) {
						$query = array();
					}
				}
				return fclose($file);
			}
		}
		return false;
	}

}
