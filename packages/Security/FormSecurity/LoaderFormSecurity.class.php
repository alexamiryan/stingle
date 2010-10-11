<?
class LoaderFormSecurity extends Loader{
	protected function includes(){
		require_once ('SecurityException.class.php');
		require_once ('FormSecurity.class.php');
	}
	
	protected function loadFormSecurity(){
		Reg::register($this->config->Objects->formSecurity, new FormSecurity());
	}
}
?>