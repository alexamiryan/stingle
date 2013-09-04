<?php
class LoaderInfo extends Loader{
	protected function includes(){
		stingleInclude ('Objects/Info.class.php');
	}
	
	protected function loadInfo(){
		$this->register(new Info($_SESSION[$this->config->AuxConfig->infoSessionVar]));
	}
	
	protected function loadError(){
		$this->register(new Info($_SESSION[$this->config->AuxConfig->errorSessionVar]));
	}	
}
