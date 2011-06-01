<?
function default_exception_handler(Exception $e){
	global $site_name;
	
	HookManager::callHook('NoDebugExceptionHandler', array('e' => $e));
	
	$config = ConfigManager::getGlobalConfig();
	if(Debug::getMode()){
		echo format_exception($e, true);
	}
	else{
		HookManager::callHook('ExceptionHandler', array('e' => $e));
	}
	if(Reg::get('packageMgr')->isPluginLoaded("Db","Db") and function_exists("write_log")){
		@write_log("Exception", format_exception($e));
	}
	
	if($config->Debug->send_email_on_exception and function_exists("send_mail")){
		@send_mail($config->site->developer_mail, "Exception on $site_name", format_exception($e, true));
	}
	exit;
}

function shutdown(){
	HookManager::callHook('Shutdown');
}
?>