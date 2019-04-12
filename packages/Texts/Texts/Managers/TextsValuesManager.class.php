<?php
class TextsValuesManager extends DbAccessor{
	
	const TBL_TEXTS_VALUES = "texts_values";
	
	private $host;
	private $language;
	
	public  function __construct(Host $host, Language $language, $instanceName = null){
		parent::__construct($instanceName);
		
		$this->host = $host;
		$this->language = $language;
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
		$qb = new QueryBuilder();
		$qb->select(new Field("*"))
			->from(Tbl::get('TBL_TEXTS_VALUES'))
			->where($qb->expr()->equal(new Field('id'), $textValueId));
		$this->query->exec($qb->getSQL(), $cacheMinutes);
		
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
		
		$qb = new QueryBuilder();
		$qb->select(new Field("*"))
			->from(Tbl::get('TBL_TEXTS_VALUES'))
			->where($qb->expr()->equal(new Field('text_id'), $text->id))
			->andWhere($qb->expr()->equal(new Field('host_language'), $hostLangId));
			
		$this->query->exec($qb->getSQL(), $cacheMinutes);
		
		if($this->query->countRecords() == 1){
			return $this->getTextValueObjectFromData($this->query->fetchRecord());
		}
		elseif(Reg::get(ConfigManager::getConfig("Texts")->Objects->TextsAliasManager)->isAliased($text, $hostLangId, $cacheMinutes)){
			$qbAlias = new QueryBuilder();
			$qbAlias->select(new Field("*", 'tv'))
					->from(Tbl::get('TBL_TEXTS_ALIASES', 'TextsAliasManager'), 'ta')
					->leftJoin(Tbl::get('TBL_TEXTS_VALUES'), 'tv', 
								$qbAlias->expr()->equal(new Field('id', 'tv'), new Field('value_id', 'ta')))
					->where($qbAlias->expr()->equal(new Field('host_language', 'ta'), $hostLangId))
					->andWhere($qbAlias->expr()->equal(new Field('text_id'), $text->id));
			$this->query->exec($qbAlias->getSQL(), $cacheMinutes);
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

		$sqlQuery = $filter->getSQL();
	
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
		
		$qb = new QueryBuilder();
		$qb->insert(Tbl::get('TBL_TEXTS_VALUES'))
			->values(array(
							"text_id" 		=> $textValue->text->id,
							"value"   		=> $textValue->value,
							"host_language" => $hostLanguageId,
							"display" 		=> $textValue->display
			));
			
		$this->query->exec($qb->getSQL());
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
		$qb = new QueryBuilder();
		$qb->update(Tbl::get('TBL_TEXTS_VALUES'))
			->set(new Field('text_id'), 		$textValue->text->id)
			->set(new Field('value'), 			$textValue->value)
			->set(new Field('host_language'), 	$hostLanguageId)
			->set(new Field('display'), 		$textValue->display)
			->set(new Field('text_id'), $textValue->text->id)
			->where($qb->expr()->equal(new Field('id'), $textValue->id));
		
		$this->query->exec($qb->getSQL());
		return $this->query->affected();
	}
	
	public function deleteTextValue(TextValue $textValue){
		if(empty($textValue->id)){
			throw new InvalidArgumentException("No ID specified in TextValue object");
		}
		if(!is_numeric($textValue->id)){
			throw new InvalidArgumentException("Text ID have to be integer");
		}
		$qb = new QueryBuilder();
		$qb->delete(Tbl::get('TBL_TEXTS_VALUES'))
			->where($qb->expr()->equal(new Field('id'), $textValue->id));
		$this->query->exec($qb->getSQL());
		
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
		$textValue->hostLanguageId = $data['host_language'];
		
		return $textValue;
	}
}
