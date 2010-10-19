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
	 * @return MysqlQuery|UserManagement|LanguageManager|Nav
	 */
	public static function get($key, $throwException = true){
		if(!isset(static::$reg[$key]) or empty(static::$reg[$key])){
			if($throwException){
				throw new RuntimeException("There is no object in registry with key $key.");
			}
			else{
				return null;
			}
		}
		return static::$reg[$key];
	}
}
?>