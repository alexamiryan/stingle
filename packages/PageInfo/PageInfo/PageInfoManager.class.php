<?
class PageInfoManager extends PageInfo 
{
	public static function getRecord(Language $lang=null, Host $host=null, $module, $page ){		
		
		$lang_id = ($lang === null ? null : $lang->id);
		$host_id = ($lang === null ? null : $host->id);
		
		$sql = MySqlDbManager::getQueryObject();
		$sql->exec(self::queryString($lang_id, $host_id, $module, $page));
		$pageInfo = $sql->fetchRecord();
		return $pageInfo;		
	}
	
	public static function getLanguageHosts(Language $lang){
		$hosts = array();
		$sql = MySqlDbManager::getQueryObject();
		$sql->exec("SELECT h.* FROM `".parent::TBL_PAGE_INFO."` pi 
					LEFT JOIN `".Host::TBL_HOSTS."` h ON h.id = pi.host_id 
					WHERE pi.lang_id='{$lang->id}' AND pi.host_id IS NOT NULL
					GROUP BY h.id");	
		$hosts_data = $sql->fetchRecords();
		foreach ($hosts_data as $host_data){
			$host = new Host();
			Host::setData($host_data, $host);
			$hosts[]=$host;
		}
		return $hosts;		
	}
	
	public static function getModules(Language $lang, Host $host){
		$modules = array();
		$sql = MySqlDbManager::getQueryObject();
		$sql->exec("SELECT pi.module FROM `".parent::TBL_PAGE_INFO."` pi 
					WHERE pi.lang_id='$lang->id' AND pi.host_id ='$host->id' AND pi.module IS NOT NULL
					GROUP BY pi.module");
		if($sql->countRecords()){	
			$modules = $sql->fetchRecords();
		}
		return $modules;		
	}
	
	public static function getModulePages(Language $lang, Host $host, $module){
		$pages = array();
		$sql = MySqlDbManager::getQueryObject();
		$sql->exec("SELECT pi.page FROM `".parent::TBL_PAGE_INFO."` pi 
					WHERE pi.lang_id='{$lang->id}' AND pi.host_id ='$host->id' AND pi.module='$module' AND pi.page IS NOT NULL
					GROUP BY pi.page");	
		if($sql->countRecords()){	
			$pages = $sql->fetchRecords();
		}
		return $pages;
	}
	
	public static function save(array $pageInfo, Language $lang=null, Host $host=null, $module=null, $page=null ){
		$sql = MySqlDbManager::getQueryObject();
				
		if($lang === null){
			if($id = self::exists()){
				$query =  self::updateQueryString($pageInfo, $id);
			}
			else{
				$query =  self::insertQueryString($pageInfo);
			}
		}
		elseif ($host === null){
			if($id = self::exists($lang->id)){
				$query =  self::updateQueryString($pageInfo, $id);
			}
			else{
				$query = self::insertQueryString($pageInfo, $lang->id);
			}			
		}
		else{
			if($id = self::exists($lang->id,$host->id, $module, $page)){
				$query =  self::updateQueryString($pageInfo, $id);
			}
			else{
				$query = self::insertQueryString($pageInfo, $lang->id, $host->id, $module, $page);
			}
		}
		$sql->exec($query);
	}
	
	/**
	 * Check if record exists
	 *
	 * @param array $pageInfo
	 * @param Language $lang
	 * @param Host $host
	 * @param string $module
	 * @param string $page
	 * @return bool
	 */
	private static function exists($lang_id=null, $host_id=null, $module=null, $page=null){
		
		$lang_where = "lang_id ". ($lang_id === null ? "IS NULL " : "=".$lang_id);
		$host_where = "host_id ". ($host_id === null ? "IS NULL " : "=".$host_id);
		$module_where = "module ". ($module === null ? "IS NULL " : "='".$module."'");
		$page_where = "page ". ($page === null ? "IS NULL " : "='".$page."'");

		$sql = MySqlDbManager::getQueryObject();
		$query = "SELECT id FROM `".self::TBL_PAGE_INFO."` 
		WHERE  ".$lang_where."
		AND ".$host_where."
		AND ".$module_where."
		AND ".$page_where;
		$sql->exec($query);
		if($sql->countRecords()){
			return  $sql->fetchField("id");
		}
		return false;
	}
	
	/**
	 * Insert query string generator
	 *
	 * @param array $pageInfo
	 * @param int $langId
	 * @param int $hostId
	 * @param sring $module
	 * @param string $page
	 * @return string
	 */
	private static function insertQueryString(array $pageInfo, $langId=null, $hostId=null, $module=null, $page=null){
		$langId  = ($langId === null? 'NULL' : $langId);
		$hostId  = ($hostId === null? 'NULL' : $hostId);
		$module = ($module === null? 'NULL' : "'".$module."'");
		$page  	= ($page === null? 'NULL' : "'".$page."'");
				
		$query = "INSERT INTO `".self::TBL_PAGE_INFO."` (`lang_id`, `host_id`, `module`, `page`, `title`, `meta_keywords`, `meta_description`) 
				VALUES ($langId, $hostId, $module, $page, '".mysql_real_escape_string($pageInfo['title'])."', '".mysql_real_escape_string($pageInfo['keywords'])."', '".mysql_real_escape_string($pageInfo['description'])."')";
		return $query;
	}
	
	private static function updateQueryString(array $pageInfo, $id){
		$query = "UPDATE `".self::TBL_PAGE_INFO."` 
		SET `title`='".mysql_real_escape_string($pageInfo['title'])."',  `meta_keywords` ='".mysql_real_escape_string($pageInfo['keywords'])."',  `meta_description`='".mysql_real_escape_string($pageInfo['description'])."'
		WHERE id=".$id;
		return $query;		
	}
}
?>