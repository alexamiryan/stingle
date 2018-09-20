<?php
function C($str, Language $language = null, $cacheMinutes = null){
	return Reg::get(ConfigManager::getConfig('Language', 'Language')->Objects->LanguageManager)->getValueOf($str, $language, $cacheMinutes);
}