<?php
abstract class DbAccessor extends Model{
	
	/**
	 * MySQL query manager instance
	 *
	 * @var MySQLQuery
	 */
	protected $query;
	
	/**
	 * Db Instance name to use for this class
	 * 
	 * @var $instanceName string
	 */
	protected $instanceName;
	
	
	public function __construct($instanceName = null){
		if($instanceName === null){
			$this->instanceName = MySqlDbManager::DEFAULT_INSTANCE_NAME;
		}
		
		$this->query = MySqlDbManager::getQueryObject($instanceName);
	}
	
	public function __clone(){
		$this->query = clone $this->query;
	}
	
	public function getQueryInstance(){
		return $this->query;
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
