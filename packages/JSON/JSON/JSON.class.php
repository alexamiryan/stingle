<?
class JSON
{
	/**
	 * Make Json output and disable Smarty output
	 * @param array $array
	 */
	public static function jsonOutput($array){
		$smartyConfig = ConfigManager::getConfig("Smarty");
		
		Reg::get($smartyConfig->Objects->Smarty)->disableOutput();
		
		header('Cache-Control: no-cache, must-revalidate');
		header('Expires: Mon, 26 Jul 1997 05:00:00 GMT');
		header('Content-type: application/json');
		echo self::jsonEncode($array);
	}
	
	public static function jsonEncode($array){
		return json_encode($array);
	}
	
	public static function jsonDecode($string){
		return json_decode($string);
	}
}
?>