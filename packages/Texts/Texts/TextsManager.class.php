<?
class TextsManager extends DbAccessor{
	
	const TBL_TEXTS = "texts";
	
	public  function __construct($dbInstanceKey = null){
		parent::__construct($dbInstanceKey);
	}
	
	private function textNameExists($textName, $groupName, $cacheMinutes = null){
		if(empty($textName)){
			throw new InvalidArgumentException("\$textName have to be non empty");
		}
		if(empty($groupName)){
			throw new InvalidArgumentException("\$groupName have to be non empty");
		}
		
		$group = Reg::get(ConfigManager::getConfig("Texts")->Objects->TextsGroupManager)->getGroupByName($groupName, $cacheMinutes);
		
		$this->query->exec("SELECT count(*) as `count` 
								FROM `".Tbl::get('TBL_TEXTS') ."` 
								WHERE `name`='$textName' AND `group_id`='{$group->id}'", $cacheMinutes);
		
		if($this->query->fetchField("count") == 1){
			return true;
		}
		return false;
	}
	
	public function getTextsList($cacheMinutes = null){
		$this->query->exec("SELECT * FROM `".Tbl::get('TBL_TEXTS') . "`", $cacheMinutes);
		
		$texts = array();
		foreach($this->query->fetchRecords() as $data){
			array_push($texts, $this->getTextObjectFromData($data));
		}
		
		return $texts;
	}
	
	public function getTextById($textId, $cacheMinutes = null){
		if(empty($textId)){
			throw new InvalidArgumentException("\$textId have to be non empty");
		}
		if(!is_numeric($textId)){
			throw new InvalidArgumentException("\$textId have to be integer");
		}
		
		$this->query->exec("SELECT * FROM `".Tbl::get('TBL_TEXTS')  ."` 
								WHERE `id` = '{$textId}'", $cacheMinutes);
		
		if($this->query->countRecords() == 0){
			throw new RuntimeException("There is no text with id $textId");
		}
		
		return $this->getTextObjectFromData($this->query->fetchRecord(), $cacheMinutes);
	}
	
	public function getTextByName($textName, $groupName, $cacheMinutes = null){
		if(empty($textName)){
			throw new InvalidArgumentException("\$textName have to be non empty");
		}
		if(empty($groupName)){
			throw new InvalidArgumentException("\$groupName have to be non empty");
		}
		
		$group = Reg::get(ConfigManager::getConfig("Texts")->Objects->TextsGroupManager)->getGroupByName($groupName, $cacheMinutes);
		
		$this->query->exec("SELECT * FROM `".Tbl::get('TBL_TEXTS')  ."` 
								WHERE `name` = '{$textName}' AND `group_id`='{$group->id}'", $cacheMinutes);
		
		if($this->query->countRecords() == 0){
			throw new RuntimeException("There is no text with name $textName");
		}
		
		return $this->getTextObjectFromData($this->query->fetchRecord(), $cacheMinutes);
	}
	
	public function addText(Text $text, TextsGroup $group){
		if(empty($text->name)){
			throw new InvalidArgumentException("You have to specify name for new text");
		}
		if(empty($group->id)){
			throw new InvalidArgumentException("Group ID have to be specified");
		}
		if(!is_numeric($group->id)){
			throw new InvalidArgumentException("Group ID have to be integer");
		}
		$this->query->exec("INSERT INTO `".Tbl::get('TBL_TEXTS') . "` (`group_id`, `name`, `description`) 
								VALUES('{$group->id}', '{$text->name}', '{$text->description}')");
		return $this->query->affected();
	}
	
	public function updateText(Text $text){
		if(empty($text->id)){
			throw new InvalidArgumentException("Text ID have to be specified");
		}
		if(!is_numeric($text->id)){
			throw new InvalidArgumentException("Text ID have to be integer");
		}
		$this->query->exec("UPDATE `".Tbl::get('TBL_TEXTS') . "` SET 
								`group_id`='{$text->group->id}', 
								`name`='{$text->name}', 
								`description`='{$text->description}', 
							WHERE `id`='{$text->id}'");
		return $this->query->affected();
	}
	
	public function deleteText(Text $text){
		if(empty($text->id)){
			throw new InvalidArgumentException("Text ID have to be specified");
		}
		if(!is_numeric($text->id)){
			throw new InvalidArgumentException("Text ID have to be integer");
		}
		
		$this->query->exec("DELETE FROM `".Tbl::get('TBL_TEXTS') . "` WHERE `id`='{$text->id}'");
		
		return $this->query->affected();
	}
	
	
	protected function getTextObjectFromData($data, $cacheMinutes = null){
		$text = new Text();
		$text->id = $data['id'];
		$text->group = Reg::get(ConfigManager::getConfig("Texts")->Objects->TextsGroupManager)->getGroupById($data['group_id'], $cacheMinutes);
		$text->name = $data['name'];
		$text->description = $data['description'];
		
		return $text;
	}
}
?>