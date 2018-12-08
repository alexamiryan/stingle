<?php
class LoaderMail extends Loader{
	
	protected function includes(){
		stingleInclude ('Exceptions/MailException.class.php');
		stingleInclude ('Managers/MailSender.class.php');
		stingleInclude ('Interfaces/MailTransportInterface.php');
		stingleInclude ('Objects/Mail.class.php');
	}
	
	protected function loadMail(){
		$this->register(new MailSender($this->config->AuxConfig));
	}
	
}
