<?
/**
 * @param string $string
 * @return string
 */

function smarty_modifier_img($filename){
	/* @var $smarty SamrtyWrapper */
	$smarty = Reg::get(ConfigManager::getConfig('Smarty', 'Smarty')->Objects->Smarty);
	return SITE_PATH . $smarty->findFilePath('img/'. $filename);
}
?>