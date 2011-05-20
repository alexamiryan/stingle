<?
/**
 * Smarty plugin
 * @package Smarty
 * @subpackage plugins
 */


function smarty_function_chunk($params, &$smarty){
	return $smarty->fetch($smarty->getChunksPath() . $params['file']);
}
?>