<?php
class ConfigManager
{
	private static $globalConfig;
	
	private static $cache;
	
	/**
	 * Set Global Config
	 * 
	 * @param $config
	 */
	public static function setGlobalConfig($config){
		if(is_object($config) and is_a($config, "Config")){
			static::$globalConfig = $config;
		}
		elseif(is_array($config)){
			static::$globalConfig = new Config($config);
		}
		else{
			throw new InvalidArgumentException("Invalid value for \$config parameter");
		}
	}
	
	/**
	 * Returns whole global config
	 * 
	 * @return Config
	 */
	public static function getGlobalConfig(){
		return static::$globalConfig;
	}
	
	/**
	 * Get Package config
	 * 
	 * @param string $packageName
	 * @return Config
	 */
	public static function getPackageGlobalConfig($packageName){
		if(empty($packageName)){
			throw new InvalidArgumentException("\$packageName is empty");
		}
		
		if(isset(static::$globalConfig->$packageName)){
			return static::$globalConfig->$packageName;
		}
		else{
			return new Config();
		}
	}
	
	public static function setCache(Config $config){
		static::$cache = $config;
	}
	
	/**
	 * Get plugin config
	 * 
	 * @param string $packageName
	 * @param string $pluginName
	 * @return Config
	 */
	public static function getConfig($packageName, $pluginName = null){
		if(empty($packageName)){
			throw new InvalidArgumentException("\$packageName is empty");
		}
		if($pluginName === null){
			$pluginName = $packageName;
		}
		
		if(!is_object(static::$cache)){
			static::$cache = new Config();
		}
		
		if(isset(static::$cache->$packageName) and isset(static::$cache->$packageName->$pluginName)){
			return static::$cache->$packageName->$pluginName;
		}
		
		//echo "$packageName - $pluginName<br>\n";
		
		if(file_exists(SITE_PACKAGES_PATH . "{$packageName}/{$pluginName}/DefaultConfig.inc.php")){
			include(SITE_PACKAGES_PATH . "{$packageName}/{$pluginName}/DefaultConfig.inc.php");
		}
		elseif(file_exists(STINGLE_PATH . "packages/{$packageName}/{$pluginName}/DefaultConfig.inc.php")){
			include(STINGLE_PATH . "packages/{$packageName}/{$pluginName}/DefaultConfig.inc.php");
		}
		else{
			$defaultConfig = array();
		}
		$defaultConfigObj = new Config($defaultConfig);
		
		if(isset(static::$globalConfig->$packageName) and isset(static::$globalConfig->$packageName->$pluginName)){
			$globalConfig = static::$globalConfig->$packageName->$pluginName;
		}
		else{
			$globalConfig = new Config();
		}
		
		$result = static::mergeConfigs($globalConfig, $defaultConfigObj);
		
		if(!isset(static::$cache->$packageName)){
			static::$cache->$packageName = new Config();
		}
		static::$cache->$packageName->$pluginName = $result;
		
		return $result;
	}
	
	/**
	 * Merge two Config objects
	 * 
	 * @param Config $masterConfig
	 * @param Config $slaveConfig
	 * @return Config
	 */
	public static function mergeConfigs(Config $masterConfig = null, Config $slaveConfig = null){
		if($masterConfig !== null and $slaveConfig ===null){
			return $masterConfig;
		}
		elseif($masterConfig === null and $slaveConfig !==null){
			return $slaveConfig;
		}
		elseif($masterConfig === null and $slaveConfig ===null){
			return new Config();
		}
		
		foreach (get_object_vars($masterConfig) as $key => $value){
			if(is_a($value,"Config")){
				if(!isset($slaveConfig->$key)){
					$slaveConfig->$key = new Config();
				}
				$slaveConfig->$key = static::mergeConfigs($masterConfig->$key, $slaveConfig->$key);
			}
			else{
				$slaveConfig->$key = $value;
			}
		}
		return $slaveConfig;
	}
	
	/**
	 * Add config into existing one
	 * 
	 * @param array $where
	 * @param string $key
	 * @param string $value
	 */
	public static function addConfig($where, $key, $value){
		if(!is_object(static::$cache)){
			static::$cache = new Config();
		}
		$currentObj = &static::$globalConfig;
		$currentCache = &static::$cache;
		
		$objCounter = 0;
		$cacheCounter = 0;
		
		foreach ($where as $this_where){
			if(isset($currentCache->$this_where)){
				$currentCache = $currentCache->$this_where;
				$cacheCounter++;
			}
			if(!isset($currentObj->$this_where)){
				$currentObj->$this_where = new Config();
			}
			$currentObj = &$currentObj->$this_where;
			$objCounter++;
		}
		$currentObj->$key = $value;
		if($objCounter == $cacheCounter){
			$currentCache = null;
		}
	}
	
	/**
	 * Function get sub config from Global config
	 * @param Array $location
	 * @throws InvalidArgumentException
	 * @return Config
	 */
	public static function getSubConfig($location = array(), Config $sourceConfig = null){
		if(!is_array($location)){
			throw new InvalidArgumentException("Given argument must be array");
		}
		if($sourceConfig == null){
			$currentObj = &static::$globalConfig;
		}
		else{
			$currentObj = &$sourceConfig;
		}
		
		foreach ($location as $this_where){
			if(!isset($currentObj->$this_where)){
				$currentObj->$this_where = new Config();
			}
			$currentObj = &$currentObj->$this_where;
		}
		return $currentObj;
	}
}
