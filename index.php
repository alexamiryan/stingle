<?php
if(defined('DISABLE_APCU') || !extension_loaded('apcu') || !apcu_exists('globalConfig') ){
	require_once (SITE_CONFIGS_PATH . "config.inc.php");
    
    if(defined('ADDONS_PATHS') && is_array(ADDONS_PATHS)){
        foreach(ADDONS_PATHS as $path){
            if(file_exists($path . "configs".DIRECTORY_SEPARATOR."config.inc.php")){
                require_once ($path . "configs".DIRECTORY_SEPARATOR."config.inc.php");
            }
        }
    }
}

require_once (STINGLE_PATH . "init/init.php");
