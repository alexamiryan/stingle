<?
class ConfigDBManager{
	
	const TBL_CONFIGS = "configs";
	
	/**
	 * Get configs from DB and merge them with global config
	 * 
	 * @param int $cacheMinutes
	 */
	public static function initDBConfig(ConfigDBFilter $filter = null, $cacheMinutes = 0){
		if($filter == null){
			$filter = new ConfigDBFilter();
			$filter->setCommon();
		}
		
		$sql = MySqlDbManager::getQueryObject();
		$sql->exec($filter->getSQL(), $cacheMinutes);
		$dbConfig = static::parseDBRowsToConfig($sql->fetchRecords());
		
		ConfigManager::setGlobalConfig(ConfigManager::mergeConfigs($dbConfig, ConfigManager::getGlobalConfig()));
	}
	
	/**
	 * 
	 * Adds new config into database
	 * @param ConfigDB $config
	 * @throws InvalidArgumentException
	 */
	public static function addConfig(ConfigDB $config){
		if(empty($config)){
			throw new InvalidArgumentException("\$config object is have toi be nopn empty ConfigDB object");
		}
		if(!is_array($config->location)){
			if(empty($config->locationString)){
				throw new InvalidArgumentException("location is empty string or locationArray is empty array");
			}
		}
		if(empty($config->name)){
			throw new InvalidArgumentException("Name of new config should be specified");
		}
		if(empty($config->value)){
			throw new InvalidArgumentException("Value of new config should be specified");
		}
			
		$sql = MySqlDbManager::getQueryObject();
		$qb = new QueryBuilder();
		$arrayValues = array();
		
		if(!empty($config->locationString)){
			$arrayValues["location"] = $config->locationString;
		}
		else{
			$arrayValues["location"] = implode(":", $config->location);
		}
		$arrayValues["name"] = $config->name;
		$arrayValues["value"] = $config->value;
		if(!empty($config->host) && !empty($config->language)){
			$arrayValues["host_lang_id"] = HostLanguageManager::getHostLanguageId($config->host, $config->language);
		}
		$qb->insert(Tbl::get("TBL_CONFIGS"))
			->values($arrayValues);
		
		$sql->exec($qb->getSQL());
	}
	
	/**
	 * Delete DB config
	 * @param ConfigDB $config
	 * @throws InvalidArgumentException
	 */
	public static function deleteConfig(ConfigDB $config){
		$location = null;
		if(empty($config)){
			throw new InvalidArgumentException("\$config object is have toi be nopn empty ConfigDB object");
		}
		if(empty($config->name)){
			throw new InvalidArgumentException("Name of new config should be specified");
		}
		if(empty($config->locationString)){
			if(!is_array($config->location)){
				throw new InvalidArgumentException("location is empty string or locationArray is empty array");
			}
			else{
				$location = implode(":", $config->location);
			}
		}
		else{
			$location = $config->locationString;
		}
		
				
		$sql = MySqlDbManager::getQueryObject();
		
		$qb = new QueryBuilder();
		$qb->delete(Tbl::get("TBL_CONFIGS"))
			->where($qb->expr()->equal(new Field('location'), $location));
		if(!empty($config->host) && !empty($config->language)){
			$hostLangId = HostLanguageManager::getHostLanguageId($config->host, $config->language);
			$qb->andWhere($qb->expr()->equal(new Field('host_lang_id'), $hostLangId));
		}
		else{
			$qb->andWhere($qb->expr()->isNull(new Field('host_lang_id')));
		}
		$qb->andWhere($qb->expr()->equal(new Field('name'), $config->name));
		
		$sql->exec($qb->getSQL());
	}
	
	/**
	 * Update DB Config
	 * @param ConfigDB $oldDBCOnfig
	 * @param ConfigDB $newDBConfig
	 * @throws InvalidArgumentException
	 */
	public static function updateConfigValue(ConfigDB $oldDBCOnfig,ConfigDB $newDBConfig){
		if(empty($oldDBCOnfig) || empty($newDBConfig)){
			throw new InvalidArgumentException("new or old DB config object is empty");
		}
		if(!isset($oldDBCOnfig->location) or empty($oldDBCOnfig->location)){
			throw new InvalidArgumentException("odl Location of config should be non empty array");
		}
		if(!isset($newDBConfig->location) or empty($newDBConfig->location)){
			throw new InvalidArgumentException("New Location of config should be non empty array");
		}
		if(!isset($oldDBCOnfig->name) or empty($oldDBCOnfig->name)){
			throw new InvalidArgumentException("Old Name of config should be specified");
		}
		if(!isset($newDBConfig->name) or empty($newDBConfig->name)){
			throw new InvalidArgumentException("New Name of config should be specified");
		}
		if(!isset($newDBConfig->value) or empty($newDBConfig->value)){
			throw new InvalidArgumentException("Value of new config should be specified");
		}
		$odlHostLangid = null;
		if(!empty($oldDBCOnfig->host) && !empty($oldDBCOnfig->language)){
			$odlHostLangid = HostLanguageManager::getHostLanguageId($oldDBCOnfig->host, $oldDBCOnfig->language);
		}
		$newHostLangId = null;
		if(!empty($newDBConfig->host) && !empty($newDBConfig->language)){
			$newHostLangId = HostLanguageManager::getHostLanguageId($newDBConfig->host, $newDBConfig->language);
		}
		
		$sql = MySqlDbManager::getQueryObject();
		$qb = new QueryBuilder();
		
		$qb->update(Tbl::get("TBL_CONFIGS"))
			->set(new Field("location"), implode(":", $newDBConfig->location))
			->set(new Field("name"), $newDBConfig->name)
			->set(new Field("value"), $newDBConfig->value)
			->where($qb->expr()->equal(new Field("location"), implode(":", $oldDBCOnfig->location)))
			->andWhere($qb->expr()->equal(new Field("name"), $oldDBCOnfig->name));
			
		if($newHostLangId !== null){
			$qb->set(new Field("host_lang_id"), $newHostLangId);
		}
		else{
			$qb->set(new Field("host_lang_id"), new Literal("null"));
		}
		if($odlHostLangid !== null){
			$qb->andWhere($qb->expr()->equal(new Field("host_lang_id"), $odlHostLangid));
		}
		else{
			$qb->andWhere($qb->expr()->isNull(new Field("host_lang_id")));
		}
		$sql->exec($qb->getSQL());
	}
	
	/**
	 * Get Config object from DB By id
	 * Location is optional.
	 * 
	 * @param array $location
	 * @throws InvalidArgumentException
	 */
	public static function getDBConfigById($dbConfigId){
		if(!is_numeric($dbConfigId)){
			throw new InvalidArgumentException("DB Config id is not numeric");
		}
		$filter = new ConfigDBFilter();
		$filter->setId($dbConfigId);
		
		return static::getDBConfig($filter);
	}
	
	/**
	 * Get DB configs list
	 * @param ConfigDBFilter $filter
	 * @param MysqlPager $pager
	 * @param Integer $cacheMinutes
	 */
	public static function getDBConfigsList(ConfigDBFilter $filter = null, MysqlPager $pager = null, $cacheMinutes = 0){
		if($filter == null){
			$filter = new ConfigDBFilter();
		}
		$sql = MySqlDbManager::getQueryObject();
		$sqlQuery = $filter->getSQL();
		//echo $sqlQuery; exit;
		if($pager !== null){
			$sql = $pager->executePagedSQL($sqlQuery, $cacheMinutes);
		}
		else{
			$sql->exec($sqlQuery, $cacheMinutes);
		}
		$configsDBArray = array();
		if($sql->countRecords() > 0){
			foreach($sql->fetchRecords() as $row){
					array_push($configsDBArray, static::getConfigDBFromData($row));
			}
		}
		return $configsDBArray;
	}
	
	/**
	 * Get DB Config object
	 * @param ConfigDBFilter $filter
	 * @param Integer $cacheMinutes
	 * @throws RuntimeException
	 */
	public static function getDBConfig(ConfigDBFilter $filter = null, $cacheMinutes = 0){
		$configsDBArray = static::getDBConfigsList($filter, null, $cacheMinutes);
		if(count($configsDBArray) !== 1){
			throw new RuntimeException("There is no DB Config or it is not unique!");
		}
		return $configsDBArray[0];
	}
	
	/**
	 * Get DB configs from DB and parse to Config objects
	 * @param ConfigDBFilter $filter
	 * @param unknown_type $cacheMinutes
	 */
	public static function getAndParsDBConfigs(ConfigDBFilter $filter = null, $cacheMinutes = 0){
		if($filter == null){
			$filter = new ConfigDBFilter();
		}
		$sql = MySqlDbManager::getQueryObject();
		$sql->exec($filter->getSQL(), $cacheMinutes);
		return static::parseDBRowsToConfig($sql->fetchRecords());
	}
	
	/**
	 * Get current database config aliases
	 * @param ConfigDB $configDB
	 * @throws InvalidArgumentException
	 * @return Array
	 */
	public static function getDBConfigAliases(ConfigDB $configDB){
		if(empty($configDB)){
			throw new InvalidArgumentException("ConfigDB object is empty!");
		}
		$qb = new QueryBuilder();
		$qb->select("*")
			->from(Tbl::get('TBL_CONFIGS'))
			->where($qb->expr()->equal(new Field("alias_of"), $configDB->id));
		$sql = MySqlDbManager::getQueryObject();
		$sql->exec($qb->getSQL());
		$aliasesArray = array();
		if($sql->countRecords() > 0){
			foreach($sql->fetchRecords() as $row){
					array_push($aliasesArray, static::getConfigDBFromData($row));
			}
		}
		return $aliasesArray;
		
	}
	
	/**
	 * Delete current config's all aliases
	 * @param ConfigDB $configDB
	 * @throws InvalidArgumentException
	 */
	public static function deleteDBConfigAllAliases(ConfigDB $configDB){
		if(empty($configDB)){
			throw new InvalidArgumentException("ConfigDB object is empty!");
		}
		if(!is_numeric($configDB->id)){
			throw new InvalidArgumentException("ConfigDB object's  id is not numeric!");
		}
		$qb = new QueryBuilder();
		$qb->delete(Tbl::get("TBL_CONFIGS"))
			->where($qb->expr()->equal(new Field('alias_of'), $configDB->id));

		$sql = MySqlDbManager::getQueryObject();
		
		$sql->exec($qb->getSQL());
	}
	
	/**
	 * Add new Alias for current db config
	 * @param ConfigDB $configDB
	 * @param Integer $aliasHostLangId new aliases value host lang id
	 * @throws InvalidArgumentException
	 */
	public static function addDBConfigAlias(ConfigDB $configDB, $aliasHostLangId){
		if(empty($configDB)){
			throw new InvalidArgumentException("ConfigDB object is empty!");
		}
		if(!is_numeric($configDB->id)){
			throw new InvalidArgumentException("ConfigDB object's  id is not numeric!");
		}
		if(!is_numeric($aliasHostLangId)){
			throw new InvalidArgumentException("ConfigDB object's  id is not numeric!");
		}
		$qb = new QueryBuilder();
		$arrayValues = array(
								"location" => implode(":", $configDB->location) , 
								"name" => $configDB->name, 
								"value" => $configDB->value,
								"host_lang_id" => $aliasHostLangId,
								"alias_of" => $configDB->id);
		
		$qb->insert(Tbl::get("TBL_CONFIGS"))
			->values($arrayValues);
		$sql = MySqlDbManager::getQueryObject();
		$sql->exec($qb->getSQL());
	}
	
	/**
	 * Delete current host and Lang id alias value for config DB 
	 * @param ConfigDB $configDB
	 * @param unknown_type $aliasHostLangId
	 * @throws InvalidArgumentException
	 */
	public static function deleteDBConfigAlias(ConfigDB $configDB, $aliasHostLangId){
		if(empty($configDB)){
			throw new InvalidArgumentException("ConfigDB object is empty!");
		}
		if(!is_numeric($configDB->id)){
			throw new InvalidArgumentException("ConfigDB object's  id is not numeric!");
		}
		if(!is_numeric($aliasHostLangId)){
			throw new InvalidArgumentException("Alias Host Language id is not numeric!");
		}
		$qb = new QueryBuilder();
		$qb->delete(Tbl::get("TBL_CONFIGS"))
			->where($qb->expr()->equal(new Field('alias_of'), $configDB->id))
			->andWhere($qb->expr()->equal(new Field('host_lang_id'), $aliasHostLangId));

		$sql = MySqlDbManager::getQueryObject();
		
		$sql->exec($qb->getSQL());
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
					$thisConfig[$row['name']] = $row['value'];
				}
			}
		}
		return new Config($configArray);
	}
	
	private static function getConfigDBFromData($row){
		$configDB = new ConfigDB();
		$configDB->id = $row["id"];
		$configDB->name = $row["name"];
		$configDB->value = $row["value"];
		$configDB->location = explode(":", $row["location"]);
		$configDB->locationString = $row["location"];
		if($row["host_lang_id"] !== null){
			$hostLangPair = HostLanguageManager::getHostLanguagePair($row["host_lang_id"]);
			$configDB->host = $hostLangPair["host"];
			$configDB->language = $hostLangPair["language"];
		}
		if($row["alias_of"] !== null){
			try{
				$dbConfig = static::getDBConfigById($row["alias_of"]);
			}
			catch (RuntimeException $e){
				return $configDB;
			}
			$configDB->aliasHost = $dbConfig->host;
			$configDB->aliasLanguage = $dbConfig->language;
		}
		return $configDB;
	}
	
}
?>