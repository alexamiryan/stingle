<?
class TextsValuesManager extends Filterable{
	
	const TBL_TEXTS_VALUES = "texts_values";
	
	const FILTER_ID_FIELD = "id";
	const FILTER_TEXT_ID_FIELD = "text_id";
	const FILTER_HOST_LANGUAGE_FIELD = "host_language";
	const FILTER_DISPLAY_FIELD = "display";
	
	private $host;
	private $language;
	
	public  function __construct(Host $host, Language $language, $dbInstanceKey = null){
		parent::__construct($dbInstanceKey);
		
		$this->host = $host;
		$this->language = $language;
	}
	
	protected function getFilterableFieldAlias($field){
		switch($field){
			case static::FILTER_ID_FIELD :
			case static::FILTER_TEXT_ID_FIELD :
			case static::FILTER_HOST_LANGUAGE_FIELD :
			case static::FILTER_DISPLAY_FIELD :
				return "texts_vals";
		}

		throw new RuntimeException("Specified field does not exist or not filterable");
	}
	
	public function getTextValue($textName, $groupName, Host $host=null, Language $lang=null, $cacheMinutes = null){
		$textValue = $this->getText($textName, $groupName, $host, $lang, $cacheMinutes);
		if($textValue !== false){
			return $textValue->value;
		}
		else{
			if(Debug::getMode()){
				return "_~#~_";
			}
			else{
				return "";
			}
		}
	}
	
	public function getTextValueById($textValueId, $cacheMinutes = null){
		if(empty($textValueId)){
			throw new InvalidArgumentException("\$textValueId have to be non empty");
		}
		if(!is_numeric($textValueId)){
			throw new InvalidArgumentException("\$textValueId have to be integer");
		}
		
		$this->query->exec("SELECT * FROM `".Tbl::get('TBL_TEXTS_VALUES') ."` WHERE 	`id`  = '$textValueId'", $cacheMinutes);
		
		if($this->query->countRecords() == 0){
			throw new RuntimeException("There is no texts value with id $textValueId");
		}
		
		return $this->getTextValueObjectFromData($this->query->fetchRecord());
	}
	
	public function getText($textName, $groupName, Host $host=null, Language $lang=null, $cacheMinutes = null){
		if($host === null){
			$host = $this->host;
		}
		if($lang === null){
			$lang = $this->language;
		}
		
		$text = Reg::get(ConfigManager::getConfig("Texts")->Objects->TextsManager)->getTextByName($textName, $groupName);
		$hostLangId = HostLanguageManager::getHostLanguageId($host, $lang);
		
		$this->query->exec("SELECT * FROM `".Tbl::get('TBL_TEXTS_VALUES') ."`
								WHERE 	`text_id`  = '{$text->id}' AND 
										`host_language` = '$hostLangId'", $cacheMinutes);
		
		if($this->query->countRecords() == 1){
			return $this->getTextValueObjectFromData($this->query->fetchRecord());
		}
		elseif(Reg::get(ConfigManager::getConfig("Texts")->Objects->TextsAliasManager)->isAliased($text, $hostLangId, $cacheMinutes)){
			$this->query->exec("SELECT `tv`.*
									FROM `".Tbl::get('TBL_TEXTS_ALIASES', 'TextsAliasManager')."` `ta`
										LEFT JOIN `".Tbl::get('TBL_TEXTS_VALUES')."` `tv` ON `tv`.`id` = `ta`.`value_id` 
			 						WHERE 	`ta`.`host_language` = '$hostLangId' AND 
			 								`text_id` = '{$text->id}'", $cacheMinutes);
			return $this->getTextValueObjectFromData($this->query->fetchRecord());
		}
		else{
			return false;
		}
	}
	
	public function getTexts(TextsValuesFilter $filter = null, MysqlPager $pager = null, $cacheMinutes = null){
		$texts = array();

		if($filter == null){
			$filter = new TextsValuesFilter();
		}

		$sqlQuery = "SELECT * FROM `".Tbl::get('TBL_TEXTS_VALUES')."` `texts_vals`
						{$this->generateJoins($filter)}
						WHERE 1
						{$this->generateWhere($filter)}
						{$this->generateOrder($filter)}
						{$this->generateLimits($filter)}";
	
		if($pager !== null){
			$this->query = $pager->executePagedSQL($sqlQuery, $cacheMinutes);
		}
		else{
			$this->query->exec($sqlQuery, $cacheMinutes);
		}
		if($this->query->countRecords()){
			foreach($this->query->fetchRecords() as $data){
				array_push($texts, $this->getTextValueObjectFromData($data, $cacheMinutes));
			}
		}

		return $texts;
	}
	
	public function addTextValue(TextValue $textValue){
		if(empty($textValue->text) or !is_a($textValue->text, "Text")){
			throw new InvalidArgumentException("You have to specify valid Text object for adding TextValue");
		}
		if(empty($textValue->value)){
			throw new InvalidArgumentException("You have to specify Value attribute for adding TextValue");
		}
		if(is_null($textValue->display) or !is_numeric($textValue->display)){
			throw new InvalidArgumentException("You have to specify valid Display attribute for adding TextValue");
		}
		
		if(!empty($textValue->hostLanguageId) and is_numeric($textValue->hostLanguageId)){
			$hostLanguageId = $textValue->hostLanguageId;
		}
		else{
			if(empty($textValue->host) or !is_a($textValue->host, "Host")){
				throw new InvalidArgumentException("You have to specify valid Host object");
			}
			if(empty($textValue->language) or !is_a($textValue->language, "Language")){
				throw new InvalidArgumentException("You have to specify valid Language object");
			}
			$hostLanguageId = HostLanguageManager::getHostLanguageId($textValue->host, $textValue->language);
		}
		
		
		$this->query->exec("INSERT INTO `".Tbl::get('TBL_TEXTS_VALUES') . "` (`text_id`, `value`, `host_language`, `display`) 
								VALUES('{$textValue->text->id}', '{$textValue->value}', '$hostLanguageId', '{$textValue->display}')");
		return $this->query->affected();
	}
	
	public function updateTextValue(TextValue $textValue){
		if(empty($textValue->id) or !is_numeric($textValue->id)){
			throw new InvalidArgumentException("No ID specified in TextValue object");
		}
		if(empty($textValue->text) or !is_a($textValue->text, "Text")){
			throw new InvalidArgumentException("You have to specify valid Text object");
		}
		if(empty($textValue->value)){
			throw new InvalidArgumentException("You have to specify Value attribute");
		}
		
		if(is_null($textValue->display) or !is_numeric($textValue->display)){
			throw new InvalidArgumentException("You have to specify valid Display attribute");
		}
		
		if(!empty($textValue->hostLanguageId) and is_numeric($textValue->hostLanguageId)){
			$hostLanguageId = $textValue->hostLanguageId;
		}
		else{
			if(empty($textValue->host) or !is_a($textValue->host, "Host")){
				throw new InvalidArgumentException("You have to specify valid Host object");
			}
			if(empty($textValue->language) or !is_a($textValue->language, "Language")){
				throw new InvalidArgumentException("You have to specify valid Language object");
			}
			$hostLanguageId = HostLanguageManager::getHostLanguageId($textValue->host, $textValue->language);
		}
		
		$this->query->exec("UPDATE `".Tbl::get('TBL_TEXTS_VALUES') . "` SET 
								`text_id`='{$textValue->text->id}', 
								`value`='{$textValue->value}', 
								`host_language`='$hostLanguageId', 
								`display`='{$textValue->display}'
							WHERE `id`='{$textValue->id}'");
		return $this->query->affected();
	}
	
	public function deleteTextValue(TextValue $textValue){
		if(empty($textValue->id)){
			throw new InvalidArgumentException("No ID specified in TextValue object");
		}
		if(!is_numeric($textValue->id)){
			throw new InvalidArgumentException("Text ID have to be integer");
		}
		
		$this->query->exec("DELETE FROM `".Tbl::get('TBL_TEXTS_VALUES') . "` WHERE `id`='{$textValue->id}'");
		
		return $this->query->affected();
	}
	
	protected function getTextValueObjectFromData($data, $cacheMinutes = null){
		$textValue = new TextValue();
		$hostLanguagePair = HostLanguageManager::getHostLanguagePair($data['host_language'], $cacheMinutes);
		
		$textValue->id = $data['id'];
		$textValue->text = Reg::get(ConfigManager::getConfig("Texts")->Objects->TextsManager)->getTextById($data['text_id'], $cacheMinutes);
		$textValue->value = $data['value'];
		$textValue->language = $hostLanguagePair['language'];
		$textValue->host = $hostLanguagePair['host'];
		$textValue->display = $data['display'];
		
		return $textValue;
	}
	
	protected function getTextAliasObjectFromData($data, $cacheMinutes = null){
		$textAlias = new TextAlias();
		$hostLanguagePair = HostLanguageManager::getHostLanguagePair($data['host_language'], $cacheMinutes);
		
		$textAlias->id = $data['id'];
		$textAlias->textValue = $this->getTextValueById($data['value_id'], $cacheMinutes);
		$textAlias->value = $data['value'];
		$textAlias->language = $hostLanguagePair['language'];
		$textAlias->host = $hostLanguagePair['host'];
		$textAlias->hostLanguageId = $data['host_language'];
		
		return $textAlias;
	}
}
?>