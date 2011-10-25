<?
class TextsManager extends DbAccessor{
	
	const TBL_TEXTS_VALUES = "texts_values";
	const TBL_TEXTS_ALIASES = "texts_aliases";
	
	private $host;
	private $language;
	
	public  function __construct(Host $host, Language $language, $dbInstanceKey = null){
		parent::__construct($dbInstanceKey);
		
		$this->host = $host;
		$this->language = $language;
	}
	
	public function isAliased(Text $text, $hostLangId, $cacheMinutes = null){
		if(!is_numeric($text->id)){
			throw new InvalidArgumentException("Text ID have to be numeric");
		}
		if(!is_numeric($hostLangId)){
			throw new InvalidArgumentException("\$hostLangId have to be numeric");
		}
		$this->query->exec("SELECT tv.`id`
								FROM `".Tbl::get('TBL_TEXTS_ALIASES') ."` ta
									LEFT JOIN `".Tbl::get('TBL_TEXTS_VALUES') ."` tv ON ta.`value_id` = tv.`id`
								WHERE 	ta.`host_language` = '$hostLangId' AND 
										tv.`text_id` = '{$text->id}'", $cacheMinutes);
		if($this->query->countRecords() == 1){
			return true;
		}
		return false;
	}
	
	public function getTextValue($textName, $groupName, Host $host=null, Language $lang=null, $cacheMinutes = null){
		return $this->getText($textName, $groupName, $host, $lang, $cacheMinutes)->value;
	}
	
	public function getText($textName, $groupName, Host $host=null, Language $lang=null, $cacheMinutes = null){
		if($host === null){
			$host = $this->host;
		}
		if($lang === null){
			$lang = $this->language;
		}
		
		$text = Reg::get(ConfigManager::getConfig("Texts")->Objects->TextsManager)->getTextByName($textName, $groupName);
		$group = Reg::get(ConfigManager::getConfig("Texts")->Objects->TextsGroupManager)->getGroupByName($groupName);
		$hostLangId = HostLanguageManager::getHostLanguageId($host, $lang);
		
		$this->query->exec("SELECT * FROM `".Tbl::get('TBL_TEXTS_VALUES') ."`
								WHERE 	`text_id`  = '{$text->id}' AND 
										`host_language` = '$hostLangId''", $cacheMinutes);
		
		if($this->query->countRecords() == 1){
			return $this->getTextValueObjectFromData($this->query->fetchRecord());
		}
		elseif($this->isAliased($text, $hostLangId, $cacheMinutes)){
			$this->query->exec("SELECT `tv`.*
									FROM `".Tbl::get('TBL_TEXTS_ALIASES')."` `ta`
										LEFT JOIN `".Tbl::get('TBL_TEXTS_VALUES')."` `tv` ON `tv`.`id` = `ta`.`value_id` 
			 						WHERE 	`ta`.`host_language` = '$hostLangId' AND 
			 								`text_id` = '{$text->id}'", $cacheMinutes);
			return $this->getTextValueObjectFromData($this->query->fetchRecord());
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
}
?>