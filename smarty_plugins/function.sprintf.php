<?
/**
 * Smarty plugin
 * @package Smarty
 * @subpackage plugins
 */


/**
 * Smarty {sprintf} function plugin
 *
 * Type:     function<br>
 * Name:     sprintf<br>
 * Purpose:  same as in php
 * @author   Vahe Evoyan ;)
 * @param format
 * @param argN N=1,2,3,...
 * @return string
 */
function smarty_function_sprintf($params, &$smarty)
{
	$sparams = '';
	foreach($params as $key => $value){
		if($key != 'format'){
			$sparams .= ', $params["'.$key.'"]';
		}
	}
	eval('$ret_val = sprintf($params["format"]'.$sparams.');');
	return $ret_val;
}
?>