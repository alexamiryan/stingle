<?php
class DBLogger extends Logger{

	const TBL_REQUEST_LOG = 'log_requests';
	const TBL_MIXED_LOG = 'log_mixed';

	public function log($name, $value){
		static::logCustom($name, $value);
	}
	
	public static function logRequest($dbInstanceKey = null){
		$sql = MySqlDbManager::getQueryObject($dbInstanceKey);
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
		$remoteIP="";
		if(isset($_SERVER['REMOTE_ADDR'])){
			$remoteIP=$_SERVER['REMOTE_ADDR'];
		}
		$qb = new QueryBuilder();
		$qb->insert(Tbl::get('TBL_MIXED_LOG'))
			->values(array(
							"session_id" => session_id(), 
							"name" => $name, 
							"value" => $value, 
							"ip" => $remoteIP 
						)
					);
		Reg::get('sql')->exec($qb->getSQL());
	}
}
