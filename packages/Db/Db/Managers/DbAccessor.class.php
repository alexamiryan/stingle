<?php
abstract class DbAccessor extends Model{
	
	/**
	 * MySQL database instance
	 *
	 * @var MySqlDatabase
	 */
	private $db;
	
	/**
	 * MySQL query manager instance
	 *
	 * @var MySQLQuery
	 */
	protected $query;
	
	/**
	 * Db Instance key that is currently beeing used
	 * 
	 * @var $dbInstanceKey string
	 */
	protected $dbInstanceKey;
	
	
	public function __construct($dbInstanceKey = null){
		if($dbInstanceKey === null){
			$this->dbInstanceKey = MySqlDbManager::getDefaultInstanceKey();
		}
		else{
			$this->dbInstanceKey = $dbInstanceKey;
		}
		
		$this->init();
	}
	
	public function __clone(){
		$this->query = clone $this->query;
	}
	
	public function init(){
		$this->db = MySqlDbManager::getDbObject($this->dbInstanceKey);
		$this->query = MySqlDbManager::getQueryObject($this->dbInstanceKey);
	}
	
	public function getDatabase(){
		return $this->db;
	}
	
	public function getQueryInstance(){
		return $this->query;
	}
	
	public function getDbInstanceKey(){
		return $this->dbInstanceKey;
	}
	
	public function setLogger(Logger $logger){
		$this->query->setLogger($logger);
	}
	
	public function setLogging($bool){
		$this->query->setLogging($bool);
	}
	
	public function getLogging(){
		return $this->query->getLogging();
	}
}
