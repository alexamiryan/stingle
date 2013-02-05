<?php
class HostLanguageManager extends LanguageManager {

	protected $host;//Host Object
	
	protected $hostLangs = array(); //Set of Languages of fiven Host
	
	private $_hostLangId;

	const TBL_HOST_LANGUAGE = "host_language";

	public function __construct(Host $host, $dbInstanceKey = null){
		$this->host = $host;
		parent::__construct(null, $dbInstanceKey);
		$this->hostLangs = static::getHostLanguages($this->host);
		$this->setCurrentHostLangId();
	}
	
	private function setCurrentHostLangId(){
		$this->_hostLangId = $this->getHostLanguageId($this->host, $this->language);
	}

	public function getCurrentHostLangId(){
		return $this->_hostLangId;
	}
	
	/**
	 * Returns host.
	 *
	 * @return Host
	 */
	public function getHost(){
		return $this->host;
	}

	/**
	 * Enter description here...
	 *
	 * @param string $language language short name
	 */
	public function languageExists($language, $cacheMinutes = null){
		$qb = new QueryBuilder();
		$qb->select(new Field('id', 'l'))
			->from(Tbl::get("TBL_LANGUAGES", "Language"), 'l')
			->leftJoin(Tbl::get('TBL_HOST_LANGUAGE'), 'hl', $qb->expr()->equal(new Field('lang_id', 'hl'), new Field('id', 'l')))
			->where($qb->expr()->equal(new Field('host_id', 'hl'), $this->host->id))
			->andWhere($qb->expr()->equal(new Field('name', 'l'), $language));
		
		$this->query->exec($qb->getSQL(), $cacheMinutes);
		if($this->query->countRecords()){
			return true;
		}
		else{
			return false;
		}

	}

	/**
	 * Get Default language
	 * @return Language
	 */
	public function getDefaultLanguage($cacheMinutes = null){
		$qb = new QueryBuilder();
		$qb->select(new Field('*', 'l'))
			->from(Tbl::get("TBL_HOST_LANGUAGE"), 'hl')
			->leftJoin(Tbl::get("TBL_LANGUAGES", "Language"), 'l', $qb->expr()->equal(new Field('lang_id', 'hl'), new Field('id', 'l')))
			->where($qb->expr()->equal(new Field('host_id', 'hl'), $this->host->id))
			->andWhere($qb->expr()->equal(new Field('default', 'hl'), 1));
		
		$this->query->exec($qb->getSQL(), $cacheMinutes);
		if($this->query->countRecords()){
			$lang_data = $this->query->fetchRecord();
			$l = new Language();
			Language::setData($lang_data,$l);
			return $l;
		}
		throw new RuntimeException("Default language not defined for '". $this->host->host ."'");
	}
	
	public function Languages(){
		return $this->hostLangs;
	}
	
	/**
	 * Add new language to the Host.
	 * Returns hostLanguageId.
	 *
	 * @param Host $host
	 * @param Language $language
	 *
	 * @access public
	 * @return integer
	 */
	public function addHostLanguage(Host $host, Language $language){
		if(empty($language->id) or empty($host->id)){
			throw new EmptyArgumentException();
		}
		$default = 0;
		if(self::getHostDefaultLanguage($host) === false){
			$default = 1;
		}
	
		$qb = new QueryBuilder();
		$qb->insert(Tbl::get("TBL_HOST_LANGUAGE"))
			->values(array(
				'host_id' => $host->id,
				'lang_id' => $language->id,
				'default' => $default
		));
	
		$this->query->exec($qb->getSQL());	
		return $this->query->getLastInsertId();
	}


	/**
	 * Get possible languages of given host
	 *
	 * @param Host $host
	 * @return array set of Language objects
	 */
	public static function getHostLanguages(Host $host, MysqlPager $pager = null, $cacheMinutes = null){
		$sql = MySqlDbManager::getQueryObject();
		$languages = array();
		
		$qb = new QueryBuilder();
		$qb->select(new Field('*', 'l'))
			->from(Tbl::get("TBL_HOST_LANGUAGE"), 'hl')
			->leftJoin(Tbl::get("TBL_LANGUAGES", "Language"), 'l', $qb->expr()->equal(new Field('lang_id', 'hl'), new Field('id', 'l')))
			->where($qb->expr()->equal(new Field('host_id', 'hl'), $host->id));
		
		if($pager !== null){
			$sql = $pager->executePagedSQL($qb->getSQL(), $cacheMinutes);
		}
		else{
			$sql->exec($qb->getSQL(), $cacheMinutes);
		}
		$langs_data = $sql->fetchRecords();
		foreach ($langs_data as $lang_data){
			$lang = new Language();
			Language::setData($lang_data, $lang);
			$languages[]=$lang;
		}
		return $languages;
	}
	
	/**
	 * Get default language for given host
	 *
	 * @param Host $host
	 * @return Language
	 */
	public static function getHostDefaultLanguage(Host $host, $cacheMinutes = null){
		$sql = MySqlDbManager::getQueryObject();
		
		$qb = new QueryBuilder();
		$qb->select(new Field('*', 'l'))
			->from(Tbl::get("TBL_HOST_LANGUAGE"), 'hl')
			->leftJoin(Tbl::get("TBL_LANGUAGES", "Language"), 'l', $qb->expr()->equal(new Field('lang_id', 'hl'), new Field('id', 'l')))
			->where($qb->expr()->equal(new Field('host_id', 'hl'), $host->id))
			->andWhere($qb->expr()->equal(new Field('default', 'hl'), 1));
		
		$sql->exec($qb->getSQL(), $cacheMinutes);
		if($sql->countRecords()){
			$data = $sql->fetchRecord();
			$lang = new Language();
			Language::setData($data, $lang);
			return $lang;
		}
		return false;
	}
	
	/**
	 * 
	 * @param Language $lang
	 * @param integer $cacheMinutes
	 * @return array
	 */
	public static function getLanguageHosts(Language $lang, $cacheMinutes = null){
		$hosts = array();
		$sql = MySqlDbManager::getQueryObject();
		
		$qb = new QueryBuilder();
		$qb->select(new Field('*', 'h'))
			->from(Tbl::get("TBL_HOST_LANGUAGE"), 'hl')
			->leftJoin(Tbl::get("TBL_HOSTS", "Host"), 'h', $qb->expr()->equal(new Field('host_id', 'hl'), new Field('id', 'h')))
			->where($qb->expr()->equal(new Field('lang_id', 'hl'), $lang->id));
		
		$sql->exec($qb->getSQL(), $cacheMinutes);
		$hosts_data = $sql->fetchRecords();
		foreach ($hosts_data as $host_data){
			$host = new Host();
			Host::setData($host_data, $host);
			$hosts[]=$host;
		}
		return $hosts;
	}
	/**
	 * Enter description here...
	 *
	 * @param int $host_languge_id
	 * @return array $HostLanguagePair
	 */
	public static function getHostLanguagePair($host_language_id, $cacheMinutes = null){
		if(!is_numeric($host_language_id)){
			throw new InvalidArgumentException("host_languge id should be an integer");
		}
		$sql = MySqlDbManager::getQueryObject();
		
		$qb = new QueryBuilder();
		$qb->select(array(new Field('host_id'), new Field('lang_id')))
			->from(Tbl::get('TBL_HOST_LANGUAGE'))
			->where($qb->expr()->equal(new Field('id'), $host_language_id));
		
		$sql->exec($qb->getSQL(), $cacheMinutes);
		if($sql->countRecords()){
			$res = $sql->fetchRecord();
			$host = new Host($res['host_id']);		
			$lang = new Language($res['lang_id']);	
			return array("host"=>$host, "language"=>$lang);	
		}
		throw new InvalidArgumentException("Wrong host_languge id given. No record with id: $host_language_id in table ".Tbl::get('TBL_HOST_LANGUAGE') );				
	}
	
	private static function _getHostLanguageId($hostId, $languageId, $cacheMinutes = null){
		$sql = MySqlDbManager::getQueryObject();
		
		$qb = new QueryBuilder();
		$qb->select(new Field('id'))
			->from(Tbl::get('TBL_HOST_LANGUAGE'))
			->where($qb->expr()->equal(new Field('host_id'), $hostId))
			->andWhere($qb->expr()->equal(new Field('lang_id'), $languageId));
		
		$sql->exec($qb->getSQL(), $cacheMinutes);
		if($sql->countRecords()){
			return $sql->fetchField("id");
		}
		throw new RuntimeException("No record with host_id ".$hostId." and lang_id ".$languageId." in ".Tbl::get('TBL_HOST_LANGUAGE'));
	}
	
	public static function getHostLanguageId(Host $host, Language $language, $cacheMinutes = null){
		return static::_getHostLanguageId($host->id,$language->id, $cacheMinutes);
	}
	/**
	 * Get all possible pairs of Host Language
	 *
	 * @return array 2D array key is host_language_id with "host" and "language" keys with values as corresponding objects 
	 */
	public static function getAllPairs($cacheMinutes = null){
		$pairs = array();
		$sql = MySqlDbManager::getQueryObject();
		
		$qb = new QueryBuilder();
		$qb->select(array(new Field('*'), new Field('id', 'hl', 'host_lang_id')))
			->from(Tbl::get('TBL_HOST_LANGUAGE'), 'hl')
			->leftJoin(Tbl::get("TBL_LANGUAGES", "Language"), 'l', $qb->expr()->equal(new Field('lang_id', 'hl'), new Field('id', 'l')))
			->leftJoin(Tbl::get("TBL_HOSTS", "Host"), 'h', $qb->expr()->equal(new Field('host_id', 'hl'), new Field('id', 'h')));
		
		$sql->exec($qb->getSQL(), $cacheMinutes);

		while (($row = $sql->fetchRecord()) != false) {			
			$host = new Host();
			$language = new Language();
			$row["id"] = $row["lang_id"];
			Language::setData($row,$language);
			$row["id"] = $row["host_id"];
			Host::setData($row,$host);
			$pairs[$row["host_lang_id"]]=array("host"=>$host,"language"=>$language);
		}		
		return $pairs;
	}
	
	public static function setHostsDefaultLanguage(Host $host, Language $language){
		$sql = MySqlDbManager::getQueryObject();
		
		$qb = new QueryBuilder();
		$qb->update(Tbl::get('TBL_HOST_LANGUAGE'))
			->set(new Field("default"), 0)
			->where($qb->expr()->equal(new Field('host_id'), $host->id));
		
		$sql->exec($qb->getSQL());
		
		
		$qb = new QueryBuilder();
		$qb->update(Tbl::get('TBL_HOST_LANGUAGE'))
			->set(new Field("default"), 1)
			->where($qb->expr()->equal(new Field('host_id'), $host->id))
			->andWhere($qb->expr()->equal(new Field('lang_id'), $language->id));
		
		$sql->exec($qb->getSQL());
	}
	
	public static function deleteHostsLanguage(Host $host, Language $language){
		$sql = MySqlDbManager::getQueryObject();
	
		$qb = new QueryBuilder();
		$qb->delete(Tbl::get('TBL_HOST_LANGUAGE'))
			->where($qb->expr()->equal(new Field('host_id'), $host->id))
			->andWhere($qb->expr()->equal(new Field('lang_id'), $language->id));
	
		$sql->exec($qb->getSQL());
	}
}
