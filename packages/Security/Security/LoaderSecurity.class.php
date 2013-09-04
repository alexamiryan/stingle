<?php
class LoaderSecurity extends Loader{
	protected function includes(){
		stingleInclude ('Exceptions/SecurityException.class.php');
		stingleInclude ('Managers/InputSecurity.class.php');
	}
	
	public function hookSecureInputData(){
		if($this->config->AuxConfig->enableInputSecurity){
			InputSecurity::secureInputData();
		}
	}
}
