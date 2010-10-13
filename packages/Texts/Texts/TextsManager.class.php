<?
/**
 * Texts manager class.
 * @name Texts
 */
class  TextsManager extends DbAccessor{

	const EMPTY_TEXT_FLAG = "[@empty@]";

	private $host; 		//Host object
	private $language; //Language object

	public  function __construct(Host $host, Language $language, $dbInstanceKey = null){
		parent::__construct($dbInstanceKey);

		$this->host = $host;
		$this->language = $language;
	}


	/**
	 * Get value of the text. If not set, takes default|current host and lang.
	 *
	 * @param string $textName
	 * @param string[optional] $host_ext
	 * @param string[optional] $lang
	 * @return string
	 */
	public function getTextValue($textName, Host $host=NULL, Language $lang=NULL){
		if($this->textNameExists($textName)){
			if($host === null){
				$host = $this->host;
			}
			if($lang === null){
				$lang = $this->language;
			}
			$text_id = Text::getTextId($textName);

			$textValue = $this->getTextVal($text_id, $lang->id, $host->id); //Given host Language text value
			if(!empty($textValue)){
				return $textValue;
			}
			else{
				$host_lang_id = HostLanguageManager::getHostLanguageId($host, $lang);
				if($this->isAliased($text_id, $host_lang_id)){
					return $this->getAlisedTextVal($text_id,$host_lang_id);
				}
			}
			//return $this->getDefaultText($text_id); //Given text default value
			return "";
		}
		throw new InvalidArgumentException("No text with name '".$textName."'");
	}

	public function getDisplayProperty($textName, Host $host=NULL, Language $lang=NULL){
		if($this->textNameExists($textName)){
			if($host === null){
				$host = $this->host;
			}
			if($lang === null){
				$lang = $this->language;
			}
			$text_id = Text::getTextId($textName);

			if($this->getDisplay($text_id,$lang->id, $host->id)==1){
				return "yes";
			}
			return "no";
		}
		throw new InvalidArgumentException("No text with name '".$textName."'");
	}

	private function getDisplay($text_id, $lang_id, $host_id, $cacheMinutes = 0){
		if(!is_numeric($text_id) or !is_numeric($lang_id) or !is_numeric($host_id) ){
			throw new InvalidIntegerArgumentException("Method arguments must be integer!!!");
		}
		$this->query->exec("SELECT `display` FROM ".Tbl::get('TBL_TEXTS_VALUES', 'Text') ." tv
							LEFT JOIN `".Tbl::get('TBL_HOST_LANGUAGE', 'HostLanguageManager') ."` hl ON hl.`id`=tv.`host_language` 
							WHERE `text_id`  = $text_id AND hl.`host_id` = $host_id AND hl.`lang_id` = $lang_id",$cacheMinutes);
		if($this->query->countRecords()){
			return $this->query->fetchField("display");
		}
		else{
			return 1;
		}
	}

	public function insertPostText($post){
		$text_id = $post["text_id"];
		$host_language_id = $post["hl_id"];
		$value = stripcslashes($post["text"]);
		$this->insertTextValue($text_id,$host_language_id,$value);
	}

	public function setDisplay($text_id, $host_language_id,$display){
		$this->query->exec("INSERT INTO `".Tbl::get('TBL_TEXTS_VALUES', 'Text') ."` (`text_id`,`host_language`,`display`)
							VALUES ('$text_id','$host_language_id','$display') 
							ON DUPLICATE KEY UPDATE `display`='$display'");
	}

	public function isAliased($text_id, $host_lang_id){
		if(!is_numeric($text_id) or !is_numeric($host_lang_id)){
			throw new InvalidArgumentException("No numeric arguments given");
		}
		$this->query->exec("SELECT tv.id FROM `".Tbl::get('TBL_TEXTS_VALUES', 'Text') ."` tv
							RIGHT JOIN ".Tbl::get('TBL_TEXTS_ALIASES', 'Text') ." ta ON tv.id = ta.value_id
							WHERE ta.host_language = '$host_lang_id' AND tv.text_id = '$text_id'");
		if($this->query->countRecords() == 1){
			return true;
		}
		return false;


	}
	public function addAliases($value_id, $hl_ids){
		if(!is_numeric($value_id)){
			throw new InvalidArgumentException("value_id argument mast be integer");
		}
		$this->query->exec("DELETE FROM `". Tbl::get('TBL_TEXTS_ALIASES', 'Text') ."` WHERE value_id=".$value_id);
		if(!empty($hl_ids)){
			if(!is_array($hl_ids)){
				throw new InvalidArgumentException("hl_ids mast be an array");
			}
			foreach ($hl_ids as $hl_id){
				$values[]= "('$value_id','".intval($hl_id)."')";
			}
			$this->query->exec("INSERT INTO `". Tbl::get('TBL_TEXTS_ALIASES', 'Text') ."` (value_id, host_language)
							VALUES ".implode(", ",$values)."");
		}
	}
	
	public function getTextValueId($text_id, $host_language_id){
		if(!is_numeric($text_id) or !is_numeric($host_language_id)){
			throw new InvalidIntegerArgumentException("text id and host_language_id should be an integer. text_id: ". $text_id ." and host_lang_id:".$host_language_id." given.");
		}
		$this->query->exec("SELECT id FROM `".Tbl::get('TBL_TEXTS_VALUES', 'Text') ."` WHERE `text_id` = {$text_id} AND `host_language`={$host_language_id}");
		return $this->query->fetchField("id");
	}

	private function insertTextValue($text_id, $host_language_id, $value){
		if(!is_numeric($text_id) or !is_numeric($host_language_id)){
			throw new InvalidIntegerArgumentException("text id and host_language_id should be an integer. text_id: ". $text_id ." and host_lang_id:".$host_language_id." given.");
		}
		$this->query->exec("INSERT INTO `".Tbl::get('TBL_TEXTS_VALUES', 'Text') ."` (`text_id`,`value`,`host_language`)
							VALUES ('$text_id','".mysql_real_escape_string($value)."','$host_language_id') 
							ON DUPLICATE KEY UPDATE `value`='".mysql_real_escape_string($value)."'");
	}

	public function deleteVal($val_id){
		if(!is_numeric($val_id)){
			throw new InvalidIntegerArgumentException("value id should be an integer. ". $val_id ." given");
		}
		$this->query->exec("DELETE FROM `".Tbl::get('TBL_TEXTS_VALUES', 'Text') ."` WHERE id=".$val_id);
	}



	/**
	 * Given host all values. (for texts manager)
	 *
	 * @param unknown_type $textName
	 * @param Host $host
	 * @param unknown_type $cacheMinutes
	 * @return unknown
	 */
	public function getHostTextValues($textName, Host $host, $cacheMinutes = 0){
		if($this->textNameExists($textName)){
			$values = array();
			
			$text_id = Text::getTextId($textName);

			$this->query->exec("SELECT tv.*, tv.id value_id, hl.lang_id, hl.id unic_id  FROM `".Tbl::get('TBL_HOST_LANGUAGE', 'HostLanguageManager') ."` hl
							LEFT JOIN `".Tbl::get('TBL_TEXTS_VALUES', 'Text') ."` tv ON (hl.`id`=tv.`host_language` AND tv.text_id=$text_id)
							WHERE  hl.host_id = ".$host->id,$cacheMinutes);
			while (($rec = $this->query->fetchRecord()) != false) {
				if(empty($rec["value"])){
					$rec["value"] = static::EMPTY_TEXT_FLAG ;
				}
				$values[$rec["lang_id"]] = array("hl_id"=>$rec["unic_id"], "value"=>$rec["value"],
				"value_id"=>$rec["value_id"],"default"=>$rec["default"]);
			}
			return $values;

		}
		throw new InvalidArgumentException("No text with name '".$textName."'");
	}
	
	private function getAlisedTextVal($text_id, $host_lang_id, $cacheMinutes = 0){
		if(!is_numeric($text_id) or !is_numeric($host_lang_id)){
			throw new InvalidIntegerArgumentException("Method arguments must be integer!!!");
		}
		$this->query->exec("SELECT tv.`value` FROM `".Tbl::get('TBL_TEXTS_ALIASES', 'TEXT')."` ta
					LEFT JOIN `".Tbl::get('TBL_TEXTS_VALUES', 'TEXT')."` tv ON tv.id = ta.value_id 
					 WHERE ta.`host_language` = $host_lang_id 
					AND text_id = $text_id");
		return $this->query->fetchField("value");
	}

	private  function getTextVal($text_id, $lang_id, $host_id, $cacheMinutes = 0){
		if(!is_numeric($text_id) or !is_numeric($lang_id) or !is_numeric($host_id) ){
			throw new InvalidIntegerArgumentException("Method arguments must be integer!!!");
		}
		$this->query->exec("SELECT `value` FROM ".Tbl::get('TBL_TEXTS_VALUES', 'Text') ." tv
							LEFT JOIN `".Tbl::get('TBL_HOST_LANGUAGE', 'HostLanguageManager') ."` hl ON hl.`id`=tv.`host_language` 
							WHERE `text_id`  = $text_id AND hl.`host_id` = $host_id AND hl.`lang_id` = $lang_id",$cacheMinutes);
		if($this->query->countRecords()){
			return $this->query->fetchField("value");
		}
	}

	/*private function getDefaultText($text_id){
		if(!is_numeric($text_id)){
			throw new InvalidIntegerArgumentException("text_id argument must be integer.");
		}
		$this->query->exec("SELECT `value` FROM `".Tbl::get('TBL_TEXTS_VALUES', 'Text') ."` WHERE `text_id` = {$text_id} AND `default`=1");
		if($this->query->countRecords()){
			return $this->query->fetchField("value");
		}
		if($debug_mode){
			throw new InvalidArgumentException("Nor value nor alias setted for text with id ".$text_id." (text_id:".$text_id.") for this host/language.");
		}
		return "_~#~_"; // return this sign if I have nothink to return.

	}*/


	private function textNameExists($textName){
		if($this->query->exec("SELECT count(*) as `count` FROM `".Tbl::get('TBL_TEXTS', 'Text') ."` WHERE `name`='$textName'")){
			if($this->query->fetchField("count") == 1){
				return true;
			}
		}
		return false;
	}
}
?>