<?
class HookManager{
	private static $hooks = array();
	
	public static function registerHook($hookName, $method, $object = null){
		if(!isset(self::$hooks[$hookName])){
			self::$hooks[$hookName] = array();
		}
		array_push(self::$hooks[$hookName], array("method" => $method, "object" => $object));
	}
	
	public static function unRegisterHook($hookName){
		if(isset(self::$hooks[$hookName])){
			unset(self::$hooks[$hookName]);
		}
	}
	
	public static function callHook($hookName, Array $arguments = null){
		if(self::isAnyHooksRegistered($hookName)){
			$results = array();
			foreach (self::$hooks[$hookName] as $hook){
				$hookMethod = $hook['method'];
				$hookObj = $hook['object'];
				
				if($hookObj !== null){
					$hook['return'] = $hookObj->$hookMethod($arguments);
				}
				else{
					$hook['return'] = $hookMethod($arguments);
				}
				array_push($results, $hook);
			}
			return $results;
		}
	}
	
	private static function isAnyHooksRegistered($hookName){
		if(isset(self::$hooks[$hookName]) and !empty(self::$hooks[$hookName])){
			return true;
		}
		return false;
	}
	
	public static function isHookRegistered($hookName, $method, $object = null){
		if(isset(self::$hooks[$hookName]) and 
			!empty(self::$hooks[$hookName]) and
			self::$hooks[$hookName]["method"] == $method and  
			self::$hooks[$hookName]["object"] == $object){
				return true;
		}
		return false;
	}
}
?>