<?php

function mglink($url = '', $mail = null) {
	
	$clickUrl = ConfigManager::getConfig('Mail', 'EmailStats')->AuxConfig->clickUrl;
	RewriteURL::ensureLastSlash($clickUrl);
	
	if($mail === null){
		$mail = Reg::get('smarty')->getTemplateVars('mail');

		if(empty($mail)){
			throw new RuntimeException('$mail is not assigned in Smarty');
		}
	}
	return glink($clickUrl . "id:" . $mail->emailId . '/r:' . base64_url_encode(glink($url)));
}
