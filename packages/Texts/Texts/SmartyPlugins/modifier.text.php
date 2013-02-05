<?php
/**
 * Return text for currect host/language
 *
 * @param string $name
 * @param string $group
 * @return string
 */
function smarty_modifier_text($name, $group){
	try{
		$textValMgr = Reg::get(ConfigManager::getConfig("Texts", "Texts")->Objects->TextsValuesManager);
		return $textValMgr->getTextValue($name, $group);
	}
	catch(Exception $e){
		if(Debug::getMode()){
			return "_~#~_";
		}
		else{
			return "";
		}
	}
}
