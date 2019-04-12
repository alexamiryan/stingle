<?php
class TextsGroupManager extends DbAccessor{
	
	const TBL_TEXTS_GROUPS = "texts_groups";
	
	public  function __construct($instanceName = null){
		parent::__construct($instanceName);
	}
	
	
	public function getGroupByName($groupName, $cacheMinutes = null){
		if(empty($groupName)){
			throw new InvalidArgumentException("\$groupName have to be non empty string");
		}
		$qb = new QueryBuilder();
		$qb->select(new Field('*'))
			->from(Tbl::get('TBL_TEXTS_GROUPS'))
			->where($qb->expr()->equal(new Field('name'), $groupName));
		
		$this->query->exec($qb->getSQL(), $cacheMinutes);
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
		$qb = new QueryBuilder();
		$qb->select(new Field('*'))
			->from(Tbl::get('TBL_TEXTS_GROUPS'))
			->where($qb->expr()->equal(new Field('id'), $groupId));
		$this->query->exec($qb->getSQL(), $cacheMinutes);
		
		if($this->query->countRecords() == 0){
			throw new RuntimeException("There is no texts group with id $groupId");
		}
		
		return $this->getGroupObjectFromData($this->query->fetchRecord());
	}
	
	public function getGroupsList($cacheMinutes = null){
		$qb = new QueryBuilder();
		$qb->select(new Field('*'))
			->from(Tbl::get('TBL_TEXTS_GROUPS'));
		$this->query->exec($qb->getSQL(), $cacheMinutes);
		
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
		$qb = new QueryBuilder();
		$qb->insert(Tbl::get('TBL_TEXTS_GROUPS'))
			->values(array(	"name" => $group->name));	
		$this->query->exec($qb->getSQL());
		return $this->query->affected();
	}
	
	public function updateGroup(TextsGroup $group){
		if(empty($group->id)){
			throw new InvalidArgumentException("Group ID have to be specified");
		}
		if(!is_numeric($group->id)){
			throw new InvalidArgumentException("Group ID have to be integer");
		}
		$qb = new QueryBuilder();
		$qb->update(Tbl::get('TBL_TEXTS_GROUPS'))
			->set(new Field('name'), $group->name)
			->where($qb->expr()->equal(new Field('id'), $group->id));
		$this->query->exec($qb->getSQL());
		return $this->query->affected();
	}
	
	public function deleteGroup(TextsGroup $group){
		if(empty($group->id)){
			throw new InvalidArgumentException("Group ID have to be specified");
		}
		if(!is_numeric($group->id)){
			throw new InvalidArgumentException("Group ID have to be integer");
		}
		$qb = new QueryBuilder();
		$qb->delete(Tbl::get('TBL_TEXTS_GROUPS'))
			->where($qb->expr()->equal(new Field("id"), $group->id));	
		$this->query->exec($qb->getSQL());
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
