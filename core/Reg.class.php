<?
class Reg
{
	private static $reg = array();
	
	public static function register($key, $value, $override = false){
		if(empty($key)){
			throw new InvalidArgumentException("\$key have to be non empty string.");
		}
		if($value === ''){
			throw new InvalidArgumentException("\$value have to be non empty mixed variable.");
		}
		if(!$override and isset(static::$reg[$key])){
			throw new RuntimeException("Key $key is already registered in registry.");
		}
		static::$reg[$key] = $value;
	}
	
	/**
	 * 
	 * @param string $key
	 * @param bool $throwException
	 * @return PackageManager|MysqlQuery|UserManagement|LanguageManager|Nav|SmartyWrapper
	 */
	public static function get($key){
		if(!static::isRegistered($key)){
			throw new RuntimeException("There is no object in registry with key $key.");
		}
		return static::$reg[$key];
	}
	
	/**
	 * Check if key is already registered 
	 * @param string $key
	 */
	public static function isRegistered($key){
		if(isset(static::$reg[$key])){
			return true;
		}
		return false;
	}
}
?>