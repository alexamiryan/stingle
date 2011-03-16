<?
require_once (STINGLE_PATH . "configs/config.inc.php");
require_once (SITE_CONFIGS_PATH . "config.inc.php");

require_once (STINGLE_PATH . "init/init.php");

HookManager::callHook("BeforeController");

HookManager::callHook("Controller");

HookManager::callHook("AfterController");

HookManager::callHook("InitEnd");
?>