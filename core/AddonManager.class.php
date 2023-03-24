<?php

class AddonManager
{
    private static $addons = array();
    
    public static function init(){
        // Init addons
        if(defined('ADDONS_PATHS') && is_array(ADDONS_PATHS)){
            foreach(ADDONS_PATHS as $path){
                if(file_exists($path)) {
                    $addonName = basename($path);
                    if (isset($addons[$addonName])) {
                        throw new RuntimeException("Addon with name '$addonName' already exists!");
                    }
                    static::$addons[$addonName] = $path;
                    if (file_exists($path . "init.inc.php")) {
                        require_once($path . "init.inc.php");
                    }
                }
            }
        }
    }
    
    public static function get(){
        return static::$addons;
    }
    
    public static function getAddonNames(){
        return array_keys(static::$addons);
    }
}
