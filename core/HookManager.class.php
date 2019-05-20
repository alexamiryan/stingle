<?php
class HookManager{
	private static $hooks = array();
	
	/**
	 * Register new hook.
	 * 
	 * @param Hook $hook
	 */
	public static function registerHook(Hook $hook){
		if(!isset(static::$hooks[$hook->getName()])){
			static::$hooks[$hook->getName()] = array();
		}
		
		array_push(static::$hooks[$hook->getName()], $hook);
	}
	
	/**
	 * Remove hook from registered hooks list
	 * @param string $hookName
	 */
	public static function unRegisterHook($hookName){
		if(isset(static::$hooks[$hookName])){
			unset(static::$hooks[$hookName]);
		}
	}
	
	/**
	 * Replace existing hook.
	 *
	 * @param Hook $hook
	 */
	public static function replaceHook(Hook $existingHook, Hook $newHook){
		if(isset(static::$hooks[$existingHook->getName()])){
			foreach (static::$hooks[$existingHook->getName()] as $key => $hook){
				if($hook === $existingHook){
					static::$hooks[$existingHook->getName()][$key] = $newHook;
					break;
				}
			}
		}
	}
	
	/**
	 * Call registered hook. More than one hook could be registered on one hook name. 
	 * If hook(s) return something then you will get array with hooks with new 'return' 
	 * key of returned value.
	 * 
	 * @param string $hookName
	 * @param array $arguments
	 */
	public static function callHook($hookName, &$arguments = null){
		$returnArr = array();
		if(static::isAnyHooksRegistered($hookName)){
			foreach (static::$hooks[$hookName] as $hook){
				$returnArr[$hookName] = static::executeHook($hook, $arguments);
			}
		}
		
		return $returnArr;
	}
	
	/**
	 * Call registered hook. More than one hook could be registered on one hook name. 
	 * All return values have to be boolean true to return true, otherwise it will return false
	 * 
	 * @param string $hookName
	 * @param array $arguments
	 */
	public static function callBooleanAndHook($hookName, &$arguments = null){
		$returnArr = static::callHook($hookName, $arguments);
		foreach($returnArr as $returnItem){
			if($returnItem !== true){
				return false;
			}
		}
		return true;
	}
	
	/**
	 * Call registered hook. More than one hook could be registered on one hook name. 
	 * One of return values have to be boolean true to return true, otherwise it will return false
	 * 
	 * @param string $hookName
	 * @param array $arguments
	 */
	public static function callBooleanOrHook($hookName, &$arguments = null){
		$returnArr = static::callHook($hookName, $arguments);
		foreach($returnArr as $returnItem){
			if($returnItem === true){
				return true;
			}
		}
		return false;
	}
	
	/**
	 * Execute single hook.
	 * 
	 * @param Hook $hook
	 * @param array $arguments
	 */
	public static function executeHook(Hook $hook, &$arguments = null){
		$hookMethod = $hook->getMethod();
		$hookObj = $hook->getObject();
		
		if($hookObj !== null){
			return $hookObj->$hookMethod($arguments);
		}
		else{
			return $hookMethod($arguments);
		}
	}
	
	/**
	 * Get registered hooks list on given name
	 * @param string $hookName
	 * @return array|false
	 */
	public static function getRegisteredHooks($hookName){
		if(static::isAnyHooksRegistered($hookName)){
			return static::$hooks[$hookName];
		}
		return false;
	}
	
	/**
	 * Check if any hook is registered on given name
	 * @param string $hookName
	 * @return boolean
	 */
	public static function isAnyHooksRegistered($hookName){
		if(isset(static::$hooks[$hookName]) and !empty(static::$hooks[$hookName])){
			return true;
		}
		return false;
	}
	
	/**
	 * Check if any hook is registered on given name
	 * @param Hook $hook
	 * @return boolean
	 */
	public static function isHookRegistered(Hook $hookToCheck){
		if(isset(static::$hooks[$hookToCheck->getName()]) and !empty(static::$hooks[$hookToCheck->getName()])){
			foreach(static::$hooks[$hookToCheck->getName()] as $hook){
				if($hookToCheck === $hook){
					return true;
				}
			}
		}
		return false;
	}
}
