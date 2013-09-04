<?php
class LoaderDBLogger extends Loader{
	protected function includes(){
		stingleInclude ('Managers/DBLogger.class.php');
	}
	
	protected function customInitBeforeObjects(){
		Tbl::registerTableNames('DBLogger');
	}
	
	public function hookLogRequest(){
		if($this->config->AuxConfig->requestLogEnabled){
			DBLogger::logRequest();
		}
	}
}
