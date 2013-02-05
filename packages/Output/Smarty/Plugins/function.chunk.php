<?php
/**
 * Smarty plugin
 * @package Smarty
 * @subpackage plugins
 */
function smarty_function_chunk($params, &$smarty){
	$file =  $params['file'];
	unset($params['file']);
	
	foreach ($params as $key=>$value){
		$smarty->assign($key, $value);
	}
	
	$path = $smarty->getChunkPath($file);
	if(!empty($path)){
		return $smarty->fetch($path);
	}
	return "";
}
