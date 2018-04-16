<?php
class RequestLoggerUsers extends DBLogger {

	public static function logRequest($dbInstanceKey = null){
		$sql = MySqlDbManager::getQueryObject($dbInstanceKey);
		
		$userId = "NULL";
		$userObjectSerialized = "''";
		$userObj = Reg::get(ConfigManager::getConfig("Users", "Users")->ObjectsIgnored->User);

		if($userObj->isAuthorized()){
			$userId = $userObj->id;
			$userObjectSerialized = "'". MySqlDbManager::getQueryObject()->escapeString(serialize($userObj)) . "'";
		}
		$qb = new QueryBuilder();
		$qb->insert(Tbl::get('TBL_REQUEST_LOG'))
			->values(array(
							"user_id" => $userId, 
							"user_obj" => $userObjectSerialized, 
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
}
