<?php
class RequestLimiter extends DbAccessor {
	
	const TBL_SECURITY_FLOODER_IPS 	= "security_flooder_ips";
	const TBL_SECURITY_REQUESTS_LOG 	= "security_requests_log";
	
	const TYPE_GENERAL = 'gen';
	
	private $config;
	
	public function __construct(Config $config, $instanceName = null){
		parent::__construct($instanceName);
		
		$this->config = $config;
	}
	
	/**
	 * Check if given IP is blacklisted by system
	 * 
	 * @param string $ip
	 * @throws InvalidArgumentException
	 */
	public function isBlacklistedIp($ip = null, $cacheMinutes = null){
		if(isset($_SERVER['REMOTE_ADDR'])){
			if($ip === null){
				$ip = $_SERVER['REMOTE_ADDR'];
			}
			$qb = new QueryBuilder();
			$qb->select($qb->expr()->count(new Field('*'), 'count'))
				->from(Tbl::get("TBL_SECURITY_FLOODER_IPS"))
				->where($qb->expr()->equal(new Field('ip'), $ip));
				
			$this->query->exec($qb->getSQL(), $cacheMinutes);
			
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
		foreach($this->config->limits->toArray() as $name => $limit){
			$qb = new QueryBuilder();
			$qbSelect = new QueryBuilder();
			$qbSelect->select(new Field('ip'))
					->from(Tbl::get('TBL_SECURITY_REQUESTS_LOG'))
					->where($qbSelect->expr()->equal(new Field('type'),  $name))
					->andWhere($qbSelect->expr()->greaterEqual(new Field('count'),  $limit));

			$qb->insertIgnore(Tbl::get('TBL_SECURITY_FLOODER_IPS'))
						->fields('ip')
						->values($qbSelect);

			$this->query->exec($qb->getSQL());
		}
		
		$this->query->exec("TRUNCATE TABLE `".Tbl::get('TBL_SECURITY_REQUESTS_LOG')."`");
		
	}
	
	/**
	 * Record current request in requests log
	 */
	public function recordRequest($type = null, $ip = null){
		if(Cgi::getMode()){
			return;
		}
		
		if($type === null){
			$type = self::TYPE_GENERAL;
		}
		
		if($ip === null){
			$ip = $_SERVER['REMOTE_ADDR'];
		}
		$qb = new QueryBuilder();
		$qb->insert(Tbl::get('TBL_SECURITY_REQUESTS_LOG'))
			->values(array('type' => $type, 'ip' => $ip))
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
		Reg::get('memcache')->invalidateCacheByTag('RequestLimiter');
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
		Reg::get('memcache')->invalidateCacheByTag('RequestLimiter');
	}
}
