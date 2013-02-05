<?php
class LoaderRecaptcha extends Loader{
	
	protected function includes(){
		require_once ('Managers/Recaptcha.class.php');
		require_once ('Objects/RecaptchaResponse.class.php');
	}
	
	protected function loadRecaptcha(){
		$this->register(new Recaptcha($this->config->AuxConfig));
	}
}
