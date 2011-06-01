<?
class HookManager{
	private static $hooks = array();
	
	public static function registerHook($hookName, $method, $object = null){
		if(!isset(static::$hooks[$hookName])){
			static::$hooks[$hookName] = array();
		}
		array_push(static::$hooks[$hookName], array("method" => $method, "object" => $object));
	}
	
	public static function unRegisterHook($hookName){
		if(isset(static::$hooks[$hookName])){
			unset(static::$hooks[$hookName]);
		}
	}
	
	public static function callHook($hookName, Array $arguments = null){
		if(static::isAnyHooksRegistered($hookName)){
			$results = array();
			foreach (static::$hooks[$hookName] as $hook){
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
		if(isset(static::$hooks[$hookName]) and !empty(static::$hooks[$hookName])){
			return true;
		}
		return false;
	}
	
	public static function isHookRegistered($hookName, $method, $object = null){
		if(isset(static::$hooks[$hookName]) and 
			!empty(static::$hooks[$hookName]) and
			array_key_exists($hookName, static::$hooks) and
			array_key_exists("method", static::$hooks[$hookName]) and
			static::$hooks[$hookName]["method"] == $method and
			array_key_exists("object", static::$hooks[$hookName]) and  
			static::$hooks[$hookName]["object"] == $object){
				return true;
		}
		return false;
	}
}
?>