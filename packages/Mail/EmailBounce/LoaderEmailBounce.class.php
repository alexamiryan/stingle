<?php
class LoaderEmailBounce extends Loader{
	protected function includes(){
		stingleInclude ('Managers/BounceHandler.class.php');
		stingleInclude ('BounceMailHandler/BounceMailHandler.php');
		stingleInclude ('BounceMailHandler/phpmailer-bmh_rules.php');
	}
	
	protected function loadBounceHandler(){
		$this->register(new BounceHandler($this->config->AuxConfig));
	}
}
