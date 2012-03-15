<?
class ConfigDBManager{
	
	const TBL_CONFIGS = "configs";
	
	/**
	 * Get configs from DB and merge them with global config
	 * 
	 * @param int $cacheMinutes
	 */
	public static function initDBConfig($cacheMinutes = null){
		$sql = MySqlDbManager::getQueryObject();
		
		$sql->exec("SELECT * FROM `".Tbl::get("TBL_CONFIGS")."`", $cacheMinutes);
		$dbConfig = static::parseDBRowsToConfig($sql->fetchRecords());
		
		ConfigManager::setGlobalConfig(ConfigManager::mergeConfigs($dbConfig, ConfigManager::getGlobalConfig()));
	}
	
	/**
	 * Translates config DB rows into Config object
	 * 
	 * @param array $dbRows
	 * @return Config
	 */
	protected static function parseDBRowsToConfig($dbRows){
		$configArray = array();
		foreach($dbRows as $row){
			$location = explode(":", $row['location']);
			$thisConfig = &$configArray;
			for ($i=0; $i<count($location);$i++){
				$locationPart = $location[$i];
				if(!isset($thisConfig[$locationPart]) or !is_array($thisConfig[$locationPart])){
					$thisConfig[$locationPart] = array();
				}
				$thisConfig = &$thisConfig[$locationPart];
				if($i == count($location)-1){
					$thisConfig[$row['key']] = $row['value'];
				}
			}
		}
		
		return new Config($configArray);
	}

	/**
	 * Adds new config into database
	 * 
	 * @param array $location
	 * @param string $key
	 * @param string $value
	 * @throws InvalidArgumentException
	 */
	public static function addConfig($location, $key, $value){
		if(empty($location) or !is_array($location)){
			throw new InvalidArgumentException("Location of new config should be non empty array");
		}
		if(empty($key)){
			throw new InvalidArgumentException("Key of new config should be specified");
		}
		if(empty($value)){
			throw new InvalidArgumentException("Value of new config should be specified");
		}
		
		$sql = MySqlDbManager::getQueryObject();
		
		$sql->exec("INSERT INTO `".Tbl::get("TBL_CONFIGS")."` (`location`, `key`, `value`)
							VALUES ('".implode(":", $location)."', '$key', '$value')");
	}
	
	/**
	 * Delete given set of configs from DB
	 * 
	 * @param array $location
	 * @param string $key
	 * @throws InvalidArgumentException
	 */
	public static function deleteConfig($location, $key = null){
		if(empty($location) or !is_array($location)){
			throw new InvalidArgumentException("Location of should be non empty array");
		}
		
		$sql = MySqlDbManager::getQueryObject();
		
		$sql->exec("DELETE FROM `".Tbl::get("TBL_CONFIGS")."` WHERE `location` = '".implode(":", $location)."'".
						($key !== null ? " AND `key`='$key'" : ""));
	}
	
	/**
	 * Update DB config's value
	 * 
	 * @param array $location
	 * @param string $key
	 * @param string $newValue
	 * @throws InvalidArgumentException
	 */
	public static function updateConfigValue($location, $key, $newValue){
		if(empty($location) or !is_array($location)){
			throw new InvalidArgumentException("Location of new config should be non empty array");
		}
		if(empty($key)){
			throw new InvalidArgumentException("Key of new config should be specified");
		}
		if(empty($newValue)){
			throw new InvalidArgumentException("Value of new config should be specified");
		}
		
		$sql = MySqlDbManager::getQueryObject();
		
		$sql->exec("UPDATE `".Tbl::get("TBL_CONFIGS")."` SET `value` = '$newValue'
						WHERE `location`='".implode(":", $location)."' AND `key` = '$key'");
	}
	
	/**
	 * Get given set of Configs from DB
	 * Location is optional.
	 * 
	 * @param array $location
	 * @throws InvalidArgumentException
	 */
	public static function getDBConfig($location = null){
		if($location !== null and (empty($location) or !is_array($location))){
			throw new InvalidArgumentException("Location of new config should be non empty array");
		}
		
		$sql = MySqlDbManager::getQueryObject();
		
		$additionalSQL = "";
		if($location !== null){
			$additionalSQL = " WHERE `location` LIKE '".implode(":", $location)."%'";
		}
		
		$sql->exec("SELECT * FROM `".Tbl::get("TBL_CONFIGS")."`" .  $additionalSQL);
		
		return static::parseDBRowsToConfig($sql->fetchRecords());
	}
}
?>