<?php
/**
 * Smarty plugin
 * @package Smarty
 * @subpackage plugins
 */


function smarty_function_rand($params, &$smarty){
	$length = null;
	$type = null;
	
	extract($params);
	
	return generateRandomString($length, $type);
}
