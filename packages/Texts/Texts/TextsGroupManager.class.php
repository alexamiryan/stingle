<?
class TextsGroupManager extends DbAccessor{
	
	const TBL_TEXTS_GROUPS = "texts_groups";
	
	public  function __construct($dbInstanceKey = null){
		parent::__construct($dbInstanceKey);
	}
	
	
	public function getGroupByName($groupName, $cacheMinutes = null){
		if(empty($groupName)){
			throw new InvalidArgumentException("\$groupName have to be non empty string");
		}
		
		$this->query->exec("SELECT *
								FROM `".Tbl::get('TBL_TEXTS_GROUPS') ."`
								WHERE 	`name`  = '$groupName'", $cacheMinutes);
		if($this->query->countRecords() == 0){
			throw new RuntimeException("There is no texts group with name $groupName");
		}
		
		return $this->getGroupObjectFromData($this->query->fetchRecord());
	}
	
	public function getGroupById($groupId, $cacheMinutes = null){
		if(empty($groupId)){
			throw new InvalidArgumentException("\$groupId have to be non empty");
		}
		if(!is_numeric($groupId)){
			throw new InvalidArgumentException("\$groupId have to be integer");
		}
		
		$this->query->exec("SELECT *
								FROM `".Tbl::get('TBL_TEXTS_GROUPS') ."`
								WHERE 	`id`  = '$groupId'", $cacheMinutes);
		
		if($this->query->countRecords() == 0){
			throw new RuntimeException("There is no texts group with id $groupId");
		}
		
		return $this->getGroupObjectFromData($this->query->fetchRecord());
	}
	
	public function getGroupsList($cacheMinutes = null){
		$this->query->exec("SELECT * FROM `".Tbl::get('TBL_TEXTS_GROUPS') . "`", $cacheMinutes);
		
		$groups = array();
		foreach($this->query->fetchRecords() as $data){
			array_push($groups,$this->getGroupObjectFromData($data));
		}
		
		return $groups;
	}
	
	public function addGroup(TextsGroup $group){
		if(empty($group->name)){
			throw new InvalidArgumentException("You have to specify name for new group");
		}
		$this->query->exec("INSERT INTO `".Tbl::get('TBL_TEXTS_GROUPS') . "` (`name`) VALUES('{$group->name}')");
		return $this->query->affected();
	}
	
	public function updateGroup(TextsGroup $group){
		if(empty($group->id)){
			throw new InvalidArgumentException("Group ID have to be specified");
		}
		if(!is_numeric($group->id)){
			throw new InvalidArgumentException("Group ID have to be integer");
		}
		$this->query->exec("UPDATE `".Tbl::get('TBL_TEXTS_GROUPS') . "` SET `name`='{$group->name}' WHERE `id`='{$group->id}'");
		return $this->query->affected();
	}
	
	public function deleteGroup(TextsGroup $group){
		if(empty($group->id)){
			throw new InvalidArgumentException("Group ID have to be specified");
		}
		if(!is_numeric($group->id)){
			throw new InvalidArgumentException("Group ID have to be integer");
		}
		$this->query->exec("DELETE FROM `".Tbl::get('TBL_TEXTS_GROUPS') . "` WHERE `id`='{$group->id}'");
		return $this->query->affected();
	}
	
	protected function getGroupObjectFromData($data){
		if(empty($data)){
			return null;
		}
		
		$group = new TextsGroup();
		$group->id = $data['id'];
		$group->name = $data['name'];
		
		return $group;
	}
}
?>