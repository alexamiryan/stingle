<?php
class UserPermissionsManager extends DbAccessor{
	
	
	public function getPermissions(UserPermissionsFilter $filter = null, $cacheminutes = null){
		if($filter === null){
			$filter = new UserPermissionsFilter();
		}
		//echo $filter->getSQL();
		$this->query->exec($filter->getSQL(), $cacheminutes);
		
		$perms = array();
		if($this->query->countRecords()){
			foreach($this->query->fetchRecords() as $row){
				array_push($perms, static::getPermissionsObjectFromData($row));
			}
		}
		
		return $perms;
	}
	
	public function getPermission(UserPermissionsFilter $filter = null, $cacheminutes = null){
		$permissions = $this->getPermissions($filter, $cacheminutes);
		
		if(count($permissions) !== 1){
			throw new Exception("There is no permission or it is not unique");
		}
		
		return $permissions[0];
	}
	
	public function getPermissionById($permissionId){
		if(!is_numeric($permissionId)){
			throw new InvalidArgumentException("PerrmisionId is not numeric");
		}
		$filter = new UserPermissionsFilter();
		$filter->setId($permissionId);
		
		return $this->getPermission($filter);
	}
	
	public function addPermissionToUser(Permission $perm, User $user, $args = null){
		$qb = new QueryBuilder();
		
		$values = array(
				'user_id' => $user->id,
				'permission_id' => $perm->id
		);
		
		if($args !== null){
			$values['args'] = serialize($args);
		}
		
		$qb->insert(Tbl::get('TBL_USERS_PERMISSIONS', 'UserManager'))
			->values($values);
		
		return $this->query->exec($qb->getSQL())->affected();
	}
	
	public function addPermissionToGroup(Permission $perm, UserGroup $group, $args = null){
		$qb = new QueryBuilder();
		
		$values = array(
				'group_id' => $group->id,
				'permission_id' => $perm->id
		);
		
		if($args !== null){
			$values['args'] = serialize($args);
		}
		
		$qb->insert(Tbl::get('TBL_GROUPS_PERMISSIONS', 'UserManager'))
			->values($values);
		
		return $this->query->exec($qb->getSQL())->affected();
	}
	
	public function removePermissionFromUser(Permission $perm, User $user){
		$qb = new QueryBuilder();
	
		$qb->delete(Tbl::get('TBL_USERS_PERMISSIONS', 'UserManager'))
		->where($qb->expr()->equal(new Field('permission_id'), $perm->id))
		->andWhere($qb->expr()->equal(new Field('user_id'), $user->id));
	
		return $this->query->exec($qb->getSQL())->affected();
	}
	
	public function removePermissionFromGroup(Permission $perm, UserGroup $group){
		$qb = new QueryBuilder();
	
		$qb->delete(Tbl::get('TBL_GROUPS_PERMISSIONS', 'UserManager'))
			->where($qb->expr()->equal(new Field('permission_id'), $perm->id))
			->andWhere($qb->expr()->equal(new Field('group_id'), $group->id));
	
		return $this->query->exec($qb->getSQL())->affected();
	}
	
	public static function getPermissionsObjectFromData($data){
		$perm = new Permission();
		
		$perm->id = $data['id'];
		$perm->name = $data['name'];
		$perm->description = $data['description'];
		
		if(isset($data['args'])){
			try{
				$perm->args = unserialize($data['args']);
			}
			catch(ErrorException $e){
				$perm->args = null;
			}
		}
		
		return $perm;
	}
	
	public static function hasPermission(User $user, $permissionName){
		if(isset($user->perms->permissionsList[$permissionName])){
			return true;
		}
		return false;
	}
	
	public static function getPermissionFromUser(User $user, $permissionName){
		if(isset($user->perms->permissionsList[$permissionName])){
			return $user->perms->permissionsList[$permissionName];
		}
		throw new UserPermissionException();
	}
}
