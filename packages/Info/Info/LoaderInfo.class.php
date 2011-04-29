<?
class LoaderInfo extends Loader{
	protected function includes(){
		require_once ('Info.class.php');
	}
	
	protected function loadInfo(){
		Reg::register($this->config->Objects->Info, new Info($_SESSION[$this->config->AuxConfig->infoSessionVar]));
	}
	
	protected function loadError(){
		Reg::register($this->config->Objects->Error, new Info($_SESSION[$this->config->AuxConfig->errorSessionVar]));
	}	
}
?>