<?php
class Tbl
{
	private static $tableNames = array();
	private static $isDataLoadedFromCache = false;
	
	const TABLE_NAMES_BEGIN = 'TBL_';
	
	public static function cacheData(){
		apcuStore('TblTableNames', self::$tableNames);
	}
	
	public static function restoreCachedData(){
		$tableNames = apcuGet('TblTableNames');

		if($tableNames !== false){
			self::$tableNames = $tableNames;
			self::$isDataLoadedFromCache = true;
		}
	}
	
	/**
	 * 
	 * @param string $className
	 * @param string $tableName
	 * @param string $tableNameValue
	 */
	public static function setTableName($tableName, $tableNameValue, $className = null){
		if(empty($tableName)){
			throw new InvalidArgumentException("\$tableName is empty!");
		}
		if(empty($tableNameValue)){
			throw new InvalidArgumentException("\$tableNameValue is empty!");
		}
		
		if($className === null){
			$className = self::getCallerClassName();
		}
		
		if(!isset(self::$tableNames[$className]) or !is_array(self::$tableNames[$className])){
			self::$tableNames[$className] = array();
		}
		
		self::$tableNames[$className][$tableName] = $tableNameValue;
	}
	
	/**
	 * 
	 * @param string $className
	 */
	public static function registerTableNames($className = null){
		if(self::$isDataLoadedFromCache && isset(self::$tableNames[$className])){
			return;
		}
		if($className === null){
			$className = self::getCallerClassName();
		}
				
		if(!class_exists($className)){
			throw new RuntimeException("Class '$className' doesn't exists or it is not loaded!");
		}
		
		$reflection = new ReflectionClass($className);
		foreach($reflection->getConstants() as $key=>$value){
			if(substr($key, 0, strlen(self::TABLE_NAMES_BEGIN)) == self::TABLE_NAMES_BEGIN){
				if(!self::isSetTableName($key, $className)){
					self::setTableName($key, $value, $className);
				}
			}
		}
	}
	
	/**
	 * 
	 * @param string $className
	 * @param string $tableName
	 * @return bool
	 */
	public static function isSetTableName($tableName, $className = null){
		if($className === null){
			$className = self::getCallerClassName();
		}
				
		if(isset(self::$tableNames[$className][$tableName]) and !empty(self::$tableNames[$className][$tableName])){
			return true;
		}
		return false;
	}
	
	/**
	 * 
	 * @param string $tableName
	 * @param string $className
	 * @return string
	 */
	public static function get($tableName, $className = null){
		if($className === null){
			$className = self::getCallerClassName();
		}
				
		if(empty($className)){
			throw new RuntimeException("Something went wrong. Can't get all necessary parameters. I think you called this method from incorrect place.");
		}
		
		return self::$tableNames[$className][$tableName];
	}
	
    public static function isTableExistsInDb($tableName, $instanceName = MySqlDbManager::DEFAULT_INSTANCE_NAME) : bool{
        $query = MySqlDbManager::getQueryObject($instanceName);
        try {
            $query->exec('select 1 from `' . $tableName . '`');
            return true;
        }
        catch (MySqlException $e){
        
        }
        return false;
    }
	
	private static function getCallerClassName(){
		$backtrace = debug_backtrace();
		if(!empty($backtrace[2]['object']) and is_object($backtrace[2]['object'])){
			return get_class($backtrace[2]['object']);
		}
		else{
			return $backtrace[2]['class'];
		}
	}
	
	private static function getCallerObject(){
		$backtrace = debug_backtrace();
		if(!empty($backtrace[2]['object'])){
			return $backtrace[2]['object'];
		}
		return null;
	}
}
