<?php
class LoaderSecurity extends Loader{
	protected function includes(){
		require_once ('Exceptions/SecurityException.class.php');
		require_once ('Managers/InputSecurity.class.php');
	}
	
	public function hookSecureInputData(){
		if($this->config->AuxConfig->enableInputSecurity){
			echo "qaq";exit;
			InputSecurity::secureInputData();
		}
	}
}
