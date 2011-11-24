<?
class DBLogger extends Logger{

	const TBL_REQUEST_LOG = 'log_requests';
	const TBL_MIXED_LOG = 'log_mixed';

	public function log($name, $value){
		static::logCustom($name, $value);
	}
	
	public static function logRequest($dbInstanceKey = null){
		$sql = MySqlDbManager::getQueryObject($dbInstanceKey);
		
		$sql->exec("INSERT DELAYED INTO `".Tbl::get("TBL_REQUEST_LOG")."` 
						(`session_id`, `get`, `post`, `server`, `cookies`, `session`, `response`)
						VALUES	(
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
	
	public static function logCustom($name, $value){
		$remoteIP="";
		if(isset($_SERVER['REMOTE_ADDR'])){
			$remoteIP=$_SERVER['REMOTE_ADDR'];
		}
		
		Reg::get('sql')->exec( "INSERT DELAYED INTO `".Tbl::get("TBL_MIXED_LOG")."` 
										(`session_id`,`name`,`value`,`ip`)
										VALUES (
													'".session_id()."',
													'".mysql_real_escape_string($name)."',
													'".mysql_real_escape_string($value)."',
													'$remoteIP'
												)"
		);
	}
}
?>