<?
/**
 * Smarty plugin
 * @package Smarty
 * @subpackage plugins
 */


function smarty_function_chunk($params, &$smarty){
	$filePath = $smarty->getFilePathFromTemplate($smarty->chunksPath . $params['file'], true);
	if($filePath !== false){
		return $smarty->fetch($filePath);
	}
	return "";
}
?>