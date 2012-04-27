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
	$filePath = $smarty->getFilePathFromTemplate($smarty->snippetsPath . $file);
	if($filePath !== false){
		return $smarty->fetch($filePath);
	}
	return "";
}
?>