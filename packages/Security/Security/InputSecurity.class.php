<?
class InputSecurity
{
	public static function secureInputData(){
		ini_set('magic_quotes_runtime', '0');
		if(!get_magic_quotes_gpc()){
			self::addslashesToArray($_GET);
			self::addslashesToArray($_POST);
			self::addslashesToArray($_COOKIE);
			self::addslashesToArray($_REQUEST);
		}
	}
	
	private static function addslashesToArray(&$array){
		foreach($array as $key => $val){
			if(is_array($val)){
				self::addslashesToArray($val);
			}
			else{
				$array[$key] = addslashes($val);
			}
		}
	}
}
?>