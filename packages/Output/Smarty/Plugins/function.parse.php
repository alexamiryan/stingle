<?php
/**
 * Smarty plugin
 * @package Smarty
 * @subpackage plugins
 */


function smarty_function_parse($params, &$smarty){
	$text = (isset($params['text'])) ? $params['text'] : false;
	if($text !== false){
		unset($params['text']);
		return parse($text, $params);
	}
}
