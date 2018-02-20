<?php
class HostManager{
	
	const HTTPS_YES = 'y';
	const HTTPS_NO = 'n';
	
	public static function updateHost(Host $host){
		if(empty($host->id)){
			throw new InvalidArgumentException("HostId is empty!");
		}
		$qb = new QueryBuilder();
		$qb->update(Tbl::get('TBL_HOSTS', 'Host'))
			->set(new Field('host'), $host->host)
			->set(new Field('subdomain'), $host->subdomain)
			->set(new Field('https'), $host->https)
			->where($qb->expr()->equal(new Field('id'), $host->id));

		$sql = MySqlDbManager::getQueryObject();
		$sql->exec($qb->getSQL());	
	}
	
	public static function deleteHost(Host $host){
		if(empty($host->id)){
			throw new InvalidArgumentException("HostId is empty!");
		}
		$qb = new QueryBuilder();
		$qb->delete(Tbl::get('TBL_HOSTS', 'Host'))
			->where($qb->expr()->equal(new Field('id'), $host->id));
		
		$sql = MySqlDbManager::getQueryObject();
		$sql->exec($qb->getSQL());
	}
	
	public static function getHostById($hostId, $cacheMinutes = null){
		$sql = MySqlDbManager::getQueryObject();
		$qb = new QueryBuilder();
		$qb->select(new Field('*'))
			->from(Tbl::get('TBL_HOSTS', 'Host'))
			->where($qb->expr()->equal(new Field('id'), $hostId));

		$sql->exec($qb->getSQL(), $cacheMinutes);
		if($sql->countRecords()){
			$data = $sql->fetchRecord();
			$host = new Host();
			Host::setData($data, $host);
			return $host;
		}
		throw new RuntimeException("There is no such host by id(".$hostId.")");
	}
	
	public static function addHost(Host $host){
		if(empty($host->host)){
			throw new InvalidArgumentException("Host name is empty!");
		}
		$values = array( "host" => $host->host, "https" => $host->https);
		if(!empty($host->subdomain)){
			$values["subdomain"] = $host->subdomain;
		}
		$qb = new QueryBuilder();
		$qb->insert(Tbl::get('TBL_HOSTS', 'Host'))
			->values($values);
		$sql = MySqlDbManager::getQueryObject();

		$sql->exec($qb->getSQL());
		
		$host->id = $sql->getLastInsertId();
	}
	
	public static function getHostByName($hostName, $tryToAutoCreateHost = true, $cacheMinutes = null){
		$sql = MySqlDbManager::getQueryObject();
		
		$qb = new QueryBuilder();
		$qb->select(new Field('*'))
			->from(Tbl::get('TBL_HOSTS', 'Host'))
			->where($qb->expr()->equal(new Field('host'), $hostName));
		
		$sql->exec($qb->getSQL(), $cacheMinutes);
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
			
			$qb = new QueryBuilder();
			$qb->select(new Field('*'))
				->from(Tbl::get('TBL_HOSTS', 'Host'))
				->where($qb->expr()->equal(new Field('host'), $wildcardHostName));
			
			$sql->exec($qb->getSQL(), $cacheMinutes);
			if($sql->countRecords()){
				$data = $sql->fetchRecord();
				$data['host'] = $originalHostName;
				$data['wildcardOf'] = $parentHostName;
				$host = new Host();
				Host::setData($data, $host);
				return $host;
			}
			elseif($tryToAutoCreateHost){
				$host = new Host();
				$host->host = $hostName;
				
				self::addHost($host);
				
				return self::getHostByName($hostName, false, $cacheMinutes);
			}
			throw new RuntimeException("There is no such host (".$originalHostName.")");
		}
		
	}
	
	/**
	 * Get all hosts
	 *@return array Set of Host objects
	 */
	public static function getAllHosts(MysqlPager $pager = null, $cacheMinutes = null){
		$hosts = array();
		$sql = MySqlDbManager::getQueryObject();
		
		
		$qb = new QueryBuilder();
		$qb->select(new Field('*'))->from(Tbl::get('TBL_HOSTS', 'Host'));
		
		if($pager !== null){
			$sql = $pager->executePagedSQL($qb->getSQL(), $cacheMinutes);
		}
		else{
			$sql->exec($qb->getSQL(), $cacheMinutes);
		}
		
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
	
	public static function getSiteUrl($withWww = false){
		return static::protocol() .($withWww ? 'www.' : ''). static::getHostName();		
	}
	
	public static function hostToURLAddress(Host $host){
		$host_address = '';
		if($host->https == HostManager::HTTPS_YES){
			$host_address .= "https://";
		}
		else{
			$host_address .= "http://";
		}
		
		if($host->subdomain == null){
			$host_address .= 'www.';
		}
		$host_address .= $host->host; 
		return $host_address;
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
