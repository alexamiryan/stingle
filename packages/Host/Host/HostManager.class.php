<?
class HostManager{
	
	public static function getHostByName($hostName){
		$sql = MySqlDbManager::getQueryObject();
		$sql->exec("SELECT * FROM `".Tbl::get('TBL_HOSTS', 'Host')  ."` WHERE `host` = '{$hostName}'");
		if($sql->countRecords()){
			$data = $sql->fetchRecord();
			$host = new Host();
			Host::setData($data, $host);
			return $host;
		}
		throw new RuntimeException("There is no such host (".$hostName.")");
	}
	
	/**
	 * Get all hosts
	 *@return array Set of Host objects
	 */
	public static function getAllHosts(){
		$hosts = array();
		$sql = MySqlDbManager::getQueryObject();
		$sql->exec("SELECT * FROM `".Tbl::get('TBL_HOSTS', 'Host')."`");
		while(($host_data = $sql->fetchRecord()) != false){
			$h = new Host();
			Host::setData($host_data, $h);
			$hosts[] = $h;
		}
		return $hosts;
	}

	public static function protocol(){
		$protocol = 'http';
		if($_SERVER["HTTPS"] == "on"){
			$protocol .= "s";
		}
		$protocol .= "://";
		return $protocol;
	}
	
	/**
	 * 
	 *
	 * @param protocol $protocol
	 * @return unknown
	 */
	public static function pageURL(){
		$hostConfig = ConfigManager::getConfig("Host");
		if(empty($_SERVER["SERVER_NAME"])){
			$_SERVER["SERVER_NAME"] = $hostConfig->cgiHost;
		}
		
		$page_url = "";
		if($_SERVER["SERVER_PORT"] != "80"){
			$page_url .= $_SERVER["SERVER_NAME"] . ":" . $_SERVER["SERVER_PORT"] . $_SERVER["REQUEST_URI"];
		}
		else{
			$page_url .= $_SERVER["SERVER_NAME"] . $_SERVER["REQUEST_URI"];
		}
		return $page_url;
	}


	/**
	 * Get host name of the given URL
	 *
	 * @param string $url
	 * @return string 
	 */

	public static function getHostName(){
		$hostConfig = ConfigManager::getConfig("Host");
		if(empty($_SERVER['HTTP_HOST'])){
			$_SERVER['HTTP_HOST'] = $hostConfig->cgiHost;
		}
		
		if(Debug::getMode() and static::getDebugHostName() !==false ){
			$host_name = static::getDebugHostName();
		}
		else{
			$host_name = $_SERVER['HTTP_HOST'];
		}		
		return $host_name;		
	}
	
	public static function getSiteUrl(){
		return static::protocol() . static::getHostName();		
	}
	
	private static function getDebugHostName(){
		if(!empty($_GET["host"])){
			$_SESSION["debug_host"] = static::noWWW($_GET['host']);
		}
		if(isset($_SESSION["debug_host"]) and !empty($_SESSION["debug_host"])){
			return $_SESSION["debug_host"];
		}
		return false;
	}

	private static function getHostFromUrl($url){
		$nowww = static::noWWW($url);
		$domain = parse_url($nowww);
		if(!empty($domain["host"])){
			return $domain["host"];
		} 
		else{
			return $domain["path"];
		}
	}
	
	private static function noWWW($string){
		return preg_replace('/www\./','',$string);
	}
}
?>