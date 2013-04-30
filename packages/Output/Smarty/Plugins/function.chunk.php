<?php
/**
 * Smarty plugin
 * @package Smarty
 * @subpackage plugins
 */
function smarty_function_chunk($params, &$smarty){
	$cacheEnabled = false;
	if(!isset($params['file'])){
		throw new InvalidArgumentException("You have tom specify 'file' parameter for the chunk");
	}
	$file =  $params['file'];
	unset($params['file']);

	if(isset($params['cache']) and $params['cache'] == true){
		$smarty->setLocalCachingOn();
		$cacheEnabled = true;
		
		if(isset($params['cacheTime']) and is_int($params['cacheTime'])){
			$smarty->setCacheTime($params['cacheTime']);
		}
	}
	
	foreach ($params as $key=>$value){
		$smarty->assign($key, $value);
	}
	
	$path = $smarty->getChunkPath($file);
	if(!empty($path)){
		$cacheId = null;
		
		if(isset($params['cacheId']) and !empty($params['cacheId'])){
			$cacheId = $params['cacheId'];
		}
		else{
			$cacheIdFromParent = $smarty->getTemplateVars('cacheId');
			if(!empty($cacheIdFromParent)){
				$cacheId = $cacheIdFromParent;
			}
		}

		if($cacheId != null){
			$result = $smarty->fetch($path, $cacheId);
		}
		else{
			$result =  $smarty->fetch($path);
		}
		
		if($cacheEnabled){
			if(ConfigManager::getConfig("Output", "Smarty")->AuxConfig->caching == Smarty::CACHING_OFF){
				$smarty->setCachingOff();
			}
		}
				
		return $result;
	}
	return "";
}
