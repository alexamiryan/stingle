<?php
class Tbl
{
	private static $tableNames = array();
	private static $tableSQLFiles = array();
	private static $isDataLoadedFromCache = false;
	
	const TABLE_NAMES_BEGIN = 'TBL_';
	
	public static function cacheData(){
		apcuStore('TblTableNames', self::$tableNames);
		apcuStore('TblSQLFiles', self::$tableSQLFiles);
	}
	
	public static function restoreCachedData(){
		$tableNames = apcuGet('TblTableNames');
		$tableSQLFiles = apcuGet('TblSQLFiles');

		if($tableNames !== false && $tableSQLFiles !== false){
			self::$tableNames = $tableNames;
			self::$tableSQLFiles = $tableSQLFiles;
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
				
		$sqlFiles = self::getCallerSQLFiles();
		self::$tableSQLFiles = array_merge(self::$tableSQLFiles, $sqlFiles);
		
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
	
	public static function getTableSQLFilePath($tableName){
		if(empty($tableName)){
			throw new InvalidArgumentException("You have to specify table name");
		}
	
		$tableName = strtolower($tableName);
	
		if(isset(self::$tableSQLFiles[$tableName]) and !empty(self::$tableSQLFiles[$tableName])){
			return self::$tableSQLFiles[$tableName];
		}
		return false;
	}
	
	public static function getPluginSQLFilePathsByTableName($tableName){
		if(empty($tableName)){
			throw new InvalidArgumentException("You have to specify table name");
		}
	
		$tableName = strtolower($tableName);
	
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
		if(!empty($backtrace[2]['object'])){
			return $backtrace[2]['object'];
		}
		return null;
	}
	
	private static function getCallerSQLFiles(){
		$backtrace = debug_backtrace();
		$callerFile = $backtrace[1]['file'];
		if(DIRECTORY_SEPARATOR == '/'){
			$sqlsPath = preg_replace("/(.*?".str_replace('/', '\/', STINGLE_PATH)."packages\/.+?\/.+?\/).*/", "$1SQL/", $callerFile);
		}
		elseif(DIRECTORY_SEPARATOR == '\\'){
			$sqlsPath = preg_replace("/(.*?".str_replace('/', "\\\\", STINGLE_PATH)."packages\\\\.+?\\\\.+?\\\\).*/", "$1SQL\\", $callerFile);
		}
		else{
			throw new RuntimeException("Unexpected DIRECTORY_SEPARATOR detected!");
		}
		
		if($sqlsPath == $callerFile){
			if(DIRECTORY_SEPARATOR == '/'){
				$sqlsPath = preg_replace("/(.*?".str_replace('/', '\/', SITE_PACKAGES_PATH).".+?\/.+?\/).*/", "$1SQL/", $callerFile);
			}
			elseif(DIRECTORY_SEPARATOR == '\\'){
				$sqlsPath = preg_replace("/(.*?".str_replace('/', "\\\\", SITE_PACKAGES_PATH).".+?\\\\.+?\\\\).*/", "$1SQL\\", $callerFile);
			}
		}
		
		$sqlFiles = array();
		if($sqlsPath != $callerFile and file_exists($sqlsPath) and is_dir($sqlsPath)){
			$dir  = opendir($sqlsPath);
			while (false !== ($filename = readdir($dir))) {
				if(preg_match("/(.+)\.sql$/i", $filename, $matches)){
					$sqlFiles[strtolower($matches[1])] = array('path' => $sqlsPath, 'filename' => $filename);
				}
			}
			closedir($dir);
		}
		return $sqlFiles;
	}
}
