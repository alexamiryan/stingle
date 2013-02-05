<?php
class LanguageManager extends DbAccessor {

	protected $language; //Language object

	public function __construct(Language $language=null, $dbInstanceKey = null){

		parent::__construct($dbInstanceKey);
		
		if($language === null){
			$shortName = false;
			if (isset($_GET['language']) && $this->languageExists($_GET['language'])) {
				$shortName = $_GET['language'];
			} elseif (isset($_SESSION['language']) && $this->languageExists($_SESSION['language'])) {
				$shortName = $_SESSION['language'];
			} elseif (isset($_COOKIE['language']) && $this->languageExists($_COOKIE['language'])) {
				$shortName = $_COOKIE['language'];
			}

			if($shortName !== false){
				$language = Language::getLanguage($shortName);
			}
			else{
				$language = $this->getDefaultLanguage();
			}
		}
		if($language instanceof Language){
			$this->language = $language;
			$this->setLanguage($language);
		}
		else{
			throw new InvalidArgumentException("Argument should be instanse of Language");
		}
	}

	/**
	 * Add new language to database.
	 * Returns ID of the new language.
	 *
	 * @param Language $language
	 *
	 * @access public
	 * @return integer
	 */
	public function addLanguage(Language $language){
		if(empty($language->shortName) or empty($language->longName)){
			throw new EmptyArgumentException();
		}

		$qb = new QueryBuilder();
		$qb->insert(Tbl::get("TBL_LANGUAGES", "Language"))
			->values(array(
					'name' => $language->shortName,
					'description' => $language->longName
					));
		
		$this->query->exec($qb->getSQL());

		return $this->query->getLastInsertId();
	}
	
	/**
	 * Update given Language
	 * @param Language $language
	 * @throws EmptyArgumentException
	 * @throws InvalidArgumentException
	 */
	public function updateLanguage(Language $language){
		if(empty($language->shortName) or empty($language->longName)){
			throw new EmptyArgumentException();
		}
		if(!is_numeric($language->id)){
			throw new InvalidArgumentException("Language id is not numeric.");
		}
		$qb = new QueryBuilder();
		$qb->update(Tbl::get("TBL_LANGUAGES", "Language"))
			->set(new Field('name'), 		$language->shortName)
			->set(new Field('description'), $language->longName)
			->where($qb->expr()->equal(new Field('id'), $language->id));
		
		$this->query->exec($qb->getSQL());

		return $this->query->affected();
	}

	/**
	 * Deletes language by given ID or name
	 *
	 * @param Language $language
	 *
	 * @access public
	 * @throws EmptyArgumentException, InvalidArgumentException
	 * @return boolean
	 */
	public function deleteLanguage(Language $language){
		$qb = new QueryBuilder();
		$qb->delete(Tbl::get("TBL_LANGUAGES", "Language"))
			->where($qb->expr()->equal(new Field('id'), $language->id));
		$this->query->exec($qb->getSQL());
	}

	public function getLanguage(){
		return $this->language;
	}

	/**
	 * Check and Set $_SESSION['language'] and $_COOKIE['language']
	 *
	 * @param Language $language
	 */
	public function setLanguage(Language $language){
		if (!isset($_SESSION['language']) || $_SESSION['language'] != $language->shortName) {
			$_SESSION['language'] = $language->shortName;
		}

		if (!isset($_COOKIE['language']) || $_COOKIE['language'] != $language->shortName) {
			setcookie('language', $language->shortName, time() + 60 * 60 * 24 * 30, '/', $_SERVER['HTTP_HOST']);
		}
	}

	public function getDefaultLanguage($cacheMinutes = null){
		return new Language(1); // This returns first (with id 1) language from table.
	}

	/**
	 * Returns flag, wheter language with given id or name exists
	 *
	 * @param string $language_short_name
	 *
	 * @access public
	 * @return boolean
	 */
	public function languageExists($language_short_name, $cacheMinutes = null){
		$qb = new QueryBuilder();
		$qb->select($qb->expr()->count("*", 'cnt'))
			->from(Tbl::get("TBL_LANGUAGES", "Language"))
			->where($qb->expr()->equal(new Field('name'), $language_short_name));
		
		$this->query->exec($qb->getSQL(), $cacheMinutes);
		if($this->query->fetchField('cnt') > 0){
			return true;
		}
		else{
			return false;
		}
	}
	
	/**
	 * Returns assoc array with all constants of given language and types
	 * If $language is not given default language is taken
	 *
	 * @param array $types Type IDs
	 * @param Language $language
	 * @return array
	 */
	public function getAllConsts(array $types = null, Language $language = null, $cacheMinutes = null){
		return $this->getConstsList($types, $language, false, null, $cacheMinutes);
	}
	
	/**
	 * Generates array( array(key, values, lang_id) array from a database
	 * for given parameters.
	 *
	 * @param array $types Types of constants. All available types if empty
	 * @param Language $language
	 * @param boolean $existing_only Gets all available constants which are not defined in current language if the value is false.
	 * @param MysqlPager $pager
	 *
	 * @access public
	 * @return array
	 *
	 */
	public function getConstsList($types = null, Language $language = null, $existing_only = false, MysqlPager $pager = null, $cacheMinutes = null){

		if($language === null){
			$language = $this->language;
		}
		
		if($types != null and !is_array($types)){
			throw new RuntimeException("\$types should be an array");
		}

		if($existing_only === true){
			$consts_count = $this->getConstsCount($types, $language->id, null, $cacheMinutes);
		}
		else{
			$consts_count = $this->getConstsCount($types, null,null, $cacheMinutes);
		}

		if(!$consts_count){
			return array();
		}

		$qb = new QueryBuilder();
		$qb->select(array(
				new Field('id', 'lc'),
				new Field('key', 'lc'),
				new Field('type', 'lc'),
				new Field('value', 'cv'),
				new Field('lang_id', 'cv')
				))
			->from(Tbl::get("TBL_CONSTANTS", "Constant"), 'lc')
			->leftJoin(Tbl::get("TBL_VALUES", "Constant"), 'cv', $qb->expr()->equal(new Field('id', 'lc'), new Field('id', 'cv')))
			->where($qb->expr()->equal(new Field('lang_id', 'cv'), $language->id));
		
		if(is_array($types) and !empty($types)){
			$qb->andWhere($qb->expr()->in(new Field('type', 'lc'), $types));
		}
		
		if(!$existing_only){
			$qb1 = new QueryBuilder();
			
			$qb1->select(array(
					new Field('id', 'lc'),
					new Field('key', 'lc'),
					new Field('type', 'lc'),
					new Field('value', 'cv'),
					new Field('lang_id', 'cv')
				))
				->from(Tbl::get("TBL_CONSTANTS", "Constant"), 'lc');
			
			$joinAnd = new Andx();
			$joinAnd->add($qb->expr()->equal(new Field('id', 'lc'), new Field('id', 'ncv')));
			$joinAnd->add($qb->expr()->equal(new Field('lang_id', 'ncv'), $language->id));
			
			$qb1->leftJoin(Tbl::get("TBL_VALUES", "Constant"), 'ncv', $joinAnd)
				->leftJoin(Tbl::get("TBL_VALUES", "Constant"), 'cv', $qb->expr()->equal(new Field('id', 'lc'), new Field('id', 'cv')))
				->where($qb->expr()->isNull(new Field('id', 'ncv')));
			
			if(is_array($types) and !empty($types)){
				$qb1->andWhere($qb->expr()->in(new Field('type', 'lc'), $types));
			}
			
			$qb1->groupBy(new Field('id', 'lc'))
				->having($qb->expr()->equal(new Field('lang_id', 'cv'), $qb->expr()->min(new Field('lang_id', 'cv'))));
			
			$qbUnion = new QueryBuilder();
			
			$union = new Unionx();
			$union->add($qb);
			$union->add($qb1);
			
			$qbUnion->select(new Field("*"))
				->from($union, 'tbl');
			
			$qb = $qbUnion;
		}

		$qb->orderBy(new Field('id'), OrderBy::DESC);
		
		$query = $qb->getSQL();
		
		if($pager !== null){
			return $pager->getRecords($query, $cacheMinutes);
		}
		else{
			$this->query->exec($query, $cacheMinutes);
			return $this->query->fetchRecords();
		}
	}

	/**
	 * Define all constants of current language and types
	 *
	 * @access public
	 * @return boolean
	 */
	public function defineAllConsts($cacheMinutes = null){
		$constants = $this->getAllConsts(null, null, $cacheMinutes);
		if(count($constants)){
			foreach($constants as $const){
				if(!defined($const['key'])){
					define($const['key'], $const['value']);
				}
			}
		}
	}



	/**
	 * Returns value of given constant in given language.
	 * If it does not exist function will try to return
	 * default language value for that constant
	 *
	 * @param string $key Key name of constant
	 * @param Language $language
	 *
	 * @access public
	 * @return string
	 */
	public function getValueOf($key, Language $language = null, $cacheMinutes = null){
		if($language === null){
			$language = $this->language;
		}
		
		$qb = new QueryBuilder();
		$qb->select(new Field('value', 'cv'))
			->from(Tbl::get("TBL_CONSTANTS", "Constant"), 'lc')
			->leftJoin(Tbl::get("TBL_VALUES", "Constant"), 'cv', $qb->expr()->equal(new Field('id', 'lc'), new Field('id', 'cv')))
			->where($qb->expr()->equal(new Field('key', 'lc'), $key))
			->andWhere($qb->expr()->equal(new Field('lang_id', 'cv'), $language->id));
		
		$this->query->exec($qb->getSQL(), $cacheMinutes);

		if($this->query->countRecords()){
			return $this->query->fetchField('value');
		}
		else{
			$default_lang = $this->getDefaultLanguage($cacheMinutes);
			if($language->id != $default_lang->id){
				return $this->getValueOf($key, $default_lang , $cacheMinutes);
			}
		}		
		if(Debug::getMode()){
			throw new RuntimeException("'$key'"." doesn't exists for given language and even for default language.");
		}
		return "_#_"; // return this sign if I have nothink to return.
	}

	/**
	 * Set value of the given constant in given language.
	 *
	 * @param string $key Constant key
	 * @param string $value Constant new value
	 * @param Language $language Language ID or name
	 *
	 * @access public
	 * @return boolean
	 */
	public function setValueOf($key, $value, Language $language){
		if(empty($key)){
			throw new InvalidArgumentException("\$key have to be non empty string.");
		}

		$this->updateValuesRow($key, $language, $value);
	}

	/**
	 * Returns id of a given key or false
	 * if it not exists
	 *
	 * @param $key
	 *
	 * @access protected
	 * @return mixed
	 */
	public function getKeyId($key, $cacheMinutes = null){
		if(!$this->keyExists($key)){
			throw new InvalidArgumentException("Specified key does not exist.");
		}

		$qb = new QueryBuilder();
		$qb->select(new Field('id'))
			->from(Tbl::get("TBL_CONSTANTS", "Constant"))
			->where($qb->expr()->equal(new Field('key'), $key));
		
		$this->query->exec($qb->getSQL(), $cacheMinutes);
		return $this->query->fetchField("id");
	}

	/**
	 * Returns true if given key exists
	 *
	 * @param $key Constant's key name
	 *
	 * @access protected
	 * @return boolean
	 */
	public function keyExists($key, $cacheMinutes = null){
		$qb = new QueryBuilder();
		$qb->select($qb->expr()->count(new Field('id'), 'cnt'))
			->from(Tbl::get("TBL_CONSTANTS", "Constant"))
			->where($qb->expr()->equal(new Field('key'), $key));
		
		$this->query->exec($qb->getSQL(), $cacheMinutes);
		if($this->query->fetchField("cnt")){
			return true;
		}
		else{
			return false;
		}
	}

	/**
	 * Retruns true if constant with given language exists.
	 *
	 * @param integer $id Constant ID
	 * @param Language $language Language ID or name
	 *
	 * @access protected
	 * @return boolean
	 */
	public function valueExists($id, Language $language, $cacheMinutes = null){
		$qb = new QueryBuilder();
		$qb->select($qb->expr()->count('*', 'value'))
			->from(Tbl::get("TBL_VALUES", "Constant"))
			->where($qb->expr()->equal(new Field('id'), $id))
			->andWhere($qb->expr()->equal(new Field('lang_id'), $language->id));
		
		$this->query->exec($qb->getSQL(), $cacheMinutes);

		if($this->query->fetchField('value_exists')){
			return true;
		}
		else{
			return false;
		}
	}

	/**
	 * Returns true if constant with given parameters exists.
	 *
	 * @param string $key Key name of constant
	 * @param Language $language Language ID or name
	 * @return boolean
	 */
	public function constantExists($key, Language $language, $cacheMinutes = null){
		if($this->keyExists($key)){
			$id = $this->getKeyId($key, $cacheMinutes);
			return $this->valueExists($id, $language, $cacheMinutes);
		}
		else{
			return false;
		}
	}

	/**
	 * Creates a key row in constants table.
	 *
	 * @param $key Key name for new constant
	 * @param $type Type ID
	 *
	 * @access protected
	 * @return integer Key ID
	 */
	protected function createKey($key, $type){
		$qb = new QueryBuilder();
		$qb->insert(Tbl::get("TBL_CONSTANTS", "Constant"))
			->values(array(
					'key' => $key,
					'type' => $type
					));
		
		$this->query->exec($qb->getSQL());
		return $this->query->getLastInsertId();
	}

	/**
	 * Creates a value on given key id and language.
	 * <b>Note:</b> all protected methods must receive only
	 * integer language ID
	 *
	 * @param $key Key of given constant
	 * @param $value Contant's text value
	 * @param Language $language
	 *
	 * @access protected
	 * @return boolean
	 */
	protected function createValue($key, $value, Language $language){
		$qb = new QueryBuilder();
		$qb->select(new Field('id'), Expr::quoteLiteral($language->id), Expr::quoteLiteral($value))
			->from(Tbl::get("TBL_CONSTANTS", "Constant"))
			->where($qb->expr()->equal(new Field('key'), $key));
		
		$insertQb = new QueryBuilder();
		$insertQb->insert(Tbl::get("TBL_VALUES", "Constant"))
			->fields(array('id', 'lang_id', 'value'))
			->values($qb);
		
		$this->query->exec($insertQb->getSQL());
	}

	/**
	 * Removes current language constant passing $id
	 *
	 * @param $key Key of language constant
	 *
	 * @access protected
	 * @return void
	 */
	protected function removeKey($key){
		$qb = new QueryBuilder();
		$qb->delete(Tbl::get("TBL_CONSTANTS", "Constant"))
			->where($qb->expr()->equal(new Field('key'), $key));
		
		$this->query->exec($qb->getSQL());
	}

	/**
	 * Returns type content of passed key
	 *
	 * @param string $key
	 */
	public function getConstantType($key, $cacheMinutes = null){
		if(!$this->keyExists($key)){
			throw new InvalidArgumentException("Passed key doesn't exist");
		}
		
		$qb = new QueryBuilder();
		$qb->select(new Field('type'))
			->from(Tbl::get("TBL_CONSTANTS", "Constant"))
			->where($qb->expr()->equal(new Field('key'), $key));
		
		$this->query->exec($qb->getSQL(), $cacheMinutes);
		return $this->query->fetchField("type");
	}

	/**
	 * Add constant to database. If kay exists function will try
	 * to add new value for new given language if it not exist.
	 * If $language is not given default language is taken
	 *
	 * @param string $key Key name for constant
	 * @param string $value Constant value for given language
	 * @param integer $type Type ID
	 * @param Language $language Constant's language name or lnguage ID
	 *
	 * @access public
	 * @return boolean
	 */
	public function addConstant($key, $value, $type, Language $language = null){
		if(empty($key) or empty($type)){
			throw new EmptyArgumentException();
		}

		if($language === null){
			$language = $this->getDefaultLanguage();
		}

		if(!$this->keyExists($key)){
			$id = $this->createKey($key, $type);
			$new_key_created = true;
		}

		try{
			$this->createValue($key, $value, $language);
		}
		catch(MySqlException $mysql_exception){
			if($new_key_created === true){
				$this->removeKey($key);
			}
			throw $mysql_exception;
		}
	}


	/**
	 * Changes row fields in the values table.
	 * <b>Note:</b> all protected methods must receive only
	 * integer language ID
	 *
	 * @param string $existing_key
	 * @param Language $existing_language
	 * @param string $new_value
	 * @param Language $new_language
	 * @param string $new_value
	 * @return boolean True if successed.
	 */
	protected function updateValuesRow($existing_key, Language $existing_language, $new_value = null, Language $new_language = null){
		if(empty($existing_key)){
			throw new InvalidArgumentException("\$existing_key have to be non empty string.");
		}
		
		if($new_value === null and $new_language === null){
			return true;
		}
		
		$qb = new QueryBuilder();
		$qb->update(Tbl::get("TBL_VALUES", "Constant"));
		
		if($new_value !== null){
			$qb->set(new Field('value'), $new_value);
		}
		if($new_language !== null){
			$qb->set(new Field('lang_id'), $new_language->id);
		}

		$innerSelect = new QueryBuilder();
		$innerSelect->select(new Field('id'))
			->from(Tbl::get("TBL_CONSTANTS", "Constant"))
			->where($qb->expr()->equal(new Field('key'), $existing_key));
		
		$qb->where($qb->expr()->equal(new Field('id'), $innerSelect))
			->andWhere($qb->expr()->equal(new Field('lang_id'), $existing_language->id));
		
		$this->query->exec($qb->getSQL());
		return true;
	}

	/**
	 * Changes row fields in the constants table.
	 *
	 * @param string $existing_key
	 * @param string $new_key
	 * @param integer $type
	 * @return boolean True if successed.
	 */
	protected function updateConstantsRow($existing_key, $new_key = null, $new_type = null){

		if(empty($existing_key)){
			throw new InvalidArgumentException("\$old_key have to be non empty string.");
		}

		if($new_type !== null and !is_numeric($new_type)){
			throw new InvalidArgumentException("\$type have to be integer.");
		}

		// When nothing need to be changed
		if($new_key === null and $new_type === null){
			return true;
		}

		$qb = new QueryBuilder();
		$qb->update(Tbl::get("TBL_CONSTANTS", "Constant"));
		
		
		$query = "UPDATE `".Tbl::get("TBL_CONSTANTS", "Constant")."` SET";
		if($new_key !== null){
			$qb->set(new Field('key'), $new_key);
		}
		if($new_type !== null){
			$qb->set(new Field('type'), $new_type);
		}
		$qb->where($qb->expr()->equal(new Field('key'), $existing_key));

		$this->query->exec($qb->getSQL());
		return true;
	}

	/**
	 * Change constant in the database.
	 *
	 * @param string $existing_key
	 * @param Language $existing_language
	 * @param string $new_key
	 * @param string $new_value
	 * @param Language $new_language
	 * @param integer $new_type
	 *
	 * @access public
	 * @return boolean
	 *
	 * 		 transaction functions moved from MySqlDatabase to MySqlQuery
	 */
	public function updateConstant($existing_key, Language $existing_language, $new_key = null, $new_value = null, Language $new_language = null, $new_type = null){

		if(empty($existing_key)){
			throw new InvalidArgumentException("\$existing_key have to be non empty string.");
		}
		
		if(empty($existing_language)){
			throw new InvalidArgumentException("\$existing_language have to be non empty string.");
		}

		$this->query->exec("SELECT @@AUTOCOMMIT AS ac");

		if($this->query->fetchField("ac") == 1){
			$rollback_autocommit = true;
			$this->query->exec("SET AUTOCOMMIT = 0");
		}
		$this->query->exec("START TRANSACTION");

		try{
			$this->updateValuesRow($existing_key, $existing_language, $new_value, $new_language);
			$this->updateConstantsRow($existing_key, $new_key, $new_type);
		}
		catch (MySqlException $e){
			$this->query->exec("ROLLBACK");
			if(isset($rollback_autocommit)){
				$this->query->exec("SET AUTOCOMMIT = 1");
			}
			return false;
		}
		$this->query->exec("COMMIT");
		if(isset($rollback_autocommit)){
			$this->query->exec("SET AUTOCOMMIT = 1");
		}
		return true;
	}
	/**
	 * Remove constant passing Key for current constant.
	 *
	 * @param string $key
	 * 
	 * @access public
	 * @return boolean True if rows affected
	 */
	public function removeConstant($key){
		if(empty($key)){
			throw new InvalidArgumentException("\$key have to be non empty string.");
		}

		$qb = new QueryBuilder();
		$qb->delete(Tbl::get("TBL_CONSTANTS", "Constant"))
			->where($qb->expr()->equal(new Field('key'), $key));
		
		$this->query->exec($qb->getSQL());
		
		return ($this->query->affected() > 0 ? true : false);
	}
	
	/**
	 * Removes constant value. If it was last value for given 
	 * constant it will delete constant itself
	 * 
	 * @param strung $key
	 * @param Language $language
	 * @throws InvalidArgumentException
	 * @return boolean
	 */
	public function removeConstantValue($key, Language $language){
		if(empty($key)){
			throw new InvalidArgumentException("\$key have to be non empty string.");
		}
	
		$constantId = $this->getKeyId($key);
		
		$qb = new QueryBuilder();
		$qb->delete(Tbl::get("TBL_VALUES", "Constant"))
			->where($qb->expr()->equal(new Field('id'), $constantId))
			->andWhere($qb->expr()->equal(new Field('lang_id'), $language->id));
	
		$this->query->exec($qb->getSQL());
		
		$deletedCount = $this->query->affected();
		
		$qb = new QueryBuilder();
		$qb->select($qb->expr()->count("*", 'count'))
			->from(Tbl::get("TBL_VALUES", "Constant"))
			->where($qb->expr()->equal(new Field('id'), $constantId)); 
		
		$valuesCount = $this->query->exec($qb->getSQL())->fetchField('count');
		
		if($valuesCount == 0){
			$this->removeConstant($key);
		}
	
		return ($deletedCount > 0 ? true : false);
	}

	/**
	 * Returns count of constants filtered by function parametes
	 * and additional where mysql clause.
	 *
	 * <b>Note:</b> for counting results for function
	 * get_consts_list with $existing_only = false
	 * just don't pass $language param, because
	 * $existing_only = false means that get_consts_list
	 * will return all constants with their availible values
	 *
	 * @param array $types
	 * @param Language $language
	 * @param string $additional_where
	 * @return integer
	 *
	 */
	public function getConstsCount(array $types = null, Language $language = null, $additional_where = null, $cacheMinutes = null){

		$query = "SELECT COUNT(*) as `count`
					FROM `".Tbl::get("TBL_CONSTANTS", "Constant") ."` lc
					JOIN `".Tbl::get("TBL_VALUES", "Constant") ."` cv
					USING (`id`)";

		if($language !== null){
			$query .= "	WHERE cv.`lang_id` = '$language->id'";
		}

		if($types !== null){
			if($language !== null){
				$query .= " AND lc.`type` IN (" . implode(",", $types) . ")";
			}
			else{
				$query .= " WHERE lc.`type` IN (" . implode(",", $types) . ")";
			}
		}

		if($additional_where !== null){
			if($types !== null or $language !== null){
				$query .= " AND ( $additional_where )";
			}
			else{
				$query .= " WHERE $additional_where";
			}
		}

		$this->query->exec($query, $cacheMinutes);
		return $this->query->fetchField("count");
	}

	/**
	 * Searches through constants and values tables.
	 *
	 * Difference beetween search function and get_constants_list is the language parameter
	 * and additional where parameter which allows to write mysql where statement for searching.
	 * In get_constants_list if language is not specified then default language taken.
	 *
	 * @param array $types
	 * @param Language $language
	 * @param MysqlPager $pager
	 * @param string $additional_where Mysql where clause
	 *
	 * @access public
	 * @return array
	 *
	 */
	public function search(array $types = null, Language $language = null, MysqlPager $pager = null, $additional_where = null, $cacheMinutes = null){
		$query = "SELECT lc.`id`, lc.`key`, lc.`type`,
					 	cv.`value`, cv.`lang_id`
					FROM `".Tbl::get("TBL_CONSTANTS", "Constant")."` lc
					JOIN `".Tbl::get("TBL_VALUES", "Constant") ."` cv
					USING (`id`)";

		if($language !== null){
			$query .= " WHERE cv.`lang_id` = '{$language->id}'";
		}

		if($types !== null){
			if($language !== null){
				$query .= " AND lc.`type` IN (" . implode(",", $types) . ")";
			}
			else{
				$query .= " WHERE lc.`type` IN (" . implode(",", $types) . ")";
			}
		}

		if($additional_where !== null){
			if($types !== null or $language !== null){
				$query .= " AND ( $additional_where )";
			}
			else{
				$query .= " WHERE $additional_where";
			}
		}

		$query .= " ORDER BY `id` DESC";

		if($pager !== null){
			$this->query = $pager->executePagedSQL($query, $cacheMinutes);
		}
		else{
			$this->query->exec($query, $cacheMinutes);
		}
		
		return $this->query->fetchRecords();
	}
}
