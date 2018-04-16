<?php
function default_exception_handler($e){
	$hookArgs = array('e' => $e);
	HookManager::callHook('NoDebugExceptionHandler', $hookArgs);
	if(!in_array($e->getCode(), ConfigManager::getGlobalConfig()->Stingle->disabledErrors->toArray())){
		if(Debug::getMode()){
			echo format_exception($e, true);
		}
		else{
			HookManager::callHook('ExceptionHandler', $hookArgs);
		}
		exit;
	}
}

function default_error_handler($severity, $message, $file, $line){
	if(!in_array($severity, ConfigManager::getGlobalConfig()->Stingle->disabledErrors->toArray())){
		throw new ErrorException($message, $severity, $severity, $file, $line);
	}
}

function stingleOutputHandler($buffer){
	$hookArgs = array( 'buffer' => &$buffer );
	
	HookManager::callHook("onOutputHandler", $hookArgs);
	return $buffer;
}

function shutdown(){
	HookManager::callHook('Shutdown');
}
