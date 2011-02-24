<?
/**
 * Smarty plugin
 * @package Smarty
 * @subpackage plugins
 */


function smarty_function_snippet($params, &$smarty){
	$file =  $params['file'];
	unset($params['file']);
	foreach ($params as $key=>$value){
		$smarty->assign($key, $value);
	}
	return $smarty->fetch($smarty->getCurrentTemplatePath() .'tpl/incs/snippets/'. $file);
}
?>