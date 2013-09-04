<?php
class LoaderRequestLoggerUsers extends Loader{
	protected function includes(){
		stingleInclude ('Managers/RequestLoggerUsers.class.php');
	}
	
	protected function customInitBeforeObjects(){
		Tbl::registerTableNames('RequestLoggerUsers');
	}
	
	public function hookLogRequest(){
		if(ConfigManager::getConfig('Logger', 'DBLogger')->AuxConfig->requestLogEnabled){
			RequestLoggerUsers::logRequest();
		}
	}
}
