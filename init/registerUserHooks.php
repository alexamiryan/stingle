<?
if(isset($config->hooks)){
	foreach(get_object_vars($config->hooks) as $hookName => $funcName){
		if(is_object($funcName)){
			foreach (get_object_vars($funcName) as $regFuncName){
				HookManager::registerHook($hookName, $regFuncName);
			}
		}
		else{
			HookManager::registerHook($hookName, $funcName);
		}
	}
}
?>