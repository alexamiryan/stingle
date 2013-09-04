<?php
class LoaderRecaptcha extends Loader{
	
	protected function includes(){
		stingleInclude ('Managers/Recaptcha.class.php');
		stingleInclude ('Objects/RecaptchaResponse.class.php');
	}
	
	protected function loadRecaptcha(){
		$this->register(new Recaptcha($this->config->AuxConfig));
	}
}
