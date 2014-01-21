<?php
/**
 * @param string $configPath (delimited with '->')
 * @return string
 */

function smarty_modifier_config($configPath){
	if(empty($configPath)){
		return null;
	}
	
	$configparts = explode("->", $configPath);
	
	$currentItem = ConfigManager::getGlobalConfig();
	
	foreach ($configparts as $part){
		if(!empty($part) and isset($currentItem->$part)){
			$currentItem = $currentItem->$part;
		}
	}
	
	
	return $currentItem;
}
