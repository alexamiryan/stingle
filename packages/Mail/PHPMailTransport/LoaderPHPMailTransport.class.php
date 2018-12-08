<?php
class LoaderPHPMailTransport extends Loader{
	protected function includes(){
		stingleInclude ('PHPMailer/PHPMailer.php');
		stingleInclude ('PHPMailer/SMTP.php');
		stingleInclude ('Managers/PHPMailTransport.class.php');
	}
	
}
