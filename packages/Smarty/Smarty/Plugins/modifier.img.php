<?
/**
 * @param string $string
 * @return string
 */

function smarty_modifier_img($filename){
	return Reg::get(ConfigManager::getConfig('Smarty', 'Smarty')->Objects->Smarty)->getCurrentTemplatePath() .'img/'. $filename;
}
?>