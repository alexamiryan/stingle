<?php
class Tbl
{
	private static $tableNames;
	private static $tableSQLFiles = array();
	
	const TABLE_NAMES_BEGIN = 'TBL_';
	
	/**
	 * 
	 * @param string $className
	 * @param string $tableName
	 * @param string $tableNameValue
	 * @param string $dbInstanceKey
	 */
	public static function setTableName($tableName, $tableNameValue, $className = null, $dbInstanceKey = null){
		if(empty($tableName)){
			throw new InvalidArgumentException("\$tableName is empty!");
		}
		if(empty($tableNameValue)){
			throw new InvalidArgumentException("\$tableNameValue is empty!");
		}
		
		if($className === null){
			$className = self::getCallerClassName();
		}
		if($dbInstanceKey === null){
			$dbInstanceKey = self::getCallerDbInstanceKey();
		}
		
		if(!isset(self::$tableNames[$dbInstanceKey]) or !is_array(self::$tableNames[$dbInstanceKey])){
			self::$tableNames[$dbInstanceKey] = array();
		}
		
		if(!isset(self::$tableNames[$dbInstanceKey][$className]) or !is_array(self::$tableNames[$dbInstanceKey][$className])){
			self::$tableNames[$dbInstanceKey][$className] = array();
		}
		
		self::$tableNames[$dbInstanceKey][$className][$tableName] = $tableNameValue;
	}
	
	/**
	 * 
	 * @param string $className
	 * @param string $dbInstanceKey
	 */
	public static function registerTableNames($className = null, $dbInstanceKey = null){
		if($className === null){
			$className = self::getCallerClassName();
		}
		if($dbInstanceKey === null){
			$dbInstanceKey = self::getCallerDbInstanceKey();
		}
		
		$sqlFiles = self::getCallerSQLFiles();
		self::$tableSQLFiles = array_merge(self::$tableSQLFiles, $sqlFiles);
		
		if(!class_exists($className)){
			throw new RuntimeException("Class '$className' doesn't exists or it is not loaded!");
		}
		
		$reflection = new ReflectionClass($className);
		foreach($reflection->getConstants() as $key=>$value){
			if(substr($key, 0, strlen(self::TABLE_NAMES_BEGIN)) == self::TABLE_NAMES_BEGIN){
				if(!self::isSetTableName($key, $className, $dbInstanceKey)){
					self::setTableName($key, $value, $className, $dbInstanceKey);
				}
			}
		}
	}
	
	/**
	 * 
	 * @param string $className
	 * @param string $tableName
	 * @param string $dbInstanceKey
	 * @return bool
	 */
	public static function isSetTableName($tableName, $className = null, $dbInstanceKey = null){
		if($className === null){
			$className = self::getCallerClassName();
		}
		if($dbInstanceKey === null){
			$dbInstanceKey = self::getCallerDbInstanceKey();
		}
		
		if(isset(self::$tableNames[$dbInstanceKey][$className][$tableName]) and !empty(self::$tableNames[$dbInstanceKey][$className][$tableName])){
			return true;
		}
		return false;
	}
	
	/**
	 * 
	 * @param string $tableName
	 * @param string $className
	 * @param string $dbInstanceKey
	 * @return string
	 */
	public static function get($tableName, $className = null, $dbInstanceKey = null){
		if($className === null){
			$className = self::getCallerClassName();
		}
		if($dbInstanceKey === null){
			$dbInstanceKey = self::getCallerDbInstanceKey();
		}
		
		if(empty($dbInstanceKey) or empty($className)){
			throw new RuntimeException("Something went wrong. Can't get all necessary parameters. I think you called this method from incorrect place.");
		}
		
		return self::$tableNames[$dbInstanceKey][$className][$tableName];
	}
	
	public static function getTableSQLFilePath($tableName){
		if(empty($tableName)){
			throw new InvalidArgumentException("You have to specify table name");
		}
	
		if(isset(self::$tableSQLFiles[$tableName]) and !empty(self::$tableSQLFiles[$tableName])){
			return self::$tableSQLFiles[$tableName];
		}
		return false;
	}
	
	public static function getPluginSQLFilePathsByTableName($tableName){
		if(empty($tableName)){
			throw new InvalidArgumentException("You have to specify table name");
		}
	
		if(isset(self::$tableSQLFiles[$tableName]) and !empty(self::$tableSQLFiles[$tableName])){
			$myTableSQLFile = self::$tableSQLFiles[$tableName];
			
			$files = array();
			
			foreach(self::$tableSQLFiles as $tableName => $sqlFile){
				if($myTableSQLFile['path'] == $sqlFile['path']){
					array_push($files, $sqlFile['path'] . $sqlFile['filename']);
				}
			}
			
			return $files;
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
		if(!empty($backtrace[2]['object']) and method_exists($backtrace[2]['object'], 'getDbInstanceKey')){
			return $backtrace[2]['object'];
		}
		return null;
	}
	
	private static function getCallerDbInstanceKey(){
		$backtrace = debug_backtrace();
		if(!empty($backtrace[2]['object']) and method_exists($backtrace[2]['object'], 'getDbInstanceKey')){
			return $backtrace[2]['object']->getDbInstanceKey();
		}
		else{
			return MySqlDbManager::getDefaultInstanceKey();
		}
	}
	
	private static function getCallerSQLFiles(){
		$backtrace = debug_backtrace();
		$callerFile = $backtrace[1]['file'];
		
		$sqlsPath = preg_replace("/(.+?".str_replace('/', '\/', STINGLE_PATH)."packages\/.+?\/.+?\/).*/", "$1SQL/", $callerFile);
		
		$sqlFiles = array();
		$dir  = opendir($sqlsPath);
		while (false !== ($filename = readdir($dir))) {
			if(preg_match("/(.+)\.sql$/i", $filename, $matches)){
				$sqlFiles[$matches[1]] = array('path' => $sqlsPath, 'filename' => $filename);
			}
		}
		closedir($dir);
		
		return $sqlFiles;
	}
}
