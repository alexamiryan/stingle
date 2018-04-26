<?php
class LoaderMail extends Loader{
	protected function includes(){
		stingleInclude ('Exceptions/MailException.class.php');
		stingleInclude ('Managers/PHPMailer/PHPMailer.php');
		stingleInclude ('Managers/PHPMailer/SMTP.php');
		stingleInclude ('Managers/MailSender.class.php');
		stingleInclude ('Objects/Mail.class.php');
	}
	
	protected function loadMail(){
		$this->register(new MailSender($this->config->AuxConfig));
	}
}
