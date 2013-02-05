<?php
function default_exception_handler(Exception $e){
	$hookArgs = array('e' => $e);
	HookManager::callHook('NoDebugExceptionHandler', $hookArgs);
	
	if(Debug::getMode()){
		echo format_exception($e, true);
	}
	else{
		HookManager::callHook('ExceptionHandler', $hookArgs);
	}
	exit;
}

function stingleOutputHandler($buffer){
	$hookArgs = array( 'buffer' => &$buffer );
	
	HookManager::callHook("onOutputHandler", $hookArgs);
	return $buffer;
}

function shutdown(){
	HookManager::callHook('Shutdown');
}
