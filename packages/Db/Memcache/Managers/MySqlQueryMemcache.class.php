<?php
/**
 * Class query with memcahce support
 */

class MySqlQueryMemcache extends MySqlQuery{
	
	public $memcache = null;
	private $memcacheConfig;
	private $isResultCached = false;

	/**
	 * Class constructor
	 *
	 * @param MySqlDatabase db
	 * @param Logger $logger
	 * @param bool $memcahe_on
	 *
	 */
	public function __construct($instanceName = null, Logger $logger = null){
		parent::__construct($instanceName, $logger);
		
		$this->memcacheConfig = ConfigManager::getConfig("Db", "Memcache")->AuxConfig;
		
		if(strpos($this->memcacheConfig->keyPrefix, ":")){
			throw new RuntimeException("Memcache key prefix can't contain colon \":\"!");
		}
		
		$this->memcache = Reg::get('memcache');
	}

	private function findDefaultMemcacheConfig(){
		$backtrace = debug_backtrace();
		if(!empty($backtrace) and count($backtrace)>=3 and !empty($backtrace[2]['class'])){
			$calling_class = $backtrace[2]['class'];

			$current_class = $calling_class;
			while($current_class !== false){
				if(isset($this->memcacheConfig->Time->$current_class)){
					return $this->memcacheConfig->Time->$current_class;
				}
				$current_class = get_parent_class($current_class);
			}
		}
		return MemcacheWrapper::MEMCACHE_OFF;
	}
	
	protected function getMemcacheKey($sqlQuery, $tag = null){
		return $this->memcache->getNamespacedKey($tag) . ":" . md5($sqlQuery);
	}
	
	public function getFoundRowsCount($cacheMinutes = 0, $cacheTag = null){
		$this->exec("SELECT FOUND_ROWS() AS `cnt`", $cacheMinutes, $cacheTag);
		return $this->fetchField('cnt');
	}
	
	/**
	 * Execute SQL query
	 *
	 * @param string $sql_statement
	 * @param int $cacheMinutes (in minutes) -1 - Unlimited, 0 - Turned off, >0 for given amount of minutes
	 * @return MySqlQueryMemcache
	 */
	public function exec($sqlStatement, $cacheMinutes = MemcacheWrapper::MEMCACHE_OFF, $cacheTag = null){
		if($cacheMinutes === null){
			$cacheMinutes = $this->findDefaultMemcacheConfig(); 
		}
		$enabledStatus = ConfigManager::getConfig("Db", "Memcache")->AuxConfig->enabled;
		
		$this->isResultCached = false;
		if($enabledStatus and $this->memcache !== null and $cacheMinutes != MemcacheWrapper::MEMCACHE_OFF){
			$cache = $this->memcache->get($this->getMemcacheKey($sqlStatement, $cacheTag));
			
			if($cache !== false and is_array($cache)){
				$this->result = $cache;
				$this->isResultCached = true;
				
				$this->last_fetch_type='';
				$this->last_field_position=0;
				$this->last_record_position=0;
				return $this;
			}
			else{
				parent::exec($sqlStatement);
				$resultset = parent::fetchRecords();
				
				if($cacheMinutes > 0){
					$memcache_expire_time = time() + ($cacheMinutes * 60);
				}
				elseif($cacheMinutes == -1){
					$memcache_expire_time = 0;
				}
				$this->memcache->set( $this->getMemcacheKey($sqlStatement, $cacheTag), $resultset, $memcache_expire_time );
				
				return $this;
			}
		}
		else{
			return parent::exec($sqlStatement);
		}
	}
	
/**
	 * Analog of mysql_num_rows()
	 *
	 * @return int $number
	 */
	public function countRecords(){
		if($this->isResultCached){
			return count($this->result);
		}
		else{
			return parent::countRecords();
		}
	}
	
	/**
	 * Analog of mysql_num_fields()
	 *
	 * @return int $number
	 */
	public function countFields(){
		if($this->isResultCached){
			return count($this->result[0]);
		}
		else{
			return parent::countFields();
		}
	}
	
	/**
	 * Fetch one row and move cursor to nex row
	 *
	 * @param int $type (For non cached results)(0-Normal Array, 1-Associative Array, 2-Object)
	 * @return array
	 */
	public function fetchRecord($type=1){
		if($this->isResultCached){
			if(!isset($this->result[$this->last_record_position])){
				return array();
			}
			$record = $this->result[$this->last_record_position];
			$this->last_record_position++;
			return $this->convertRowType($record, $type);
		}
		else{
			return parent::fetchRecord($type);
		}
	}
	
	/**
	 * Fetch one fileld from row
	 *
	 * @param string $field_identifier (both numeric and associative)
	 * @param int $is_numeric (For non cached results)(0-$field_identifier is name of the field,1-$field_identifier is number of the field)
	 * @return string
	 */
	public function fetchField($field_identifier, $is_numeric=0){
		if($this->isResultCached){
			if(!isset($this->result[$this->last_record_position])){
				return null;
			}
			
			if($is_numeric){
				$keys = array_keys($this->result[$this->last_record_position]);
				$field = $this->result[$this->last_record_position][$keys[$field_identifier]];
			}
			else{
				$field = $this->result[$this->last_record_position][$field_identifier];
			}
			
			$this->last_record_position++;
			return $field;
		}
		else{
			return parent::fetchField($field_identifier, $is_numeric);
		}
	}
	
	/**
	 * Get array of the query
	 *
	 * @param int $offset (For non cached results)
	 * @param int $len (For non cached results)
	 * @param int $fields_type (0-Normal,1-Assoc, 2-Object)
	 * @param int $rows_type((0-Normal,1-Assoc, 2-Object))
	 * @param string $rows_type_field (name of the field to become index for Assoc $rows_type)
	 * @return array
	 */
	public function fetchRecords($offset=0, $len=0, $fields_type=1, $rows_type=0, $rows_type_field=''){
		if($this->isResultCached){
			if ($rows_type == 1 and !empty($rows_type_field)){
				$array_to_return = array();
				foreach ($this->result as $key=>$val){
					$array_to_return[$val[$rows_type_field]] = $val;
				}
				return $this->convertResultSetType($array_to_return, $fields_type);
			}
			elseif ($rows_type==0){
				return $this->convertResultSetType($this->result, $fields_type);
			}
		}
		else{
			return parent::fetchRecords($offset, $len, $fields_type, $rows_type, $rows_type_field);
		}
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
		if($this->isResultCached){
			$array_to_return = array();
			foreach ($this->result as $key=>$val){
				if($is_numeric_identifier){
					$keys = array_keys($val);
					$array_to_return[] = $val[$keys[$field_identifier]];
				}
				else{
					$array_to_return[] = $val[$field_identifier];
				}
			}
			return $array_to_return;
		}
		else{
			return parent::fetchFields($field_identifier, $is_numeric_identifier, $offset, $len);
		}
	}
	
	private function convertResultSetType($resultset, $type){
		foreach ($resultset as &$row){
			$row = $this->convertRowType($row, $type);
		}
		return $resultset;
	}
	
	private function convertRowType($row, $type){
		switch ($type){
			case 0:
				$converted = array();
				foreach ($row as $key=>$val){
					$converted[]=$val;
				}
				return $converted;
			case 1:
				return $row;
			case 2:
				$array_of_objects = array();
				
				$obj = new stdClass();
				foreach ($row as $key=>$val){
					$obj->$key = $val;
				}
				return $obj;
		}
	}
}