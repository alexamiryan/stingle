<?
class HostManager{
	
	public static function getHostByName($hostName, $cacheMinutes = null){
		$sql = MySqlDbManager::getQueryObject();
		$sql->exec("SELECT * FROM `".Tbl::get('TBL_HOSTS', 'Host')  ."` WHERE `host` = '{$hostName}'", $cacheMinutes);
		if($sql->countRecords()){
			$data = $sql->fetchRecord();
			$host = new Host();
			Host::setData($data, $host);
			return $host;
		}
		else{			
			$originalHostName = $hostName;
			$explodedArray = explode(".", $hostName, 2);
			$parentHostName = array_pop($explodedArray);
			$wildcardHostName = "*.". $parentHostName;
			
			$sql->exec("SELECT * FROM `".Tbl::get('TBL_HOSTS', 'Host')  ."` WHERE `host` = '{$wildcardHostName}'", $cacheMinutes);
			if($sql->countRecords()){
				$data = $sql->fetchRecord();
				$data['host'] = $originalHostName;
				$data['wildcardOf'] = $parentHostName;
				$host = new Host();
				Host::setData($data, $host);
				return $host;
			}
			throw new RuntimeException("There is no such host (".$originalHostName.")");
		}
		
	}
	
	/**
	 * Get all hosts
	 *@return array Set of Host objects
	 */
	public static function getAllHosts($cacheMinutes = null){
		$hosts = array();
		$sql = MySqlDbManager::getQueryObject();
		$sql->exec("SELECT * FROM `".Tbl::get('TBL_HOSTS', 'Host')."`", $cacheMinutes);
		while(($host_data = $sql->fetchRecord()) != false){
			$h = new Host();
			Host::setData($host_data, $h);
			$hosts[] = $h;
		}
		return $hosts;
	}

	public static function protocol(){
		$protocol = 'http';
		if(array_key_exists("HTTPS", $_SERVER) and $_SERVER["HTTPS"] == "on"){
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
		$hostConfig = ConfigManager::getConfig("Host")->AuxConfig;

		if(Cgi::getMode()){
			return $hostConfig->cgiHost;
		}
		
		if(empty($_SERVER["SERVER_NAME"])){
			$_SERVER["SERVER_NAME"] = $hostConfig->cgiHost;
		}
		
		$page_url = "";
		if(isset($_SERVER["SERVER_PORT"]) and $_SERVER["SERVER_PORT"] != "80"){
			$page_url .= $_SERVER["SERVER_NAME"] . ":" . $_SERVER["SERVER_PORT"] . $_SERVER["REQUEST_URI"];
		}
		else{
			$page_url .= $_SERVER["SERVER_NAME"] . $_SERVER["REQUEST_URI"];
		}
		return $page_url;
	}
	
	/**
	 * Check is host name wildcard (has * in the end)
	 *
	 * @param string $hostName
	 * @return bool 
	 */
	public static function isWildcard($hostName){
		if(array_shift(explode(".",$hostName)) =="*"){
			return true;
		}
		else{
			return false;	
		} 
	}


	/**
	 * Get host name of the given URL
	 *
	 * @param string $url
	 * @return string 
	 */

	public static function getHostName(){
		$hostConfig = ConfigManager::getConfig("Host")->AuxConfig;
		if(empty($_SERVER['HTTP_HOST'])){
			$_SERVER['HTTP_HOST'] = $hostConfig->cgiHost;
		}
		
		if(SiteMode::get()==SiteMode::MODE_DEVELOPMENT and static::getDevHostName() !==false ){
			$host_name = static::getDevHostName();
		}
		else{
			$host_name = static::noWWW($_SERVER['HTTP_HOST']);
		}		
		return $host_name;		
	}
	
	public static function getSiteUrl(){
		return static::protocol() . static::getHostName();		
	}
	
	private static function getDevHostName(){
		if(!empty($_GET["host"])){
			$_SESSION["dev_host"] = static::noWWW($_GET['host']);
		}
		if(isset($_SESSION["dev_host"]) and !empty($_SESSION["dev_host"])){
			return $_SESSION["dev_host"];
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