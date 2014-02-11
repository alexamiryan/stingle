<?php
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
			$qb = new QueryBuilder();
			$qb->select($qb->expr()->count(new Field('*'), 'count'))
				->from(Tbl::get("TBL_SECURITY_FLOODER_IPS"))
				->where($qb->expr()->equal(new Field('ip'), $ip));
				
			$this->query->exec($qb->getSQL());
			
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
		
		MySqlDbManager::getDbObject()->startTransaction();
		
		$qb = new QueryBuilder();
		$qbSelect = new QueryBuilder();
		$qbSelect->select(new Field('ip'))
				->from(Tbl::get('TBL_SECURITY_REQUESTS_LOG'))
				->where($qbSelect->expr()->greaterEqual(new Field('count'),  $this->config->requestsLimit));
		
		$qb->insertIgnore(Tbl::get('TBL_SECURITY_FLOODER_IPS'))
					->fields('ip')
					->values($qbSelect);
					
		$this->query->exec($qb->getSQL());
		
		$this->query->exec("TRUNCATE TABLE `".Tbl::get('TBL_SECURITY_REQUESTS_LOG')."`");
		
		if(!MySqlDbManager::getDbObject()->commit()){
			MySqlDbManager::getDbObject()->rollBack();
		}
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
		$qb = new QueryBuilder();
		$qb->insert(Tbl::get('TBL_SECURITY_REQUESTS_LOG'))
			->values(array('ip' => $ip))
			->onDuplicateKeyUpdate()
			->set(new Field('count'), $qb->expr()->sum(new Field('count'), 1));
		$this->query->exec($qb->getSQL());
	}
	
	/**
	 * Block given IP
	 * @param string $ip
	 */
	public function blockIP($ip = null){
		if($ip === null){
			$ip = $_SERVER['REMOTE_ADDR'];
		}
		$qb = new QueryBuilder();
		$qb->insert(Tbl::get('TBL_SECURITY_FLOODER_IPS'))
			->values(array('ip' => $ip));
		$this->query->exec($qb->getSQL());
	}
	
	/**
	 * Unblock blocked IP
	 * @param string $ip
	 */
	public function unblockIP($ip = null){
		if($ip === null){
			$ip = $_SERVER['REMOTE_ADDR'];
		}
		$qb = new QueryBuilder();
		$qb->delete(Tbl::get('TBL_SECURITY_FLOODER_IPS'))
			->where($qb->expr()->equal(new Field("ip"), $ip));	
		$this->query->exec($qb->getSQL());
	}
}
