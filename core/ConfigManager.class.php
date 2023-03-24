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
	
	public static function initCache(){
		if(!defined('DISABLE_APCU') && extension_loaded('apcu')){
			$cache = apcu_fetch('configCache');
			if($cache !== false){
				static::$cache = $cache;
			}
		}
	}
	
	public static function setCache(Config $config){
		static::$cache = $config;
	}
	
	public static function getCache(){
		return static::$cache;
	}
	
	public static function storeCache(){
		if(!defined('DISABLE_APCU') && extension_loaded('apcu')){
			apcu_store('configCache', static::$cache);
		}
	}
	
	public static function flushCache(){
		if(!defined('DISABLE_APCU') && extension_loaded('apcu')){
			apcu_delete('configCache');
		}
	}
	
	/**
	 * Get plugin config
	 * 
	 * @param string $packageName
	 * @param string $pluginName
	 * @return Config
	 */
	public static function getConfig($packageName, $pluginName = null, $ignoreCache = false){
		if(empty($packageName)){
			throw new InvalidArgumentException("\$packageName is empty");
		}
		if($pluginName === null){
			$pluginName = $packageName;
		}
		
		if(isset(static::$globalConfig->$packageName) and isset(static::$globalConfig->$packageName->$pluginName)){
			$globalConfig = static::$globalConfig->$packageName->$pluginName;
		}
		else{
			$globalConfig = new Config();
		}
		
		if(!is_object(static::$cache)){
			static::$cache = $globalConfig;
		}
		
		if(!$ignoreCache and isset(static::$cache->$packageName) and isset(static::$cache->$packageName->$pluginName)){
			return static::$cache->$packageName->$pluginName;
		}
        
        $found = false;
        foreach (AddonManager::get() as $path) {
            if (file_exists($path . "packages/{$packageName}/{$pluginName}/DefaultConfig.inc.php")) {
                include($path . "packages/{$packageName}/{$pluginName}/DefaultConfig.inc.php");
                $found = true;
            }
        }
        if(!$found) {
            if (file_exists(SITE_PACKAGES_PATH . "{$packageName}/{$pluginName}/DefaultConfig.inc.php")) {
                include(SITE_PACKAGES_PATH . "{$packageName}/{$pluginName}/DefaultConfig.inc.php");
            } elseif (file_exists(STINGLE_PATH . "packages/{$packageName}/{$pluginName}/DefaultConfig.inc.php")) {
                include(STINGLE_PATH . "packages/{$packageName}/{$pluginName}/DefaultConfig.inc.php");
            } else {
                $defaultConfig = array();
            }
        }
		$defaultConfigObj = new Config($defaultConfig);
		
		$result = static::mergeConfigs($globalConfig, $defaultConfigObj);
		
		if(!isset(static::$cache->$packageName)){
			static::$cache->$packageName = new Config();
		}
		static::$cache->$packageName->$pluginName = $result;
		static::storeCache();
		
		return $result;
	}
	
	public static function refreshPluginCache($packageName, $pluginName = null){
		static::getConfig($packageName, $pluginName, true);
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
		$currentObj = &static::$cache;
		
		foreach ($where as $thisWhere){
			if(!isset($currentObj->$thisWhere)){
				$currentObj->$thisWhere = new Config();
			}
			$currentObj = &$currentObj->$thisWhere;
		}
		$currentObj->$key = $value;
		
		//static::storeCache();
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
			$currentObj = &static::$cache;
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
