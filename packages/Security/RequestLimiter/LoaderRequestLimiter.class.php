<?php
class LoaderRequestLimiter extends Loader{
	protected function includes(){
		stingleInclude ('Exceptions/RequestLimiterBlockedException.class.php');
		stingleInclude ('Managers/RequestLimiter.class.php');
		stingleInclude ('Helpers/helpers.inc.php');
	}
	
	protected function customInitBeforeObjects(){
		Tbl::registerTableNames('RequestLimiter');
	}
	
	protected function loadRequestLimiter(){
		$this->requestLimiter = new RequestLimiter($this->config->AuxConfig);
		
		$this->register($this->requestLimiter);
	}
	
	public function hookRequestLimiterGeneralRun(){
		if($this->requestLimiter->isBlacklistedIp()){
			throw new RequestLimiterBlockedException("This IP exceeded it's maximum request limit per minute.");
		}
		$this->requestLimiter->recordRequest();
	}
	
	public function hookRecordRequest($type){
		$this->requestLimiter->recordRequest($type);
	}
}
