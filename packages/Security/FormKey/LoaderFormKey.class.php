<?php
class LoaderFormKey extends Loader{
	protected function includes(){
		stingleInclude ('Managers/FormKey.class.php');
		stingleInclude ('Exceptions/FormKeySecurityException.class.php');
	}
	
	protected function loadFormKey(){
		$this->register(new FormKey($this->config->AuxConfig));
	}
}
