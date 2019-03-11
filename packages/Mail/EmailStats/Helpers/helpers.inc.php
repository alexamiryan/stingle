<?php

function mglink($url = '', $mail = null) {
	$config = ConfigManager::getConfig('Mail', 'EmailStats')->AuxConfig;
	
	$clickUrl = $config->clickUrl;
	RewriteURL::ensureLastSlash($clickUrl);
	
	if($mail === null){
		$mail = Reg::get('smarty')->getTemplateVars('mail');

		if(empty($mail)){
			throw new RuntimeException('$mail is not assigned in Smarty');
		}
	}
	$link = glink($clickUrl . "id:" . $mail->emailId . '/r:' . base64_url_encode(glink($url)));
	
	if($config->shortenLinks){
		$link = shortenLink($link);
	}
	
	return $link;
}
