<?php
require_once(STINGLE_PATH . "functions/system.php");
require_once(STINGLE_PATH . "functions/func.php");

register_shutdown_function("shutdown");
set_exception_handler("default_exception_handler");
set_error_handler('default_error_handler');

$config = new Config($CONFIG);
ConfigManager::setGlobalConfig($config);
Reg::register('packageMgr', new PackageManager());

error_reporting($config->site->error_reporting);
session_name($config->site->site_id);

session_start();
ob_start();

Debug::setMode($config->Debug->enabled);

// Register User Hooks
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

// Init packages/plugins
HookManager::callHook("BeforePackagesLoad");

foreach(get_object_vars($config->Packages) as $package){
	$package = get_object_vars($package);
	if(!isset($package[1])){
		$package[1] = array();
	}
	Reg::get('packageMgr')->addPackage($package[0], $package[1]);
}
Reg::get('packageMgr')->load();

HookManager::callHook("AfterPackagesLoad");


// Request Parser / Controller
HookManager::callHook("BeforeRequestParser");

HookManager::callHook("BeforeRequestParserStep2");

HookManager::callHook("RequestParser");

HookManager::callHook("AfterRequestParser");
?>