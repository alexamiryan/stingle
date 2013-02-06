<?php
class LoaderFormKey extends Loader{
	protected function includes(){
		require_once ('Managers/FormKey.class.php');
		require_once ('Exceptions/FormKeySecurityException.class.php');
	}
	
	protected function loadFormKey(){
		$this->register(new FormKey($this->config->AuxConfig));
	}
}
