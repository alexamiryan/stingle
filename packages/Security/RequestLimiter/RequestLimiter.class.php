<?
class RequestLimiter extends DbAccessor {
	
	const TBL_SECURITY_FLOODER_IPS 	= "security_flooder_ips";
	const TBL_SECURITY_REQUESTS_LOG 	= "security_requests_log";
	const TBL_SECURITY_INVALID_LOGINS_LOG 	= 'security_invalid_logins_log';
	
	private $config;
	
	public function __construct(Config $config, $dbInstanceKey = null){
		parent::__construct($dbInstanceKey);
		
		$this->config = $config;
	}
	
	/**
	 * Check if given IP is blacklisted by system
	 * 
	 * @param string $ip
	 * @throws InvalidArgumentException
	 */
	public function isBlacklistedIp($ip = null){
		if(isset($_SERVER['REMOTE_ADDR'])){
			if($ip === null){
				$ip = $_SERVER['REMOTE_ADDR'];
			}
			
			$this->query->exec("SELECT COUNT(*) as `count` 
									FROM `".Tbl::get("TBL_SECURITY_FLOODER_IPS")."`
									WHERE `ip`='$ip'");
			
			$count = $this->query->fetchField("count");
			
			if($count == 0){
				return false;
			}
			else{
				return true;
			}
		}
		else{
			return false;
		}
	}
	
	/**
	 * Parse requests log and blacklist flooding IPs.
	 * Should be called by cron job every minute.
	 */
	public function parseLogForFloodingIps(){
		$tablesToLock = array(Tbl::get('TBL_SECURITY_REQUESTS_LOG'),Tbl::get('TBL_SECURITY_FLOODER_IPS'));
		
		MySqlDbManager::getDbObject()->lockTables($tablesToLock, "w");
		
		$this->query->exec("INSERT IGNORE INTO `".Tbl::get('TBL_SECURITY_FLOODER_IPS')."` (`ip`) 
							SELECT `ip` 
								FROM `".Tbl::get('TBL_SECURITY_REQUESTS_LOG')."` 
								WHERE `count` >= " . $this->config->requestsLimit);
		
		$this->query->exec("TRUNCATE TABLE `".Tbl::get('TBL_SECURITY_REQUESTS_LOG')."`");
		
		MySqlDbManager::getDbObject()->unlockTables();
	}
	
	/**
	 * Record current request in requests log
	 */
	public function recordRequest($ip = null){
		if(Cgi::getMode()){
			return;
		}
		
		if($ip === null){
			$ip = $_SERVER['REMOTE_ADDR'];
		}
		
		$this->query->exec("INSERT INTO `".Tbl::get('TBL_SECURITY_REQUESTS_LOG')."` (`ip`) 
								VALUES ('$ip')
								ON DUPLICATE KEY UPDATE `count` = `count` + 1");
	}
	
	/**
	 * Block given IP
	 * @param string $ip
	 */
	public function blockIP($ip = null){
		if($ip === null){
			$ip = $_SERVER['REMOTE_ADDR'];
		}
		
		$this->query->exec("INSERT INTO `".Tbl::get('TBL_SECURITY_FLOODER_IPS')."` (`ip`) VALUES('$ip')");
	}
	
	/**
	 * Unblock blocked IP
	 * @param string $ip
	 */
	public function unblockIP($ip = null){
		if($ip === null){
			$ip = $_SERVER['REMOTE_ADDR'];
		}
		
		$this->query->exec("DELETE FROM `".Tbl::get('TBL_SECURITY_FLOODER_IPS')."` WHERE `ip` = '$ip'");
	}
}
?>