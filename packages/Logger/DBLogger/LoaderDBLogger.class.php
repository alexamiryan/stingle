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
    
    public function hookDBLog($params){
        if(is_array($params) and count($params) == 2) {
            DBLogger::logCustom($params[0], $params[1]);
            return;
        }
        throw new RuntimeException("Invalid use of DBLog hook, Params: [name, value]");
    }
}
