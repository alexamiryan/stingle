<?php
/**
 * Smarty plugin
 * @package Smarty
 * @subpackage plugins
 */


function smarty_function_rand($params, &$smarty){
	$length = null;
	$alphabet = array(RANDOM_STRING_LOWERCASE, RANDOM_STRING_UPPERCASE, RANDOM_STRING_DIGITS);
	
	extract($params);
	
	return generateRandomString($length, $alphabet);
}
