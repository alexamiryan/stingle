<?
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
	 * @param Language object
	 *
	 * @access public
	 * @return integer
	 */
	public function addLanguage(Language $language){
		if(empty($language->shortName) or empty($language->longName)){
			throw new EmptyArgumentException();
		}

		$this->query->exec("INSERT INTO `".Tbl::get("TBL_LANGUAGES", "Language") ."` (`name`, `description`)
							VALUES ('$language->shortName', '$language->longName')");

		return $this->query->getLastInsertId();
	}

	/**
	 * Deletes language by given ID or name
	 *
	 * @param Language object
	 *
	 * @access public
	 * @throws EmptyArgumentException, InvalidArgumentException
	 * @return boolean
	 */
	public function deleteLanguage(Language $language){
		$lang_id = $language->id;

		$this->query->exec("DELETE FROM `".Tbl::get("TBL_LANGUAGES", "Language") ."` WHERE `id`='$lang_id'");
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
	 * @param integer|string $language (can be ID or shortName)
	 *
	 * @access public
	 * @return boolean
	 */
	public function languageExists($language, $cacheMinutes = null){
		if(is_numeric($language)){
			$this->query->exec("SELECT `name` FROM `".Tbl::get("TBL_LANGUAGES", "Language") ."` WHERE `id`='$language'", $cacheMinutes);
			if($this->query->countRecords()){
				return true;
			}
			else{
				return false;
			}
		}
		else{
			$this->query->exec("SELECT `id` FROM `".Tbl::get("TBL_LANGUAGES", "Language") ."` WHERE `name`='$language'", $cacheMinutes);
			if($this->query->countRecords()){
				return true;
			}
			else{
				return false;
			}
		}
	}
	
	/**
	 * Returns assoc array with all constants of given language and types
	 * If $language is not given default language is taken
	 *
	 * @param integer|string $language Language ID or name
	 * @param array $types Type IDs
	 * @return array
	 */
	public function getAllConsts(array $types = null, $language = null, $cacheMinutes = null){
		return $this->getConstsList($types, $language, false, null, $cacheMinutes);
	}
	
	/**
	 * Generates array( array(key, values, lang_id) array from a database
	 * for given parameters.
	 *
	 * @param array $types Types of constants. All available types if empty
	 * @param $language Language. If language is not specified default language taken.
	 * @param boolean $existing_only Gets all available constants which are not defined in current language if the value is false.
	 * @param MysqlPager $pager
	 *
	 * @access public
	 * @return array
	 *
	 */
	public function getConstsList($types = null, $language = null, $existing_only = false, MysqlPager $pager = null, $cacheMinutes = null){

		if($language === null){
			$language = $this->language;
		}
		
		if(is_array($types)){
			$types_query = " AND lc.`type` IN (" . implode(",", $types) . ")";
		}
		elseif( !is_null($types) ){
			throw new RuntimeException("types should be an array");
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

		$query = "SELECT lc.`id`, lc.`key`, lc.`type`,
						 cv.`value`, cv.`lang_id`
					FROM `".Tbl::get("TBL_CONSTANTS", "Constant")."` lc
					JOIN `".Tbl::get("TBL_VALUES", "Constant")."` cv
					USING (`id`)
					WHERE cv.`lang_id`='$language->id'
					$types_query";

		if(!$existing_only){
			$query .= " UNION ( SELECT lc.`id`, lc.`key`, lc.`type`,
									 cv.value, cv.lang_id
						FROM `lm_constants` lc
						LEFT JOIN `lm_values` ncv
						ON (
							lc.`id` = ncv.`id`
							AND ncv.`lang_id`='$language->id'
						)
						LEFT JOIN `lm_values` cv
						ON (lc.`id` = cv.`id`)
						WHERE ncv.`id` IS NULL
						$types_query
						GROUP BY lc.`id`
						HAVING cv.`lang_id` = MIN(cv.`lang_id`) )";
		}

		$query .= " ORDER BY `id` DESC";

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
		$constants = $this->getAllConsts(null, $cacheMinutes);
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
		
		$this->query->exec("SELECT cv.`value`
							FROM `".Tbl::get("TBL_CONSTANTS", "Constant")."` lc
							JOIN `".Tbl::get("TBL_VALUES", "Constant") ."` cv
							USING (`id`)
							WHERE lc.`key`='$key'
							AND cv.`lang_id`='{$language->id}'", $cacheMinutes);

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
	 * @param integer|string $language Language ID or name
	 *
	 * @access public
	 * @return boolean
	 */
	public function setValueOf($key, $value, $language){
		if(empty($key)){
			throw new EmptyArgumentException();
		}
		$lang_id = $this->parseLanguage($language);

		$this->updateValuesRow(array("key" => $key, "lang_id" => $lang_id), $value);
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

		$this->query->exec("SELECT `id` FROM `".Tbl::get("TBL_CONSTANTS", "Constant")."` WHERE `key`='$key'", $cacheMinutes);
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
		$this->query->exec("SELECT COUNT(`id`) as cnt FROM `".Tbl::get("TBL_CONSTANTS", "Constant")."` WHERE `key`='$key'", $cacheMinutes);
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
	 * @param integer|string $language Language ID or name
	 *
	 * @access protected
	 * @return boolean
	 */
	public function valueExists($id, $language, $cacheMinutes = null){
		$lang_id = $this->parseLanguage($language);
		$this->query->exec("SELECT COUNT(*) AS value_exists
							FROM `".Tbl::get("TBL_VALUES", "Constant")."`
							WHERE `id`='$id'
							AND `lang_id`='$lang_id'", $cacheMinutes);

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
	 * @param integer|string $language Language ID or name
	 * @return boolean
	 */
	public function constantExists($key, $language, $cacheMinutes = null){
		$lang_id = $this->parseLanguage($language);
		if($this->keyExists($key)){
			$id = $this->getKeyId($key, $cacheMinutes);
			return $this->valueExists($id, $lang_id, $cacheMinutes);
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
		$this->query->exec("INSERT INTO `".Tbl::get("TBL_CONSTANTS", "Constant")."` (`key`, `type`) VALUES ('$key', '$type')");
		return $this->query->getLastInsertId();
	}

	/**
	 * Creates a value on given key id and language.
	 * <b>Note:</b> all protected methods must receive only
	 * integer language ID
	 *
	 * @param $key Key of given constant
	 * @param $value Contant's text value
	 * @param $lang_id Language ID
	 *
	 * @access protected
	 * @return boolean
	 */
	protected function createValue($key, $value, $lang_id){
		$this->query->exec("INSERT INTO `".Tbl::get("TBL_VALUES", "Constant")."` (`id`, `lang_id`, `value`)
							SELECT
								(SELECT `id` FROM `".Tbl::get("TBL_CONSTANTS", "Constant")."` WHERE `key` = '$key') as `id`,
								'$lang_id',
								'$value'");
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
		$this->query->exec("DELETE FROM `".Tbl::get("TBL_CONSTANTS", "Constant")."` WHERE `key`='$key'");
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
		$this->query->exec("SELECT `type`
							FROM `{".Tbl::get("TBL_CONSTANTS", "Constant")."}`
							WHERE `key`='$key'", $cacheMinutes);
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
	 * @param integer|string $language Constant's language name or lnguage ID
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

		$lang_id = $language->id;

		if(!$this->keyExists($key)){
			$id = $this->createKey($key, $type);
			$new_key_created = true;
		}

		try{
			$this->createValue($key, $value, $lang_id);
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
	 * @param array $identifier Array which identifies current value uniquely.
	 * 				Array must be like this:
	 * 				array(
	 * 					"key"		=> {string} <key>,
	 * 					"lang_id"	=> {integer} <language>
	 * 				)
	 * @param string $value Constant new text value
	 * @param integer $lang_id New language id for current row
	 * @return boolean True if successed.
	 */
	protected function updateValuesRow($identifier, $value = null, $lang_id = null){

		if(!isset($identifier["key"]) or !isset($identifier["lang_id"])){
			throw new InvalidArrayArgumentException();
		}

		if(!is_numeric($identifier["lang_id"])){
			throw new InvalidArrayArgumentException();
		}

		if($lang_id !== null and !is_numeric($lang_id)){
			throw new InvalidIntegerArgumentException();
		}

		// When nothing need to be changed
		if($value === null and $lang_id === null){
			return true;
		}

		$query = "UPDATE `".Tbl::get("TBL_VALUES", "Constant")."` SET";
		if($value !== null){
			$query .= " `value`='$value',";
		}
		if($lang_id !== null){
			$query .= " `lang_id`='$lang_id',";
		}
		// Removing last comma
		$query = substr($query, 0, -1);
		$query .= " WHERE `id`=(SELECT `id` FROM `".Tbl::get("TBL_CONSTANTS", "Constant")."` WHERE `key`='{$identifier["key"]}')
					AND `lang_id`='{$identifier["lang_id"]}'";

		$this->query->exec($query);
	}

	/**
	 * Changes row fields in the constants table.
	 *
	 * @param integer $old_key Key of constant, which needs to be changed.
	 * @param string $new_key Constant new key name
	 * @param integer $type New type id
	 * @return boolean True if successed.
	 */
	protected function updateConstantsRow($old_key, $new_key = null, $type = null){

		if(empty($old_key)){
			throw new InvalidIntegerArgumentException();
		}

		if($type !== null and !is_numeric($type)){
			throw new InvalidIntegerArgumentException();
		}

		// When nothing need to be changed
		if($new_key === null and $type === null){
			return true;
		}

		$query = "UPDATE `".Tbl::get("TBL_CONSTANTS", "Constant")."` SET";
		if($new_key !== null){
			$query .= " `key`='$new_key',";
		}
		if($type !== null){
			$query .= " `type`='$type',";
		}
		// Removing last comma
		$query = substr($query, 0, -1);
		$query .= " WHERE `key`='$old_key'";

		$this->query->exec($query);
	}

	/**
	 * Change constant in the database.
	 *
	 * @param array $identifier Array which identifies current constant uniquely.
	 * 				Array must be like this:
	 * 				array(
	 * 					"key"		=> {string} <key>,
	 * 					"language"	=> {integer|string} <language>
	 * 				)
	 * @param string $key New key
	 * @param string $value New value
	 * @param integer|string $language New language
	 * @param integer $type New type
	 *
	 * @access public
	 * @return boolean
	 *
	 * 		 transaction functions moved from MySqlDatabase to MySqlQuery
	 */
	public function updateConstant(array $identifier, $key = null, $value = null, $language = null, $type = null){

		if(!isset($identifier["key"]) or !isset($identifier["language"])){
			throw new InvalidArrayArgumentException();
		}
		
		$lang = $identifier["language"];
		$identifier["lang_id"] = $lang->id;
		
		//$identifier["lang_id"] = $this->parseLanguage($identifier["language"]);
		//$lang_id = $this->parseLanguage($language);
		
		$lang_id = $language;

		$this->query->exec("SELECT @@AUTOCOMMIT AS ac");

		if($this->query->fetchField("ac") == 1){
			$rollback_autocommit = true;
			$this->query->exec("SET AUTOCOMMIT = 0");
		}
		$this->query->exec("START TRANSACTION");

		try{
			$this->updateValuesRow($identifier, $value, $lang_id);
			$this->updateConstantsRow($identifier["key"], $key, $type);
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
	 * Remove constant passing ID or Key for current constant.
	 * If language is set then only value with current language
	 * will be deleted.
	 *
	 * @param array $identifier Array which identifies current constant uniquely
	 * 				It looks like this:
	 * 					array(
	 * 						"key" => {string},
	 * 						"language" => Language[optinal]
	 * 					)
	 * @access public
	 * @return boolean True if rows affected
	 */
	public function removeConstant($identifier){

		if(empty($identifier["key"])){
			throw new InvalidArrayArgumentException();
		}

		$tables = " `".Tbl::get("TBL_CONSTANTS", "Constant")."` lc
					JOIN `".Tbl::get("TBL_VALUES", "Constant")."` cv
					USING (`id`)";
		$where_clause = " WHERE `key`='{$identifier["key"]}'";


		if(isset($identifier["language"])){
			$lang = $identifier["language"];
			
			$this->query->exec("SELECT count(`id`) AS cnt
								FROM $tables $where_clause");

			if($this->query->fetchField("cnt") > 1){
				$this->query->exec("DELETE FROM cv
									USING $tables $where_clause
									AND cv.`lang_id`='{$lang->id}'");
			}
			else{
				$this->query->exec("DELETE FROM
										`".Tbl::get("TBL_CONSTANTS", "Constant")."`
									$where_clause");
			}
		}
		else{
			$this->query->exec("DELETE FROM
									`".Tbl::get("TBL_CONSTANTS", "Constant")."`
								$where_clause");
		}

		return ($this->query->affected() > 0 ? true : false);
	}




	/**
	 * Changes given offset and lengh to absolute values
	 * relative to full length
	 *
	 * @param integer $offset
	 * @param integer $length
	 * @param integer $full_length
	 * @return void
	 */
	private function absoluteOffsetLength(&$offset, &$length, $full_length){
		if($offset < 0){
			$offset = $full_length + $offset;
		}

		if($offset < 0){
			$offset = 0;
		}

		if($length == 0){
			$length = $full_length;
		}
		elseif($length < 0){
			$length = abs($length);
			$offset = $offset - $length;
		}
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
	 * @param array $types Type IDs
	 * @param integer|string $language Language ID or name
	 * @param string $additional_where Mysql where clause
	 * @return integer
	 *
	 */
	public function getConstsCount(array $types = null, $language = null,$additional_where = null, $cacheMinutes = null){

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
	 * @param array $types Type IDs array
	 * @param integer|string $language Language name or ID
	 * @param integer $offset Offset for fetching constants list
	 * @param integer $length Length of fetched constants list
	 * @param string $additional_where Mysql where clause
	 *
	 * @access public
	 * @return array
	 *
	 */
	public function search(
		array $types = null, $language = null,
		$offset = 0, $length = 0,
		$additional_where = null, $cacheMinutes = null
	){

		$query = "SELECT lc.`id`, lc.`key`, lc.`type`,
					 	cv.`value`, cv.`lang_id`
					FROM `".Tbl::get("TBL_CONSTANTS", "Constant")."` lc
					JOIN `".Tbl::get("TBL_VALUES", "Constant") ."` cv
					USING (`id`)";

		if($language !== null){
			$lang_id = $this->parseLanguage($language);
			$query .= " WHERE cv.`lang_id` = '$lang_id'";
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

		$consts_count = $this->getConstsCount($types, $lang_id, $additional_where, $cacheMinutes);
		if(!$consts_count){
			return array();
		}

		$query .= " ORDER BY `id` DESC";

		$this->absoluteOffsetLength($offset, $length, $consts_count);
		$query .= " LIMIT $offset, $length";

		if($this->query->exec($query, $cacheMinutes)){
			return $this->query->fetchRecords();
		}
		return array();
	}
}
?>