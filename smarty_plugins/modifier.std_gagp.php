<?php
/**
 * Smarty plugin
 * @package Smarty
 * @subpackage plugins
 */


function smarty_modifier_std_gagp($string, $exclude='', $sign='&amp;', $delimiter=',', $with_base_script=true)
{
	
	$exclude_array=explode($delimiter, $exclude);
//	array_walk($exclude_array, "trim");
	return (($with_base_script)?SITE_PATH:"") . "?" . std_get_all_get_params($exclude_array, $sign) . $string;
}

?>
