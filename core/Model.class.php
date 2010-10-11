<?
abstract class Model
{
	public static function getConstsArray($constNameBegins){
		$returnArray = array();
		$reflection = new ReflectionClass(get_called_class());
		foreach($reflection->getConstants() as $key=>$value){
			if(substr($key, 0, strlen($constNameBegins)) == $constNameBegins){
				array_push($returnArray, array($key=>$value));
			}
		}
		return $returnArray;
	}
}
?>