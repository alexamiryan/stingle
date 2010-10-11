<?
class ConfigManager
{
	private static $globalConfig;
	
	private static $cache;
	
	/**
	 * Set Global Config
	 * @param $config
	 */
	public static function setGlobalConfig($config){
		if(is_object($config) and is_a($config, "Config")){
			self::$globalConfig = $config;
		}
		elseif(is_array($config)){
			self::$globalConfig = new Config($config);
		}
		else{
			throw new InvalidArgumentException("Invalid value for \$config parameter");
		}
	}
	
	/**
	 * Returns whole global config
	 * @return Config
	 */
	public static function getGlobalConfig(){
		return self::$globalConfig;
	}
	
	/**
	 * Get Package config
	 * @param string $packageName
	 * @return Config
	 */
	public static function getPackageGlobalConfig($packageName){
		if(empty($packageName)){
			throw new InvalidArgumentException("\$packageName is empty");
		}
		
		if(isset(self::$globalConfig->$packageName)){
			return self::$globalConfig->$packageName;
		}
		else{
			return new Config();
		}
	}
	
	/**
	 * Get plugin config
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
		
		if(isset(self::$cache->$packageName) and isset(self::$cache->$packageName->$pluginName)){
			return self::$cache->$packageName->$pluginName;
		}
		
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
		
		if(isset(self::$globalConfig->$packageName) and isset(self::$globalConfig->$packageName->$pluginName)){
			$globalConfig = self::$globalConfig->$packageName->$pluginName;
		}
		
		$result = self::mergeConfigs($globalConfig, $defaultConfigObj);
		
		if(!isset(self::$cache->$packageName)){
			self::$cache->$packageName = new Config();
		}
		self::$cache->$packageName->$pluginName = $result;
		
		return $result;
	}
	
	/**
	 * Merge two Config objects
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
				$slaveConfig->$key = self::mergeConfigs($masterConfig->$key, $slaveConfig->$key);
			}
			else{
				$slaveConfig->$key = $value;
			}
		}
		return $slaveConfig;
	}
	
	public static function addConfig($where, $key, $value){
		$currentObj = &self::$globalConfig;
		$currentCache = &self::$cache;
		
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
}
?>