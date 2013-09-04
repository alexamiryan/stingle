<?php
class LoaderMail extends Loader{
	protected function includes(){
		stingleInclude ('Exceptions/MailException.class.php');
		stingleInclude ('Exceptions/DKIMConfigException.class.php');
		stingleInclude ('Managers/MailSender.class.php');
		stingleInclude ('Objects/Mail.class.php');
		stingleInclude ('Objects/DKIMConfig.class.php');
	}
	
	protected function loadMailSender(){
		Reg::register($this->config->Objects->MailSender, new MailSender($this->config->AuxConfig));
	}
}
