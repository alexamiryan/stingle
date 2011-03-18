<?
/**
 * Smarty plugin
 * @package Smarty
 * @subpackage plugins
 */


function smarty_function_rand($params, &$smarty){
	return create_random_value($params['length'], $params['type']);
}
?>