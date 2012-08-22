<?
class RequestLoggerUsers extends DBLogger {

	public static function logRequest($dbInstanceKey = null){
		$sql = MySqlDbManager::getQueryObject($dbInstanceKey);
		
		$userId = "NULL";
		$userObjectSerialized = "''";
		$userObj = Reg::get(ConfigManager::getConfig("Users", "Users")->ObjectsIgnored->User);

		if($userObj->isAuthorized()){
			$userId = $userObj->id;
			$userObjectSerialized = "'". mysql_real_escape_string(serialize($userObj)) . "'";
		}

		$sql->exec("INSERT DELAYED INTO `".Tbl::get("TBL_REQUEST_LOG")."` 
						(`user_id`, `user_obj`,`session_id`, `get`, `post`, `server`, `cookies`, `session`, `response`)
						VALUES	(
									$userId,
									$userObjectSerialized,
									'".session_id()."',
									'".mysql_real_escape_string(serialize($_GET))."',
									'".mysql_real_escape_string(serialize($_POST))."',
									'".mysql_real_escape_string(serialize($_SERVER))."',
									'".mysql_real_escape_string(serialize($_COOKIE))."',
									'".mysql_real_escape_string(serialize($_SESSION))."',
									'".mysql_real_escape_string(ob_get_contents())."'
								)"
		);
						
	} 
}
?>