<?php
if(defined('DISABLE_APCU') || !extension_loaded('apcu') || !apcu_exists('globalConfig') ){
	require_once (SITE_CONFIGS_PATH . "config.inc.php");
}

require_once (STINGLE_PATH . "init/init.php");
