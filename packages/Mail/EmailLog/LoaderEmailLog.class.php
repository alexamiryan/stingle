<?php
class LoaderEmailLog extends Loader{
	
	protected function includes(){
		stingleInclude ('Filters/EmailLogFilter.class.php');
		stingleInclude ('Managers/EmailLogManager.class.php');
		stingleInclude ('Objects/EmailLog.class.php');
	}
	
	protected function customInitBeforeObjects(){
		Tbl::registerTableNames('EmailLogManager');
	}
	
	protected function loadEmailLog(){
		$this->register(new EmailLogManager());
	}
	
	public function hookRecordBounceLog($args){
		$log = new EmailLog();
		
		$matches = array();
		if(preg_match('/^X-MailId:\s(.+)$/m', $args['bodyFull'], $matches)){
			if(!empty($matches[1])){
				$log->emailId = trim($matches[1]);
			}
		}
		
		$log->userId = $args['user']->id;
		$log->email = $args['user']->email;
		$log->type = EmailLogManager::TYPE_BOUNCE;
		$log->bounceType = $args['bounceType'];
		$log->bounceCode = $args['ruleNo'];
		$log->data = serialize(array(
				'remove'=>$args['remove'],
				'email'=>$args['email'],
				'emailName'=>$args['emailName'],
				'emailAddy'=>$args['emailAddy'],
				'headerFull'=>$args['headerFull'],
				'bodyFull'=>$args['bodyFull']
		));
		Reg::get('emailLog')->addEmailLog($log);
	}
}
