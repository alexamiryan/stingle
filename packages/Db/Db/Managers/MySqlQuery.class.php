<?php

class MySqlQuery extends Model {

	public $sqlStatement = null;

	/**
	 *
	 * @var type mysqli
	 */
	protected $link = null;

	/**
	 *
	 * @var type mysqli_result
	 */
	protected $result = null;
	////////counter vars/////////////
	protected $lastFetchType = null;
	protected $lastRecordPosition = 0;
	protected $lastFieldPosition = 0;
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
	public function __construct(MySqlDatabase $db, Logger $logger = null) {
		if ($logger === null) {
			$this->setLogger(new SessionLogger());
		}
		else {
			$this->setLogger($logger);
		}
		$this->link = $db->getLink();
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
		$queryStr = '';
		if (empty($sqlStatement)) {
			if (!empty($this->sqlStatement)) {
				$queryStr = $this->sqlStatement;
			}
			else {
				throw new EmptyArgumentException();
			}
		}
		else {
			$queryStr = $sqlStatement;
		}

		if ($this->log) {
			$this->logger->log(static::LOGGER_NAME, $sqlStatement);
		}

		if (($this->result = $this->link->query($queryStr)) !== false) {
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
							$db = Reg::get(ConfigManager::getConfig("Db", "Db")->Objects->Db);
							$db->startTransaction();
							foreach ($sqlFiles as $sqlFilePath) {
								self::executeSQLFile($sqlFilePath);
							}

							if ($db->commit()) {
								array_push($this->nonExitentTables, $nonExistantTableName);
								return $this->exec($sqlStatement);
							}
							else {
								$db->rollBack();
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

	public function executeSQLFile($file, $delimiter = ';') {
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
