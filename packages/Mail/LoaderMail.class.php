<?
class LoaderMail extends Loader{
	protected function includes(){
		require_once ('PHPMailer/class.phpmailer.php');
		require_once ('PHPMailer/class.smtp.php');
		require_once ('PHPMailer/class.pop3.php');
		require_once ('Mail.class.php');
	}
	
	protected function loadMail(){
		$this->mail = new Mail($this->config->auxConfig);
		Reg::register($this->config->Objects->Mail, $this->mail);
	}
}
?>