<?php
class InputSecurity
{
	public static function secureInputData(){
		ini_set('magic_quotes_runtime', '0');
		if(!get_magic_quotes_gpc()){
			static::addslashesToArray($_GET);
			static::addslashesToArray($_POST);
			static::addslashesToArray($_COOKIE);
			static::addslashesToArray($_REQUEST);
		}
	}
	
	private static function addslashesToArray(&$array){
		foreach($array as $key => $val){
			if(is_array($val)){
				static::addslashesToArray($val);
			}
			else{
				$array[$key] = addslashes($val);
			}
		}
	}
}
