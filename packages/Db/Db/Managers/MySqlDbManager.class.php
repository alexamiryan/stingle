<?php

class MySqlDbManager {

	const DEFAULT_INSTANCE_NAME = 'default';
	
	const ENDPOINT_TYPE_RW = 'rw';
	const ENDPOINT_TYPE_RO = 'ro';

	private static $dbClassName = "MySqlDatabase";
	private static $queryClassName = "MySqlQuery";
	
	protected static $instances = [
		self::DEFAULT_INSTANCE_NAME => [
			self::ENDPOINT_TYPE_RW => null,
			self::ENDPOINT_TYPE_RO => []
		]
	];
	
	protected static $instanceConf = [
		self::DEFAULT_INSTANCE_NAME => []
	];

	
	public static function init(Config $config) {
		foreach ($config->toArray() as $instanceName => $dbConf) {
			if (!isset(self::$instances[$instanceName])) {
				self::$instances[$instanceName] = [
					self::ENDPOINT_TYPE_RW => null,
					self::ENDPOINT_TYPE_RO => []
				];
			}
			if (!isset(self::$instanceConf[$instanceName])) {
				self::$instanceConf[$instanceName] = [];
			}
			
			
			foreach ($dbConf->endpoints->toArray() as $endpointConf) {
				self::initEndpointFromConf($instanceName, $endpointConf);
			}
			
			self::$instanceConf[$instanceName]['readsFromRWEndpoint'] = $dbConf->readsFromRWEndpoint;
		}
	}

	protected static function initEndpointFromConf($instanceName, Config $endpointConf) {
		if ($endpointConf->type === self::ENDPOINT_TYPE_RW) {
			if (self::$instances[$instanceName][self::ENDPOINT_TYPE_RW] !== null) {
				throw new MySqlException("Invalid config. Instance can have only one RW endpoint", 0);
			}
			
			self::$instances[$instanceName][self::ENDPOINT_TYPE_RW] = self::createEndpoint($endpointConf->host, $endpointConf->user, $endpointConf->password, $endpointConf->name, $endpointConf->isPersistent, $endpointConf->encoding, false);
		}
		elseif ($endpointConf->type === self::ENDPOINT_TYPE_RO) {
			self::$instances[$instanceName][self::ENDPOINT_TYPE_RO][] = self::createEndpoint($endpointConf->host, $endpointConf->user, $endpointConf->password, $endpointConf->name, $endpointConf->isPersistent, $endpointConf->encoding, true);
		}
	}

	/**
	 * Creates a new database instance and returns key for it
	 *
	 * @param $server
	 * @param $username
	 * @param $password
	 * @param $db_name
	 * @param $persistency
	 *
	 * @return mixed Automatically generated key for current instance
	 */
	public static function createEndpoint($server, $username, $password, $db_name, $persistency = true, $encoding = 'UTF-8', $isRO = false) {
		$db = new static::$dbClassName($server, $username, $password, $db_name, $persistency);
		$db->setConnectionEncoding($encoding);
		$db->setIsRO($isRO);
		return $db;
	}

	/**
	 * Returns an instance of MySqlDatabase
	 * by given key
	 *
	 * @param $key instance key
	 *
	 * @return MySqlDatabase
	 */
	public static function getDbObject($instanceName = null, $type = null) {
		if($instanceName === null){
			$instanceName = self::DEFAULT_INSTANCE_NAME;
		}
		if($type === null){
			$type = self::ENDPOINT_TYPE_RW;
		}
		
		if (!isset(self::$instances[$instanceName])) {
			throw new MySqlException("Database instance with name $instanceName not initialized.", 0);
		}

		if($type === self::ENDPOINT_TYPE_RW){
			return self::$instances[$instanceName][self::ENDPOINT_TYPE_RW];
		}
		elseif($type === self::ENDPOINT_TYPE_RO){
			if(self::$instanceConf[$instanceName]['readsFromRWEndpoint']){
				$availableEPs = array_merge([self::$instances[$instanceName][self::ENDPOINT_TYPE_RW]], self::$instances[$instanceName][self::ENDPOINT_TYPE_RO]);
				return $availableEPs[array_rand($availableEPs)];
			}
			
			if(count(self::$instances[$instanceName][self::ENDPOINT_TYPE_RO]) > 0){
				return self::$instances[$instanceName][self::ENDPOINT_TYPE_RO][array_rand(self::$instances[$instanceName][self::ENDPOINT_TYPE_RO])];
			}
			else{
				return self::$instances[$instanceName][self::ENDPOINT_TYPE_RW];
			}
		}
	}

	/**
	 * Returns an instance of MySqlQuery
	 * by given key
	 *
	 * @param $instanceName instance name
	 *
	 * @return MySqlQuery
	 */
	public static function getQueryObject($instanceName = self::DEFAULT_INSTANCE_NAME) {
		return new static::$queryClassName($instanceName);
	}

	/**
	 * Set DB class name
	 */
	public static function setDbClassName($name) {
		if (empty($name)) {
			throw new InvalidArgumentException("Class name is empty");
		}

		static::$dbClassName = $name;
	}

	/**
	 * Set Query class name
	 */
	public static function setQueryClassName($name) {
		if (empty($name)) {
			throw new InvalidArgumentException("Class name is empty");
		}

		static::$queryClassName = $name;
	}

}
