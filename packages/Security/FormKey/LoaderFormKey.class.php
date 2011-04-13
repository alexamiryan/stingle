<?
class LoaderFormKey extends Loader{
	protected function includes(){
		require_once ('FormKey.class.php');
	}
	
	protected function loadFormKey(){
		Reg::register($this->config->Objects->FormKey, new FormKey($_SESSION[$this->config->AuxConfig->sessionVarName]));
	}
}
?>