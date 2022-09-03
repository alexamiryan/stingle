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

if(!isset($SYSCONFIG)){ $SYSCONFIG = array(); }
if(!isset($CONFIG)){ $CONFIG = array(); }

$sysconfig = new Config($SYSCONFIG);

register_shutdown_function("shutdown");
set_exception_handler("default_exception_handler");
set_error_handler("default_error_handler");

Reg::register('packageMgr', new PackageManager());

$globalConfig = null;
if($sysconfig->Stingle->BootCompiler === true){
	if(!defined('DISABLE_APCU') && extension_loaded('apcu')){
		$globalConfig = apcu_fetch('globalConfig');
		if($globalConfig === false){
			$globalConfig = ConfigManager::mergeConfigs(new Config($CONFIG), $sysconfig);
			apcu_store('globalConfig', $globalConfig);
		}
	}
	else{
		$configCacheFilename = $sysconfig->Stingle->CoreCachePath . 'configs';
		if(file_exists($configCacheFilename)){
			try{
				$globalConfig = unserialize(file_get_contents($configCacheFilename));
			}
			catch(Exception $e){
				unlink($configCacheFilename);
				$globalConfig = ConfigManager::mergeConfigs(new Config($CONFIG), $sysconfig);
			}
		}
		else{
			$globalConfig = ConfigManager::mergeConfigs(new Config($CONFIG), $sysconfig);
			file_put_contents($configCacheFilename, serialize($globalConfig));
		}
	}
}
else{
	$globalConfig = ConfigManager::mergeConfigs(new Config($CONFIG), $sysconfig);
}
ConfigManager::setGlobalConfig($globalConfig);
ConfigManager::initCache();

// Init addons
if(defined('ADDONS_PATHS') && is_array(ADDONS_PATHS)){
    foreach(ADDONS_PATHS as $path){
        if(file_exists($path . "init.inc.php")){
            require_once ($path . "init.inc.php");
        }
    }
}

if(isset($globalConfig->Stingle->errorReporting)){
	error_reporting($globalConfig->Stingle->errorReporting);
}

if($globalConfig->Stingle->autostartSession){
	startSession();
}

if($globalConfig->Stingle->autoObStart){
	ob_start('stingleOutputHandler');
}

Cgi::setMode(defined("IS_CGI"));
Debug::setMode($globalConfig->Debug->enabled);
SiteMode::set($globalConfig->SiteMode->mode);

// Register User Hooks
if(isset($globalConfig->Hooks)){
	foreach(get_object_vars($globalConfig->Hooks) as $hookName => $funcName){
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

$classesCacheFilename = $globalConfig->Stingle->CoreCachePath . 'classes.php';
if($globalConfig->Stingle->BootCompiler === true){
	if(file_exists($classesCacheFilename)){
		$GLOBALS['doNotIncludeClasses'] = true;
		require_once ($classesCacheFilename);
	}
	if(!isset($GLOBALS['includedClasses'])){
		$GLOBALS['includedClasses'] = array();
	}
}

foreach(get_object_vars($globalConfig->Packages) as $package){
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

