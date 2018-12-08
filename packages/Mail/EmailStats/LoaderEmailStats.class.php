<?php
class LoaderEmailStats extends Loader{
	
	protected function includes(){
		stingleInclude ('Filters/EmailStatsFilter.class.php');
		stingleInclude ('Managers/EmailStatsManager.class.php');
		stingleInclude ('Objects/EmailStat.class.php');
		stingleInclude ('Helpers/helpers.inc.php');
	}
	
	protected function customInitBeforeObjects(){
		Tbl::registerTableNames('EmailStatsManager');
	}
	
	protected function loadEmailStats(){
		$this->register(new EmailStatsManager());
	}
	
	public function hookAddEmailStat($mail){
		if(!empty($mail) and !empty($mail->user)){
			$tos = $mail->getToAddresses();
			foreach($tos as $to){
				Reg::get('emailStats')->sendEmail($to['address'], $mail->from, $mail->emailId, $mail->type, $mail->user->id);
			}
		}
	}
	
	public function hookRecordBounce($args){
		$matches = array();
		if(preg_match('/^X-MailId:\s(.+)$/m', $args['bodyFull'], $matches)){
			if(!empty($matches[1])){
				$emailId = trim($matches[1]);
				$stat = Reg::get('emailStats')->getEmailStatById($emailId);
				if($stat){
					if($args['bounceType'] == 'blocked'){
						Reg::get('emailStats')->setEmailAsBouncedBlock($stat->emailId);
					}
					elseif($args['bounceType'] == 'hard'){
						Reg::get('emailStats')->setEmailAsBouncedHard($stat->emailId);
					}
					else{
						Reg::get('emailStats')->setEmailAsBouncedSoft($stat->emailId);
					}
				}
			}
		}
	}
}
