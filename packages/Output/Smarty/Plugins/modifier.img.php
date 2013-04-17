<?php
/**
 * @param string $string
 * @return string
 */

function smarty_modifier_img($filename){
	/* @var $smarty SamrtyWrapper */
	$smarty = Reg::get(ConfigManager::getConfig("Output", "Smarty")->Objects->Smarty);
    try{
	    return SITE_PATH . $smarty->findFilePath('img/'. $filename);
    }
    catch(TemplateFileNotFoundException $e){
        $config = ConfigManager::getGlobalConfig();
        if($config->Debug->send_email_on_exception and Reg::isRegistered('mail')){
            Reg::get('mail')->developer("Exception", "Can not find image ". $filename);
        }
        return SITE_PATH . $smarty->findFilePath('img/errors/no_photo.jpg');
    }
}
