<?
class TextsAliasManager extends DbAccessor{
	
	const TBL_TEXTS_ALIASES = "texts_aliases";
	
	public  function __construct($dbInstanceKey = null){
		parent::__construct($dbInstanceKey);
	}
	
	public function isAliased(Text $text, $hostLangId, $cacheMinutes = null){
		if(!is_numeric($text->id)){
			throw new InvalidArgumentException("Text ID have to be numeric");
		}
		if(!is_numeric($hostLangId)){
			throw new InvalidArgumentException("\$hostLangId have to be numeric");
		}
		$qb = new QueryBuilder();
		$qb->select(new Field('id', 'tv'))
			->from(Tbl::get('TBL_TEXTS_ALIASES'), 'ta')
			->leftJoin(Tbl::get('TBL_TEXTS_VALUES', 'TextsValuesManager'), 'tv',
					$qb->expr()->equal(new Field('value_id', 'ta'), new Field('id', 'tv')))
			->where($qb->expr()->equal(new Field('host_language', 'ta'), $hostLangId))
			->andWhere($qb->expr()->equal(new Field('text_id', 'tv'), $text->id));
		$this->query->exec($qb->getSQL(), $cacheMinutes);
		if($this->query->countRecords() == 1){
			return true;
		}
		return false;
	}
	
	public function getAliases(TextValue $textValue, $cacheMinutes = null){
		if(!is_numeric($textValue->id)){
			throw new InvalidArgumentException("TextValue ID have to be numeric");
		}
		$qb = new QueryBuilder();
		$qb->select(new Field('*'))
			->from(Tbl::get('TBL_TEXTS_ALIASES'))
			->where($qb->expr()->equal(new Field('value_id'), $textValue->id));
		$this->query->exec($qb->getSQL(), $cacheMinutes);
		
		$arrayToReturn = array();
		foreach($this->query->fetchRecords() as $data){
			array_push($arrayToReturn, $this->getTextAliasObjectFromData($data));
		}
		
		return $arrayToReturn;
	}
	
	public function addAlias(TextAlias $alias){
		if(empty($alias->textValue) or !is_a($alias->textValue, "TextValue")){
			throw new InvalidArgumentException("You have to specify valid TextValue object");
		}
		
		if(!empty($alias->hostLanguageId) and is_numeric($alias->hostLanguageId)){
			$hostLanguageId = $alias->hostLanguageId;
		}
		else{
			if(empty($alias->host) or !is_a($alias->host, "Host")){
				throw new InvalidArgumentException("You have to specify valid Host object");
			}
			if(empty($alias->language) or !is_a($alias->language, "Language")){
				throw new InvalidArgumentException("You have to specify valid Language object");
			}
			
			$hostLanguageId = HostLanguageManager::getHostLanguageId($alias->host, $alias->language);
		}
		$qb = new QueryBuilder();
		$qb->insert(Tbl::get('TBL_TEXTS_ALIASES'))
			->values(array(
							"value_id" 		=> $alias->textValue->id, 
							"host_language" => $hostLanguageId 
						)
					);	
		$this->query->exec($qb->getSQL());
		return $this->query->affected();
	}
	
	public function deleteAlias(TextAlias $alias){
		if(empty($alias->id)){
			throw new InvalidArgumentException("Alias ID have to be specified");
		}
		if(!is_numeric($alias->id)){
			throw new InvalidArgumentException("Alias ID have to be integer");
		}
		$qb = new QueryBuilder();
		$qb->delete(Tbl::get('TBL_TEXTS_ALIASES'))
			->where($qb->expr()->equal(new Field("id"), $alias->id));
		$this->query->exec($qb->getSQL());
		return $this->query->affected();
	}
	
	public function deleteAllAliasesForTextValue(TextValue $textValue){
		if(empty($textValue->id)){
			throw new InvalidArgumentException("Text Value ID have to be specified");
		}
		if(!is_numeric($textValue->id)){
			throw new InvalidArgumentException("Text Value ID have to be integer");
		}
		$qb = new QueryBuilder();
		$qb->delete(Tbl::get('TBL_TEXTS_ALIASES'))
			->where($qb->expr()->equal(new Field("value_id"), $textValue->id));
		$this->query->exec($qb->getSQL());
		return $this->query->affected();
	}
	
	protected function getTextAliasObjectFromData($data, $cacheMinutes = null){
		$textAlias = new TextAlias();
		$hostLanguagePair = HostLanguageManager::getHostLanguagePair($data['host_language'], $cacheMinutes);
		
		$textAlias->id = $data['id'];
		$textAlias->textValue = Reg::get(ConfigManager::getConfig("Texts")->Objects->TextsValuesManager)->getTextValueById($data['value_id'], $cacheMinutes);
		$textAlias->language = $hostLanguagePair['language'];
		$textAlias->host = $hostLanguagePair['host'];
		$textAlias->hostLanguageId = $data['host_language'];
		
		return $textAlias;
	}
}
?>