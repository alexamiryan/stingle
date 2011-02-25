<?
/**
 * @param string $string
 * @return string
 */

function smarty_modifier_img($filename){
	/* @var $smarty SamrtyWrapper */
	$smarty = Reg::get(ConfigManager::getConfig('Smarty', 'Smarty')->Objects->Smarty);
	return $smarty->template_dir . $smarty->getCurrentTemplatePath() .'img/'. $filename;
}
?>