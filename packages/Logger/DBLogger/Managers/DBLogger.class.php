<?php
class DBLogger extends Logger{

	const TBL_REQUEST_LOG = 'log_requests';
	const TBL_MIXED_LOG = 'log_mixed';

	public function log($name, $value){
		static::logCustom($name, $value);
	}
	
	public static function logRequest($instanceName = null){
		$sql = MySqlDbManager::getQueryObject($instanceName);
		$qb = new QueryBuilder();
		$qb->insert(Tbl::get('TBL_REQUEST_LOG'))
			->values(array(
							"session_id" => session_id(), 
							"get" => serialize($_GET), 
							"post" => serialize($_POST), 
							"server" => serialize($_SERVER), 
							"cookies" => serialize($_COOKIE), 
							"session" => serialize($_SESSION), 
							"response" => ob_get_contents() 
						)
					);
		$sql->exec($qb->getSQL());
						
	}
	
	public static function logCustom($name, $value){
		$ipLoggingEnabled = ConfigManager::getConfig("Logger", "DBLogger")->AuxConfig->saveIPInCustomLog;
        $isUsingSessions = ConfigManager::getConfig("Logger", "DBLogger")->AuxConfig->isUsingSessions;
        $insertArr = [
            "name" => $name,
            "value" => $value,
        ];
        
		if($ipLoggingEnabled && isset($_SERVER['REMOTE_ADDR'])){
            $insertArr["ip"] = $_SERVER['REMOTE_ADDR'];
		}
		if($isUsingSessions){
            $insertArr["session_id"] = session_id();
		}
		
		$qb = new QueryBuilder();
		$qb->insert(Tbl::get('TBL_MIXED_LOG'))
			->values($insertArr);
		
		Reg::get('sql')->exec($qb->getSQL());
	}
}
