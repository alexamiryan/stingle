<?php
/**
 * @param string $string
 * @return string
 */

function smarty_modifier_img($filename, $backupFileName = null){
	/* @var $smarty SamrtyWrapper */
	$smarty = Reg::get(ConfigManager::getConfig("Output", "Smarty")->Objects->Smarty);
	try{
		return SITE_PATH . $smarty->findFilePath('img/'. $filename);
	}
	catch(Exception $e){
		if($backupFileName !== null){
			return SITE_PATH . $smarty->findFilePath('img/'. $backupFileName);
		}
		else{
			throw $e;
		}
	}
}
