<?
class UserGroupsManager extends DbAccessor{
	
	public function createGroup(UserGroup $group){
		if(empty($group->name)){
			throw new InvalidArgumentException("Group name have to be non empty string");
		}
		
		$qb = new QueryBuilder();
		
		$qb->insert(Tbl::get('TBL_GROUPS', 'UserManager'))
			->values(array(
					'name' => $group->name,
					'description' => $group->description
					));
		
		return $this->query->exec($qb->getSQL())->getLastInsertId();
	}
	
	public function editGroup(UserGroup $group){
		if(empty($group->id) or !is_numeric($group->id)){
			throw new InvalidArgumentException("Group id have to be non empty string");
		}
		if(empty($group->name)){
			throw new InvalidArgumentException("Group name have to be non empty string");
		}
		
		$qb = new QueryBuilder();
		
		$qb->update(Tbl::get('TBL_GROUPS', 'UserManager'))
			->set('name', $group->name)
			->set('description', $group->description)
			->where($qb->expr()->equal(new Field('id'), $group->id));
		
		return $this->query->exec($qb->getSQL())->affected();
	}
	
	public function deleteGroup(UserGroup $group){
		if(empty($group->id) or !is_numeric($group->id)){
			throw new InvalidArgumentException("Group id have to be non empty string");
		}
	
		$qb = new QueryBuilder();
	
		$qb->delete(Tbl::get('TBL_GROUPS', 'UserManager'))
			->where($qb->expr()->equal(new Field('id'), $group->id));
	
		return $this->query->exec($qb->getSQL())->affected();
	}
	
	public function getGroups(UserGroupsFilter $filter = null, $cacheMinutes = 0){
		if($filter === null){
			$filter = new UserGroupsFilter();
		}
		
		$this->query->exec($filter->getSQL(), $cacheMinutes);
		
		$groups = array();
		if($this->query->countRecords()){
			foreach($this->query->fetchRecords() as $row){
				array_push($groups, $this->getGroupObjectFromData($row));
			}
		}
		
		return $groups;
	}
	
	public function getGroup(UserGroupsFilter $filter = null, $cacheMinutes = 0){
		$groups = $this->getGroups($filter, $cacheMinutes);
		if(count($groups) !== 1){
			throw new UserNotUniqueException("There is no such group or group is not unique.");
		}
		return $groups[0];
	}
	
	public function getGroupByName($name, $cacheMinutes = 0){
		if(empty($name)){
			throw new InvalidArgumentException("\$name have to be non empty string");
		}
		
		$filter = new UserGroupsFilter();
		$filter->setName($name);
		
		return $this->getGroup($filter, $cacheMinutes);
	}
	
	public function addUserToGroup(User $user, UserGroup $group){
		$qb = new QueryBuilder();
		
		$qb->insert(Tbl::get('TBL_USERS_GROUPS', 'UserManager'))
			->values(array(
				'user_id' => $user->id,
				'group_id' => $group->id
		));
		
		return $this->query->exec($qb->getSQL())->affected();
	}
	
	public function removeUserFromGroup(User $user, UserGroup $group){
		$qb = new QueryBuilder();
	
		$qb->delete(Tbl::get('TBL_USERS_GROUPS', 'UserManager'))
			->where($qb->expr()->equal(new Field('user_id'), $user->id))
			->andWhere($qb->expr()->equal(new Field('group_id'), $group->id));
	
		return $this->query->exec($qb->getSQL())->affected();
	}
	
	protected function getGroupObjectFromData($data){
		$group = new UserGroup();
		
		$group->id = $data['id'];
		$group->name = $data['name'];
		$group->description = $data['description'];
		
		return $group;
	}
}
?>