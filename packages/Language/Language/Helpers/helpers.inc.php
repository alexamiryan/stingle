<?php
function C($str){
	return Reg::get(ConfigManager::getConfig('Language', 'Language')->Objects->LanguageManager)->getValueOf($str);
}