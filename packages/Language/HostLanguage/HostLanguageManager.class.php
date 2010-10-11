<?
class HostLanguageManager extends LanguageManager {

	protected $host;//Host Object
	
	protected $hostLangs = array(); //Set of Languages of fiven Host

	const TBL_HOST_LANGUAGE = "host_language";

	public function __construct(Host $host){
		$this->host = $host;
		parent::__construct();
		$this->hostLangs = $this->getHostLanguages();
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
		$this->query->exec("SELECT l.`id` FROM `".Language::TBL_LANGUAGES ."` l
							LEFT JOIN `".self::TBL_HOST_LANGUAGE ."` hl ON hl.lang_id = l.id
							WHERE hl.host_id=".$this->host->id."
							AND l.`name`='$language'", $cacheMinutes);
		if($this->query->countRecords()){
			return true;
		}
		else{
			return false;
		}

	}

	public function getDefaultLanguage($cacheMinutes = null){
		$this->query->exec("SELECT l.* FROM `".self::TBL_HOST_LANGUAGE."` hl
					LEFT JOIN `".Language::TBL_LANGUAGES ."` l ON hl.lang_id=l.id
					WHERE hl.host_id=".$this->host->id." AND hl.default=1", $cacheMinutes);
		if($this->query->countRecords()){
			$lang_data = $this->query->fetchRecord();
			$l = new Language();
			Language::setData($lang_data,$l);
			return $l;
		}
		throw new RuntimeException("Default language not defined for '". $this->host->domain.".".$this->host->extension."'");
	}
	
	public function Languages(){
		return $this->hostLangs;
	}


	/**
	 * Get possible languages of given host
	 *
	 * @param Host $host
	 * @return array set of Language objects
	 */
	protected function getHostLanguages($cacheMinutes = null){
		$languages = array();
		$this->query->exec("SELECT l.* FROM `".self::TBL_HOST_LANGUAGE."` hl
					LEFT JOIN `".Language::TBL_LANGUAGES ."` l ON hl.lang_id=l.id
					WHERE hl.host_id=".$this->host->id."", $cacheMinutes);
		$langs_data = $this->query->fetchRecords();
		foreach ($langs_data as $lang_data){
			$lang = new Language();
			Language::setData($lang_data, $lang);
			$languages[]=$lang;
		}
		return $languages;
	}
	
	public static function getLanguageHosts(Language $lang, $cacheMinutes = null){
		$hosts = array();
		$sql = MySqlDbManager::getQueryObject();
		$sql->exec("SELECT h.* FROM `".self::TBL_HOST_LANGUAGE."` hl
					LEFT JOIN `".Host::TBL_HOSTS."` h ON hl.host_id=h.id
					WHERE hl.lang_id=".$lang->id."", $cacheMinutes);
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
	public static function getHostLanguagePair($host_languge_id, $cacheMinutes = null){
		if(!is_numeric($host_languge_id)){
			throw new InvalidArgumentException("host_languge id should be an integer");
		}
		$sql = MySqlDbManager::getQueryObject();
		$sql->exec("SELECT host_id, lang_id FROM ".self::TBL_HOST_LANGUAGE ." WHERE id='$host_languge_id'", $cacheMinutes);
		if($sql->countRecords()){
			$res = $sql->fetchRecord();
			$host = new Host($res['host_id']);		
			$lang = new Language($res['lang_id']);	
			return array("host"=>$host, "language"=>$lang);	
		}
		throw new InvalidArgumentException("Wrong host_languge id given. No record with id: $host_languge_id in table ".self::TBL_HOST_LANGUAGE );				
	}
	
	private static function _getHostLanguageId($hostId, $languageId, $cacheMinutes = null){
		$sql = MySqlDbManager::getQueryObject();
		$sql->exec("SELECT id FROM ".self::TBL_HOST_LANGUAGE ." WHERE host_id=".$hostId." AND lang_id=".$languageId, $cacheMinutes);
		if($sql->countRecords()){
			return $sql->fetchField("id");
		}
		throw new RuntimeException("No record with host_id ".$hostId." and lang_id ".$languageId." in ".self::TBL_HOST_LANGUAGE);
	}
	
	public static function getHostLanguageId(Host $host, Language $language){
		return self::_getHostLanguageId($host->id,$language->id);
	}
	/**
	 * Get all possible pairs of Host Language
	 *
	 * @return array 2D array key is host_language_id with "host" and "language" keys with values as corresponding objects 
	 */
	public static function getAllPairs($cacheMinutes = null){
		$pairs = array();
		$sql = MySqlDbManager::getQueryObject();
		$sql->exec("SELECT *, hl.id host_lang_id FROM ".self::TBL_HOST_LANGUAGE ." hl
					LEFT JOIN `".Language::TBL_LANGUAGES ."` l ON hl.lang_id=l.id
					LEFT JOIN `".Host::TBL_HOSTS."` h ON hl.host_id=h.id", $cacheMinutes);

		while ($row = $sql->fetchRecord()) {			
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
}
?>