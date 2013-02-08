<?php
/**
 * Class query
 * Constructor params
 *
 *
 */

class MySqlQuery
{
	public  $sql_statement;

	protected $db_link;
	protected $result;

	////////counter vars/////////////
	protected $last_fetch_type;
	protected $last_record_position;
	protected $last_field_position;
	////////////////////////////////

	protected $logger;
	protected $log = false;
	
	protected $nonExitentTables = array();
	
	const LOGGER_NAME = 'MysqlQuery';

	/**
	 * Class constructor
	 *
	 * @param Db_MySqlDatabase db
	 *
	 */
	public function __construct(MySqlDatabase $db, Logger $logger = null){
		if($logger === null){
			$this->setLogger(new SessionLogger());
		}
		else{
			$this->setLogger($logger);
		}
		$this->db_link = $db->getLink();
	}

	/**
	 * Class destructor
	 *
	 */
	public function __destruct(){
		if(is_resource($this->result)){
			mysql_free_result($this->result);
		}
	}

	public function setLogger(Logger $logger){
		$this->logger = $logger;
	}

	public function setLogging($bool){
		if(!is_bool($bool)){
			return false;
		}
		$this->log = $bool;
	}

	public function getLogging(){
		return $this->log;
	}

	/**
	 * Get current error messgae from database
	 *
	 * @return bool
	 */
	public function errorMessage(){
		return mysql_error($this->db_link);
	}

	/**
	 * Get current error code from database
	 *
	 * @return unknown
	 */
	public function errorCode(){
		return mysql_errno($this->db_link);
	}

	/**
	 * Execute SQL query
	 *
	 * @param string $sql_statement
	 * @return MysqlQuery
	 */
	public function exec($sql_statement){
		$query_str='';
		if(empty($sql_statement)){
			if(!empty($this->sql_statement)){
				$query_str = $this->sql_statement;
			}
			else{
				throw new EmptyArgumentException();
			}
		}
		else{
			$query_str = $sql_statement;
		}

		if($this->log){
			$this->logger->log(static::LOGGER_NAME, $sql_statement);
		}
		
		if( ($this->result = mysql_query($query_str, $this->db_link)) != false ){
			$this->last_fetch_type='';
			$this->last_field_position=0;
			$this->last_record_position=0;
			return $this;
		}
		else{
			$errorCode = $this->errorCode();
			$errorMessage = $this->errorMessage();
			if($errorCode == 1146){
				preg_match("/Table \'.*?\.(.+?)\' doesn\'t exist/", $errorMessage, $matches);
				
				if(isset($matches[1])){
					$nonExistantTableName = $matches[1];

					if(!in_array($nonExistantTableName, $this->nonExitentTables)){
						
						$sqlFiles = Tbl::getPluginSQLFilePathsByTableName($nonExistantTableName);
						if($sqlFiles !== false){
							$db = Reg::get(ConfigManager::getConfig("Db", "Db")->Objects->Db);
							$db->startTransaction();
							foreach($sqlFiles as  $sqlFilePath){
								self::executeSQLFile($sqlFilePath);
							}
							
							if($db->commit()){
								array_push($this->nonExitentTables, $nonExistantTableName);
								return $this->exec($sql_statement);
							}
							else{
								$db->rollBack();
							}
						}
					}
				}
			}
			throw new MySqlException("MySQL Error: $errorCode: $errorMessage in query `$sql_statement`", $errorCode);
		}
	}

	/**
	 * Rows affected by query
	 *
	 * @return bool
	 */
	public function affected(){
		if($this->result){
			return mysql_affected_rows($this->db_link);
		}
		else{
			return false;
		}
	}

	/**
	 * Get last insert id
	 *
	 * @return int $insert_id
	 */
	public function getLastInsertId(){
		if($this->result){
			return mysql_insert_id($this->db_link);
		}
		else{
			return false;
		}
	}

	/**
	 * Analog of mysql_num_rows()
	 *
	 * @return int $number
	 */
	public function countRecords(){
		if($this->result){
			return mysql_num_rows($this->result);
		}
		else{
			return false;
		}
	}
	/**
	 * Analog of mysql_num_fields()
	 *
	 * @return int $number
	 */
	public function countFields(){
		if($this->result){
			return mysql_num_fields($this->result);
		}
		else{
			return false;
		}
	}

	/**
	 * Fetch one row and move cursor to nex row
	 *
	 * @param int $type (0-Normal Array, 1-Associative Array, 2-Object)
	 * @return array
	 */
	public function fetchRecord($type=1){
		if(mysql_num_rows($this->result) == 0){
			return false;
		}
		
		if($this->last_fetch_type!='record' and is_resource($this->result)){
			@mysql_data_seek($this->result, $this->last_record_position);
			$this->last_fetch_type='record';
		}
		else{
			++$this->last_record_position;
		}

		if($this->result){
			if($type==0){
				return mysql_fetch_row($this->result);
			}
			elseif($type==1){
				return mysql_fetch_assoc($this->result);
			}
			elseif($type==2){
				return mysql_fetch_object($this->result);
			}
			else{
				return array();
			}
		}
		else{
			return array();
		}
	}
	/**
	 * Fetch one fileld from row
	 *
	 * @param string $field_identifier
	 * @param int $is_numeric (0-$field_identifier is name of the field,1-$field_identifier is number of the field)
	 * @return string
	 */
	public function fetchField($field_identifier, $is_numeric=0){
		if(mysql_num_rows($this->result) == 0){
			return false;
		}
		
		if($this->last_fetch_type!='field' and is_resource($this->result)){
			@mysql_data_seek($this->result, $this->last_field_position);
			$this->last_fetch_type='field';
		}
		else{
			++$this->last_field_position;
		}

		if($this->result){
			if($is_numeric){
				$record=mysql_fetch_row($this->result);
			}
			else{
				$record=mysql_fetch_assoc($this->result);
			}
			if($record){
				return $record[$field_identifier];
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
	 * Get array of the query
	 *
	 * @param int $offset
	 * @param int $len
	 * @param int $fields_type (0-Normal,1-Assoc, 2-Object)
	 * @param int $rows_type((0-Normal,1-Assoc, 2-Object))
	 * @param string $rows_type_field (name of the field to become index for Assoc $rows_type)
	 * @return array
	 */
	public function fetchRecords($offset=0, $len=0, $fields_type=1, $rows_type=0, $rows_type_field=''){
		$array_to_return=array();
		$counter=0;
		$num_records=$this->countRecords();

		if($this->last_fetch_type=='field'){
			$result_last_position=$this->last_field_position;
		}
		elseif($this->last_fetch_type=='record'){
			$result_last_position=$this->last_record_position;
		}
		else{
			$result_last_position=0;
		}

		$flag_to_reverce_array=false;

		if($len<0){
			$flag_to_reverce_array=true;
		}

		if(abs($offset)>$num_records){
			return array();
		}

		if($len>0){
			if($offset>0){

			}
			elseif($offset<0){
				$offset=$num_records-abs($offset);
			}
		}
		elseif($len<0){
			if($offset>0){
				if(abs($len)>abs($offset)){
					$len=abs($offset);
					$offset=0;
				}
				else{
					$offset=$offset-abs($len)+1;
					$len=abs($len);
				}
			}
			elseif($offset<0){
				if(abs($len)>abs($offset)){
					$len=$num_records-abs($offset)+1;
					$offset=0;
				}
				else{
					$offset=$num_records-abs($offset)-abs($len)+1;
					$len=abs($len);
				}
			}
		}
		elseif($len==0){
			$len=$num_records;
		}

		if(is_resource($this->result) and mysql_num_rows($this->result) != 0){
			@mysql_data_seek($this->result,$offset);

			if($rows_type==0){
				if($fields_type==0){
					while(($current_result=mysql_fetch_row($this->result))){
						$array_to_return[$counter]=$current_result;
						$counter++;
						if($counter==abs($len)){
							break;
						}
					}
				}
				elseif($fields_type==1){
					while(($current_result=mysql_fetch_assoc($this->result))){
						$array_to_return[$counter]=$current_result;
						$counter++;
						if($counter==abs($len)){
							break;
						}
					}
				}
				elseif($fields_type==2){
					while(($current_result=mysql_fetch_object($this->result))){
						$array_to_return[$counter]=$current_result;
						$counter++;
						if($counter==abs($len)){
							break;
						}
					}
				}
				else{
					return array();
				}
			}
			elseif($rows_type==1 and !empty($rows_type_field)){
				$num_field_type=0;
				if($fields_type==0){
					for($i=0;$i<$this->countFields();$i++){
						if(mysql_fieldName($this->result,$i)==$rows_type_field){
							$num_field_type=$i;
							break;
						}
					}
					while(($current_result=mysql_fetch_row($this->result))){
						$array_to_return[$current_result[$num_field_type]]=$current_result;
						$counter++;
						if($counter==abs($len)){
							break;
						}
					}
				}
				elseif($fields_type==1){
					while(($current_result=mysql_fetch_assoc($this->result))){
						$array_to_return[$current_result[$rows_type_field]]=$current_result;
						$counter++;
						if($counter==abs($len)){
							break;
						}
					}
				}
				elseif($fields_type==2){
					while(($current_result=mysql_fetch_object($this->result))){
						$array_to_return[$current_result->$rows_type_field]=$current_result;
						$counter++;
						if($counter==abs($len)){
							break;
						}
					}
				}
			}
			else{
				return array();
			}
			if($result_last_position<$num_records){
				mysql_data_seek($this->result,$result_last_position);
			}
			if($flag_to_reverce_array){
				$array_to_return=array_reverse($array_to_return);
			}
		}
		return $array_to_return;
	}

	/**
	 * Get array of only one field
	 *
	 * @param string $field_identifier
	 * @param bool $is_numeric_identifier(true-$field_identifier is numeric, false-$field_identifier is a name)
	 * @param int $offset
	 * @param int $len
	 * @return array
	 */
	public function fetchFields($field_identifier, $is_numeric_identifier=false, $offset=0, $len=0){
		$array_to_return=array();
		$counter=0;
		$num_records=$this->countRecords();

		if($this->last_fetch_type=='field'){
			$result_last_position=$this->last_field_position;
		}
		elseif($this->last_fetch_type=='record'){
			$result_last_position=$this->last_record_position;
		}
		else{
			$result_last_position=0;
		}

		$flag_to_reverce_array=false;

		if($len<0){
			$flag_to_reverce_array=true;
		}

		if(abs($offset)>$num_records){
			return array();
		}

		if($len>0){
			if($offset>0){

			}
			elseif($offset<0){
				$offset=$num_records-abs($offset);
			}
		}
		elseif($len<0){
			if($offset>0){
				if(abs($len)>abs($offset)){
					$len=abs($offset);
					$offset=0;
				}
				else{
					$offset=$offset-abs($len)+1;
					$len=abs($len);
				}
			}
			elseif($offset<0){
				if(abs($len)>abs($offset)){
					$len=$num_records-abs($offset)+1;
					$offset=0;
				}
				else{
					$offset=$num_records-abs($offset)-abs($len)+1;
					$len=abs($len);
				}
			}
		}
		elseif($len==0){
			$len=$num_records;
		}

		if(is_resource($this->result) and mysql_num_rows($this->result) != 0){
			mysql_data_seek($this->result,$offset);

			if($is_numeric_identifier==false){
				while(($current_result=mysql_fetch_assoc($this->result))){
					$array_to_return[$counter]=$current_result[$field_identifier];
					$counter++;
					if($counter==abs($len)){
						break;
					}
				}
			}
			elseif($is_numeric_identifier==true){
				while(($current_result=mysql_fetch_row($this->result))){
					$array_to_return[$counter]=$current_result[$field_identifier];
					$counter++;
					if($counter==abs($len)){
						break;
					}
				}
			}
			else{
				return array();
			}


			if($result_last_position<$num_records){
				mysql_data_seek($this->result,$result_last_position);
			}
			if($flag_to_reverce_array){
				$array_to_return=array_reverse($array_to_return);
			}
		}
		return $array_to_return;
	}

	/**
	 * Get name of specified field
	 *
	 * @param int $offset
	 * @return string
	 */
	public function fieldName($offset){
		if($this->result and $offset>=0 and $offset<$this->countFields()){
			return mysql_fieldName($this->result,$offset);
		}
		return false;
	}

	/**
	 * Get type of specified field
	 *
	 * @param int $offset
	 * @return string
	 */
	public function fieldType($offset){
		if($this->result and $offset>=0 and $offset<$this->countFields()){
			return mysql_fieldType($this->result,$offset);
		}
		return false;
	}
	/**
	 * Get length of specified field
	 *
	 * @param int $offset
	 * @return int
	 */
	public function fieldLen($offset){
		if($this->result and $offset>=0 and $offset<$this->countFields()){
			return mysql_fieldLen($this->result,$offset);
		}
		return false;
	}
	/**
	 * Get flags of specified field
	 *
	 * @param int $offset
	 * @return array
	 */
	public function fieldFlags($offset){
		if($this->result and $offset>=0 and $offset<$this->countFields()){
			return explode(" ",mysql_fieldFlags($this->result,$offset));
		}
		return false;
	}
	/**
	 * Get full information about specified field
	 *
	 * @param int $offset
	 * @return array
	 */
	public function fieldInfo($offset){
		if($this->result and $offset>=0 and $offset<$this->countFields()){
			return array('name'=>$this->fieldName($offset),
			'type'=>$this->fieldType($offset),
			'len'=>$this->fieldLen($offset),
			'flags'=>$this->fieldFlags($offset));
		}
		return false;
	}

	/**
	 * Get found rows count from select query which
	 * uses SQL_CALC_FOUND_ROWS parameter
	 *
	 * @return integer
	 */
	public function getFoundRowsCount(){
		$this->exec("SELECT FOUND_ROWS() AS `cnt`");
		return $this->fetchField('cnt');
	}
	
	public function executeSQLFile($file, $delimiter = ';'){
		$matches = array();
		$otherDelimiter = false;
		if (is_file($file) === true) {
			$file = fopen($file, 'r');
			if (is_resource($file) === true) {
				$query = array();
				while (feof($file) === false) {
					$query[] = fgets($file);
					if (preg_match('~' . preg_quote('delimiter', '~') . '\s*([^\s]+)$~iS', end($query), $matches) === 1){
						//DELIMITER DIRECTIVE DETECTED
						array_pop($query); //WE DON'T NEED THIS LINE IN SQL QUERY
						if( $otherDelimiter = ( $matches[1] != $delimiter )){
						}
						else{
							// THIS IS THE DEFAULT DELIMITER, DELETE THE LINE BEFORE THE LAST (THAT SHOULD BE THE NOT DEFAULT DELIMITER) AND WE SHOULD CLOSE THE STATEMENT
							array_pop($query);
							$query[]=$delimiter;
						}
					}
					if ( !$otherDelimiter && preg_match('~' . preg_quote($delimiter, '~') . '\s*$~iS', end($query)) === 1) {
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