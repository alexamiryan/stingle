<?php

/**
 * @param string $string
 * @return string
 */
function smarty_modifier_MC($constantName) {
		$mail = Reg::get('smarty')->getTemplateVars('mail');
		if(empty($mail)){
			throw new RuntimeException('$mail is not assigned in Smarty');
		}
		return Reg::get('lm')->getValueOf($constantName, $mail->language);
}
