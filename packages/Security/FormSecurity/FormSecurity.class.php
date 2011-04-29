<?
class FormSecurity extends DbAccessor {
	
	const TBL_SECURITY_USERS 	= "security_users";
	const TBL_SECURITY_SESSIONS = "security_sessions";
	const TBL_SECURITY_HOSTS	= "security_hosts";

	private $unblockTime = 900;
	private $userId = null;
	private $sessionId = null;
	private $blockedtime = 0;
	
	private $allowedUserTime		= 180;
	private $allowedHostTime		= 180;
	private $allowedSessionTime 	= 180;
	
	private $allowedUserAttempts	= array(20 => 180, 50 => 300, 100 => 3600);
	private $allowedHostAttempts	= array(20 => 180, 50 => 300, 100 => 3600);
	private $allowedSessionAttempts = array(20 => 180, 50 => 300, 100 => 3600);
		
	private $label = "*";
	private $message = "You have overquoted your request limit.";
	private $clientHostIp;
	private $clientForwardedIp;
	
	
	public function __construct ($dbInstanceKey = null) {
		parent::__construct($dbInstanceKey);
		
		if( Reg::get('usr')->isAuthorized() ) {
			$this->userId = Reg::get('usr')->getId();
		}
		
		$this->clientHostIp = $this->getHost();
		if($this->clientHostIp == false){
			throw new Exception("Could not determine client ip address.");
		}
		
		if(session_id() != ""){
			$this->sessionId = session_id();
		}
		
		$this->getForwardedIp();
		
		if($this->isBlockedClient()){
			$e = new SecurityException("User is overquoting the requests limit.");
			$e->setUserMessage($this->message);
			$e->setBlockMessage("Please, wait ".$this->blockedtime." minute(s) and try again.");
		
			throw $e;
		}
	}	
	
	private function isBlockedClient(){
		$this->unblockIfBlockTimePassed();
		
		if($this->userId !== null){
			$this->query->exec("SELECT (UNIX_TIMESTAMP(`unblocktime`) - UNIX_TIMESTAMP(CURRENT_TIMESTAMP())) as `blocktime` 
								FROM `".Tbl::get('TBL_SECURITY_USERS')."` 
								WHERE `user_id` = '{$this->userId}' 
								AND label='{$this->label}' AND blockstatus > 0");
			
			if($this->query->countRecords() > 0){
				$this->blockedtime = ceil(((int)$this->query->fetchField("blocktime"))/60);
				return true;
			}
			else{
				return false;
			}
		}		
		
		if($this->sessionId !== null){
			$this->query->exec("SELECT (UNIX_TIMESTAMP(`unblocktime`) - UNIX_TIMESTAMP(CURRENT_TIMESTAMP())) as `blocktime`  
								FROM `".Tbl::get('TBL_SECURITY_SESSIONS')."` 
								WHERE `session_id` = '{$this->sessionId}' 
								AND `label`='{$this->label}' AND blockstatus > 0");
			if($this->query->countRecords() > 0){
				$this->blockedtime = ceil(((int)$this->query->fetchField("blocktime"))/60);
				return true;
			}
			else{
				return false;
			}
		}
		
		$this->query->exec("SELECT (UNIX_TIMESTAMP(`unblocktime`) - UNIX_TIMESTAMP(CURRENT_TIMESTAMP())) as `blocktime`  
							FROM `".Tbl::get('TBL_SECURITY_HOSTS')."` 
							WHERE `remote_ip` = '{$this->clientHostIp}' 
							AND `forwarded_ip`='{$this->clientForwardedIp}'
							AND `label`='{$this->label}' AND blockstatus > 0");
		if($this->query->countRecords() > 0){
			$this->blockedtime = ceil(((int)$this->query->fetchField("blocktime"))/60);
			return true;
		}
		
		return false;		
	}
	
	public function checkRequestLimit ($label = "", $message = "") {
		
		if(!empty($label)) {
			$this->label = $label;
		}
		
		if(!empty($message)) {
			$this->message = $message;
		}		
		
		if ( $this->isBlockedClient() ) {
			$e = new SecurityException("User is overquoting the requests limit.");
			$e->setUserMessage($this->message);
			$e->setBlockMessage("Please, wait ".$this->blockedtime." minute(s) and try again.");

			throw $e;
		}	
		
		if ( !$this->checkUserStatus() ) {
			
			$this->checkHostStatus();
			$this->checkSessionStatus();
		}
	}
	
	private function getHost(){
		$clientHostIp = $_SERVER["REMOTE_ADDR"];
		
		if(empty($clientHostIp)){
			return false;
		}
		return $clientHostIp;
	}
	
	private function getForwardedIp() {
		if(!empty($_SERVER['HTTP_X_FORWARDED_FOR'])){
			$this->clientForwardedIp = $_SERVER['HTTP_X_FORWARDED_FOR'];
		}
	}
	
	private function checkUserStatus() {
		if($this->userId !== null){
			$this->query->exec("SELECT (UNIX_TIMESTAMP(`request_time`)-UNIX_TIMESTAMP(`counter_last_reset_time`)) as `timediff`,`counter`,`blockstatus` 
								FROM `".Tbl::get('TBL_SECURITY_USERS')."` 
								WHERE `user_id`='{$this->userId}' AND `label`='{$this->label}'");
			
			if($this->query->countRecords()){
				$record = $this->query->fetchRecord();
				$db_timediff = $record["timediff"];
				$db_counter = $record["counter"];
				$db_blockstatus = $record["blockstatus"];
				
				if($db_blockstatus == 0){					
					if($db_timediff >= $this->allowedUserTime){
						$blockTimeSec = $this->getBlockTimeFromConf($this->allowedUserAttempts, $db_counter);
						
						if($blockTimeSec > 0){
							$this->blockClientByUser($blockTimeSec); // block user here
						}
						else{
							$this->query->exec("UPDATE `".Tbl::get('TBL_SECURITY_USERS')."` SET `counter`=0, `counter_last_reset_time`=CURRENT_TIMESTAMP()
												WHERE `user_id`='{$this->userId}' AND `label`='{$this->label}' AND `blockstatus` = 0");
						}
					}
					$this->query->exec("UPDATE `".Tbl::get('TBL_SECURITY_USERS')."` SET `counter`=`counter` + 1 
										WHERE `user_id`='{$this->userId}' AND `label`='{$this->label}' AND `blockstatus` = 0");
				}
			}
			else{
				$this->query->exec("INSERT INTO `".Tbl::get('TBL_SECURITY_USERS')."` (`user_id`,`counter_last_reset_time`,`label`)
													  	  		VALUES ('{$this->userId}', CURRENT_TIMESTAMP(),'{$this->label}')");
			}
			return true;	
		}
		return false;
	}
	
	private function checkHostStatus() {

		$this->query->exec("SELECT (UNIX_TIMESTAMP(`request_time`)-UNIX_TIMESTAMP(`counter_last_reset_time`)) as `timediff`, `counter`, `blockstatus`  
							FROM `".Tbl::get('TBL_SECURITY_HOSTS')."` WHERE `remote_ip` = '{$this->clientHostIp}' 
							AND `forwarded_ip`='{$this->clientForwardedIp}'
							AND `label`='{$this->label}'");
		
		if($this->query->countRecords()){
			$record = $this->query->fetchRecord();
			$db_timediff = $record["timediff"];
			$db_counter = $record["counter"];
			$db_blockstatus = $record["blockstatus"];
			
			if($db_blockstatus == 0){	
				if($db_timediff >= $this->allowedHostTime){
					if(($blockTimeSec = $this->getBlockTimeFromConf ( $this->allowedHostAttempts, $db_counter))> 0){
						$this->blockClientByHost($blockTimeSec); // block host here
					}
					else {
						$this->query->exec("UPDATE `".Tbl::get('TBL_SECURITY_HOSTS')."` 
											SET `counter` = 0, `counter_last_reset_time` = CURRENT_TIMESTAMP() 
											WHERE `remote_ip` = '{$this->clientHostIp}' 
											AND `forwarded_ip` = '{$this->clientForwardedIp}'
											AND `label`='{$this->label}' AND `blockstatus` = 0");
					}				 
				}
				$this->query->exec("UPDATE `".Tbl::get('TBL_SECURITY_HOSTS')."` 
									SET `counter` = `counter` + 1 
									WHERE `remote_ip` = '{$this->clientHostIp}' 
									AND `forwarded_ip` = '{$this->clientForwardedIp}'
									AND label='{$this->label}' AND `blockstatus` = 0");			
			}
		}
		else{
			$this->query->exec("INSERT INTO `".Tbl::get('TBL_SECURITY_HOSTS')."` (`remote_ip`, `forwarded_ip`, `counter_last_reset_time`, `label`)
								VALUES ('{$this->clientHostIp}', '{$this->clientForwardedIp}', CURRENT_TIMESTAMP(), '{$this->label}')");
		}
	}
	
	private function checkSessionStatus() {
		
		$this->query->exec("SELECT (UNIX_TIMESTAMP(`request_time`)-UNIX_TIMESTAMP(`counter_last_reset_time`)) as `timediff`, `counter`, `blockstatus` 
							FROM `".Tbl::get('TBL_SECURITY_SESSIONS')."` WHERE `session_id` = '{$this->sessionId}' AND `label`='{$this->label}'");
	
		if ( $this->query->countRecords() == 0 ) {
			$this->query->exec("INSERT INTO `".Tbl::get('TBL_SECURITY_SESSIONS')."` (session_id,remote_ip,counter_last_reset_time,label)
														  	  		   VALUES ('".$this->sessionId."', '".$this->clientHostIp."', CURRENT_TIMESTAMP(),'".$this->label."')");
		}
		else {
			$record = $this->query->fetchRecord();
			$db_timediff = $record["timediff"];
			$db_counter = $record["counter"];
			$db_blockstatus = $record["blockstatus"];
			
			if(((int)$db_blockstatus) == 0) {
				if( $db_timediff >= $this->allowedSessionTime) {
					if( ( $blockTimeSec = $this->getBlockTimeFromConf ( $this->allowedSessionAttempts, $db_counter ) ) > 0 ) {
							$this->blockClientBySession($blockTimeSec); // block session here
					}
					else {
						$this->query->exec("UPDATE `".Tbl::get('TBL_SECURITY_SESSIONS')."` 
											SET counter=0,counter_last_reset_time=CURRENT_TIMESTAMP() 
											WHERE session_id = '".$this->sessionId."'
											AND label='".$this->label."' AND blockstatus = 0");
					}				 
				}
				$this->query->exec("UPDATE `".Tbl::get('TBL_SECURITY_SESSIONS')."` SET counter=counter + 1 
									WHERE session_id = '".$this->sessionId."'
									AND label='".$this->label."' AND blockstatus = 0");
			}							
		}
	}
		
	private function unblockIfBlockTimePassed() {
		
		if ($this->userId !== null) {
			
			// Unblock current user if block time already passed 
			$this->query->exec("UPDATE `".Tbl::get('TBL_SECURITY_USERS')."` 
							SET counter=1,blockstatus=0
							WHERE unblocktime<CURRENT_TIMESTAMP() AND blockstatus > 0
							AND user_id='{$this->userId}'");
		}
		else {			
			$this->query->exec("UPDATE `".Tbl::get('TBL_SECURITY_HOSTS')."` 
								SET counter=1,blockstatus=0
								WHERE unblocktime<CURRENT_TIMESTAMP() AND blockstatus > 0
								AND remote_ip = '{$this->clientHostIp}' 
								AND forwarded_ip = '{$this->clientForwardedIp}'");
			
			$this->query->exec("UPDATE `".Tbl::get('TBL_SECURITY_SESSIONS')."` 
								SET counter=1,blockstatus=0
								WHERE unblocktime<CURRENT_TIMESTAMP() AND blockstatus > 0
								AND session_id='{$this->sessionId}'");
								
		}
	}
	
	private function getBlockTimeFromConf($limitsArr, $attemptCount) {
		
		if( !is_numeric($attemptCount) ) {
			throw new InvalidArgumentException("attemptCount must be a numeric value.");
		}
		
		$blockTimeSec = 0;
		foreach ($limitsArr as $key=>$value) {
			if( $attemptCount >= (int)$key ) {
				$blockTimeSec = $value;
			}
		}
		
		return $blockTimeSec;
	}
	
	private function blockClientByUser($blockTimeSec) {
		
		if( !is_numeric($blockTimeSec) ) {
			throw new InvalidArgumentException("blockTimeSec must be a numeric value.");
		}
		
		$this->query->exec("UPDATE `".Tbl::get('TBL_SECURITY_USERS')."` 
							SET counter=0,blockstatus=1,unblocktime=FROM_UNIXTIME(UNIX_TIMESTAMP(CURRENT_TIMESTAMP()) + ".(int)$blockTimeSec.")
							WHERE user_id=".$this->userId);	
	}
	
	private function blockClientByHost($blockTimeSec) {
		
		if( !is_numeric($blockTimeSec) ) {
			throw new InvalidArgumentException("blockTimeSec must be a numeric value.");
		}
		
		$this->query->exec("UPDATE `".Tbl::get('TBL_SECURITY_HOSTS')."` 
							SET counter=0,blockstatus=1,unblocktime=FROM_UNIXTIME(UNIX_TIMESTAMP(CURRENT_TIMESTAMP()) + ".(int)$blockTimeSec.")
							WHERE remote_ip = '".$this->clientHostIp."' 
							AND forwarded_ip = '".$this->clientForwardedIp."'");	
	}
	
	private function blockClientBySession($blockTimeSec) {
		
		if( !is_numeric($blockTimeSec) ) {
			throw new InvalidArgumentException("blockTimeSec must be a numeric value.");
		}
		
		$this->query->exec("UPDATE `".Tbl::get('TBL_SECURITY_SESSIONS')."` 
							SET counter=0,blockstatus=1,unblocktime=FROM_UNIXTIME(UNIX_TIMESTAMP(CURRENT_TIMESTAMP()) + ".(int)$blockTimeSec.")
							WHERE session_id='".$this->sessionId."'
							AND remote_ip = '".$this->clientHostIp."'");	
	}
	
	public function setAllowedUserTime($measure) {
		
		if( !is_numeric($measure) ) {
			throw new InvalidArgumentException("allowedUserTime measure must be a numeric value.");
		}
		$this->allowedUserTime = $measure;
	}
	
	public function setAllowedHostTime($measure) {
		
		if( !is_numeric($measure) ) {
			throw new InvalidArgumentException("allowedHostTime measure must be a numeric value.");
		}
		$this->allowedHostTime = $measure;
	}
	
	public function setAllowedSessionTime($measure) {
		
		if( !is_numeric($measure) ) {
			throw new InvalidArgumentException("allowedSessionTime measure must be a numeric value.");
		}
		$this->allowedSessionTime = $measure;
	}
	
	public function setAllowedUserAttempts($attemptcounts) {
		
		if( !is_array($attemptcounts) ) {
			throw new InvalidArgumentException("allowedUserAttempts counts must be an array.");
		}
		$this->allowedUserAttempts = $attemptcounts;
	}
	
	public function setAllowedHostAttempts($attemptcounts) {
		
		if( !is_array($attemptcounts) ) {
			throw new InvalidArgumentException("allowedHostAttempts counts must be an array.");
		}
		$this->allowedHostAttempts = $attemptcounts;
	}
	
	public function setAllowedSessionAttempts($attemptcounts) {
		
		if( !is_array($attemptcounts) ) {
			throw new InvalidArgumentException("allowedSessionAttempts counts must be an array.");
		}
		$this->allowedSessionAttempts = $attemptcounts;
	}	
}
?>