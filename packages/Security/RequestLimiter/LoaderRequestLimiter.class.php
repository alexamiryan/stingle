<?
class LoaderRequestLimiter extends Loader{
	protected function includes(){
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
}
?>