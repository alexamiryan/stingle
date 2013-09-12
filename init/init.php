<?php
require_once(STINGLE_PATH . "core/Cgi.class.php");
require_once(STINGLE_PATH . "core/Config.class.php");
require_once(STINGLE_PATH . "core/ConfigManager.class.php");
require_once(STINGLE_PATH . "core/Debug.class.php");
require_once(STINGLE_PATH . "core/Dependency.class.php");
require_once(STINGLE_PATH . "core/Hook.class.php");
require_once(STINGLE_PATH . "core/HookManager.class.php");
require_once(STINGLE_PATH . "core/Loader.class.php");
require_once(STINGLE_PATH . "core/Model.class.php");
require_once(STINGLE_PATH . "core/PackageManager.class.php");
require_once(STINGLE_PATH . "core/Reg.class.php");
require_once(STINGLE_PATH . "core/SiteMode.class.php");

require_once(STINGLE_PATH . "core/Exceptions/EmptyArgumentException.class.php");
require_once(STINGLE_PATH . "core/Exceptions/InvalidArrayArgumentException.class.php");
require_once(STINGLE_PATH . "core/Exceptions/InvalidIntegerArgumentException.class.php");
require_once(STINGLE_PATH . "core/Exceptions/InvalidTimestampArgumentException.class.php");

require_once(STINGLE_PATH . "configs/system.inc.php");

require_once(STINGLE_PATH . "helpers/system.php");
require_once(STINGLE_PATH . "helpers/func.php");

register_shutdown_function("shutdown");
set_exception_handler("default_exception_handler");
set_error_handler(
    create_function(
        '$severity, $message, $file, $line',
        'throw new ErrorException($message, $severity, $severity, $file, $line);'
    )
);

if(!isset($SYSCONFIG)){ $SYSCONFIG = array(); }
if(!isset($CONFIG)){ $CONFIG = array(); }

$sysconfig = new Config($SYSCONFIG);

Reg::register('packageMgr', new PackageManager());
$configCacheFilename = $sysconfig->Stingle->CoreCachePath . 'configs';
if($sysconfig->Stingle->BootCompiler === true and file_exists($configCacheFilename)){
	try{
		$config = unserialize(file_get_contents($configCacheFilename));
		
		ConfigManager::setCache($config);
		ConfigManager::setGlobalConfig($config);
	}
	catch(Exception $e){
		unlink($configCacheFilename);
	}
}
else{
	$config = ConfigManager::mergeConfigs(new Config($CONFIG), $sysconfig);
	ConfigManager::setGlobalConfig($config);
}

if(isset($config->site->error_reporting)){
	error_reporting($config->site->error_reporting);
}
if(isset($config->site->site_id)){
	session_name($config->site->site_id);
}

session_start();
ob_start('stingleOutputHandler');

Cgi::setMode(defined("IS_CGI"));
Debug::setMode($config->Debug->enabled);
SiteMode::set($config->SiteMode->mode);

// Register User Hooks
if(isset($config->Hooks)){
	foreach(get_object_vars($config->Hooks) as $hookName => $funcName){
		if(is_object($funcName)){
			foreach (get_object_vars($funcName) as $regFuncName){
				HookManager::registerHook(new Hook($hookName, $regFuncName));
			}
		}
		else{
			HookManager::registerHook(new Hook($hookName, $funcName));
		}
	}
}

// Init packages/plugins
HookManager::callHook("BeforePackagesLoad");

$classesCacheFilename = ConfigManager::getGlobalConfig()->Stingle->CoreCachePath . 'classes.php';
if(ConfigManager::getGlobalConfig()->Stingle->BootCompiler === true){
	if(file_exists($classesCacheFilename)){
		$GLOBALS['doNotIncludeClasses'] = true;
		require_once ($classesCacheFilename);
	}
	if(!isset($GLOBALS['includedClasses'])){
		$GLOBALS['includedClasses'] = array();
	}
}

foreach(get_object_vars($config->Packages) as $package){
	$package = get_object_vars($package);
	if(!isset($package[1])){
		$package[1] = array();
	}
	Reg::get('packageMgr')->addPackage($package[0], $package[1]);
}
Reg::get('packageMgr')->load();

HookManager::callHook("AfterPackagesLoad");

if(ConfigManager::getGlobalConfig()->Stingle->BootCompiler === true){
	if(!file_exists($classesCacheFilename)){
		$fileContents = "<?php\n\n";
		if(count($GLOBALS['includedClasses']) > 0 and !isset($GLOBALS['doNotIncludeClasses'])){
			foreach($GLOBALS['includedClasses'] as $file){
				$content = file_get_contents($file['file']);
				$content = str_replace("<?php", "", $content);
				$content = str_replace("<?", "", $content);
				$content = str_replace("?>", "", $content);
				
				if(!empty($file['precompileCode'])){
					$fileContents .= $file['precompileCode'] . "\n\n";
				}
				
				$fileContents .= $content . "\n\n";
				
				if(!empty($file['postcompileCode'])){
					$fileContents .= $file['postcompileCode'] . "\n\n";
				}
			}
				
			file_put_contents($classesCacheFilename, $fileContents);
		}
	}
	
	
}

// Request Parser
HookManager::callHook("BeforeRequestParser");

HookManager::callHook("BeforeRequestParserStep2");

HookManager::callHook("RequestParser");

HookManager::callHook("AfterRequestParser");

HookManager::callHook("BeforeController");

HookManager::callHook("Controller");

HookManager::callHook("AfterController");

//$time = microtime(true);
HookManager::callHook("BeforeOutput");

HookManager::callHook("Output");

HookManager::callHook("AfterOutput");

if(ConfigManager::getGlobalConfig()->Stingle->BootCompiler === true){
	if(!file_exists($configCacheFilename)){
		file_put_contents($configCacheFilename, serialize(ConfigManager::mergeConfigs(ConfigManager::getGlobalConfig(), ConfigManager::getCache())));
	}
}
//echo "out - " . (microtime(true) - $time) . "<br>";
// Finish
