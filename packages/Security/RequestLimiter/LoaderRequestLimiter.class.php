<?
class LoaderRequestLimiter extends Loader{
	protected function includes(){
		require_once ('Exceptions/RequestLimiterTooManyAuthTriesException.class.php');
		require_once ('RequestLimiter.class.php');
		require_once ('RequestLimiterBlockedException.class.php');
	}
	
	protected function customInitBeforeObjects(){
		Tbl::registerTableNames('RequestLimiter');
	}
	
	protected function loadRequestLimiter(){
		$this->requestLimiter = new RequestLimiter($this->config->AuxConfig);
		
		$this->register($this->requestLimiter);
	}
	
	public function hookRequestLimiterRun(){
		if($this->requestLimiter->isBlacklistedIp()){
			throw new RequestLimiterBlockedException("This IP exceeded it's maximum request limit per minute.");
		}
		$this->requestLimiter->recordRequest();
	}
	
	public function hookClearInvalidLoginsLog($params){
		if($this->config->AuxConfig->loginBruteForceProtectionEnabled){
			if(isset($_SERVER['REMOTE_ADDR'])){
				$sql = MySqlDbManager::getQueryObject();
				$qb = new QueryBuilder();
				$sql->exec(
						$qb->delete(Tbl::get('TBL_SECURITY_INVALID_LOGINS_LOG', 'RequestLimiter'))
							->where($qb->expr()->equal(new Field('ip'), $_SERVER['REMOTE_ADDR']))
							->getSQL()
				);
			}
		}
	}
	
	public function hookInvalidLoginAttempt($params){
		if($this->config->AuxConfig->loginBruteForceProtectionEnabled){
			if(isset($_SERVER['REMOTE_ADDR'])){
				$sql = MySqlDbManager::getQueryObject();
				$qb = new QueryBuilder();
					
				$sql->exec(
						$qb->select(new Field('count'))
						->from(Tbl::get('TBL_SECURITY_INVALID_LOGINS_LOG', 'RequestLimiter'))
						->where($qb->expr()->equal(new Field('ip'), $_SERVER['REMOTE_ADDR']))
						->getSQL()
				);
					
				$failedAuthCount = $sql->fetchField('count');
					
				$newFailedAuthCount = $failedAuthCount + 1;
					
				if($newFailedAuthCount >= $this->config->AuxConfig->failedLoginLimit){
					Reg::get(ConfigManager::getConfig("Security", "RequestLimiter")->Objects->RequestLimiter)->blockIP();
		
					$qb = new QueryBuilder();
					$sql->exec(
							$qb->delete(Tbl::get('TBL_SECURITY_INVALID_LOGINS_LOG', 'RequestLimiter'))
							->where($qb->expr()->equal(new Field('ip'), $_SERVER['REMOTE_ADDR']))
							->getSQL()
					);
		
					throw new RequestLimiterTooManyAuthTriesException("Too many unsucessful authorization tries.");
				}
					
				$qb = new QueryBuilder();
				$sql->exec(
						$qb->insert(Tbl::get('TBL_SECURITY_INVALID_LOGINS_LOG', 'RequestLimiter'))
						->values(array('ip' => $_SERVER['REMOTE_ADDR']))
						->onDuplicateKeyUpdate()
						->set(new Field('count'), $qb->expr()->sum(new Field('count'), 1))
						->getSQL()
				);
			}
		}
	}
}
?>