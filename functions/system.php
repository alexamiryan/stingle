<?
function default_exception_handler(Exception $e){
	HookManager::callHook('NoDebugExceptionHandler', array('e' => $e));
	
	if(Debug::getMode()){
		echo format_exception($e, true);
	}
	else{
		HookManager::callHook('ExceptionHandler', array('e' => $e));
	}
	exit;
}

function shutdown(){
	HookManager::callHook('Shutdown');
}
?>