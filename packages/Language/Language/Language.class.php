<?
class Language extends DbAccessor{
	public $id; 		// int
	public $shortName; 	// string
	public $longName; 	// string

	const TBL_LANGUAGES = "lm_languages";

	public function __construct($lang_id=null, $cacheMinutes = null){
		parent::__construct();
		if($lang_id !== null){
			if(!is_numeric($lang_id)){
				throw new InvalidIntegerArgumentException("lang_id argument should be an integer.");
			}
			$this->query->exec("SELECT * FROM `".self::TBL_LANGUAGES ."` WHERE `id` = '{$lang_id}'", $cacheMinutes);
			if($this->query->countRecords()){
				$data = $this->query->fetchRecord();
				self::setData($data, $this);
			}
			else{
				throw new RuntimeException("Wrong languge id is given. No record with id: $lang_id in table ".self::TBL_LANGUAGES );				
			}
			
		}
	}
	
	/**
	 * set Object members from Database data
	 *
	 * @param array Db query result $data
	 * @return void
	 */
	public static function setData($data, Language $object){
		$object->id = $data["id"];
		$object->shortName = $data["name"];
		$object->longName = $data["description"];
		return ;
	}
	/**
	 * Get language by short name
	 *
	 * @param string $shortName
	 * @throws EmptyArgumentException, InvalidArgumentException
	 * @return Language object
	 */
	public static function getLanguage($shortName, $cacheMinutes = null){
		if(empty($shortName)){
			throw new EmptyArgumentException("Empty shortName argument.");
		}
		$sql = MySqlDbManager::getQueryObject();
		$sql->exec("SELECT * FROM `".self::TBL_LANGUAGES ."` WHERE `name` = '{$shortName}'", $cacheMinutes);
		if($sql->countRecords()){
			$l = new Language();
			$data = $sql->fetchRecord();
			self::setData($data, $l);
			return $l;
		}
		throw new InvalidArgumentException("There is no language with such short name (".$shortName.")");	
	}
	
	public static function getAllLanguages($cacheMinutes = null){
		$languages = array();
		$sql = MySqlDbManager::getQueryObject();
		$sql->exec("SELECT * FROM `".self::TBL_LANGUAGES ."`", $cacheMinutes);
		while($lang_data = $sql->fetchRecord()){
			$l = new Language();
			self::setData($lang_data, $l);
			$languages[] = $l;			
		}
		return $languages;
	}
	
}
?>