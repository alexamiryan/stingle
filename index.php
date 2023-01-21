<?php
if(defined('ADDONS_FOLDER_PATH') && is_dir(ADDONS_FOLDER_PATH) && !defined('ADDONS_PATHS')) {
    $addonsPath = ADDONS_FOLDER_PATH;
    $folders = [];
    if(substr($addonsPath, strlen($addonsPath) - 1) !== '/'){
        $addonsPath .= '/';
    }
    $files = scandir($addonsPath);
    foreach($files as $file) {
        if(is_dir($addonsPath . $file) && $file != '.' && $file != '..') {
            $folders[] = $addonsPath . $file;
        }
    }
    define ("ADDONS_PATHS", $folders);
}

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
