<?
class Host{

	public $id;
	public $domain;
	public $extension;
	public $host;
	public $wildcardOf = null;

	const TBL_HOSTS = "hosts";

	function __construct($host_id = null, $cacheMinutes = null, $dbInstanceKey = null){
		if($host_id !== null){
			if(!is_numeric($host_id)){
				throw new InvalidIntegerArgumentException("host_id argument should be an integer.");
			}
			$sql = MySqlDbManager::getQueryObject($dbInstanceKey);
			$sql->exec("SELECT * FROM `".Tbl::get('TBL_HOSTS') ."` WHERE `id` = '{$host_id}'", $cacheMinutes);
			if($sql->countRecords()){
				$res = $sql->fetchRecord();
				static::setData($res, $this);
			}
			else{
				throw new InvalidArgumentException("Wrong host id is given. No record with id: $host_id in table ".Tbl::get('TBL_HOSTS') );				
			}
		}
	}
	/**
	 * set Object members from Database data
	 *
	 * @param array Db query result $data
	 * @return void
	 */
	public static function setData($data, Host $host){
		$host->id = $data["id"];
		$host->domain = $data["domain"];
		$host->extension = $data["extension"];
		$host->host = $data["host"];
		if(isset($data['wildcardOf'])){
			$host->wildcardOf = $data['wildcardOf'];
		}
	}
}
?>