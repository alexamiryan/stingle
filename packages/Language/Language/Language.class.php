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
			$qb = new QueryBuilder();
			$qb->select(new Field('*'))
				->from(Tbl::get('TBL_LANGUAGES'))
				->where($qb->expr()->equal(new Field('id'), $lang_id));
			$this->query->exec($qb->getSQL(), $cacheMinutes);
			if($this->query->countRecords()){
				$data = $this->query->fetchRecord();
				static::setData($data, $this);
			}
			else{
				throw new RuntimeException("Wrong languge id is given. No record with id: $lang_id in table ".Tbl::get('TBL_LANGUAGES') );				
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
		$qb = new QueryBuilder();
		$qb->select(new Field('*'))
			->from(Tbl::get('TBL_LANGUAGES'))
			->where($qb->expr()->equal(new Field('name'), $shortName));
		$sql->exec($qb->getSQL(), $cacheMinutes);
		if($sql->countRecords()){
			$l = new Language();
			$data = $sql->fetchRecord();
			static::setData($data, $l);
			return $l;
		}
		throw new InvalidArgumentException("There is no language with such short name (".$shortName.")");	
	}
	
	public static function getAllLanguages($cacheMinutes = null){
		$languages = array();
		$sql = MySqlDbManager::getQueryObject();
		$qb = new QueryBuilder();
		$qb->select(new Field('*'))
			->from(Tbl::get('TBL_LANGUAGES'));
		$sql->exec($qb->getSQL(), $cacheMinutes);
		while(($lang_data = $sql->fetchRecord()) != false){
			$l = new Language();
			static::setData($lang_data, $l);
			$languages[] = $l;			
		}
		return $languages;
	}
	
}
?>