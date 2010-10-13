<?
class Tbl
{
	private static $tableNames;
	
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
	
	private static function getCallerClassName(){
		$backtrace = debug_backtrace();
		return $backtrace[2]['class'];
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
}
?>