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

require_once(STINGLE_PATH . "init/registerUserHooks.php");

// Parse request
require_once(STINGLE_PATH . "init/objects.php");
require_once(STINGLE_PATH . "init/requestParser.php");
?>