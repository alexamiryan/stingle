<?
abstract class Model
{
	/**
	 * Get list of constants in class whos names 
	 * begin with $constNameBegins
	 * 
	 * @param string $constNameBegins
	 * @return array
	 */
	public static function getConstsArray($constNameBegins){
		$returnArray = array();
		$reflection = new ReflectionClass(get_called_class());
		foreach($reflection->getConstants() as $key=>$value){
			if(substr($key, 0, strlen($constNameBegins)) == $constNameBegins){
				$returnArray[$key] = $value;
			}
		}
		return $returnArray;
	}
}
?>