<?php
class LoaderSecurity extends Loader{
	protected function includes(){
		require_once ('SecurityException.class.php');
		require_once ('InputSecurity.class.php');
	}
	
	public function hookSecureInputData(){
		if($this->config->AuxConfig->enableInputSecurity){
			echo "qaq";exit;
			InputSecurity::secureInputData();
		}
	}
}
