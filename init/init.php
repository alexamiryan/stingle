<?
require_once(STINGLE_PATH . "core/Cgi.class.php");
require_once(STINGLE_PATH . "core/Config.class.php");
require_once(STINGLE_PATH . "core/ConfigManager.class.php");
require_once(STINGLE_PATH . "core/Debug.class.php");
require_once(STINGLE_PATH . "core/Dependency.class.php");
require_once(STINGLE_PATH . "core/HookManager.class.php");
require_once(STINGLE_PATH . "core/Loader.class.php");
require_once(STINGLE_PATH . "core/Model.class.php");
require_once(STINGLE_PATH . "core/PackageManager.class.php");
require_once(STINGLE_PATH . "core/Reg.class.php");

require_once(STINGLE_PATH . "core/Exceptions/EmptyArgumentException.class.php");
require_once(STINGLE_PATH . "core/Exceptions/InvalidArrayArgumentException.class.php");
require_once(STINGLE_PATH . "core/Exceptions/InvalidIntegerArgumentException.class.php");
require_once(STINGLE_PATH . "core/Exceptions/InvalidTimestampArgumentException.class.php");

require_once(STINGLE_PATH . "functions/system.php");
require_once(STINGLE_PATH . "functions/func.php");

register_shutdown_function("shutdown");
set_exception_handler("default_exception_handler");
set_error_handler(
    create_function(
        '$severity, $message, $file, $line',
        'throw new ErrorException($message, $severity, $severity, $file, $line);'
    )
);

$config = new Config($CONFIG);
ConfigManager::setGlobalConfig($config);
Reg::register('packageMgr', new PackageManager());

error_reporting($config->site->error_reporting);
session_name($config->site->site_id);

session_start();
ob_start();

Cgi::setMode(defined("IS_CGI"));
Debug::setMode($config->Debug->enabled);

// Register User Hooks
if(isset($config->Hooks)){
	foreach(get_object_vars($config->Hooks) as $hookName => $funcName){
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


// Request Parser
HookManager::callHook("BeforeRequestParser");

HookManager::callHook("BeforeRequestParserStep2");

HookManager::callHook("RequestParser");

HookManager::callHook("AfterRequestParser");
?>