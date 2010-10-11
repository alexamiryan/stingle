<?
class Reg
{
	private static $reg = array();
	
	public static function register($key, $value, $override = false){
		if(empty($key)){
			throw new InvalidArgumentException("\$key have to be non empty string.");
		}
		if(empty($value)){
			throw new InvalidArgumentException("\$value have to be non empty mixed variable.");
		}
		if(!$override and isset(self::$reg[$key])){
			throw new RuntimeException("Key $key is already registered in registry.");
		}
		self::$reg[$key] = $value;
	}
	
	public static function get($key, $throwException = true){
		if(!isset(self::$reg[$key]) or empty(self::$reg[$key])){
			if($throwException){
				throw new RuntimeException("There is no object in registry with key $key.");
			}
			else{
				return null;
			}
		}
		return self::$reg[$key];
	}
}
?>