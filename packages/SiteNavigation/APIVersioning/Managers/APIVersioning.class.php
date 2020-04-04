<?php

class APIVersioning extends Model {
    
    public static string $current = 'CURR';
    

    public static function parseApiVersioningURL($config){
        $uri = '/';
        if(isset($_SERVER['REQUEST_URI']) && !empty($_SERVER['REQUEST_URI'])){
            $uri = rawurldecode($_SERVER['REQUEST_URI']);
        }
        
        if(!preg_match("/^\/v(\d+?)\//m", $uri, $matches)){
            $replacement = $config->replaceWithVersionIfAbsent;
            if($config->replaceWithVersionIfAbsent == static::$current){
                $replacement = $config->currentApiVersion;
            }
            $uri = '/v' . $replacement . $uri;
        }
        else{
            if($matches[1] > $config->currentApiVersion || $matches[1] < 1){
                $uri = '/v' . $config->currentApiVersion .'/'. preg_replace("/^\/v(\d+?)\//m", "", $uri);
            }
        }
        
        $_SERVER['REQUEST_URI'] = $uri;
    }
    
    public static function useVersion($version){
        Reg::get('nav')->module = 'v' . $version;
        Reg::get('controller')->exec();
        
    }
}
