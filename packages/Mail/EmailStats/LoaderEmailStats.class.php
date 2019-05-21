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
		if(empty($args['mailId'])){
			return;
		}
		$stat = Reg::get('emailStats')->getEmailStatById($args['mailId']);
		if($stat){
			if($args['bounceType'] == MailSender::BOUNCE_TYPE_BLOCKED){
				Reg::get('emailStats')->setEmailAsBouncedBlock($stat->emailId, $args['msgHeaders'], $args['msgBody']);
			}
			elseif($args['bounceType'] == MailSender::BOUNCE_TYPE_HARD){
				Reg::get('emailStats')->setEmailAsBouncedHard($stat->emailId, $args['msgHeaders'], $args['msgBody']);
			}
			else{
				Reg::get('emailStats')->setEmailAsBouncedSoft($stat->emailId, $args['msgHeaders'], $args['msgBody']);
			}
		}
	}
	
	public function hookIsMailSendAllowed(&$args){
		$isSofBounced = Reg::get('emailStats')->isEmailSoftBounced($args['email'], $this->config->AuxConfig->doNotSendEmailForSoftBouncedInLastXDays);
		return !$isSofBounced;
	}
}
