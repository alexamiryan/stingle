<?php
class LoaderKeybase extends Loader{
	
	protected function includes(){
		stingleInclude ('Managers/Keybase.class.php');
	}
	
	protected function loadKeybase(){
		$this->register(new Keybase($this->config->AuxConfig));
	}
	
}
