<?
/**
 * Return text for currect host/language
 *
 * @param string $name
 * @param string $group
 * @return string
 */
function smarty_modifier_text($name, $group){
	$textValMgr = Reg::get(ConfigManager::getConfig("Texts", "Texts")->Objects->TextsValuesManager);
	return $textValMgr->getTextValue($name, $group);
}
?>