<?
class LoaderRecaptcha extends Loader{
	
	protected function includes(){
		require_once ('Recaptcha.class.php');
		require_once ('RecaptchaResponse.class.php');
	}
	
	protected function loadRecaptcha(){
		Reg::register($this->config->Objects->Recaptcha, new Recaptcha($this->config->AuxConfig));
	}
}
?>