<?php

class MongoDB {

	/**
	 *
	 * @var Config
	 */
	protected $config;
	
	protected $mongo;
	
	public function __construct(Config $config) {
		$this->config = $config;
		
		$this->mongo = new MongoDB\Client($config->dbConnection);
	}
	
	public function getDb($dbName = null){
        if($dbName === null){
            $dbName = $this->config->dbName;
        }
        return $this->mongo->$dbName;
    }
	
	public function getCollection($collectionName, $dbName = null){
		if(empty($collectionName)){
			throw new InvalidArgumentException("collectioName cannot be empty");
		}
		
		if($dbName === null){
			$dbName = $this->config->dbName;
		}
		return $this->mongo->$dbName->$collectionName;
	}
	
	public function getMongo(){
	    return $this->mongo;
    }
	
	public static function getMongoDate($date = null){
		return new MongoDB\BSON\UTCDateTime($date);
	}
	
}
