<?php

class MySqlDbManager {

	const INSTANCE_TYPE_RW = 'rw';
	const INSTANCE_TYPE_RO = 'ro';
	const DEFAULT_INSTANCE_NAME = 'default';

	private static $dbClassName = "MySqlDatabase";
	private static $queryClassName = "MySqlQuery";
	
	protected static $instances = [
		self::DEFAULT_INSTANCE_NAME => [
			self::INSTANCE_TYPE_RW => null,
			self::INSTANCE_TYPE_RO => []
		]
	];

	
	public static function init(Config $config) {
		foreach ($config->toArray() as $dbName => $dbConf) {
			foreach ($dbConf->toArray() as $hostConf) {
				self::initInstanceFromConf($dbName, $hostConf);
			}
		}
	}

	protected static function initInstanceFromConf($dbName, Config $config) {
		if (!isset(self::$instances[$dbName])) {
			self::$instances[$dbName] = [
				self::INSTANCE_TYPE_RW => null,
				self::INSTANCE_TYPE_RO => []
			];
		}
		if ($config->type === self::INSTANCE_TYPE_RW) {
			if (self::$instances[$dbName][self::INSTANCE_TYPE_RW] !== null) {
				throw new MySqlException("Invalid config. Instance can have only one RW node", 0);
			}
			
			self::$instances[$dbName][self::INSTANCE_TYPE_RW] = self::createInstance($config->host, $config->user, $config->password, $config->name, $config->isPersistent, $config->encoding, false);
		}
		elseif ($config->type === self::INSTANCE_TYPE_RO) {
			self::$instances[$dbName][self::INSTANCE_TYPE_RO][] = self::createInstance($config->host, $config->user, $config->password, $config->name, $config->isPersistent, $config->encoding, true);
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
	public static function createInstance($server, $username, $password, $db_name, $persistency = true, $encoding = 'UTF-8', $isRO = false) {
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
			$type = self::INSTANCE_TYPE_RW;
		}
		
		if (!isset(self::$instances[$instanceName])) {
			throw new MySqlException("Database instance with name $instanceName not initialized.", 0);
		}

		if($type === self::INSTANCE_TYPE_RW){
			return self::$instances[$instanceName][self::INSTANCE_TYPE_RW];
		}
		elseif($type === self::INSTANCE_TYPE_RO){
			if(count(self::$instances[$instanceName][self::INSTANCE_TYPE_RO]) > 0){
				return self::$instances[$instanceName][self::INSTANCE_TYPE_RO][array_rand(self::$instances[$instanceName][self::INSTANCE_TYPE_RO])];
			}
			else{
				return self::$instances[$instanceName][self::INSTANCE_TYPE_RW];
			}
		}
	}

	/**
	 * Returns an instance of MySqlQuery
	 * by given key
	 *
	 * @param $key instance key
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
