<?
function __autoload($className){
	global $stingle_autoloadList;
	
	if(!class_exists($className, false)){
		if(array_key_exists($className, $stingle_autoloadList["classes"])){
			require_once $stingle_autoloadList["classes"][$className]['path'];
		}
		elseif(array_key_exists($className, $stingle_autoloadList["interfaces"])){
			require_once $stingle_autoloadList['interfaces'][$className]['path'];
		}
		else{
			throw new RuntimeException("Class $className not found in mapping");
		}
	}
}

function default_exception_handler(Exception $e){
	global $config, $packageMgr, $sql, $site_name;
	
	if(Debug::getMode()){
		echo format_exception($e, true);
	}
	else{
		HookManager::callHook('ExceptionHandler', array('e' => $e));
	}
	if($packageMgr->isPluginLoaded("Db","Db") and function_exists("write_log")){
		@write_log("Exception", format_exception($e));
	}
	
	if($config->debug->send_email_on_exception and function_exists("send_mail")){
		@send_mail($config->site->developer_mail, "Exception on $site_name", format_exception($e, true));
	}
}

function default_error_handler($errno, $errstr, $errfile, $errline){
	global $config, $error, $sql, $smarty, $site_name;
	if ( $errno === E_RECOVERABLE_ERROR or $errno === E_WARNING ) {
		if(Debug::getMode()){
			echo format_error($errno, $errstr, $errfile, $errline, true);
		}
		else{
			echo $smarty->fetch("modules/{$config->smarty->errors_module}/{$config->smarty->error_page}.tpl");
		}
		if(is_object($sql) and function_exists("write_log")){
			@write_log("Eerror", format_error($errno, $errstr, $errfile, $errline));
		}
		
		if($config->debug->send_email_on_exception and function_exists("send_mail")){
			@send_mail($config->site->developer_mail, "Error on $site_name", format_error($errno, $errstr, $errfile, $errline, true));
		}
	}
	return false;
}

function shutdown(){
	global $gi;
	if($gi){
		geoip_close($gi);
	}
}
?>