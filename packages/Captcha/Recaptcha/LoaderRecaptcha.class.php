<?php
class LoaderRecaptcha extends Loader{
	
	protected function includes(){
		require_once ('Recaptcha.class.php');
		require_once ('RecaptchaResponse.class.php');
	}
	
	protected function loadRecaptcha(){
		$this->register(new Recaptcha($this->config->AuxConfig));
	}
}
