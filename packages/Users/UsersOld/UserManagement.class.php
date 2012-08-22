<?
class UserManager extends DbAccessor{
	
	const TBL_USERS 				= "wum_users";
	const TBL_USERS_GROUPS 			= "wum_users_groups";
	const TBL_USERS_PERMISSIONS 	= "wum_users_permissions";
	const TBL_REG_CODES 			= "wum_reg_codes";
	const TBL_PERMISSIONS 			= "wum_permissions";
	const TBL_GROUPS 				= "wum_groups";
	const TBL_GROUPS_PERMISSIONS 	= "wum_groups_permissions";
	
	const STATE_ENABLE_ENABLED = 1;
	const STATE_ENABLE_DISABLED = 0;

	const STATE_EMAIL_CONFIRMED = 1;
	const STATE_EMAIL_UNCONFIRMED = 0;

	const STATE_ONLINE_ONLINE = 1;
	const STATE_ONLINE_OFFLINE = 0;

	public function __construct($dbInstanceKey = null){
		parent::__construct($dbInstanceKey);
	}

	/**
	 * Translate permission names to IDs
	 *
	 * @param mixed $perms_names
	 * @return array
	 */
	private function getPermsIds($perms_names, $cacheMinutes = null){
		$perms_ids = array();
		if(is_array($perms_names)){
			foreach($perms_names as $perm_name){
				$this->query->exec("select `id` from `".Tbl::get('TBL_PERMISSIONS')."` where `name`='$perm_name'", $cacheMinutes);
				$perm_id = $this->query->fetchField("id");
				if($perm_id){
					$perms_ids[] = $perm_id;
				}
				else{
					return false;
				}
			}
		}
		else{
			if(empty($perms_names)){
				return $perms_ids;
			}
			else{
				$this->query->exec("select `id` from `".Tbl::get('TBL_PERMISSIONS')."` where `name`='$perms_names'", $cacheMinutes);
				$perm_id = $this->query->fetchField("id");
				if($perm_id){
					$perms_ids[] = $perm_id;
				}
				else{
					return false;
				}
			}
		}
		return $perms_ids;
	}

	/**
	 * Translate group names to IDs
	 *
	 * @param mixed $groups_names
	 * @return array
	 */
	private function getGroupsIds($groups_names, $cacheMinutes = null){
		$groups_ids = array();
		if(is_array($groups_names)){
			foreach($groups_names as $group_name){
				$this->query->exec("select `id` from `".Tbl::get('TBL_GROUPS')."` where `name`='$group_name'", $cacheMinutes);
				$group_id = $this->query->fetchField("id");
				if($group_id){
					$groups_ids[] = $group_id;
				}
				else{
					return false;
				}
			}
		}
		else{
			if(empty($groups_names)){
				return $groups_ids;
			}
			else{
				$this->query->exec("select `id` from `".Tbl::get('TBL_GROUPS')."` where `name`='$groups_names'", $cacheMinutes);
				$group_id = $this->query->fetchField("id");
				if($group_id){
					$groups_ids[] = $group_id;
				}
				else{
					return false;
				}
			}
		}
		return $groups_ids;
	}

	/**
	 * Class Destructor
	 *
	 */
	public function __destruct(){
		unset($this->query);
	}

	/**
	 * Create new permission
	 *
	 * @param string $name
	 * @param string $description
	 * @return bool
	 */
	public function createPermission($name, $description = ""){
		if($this->query->exec("insert into `".Tbl::get('TBL_PERMISSIONS')."`(`name`,`description`) values('$name','$description')")){
			$hookArgs = array("name" => $name, 'description'=>$description);
			HookManager::callHook("postCreateUserPermission", $hookArgs);
			return true;
		}
		else{
			return false;
		}
	}

	/**
	 * Delete permission
	 *
	 * @param string $name
	 * @return bool
	 */
	public function deletePermission($name){
		if($this->query->exec("delete from `".Tbl::get('TBL_PERMISSIONS')."` where `name`='$name'")){
			$hookArgs = array("name" => $name);
			HookManager::callHook("postDeleteUserPermission", $hookArgs);
			return true;
		}
		else{
			return false;
		}
	}

	/**
	 * Update permission
	 *
	 * @param string $name
	 * @param string $new_name
	 * @param string $new_description
	 * @return bool
	 */
	public function updatePermission($name, $new_name, $new_description = ""){
		if($this->query->exec("update `".Tbl::get('TBL_PERMISSIONS')."` set `name`='$new_name', `description` ='$new_description' where `name`='$name'")){
			return true;
		}
		else{
			return false;
		}
	}

	
	/**
	 * Create new group
	 *
	 * @param string $name
	 * @param array $permissions_list
	 * @param string $description
	 * @return bool
	 */
	public function createGroup($name, $permissions_list = array(), $description = ""){
		if($this->query->exec("insert into `".Tbl::get('TBL_GROUPS')."`(`name`,`description`) values('$name','$description')")){
			$group_id = $this->query->getLastInsertId();
			if(!empty($permissions_list) and !(($perms_ids = $this->getPermsIds($permissions_list)) === false)){
				if(count($perms_ids)){
					foreach($perms_ids as $perm_id){
						$this->query->exec("insert into `".Tbl::get('TBL_GROUPS_PERMISSIONS')."`(`group_id`,`permission_id`) values($group_id,$perm_id)");
					}
				}
			}
			$hookArgs = array("name" => $name, 'permissionsList' => $permissions_list, 'description'=>$description);
			HookManager::callHook("postCreateUserGroup", $hookArgs);
			return true;
		}
		else{
			return false;
		}
	}

	/**
	 * Delete group
	 *
	 * @param string $name
	 * @return bool
	 */
	public function deleteGroup($name){
		if($this->query->exec("delete from `".Tbl::get('TBL_GROUPS')."` where `name`='$name'")){
			$hookArgs = array("name" => $name);
			HookManager::callHook("postDeleteUserGroup", $hookArgs);
			return true;
		}
		else{
			return false;
		}
	}

	/**
	 * Update group
	 *
	 * @param string $name
	 * @param string $new_name
	 * @param array $new_permissions_list
	 * @param string $new_description
	 * @return bool
	 */
	public function updateGroup($name, $new_name = "", $new_permissions_list, $new_description = ""){
		if(($perms_ids = $this->getPermsIds($new_permissions_list)) === false){
			return false;
		}
		if(empty($new_name)){
			$new_name = $name;
		}
		if($this->query->exec("update `".Tbl::get('TBL_GROUPS')."` set `name`='$new_name', `description` ='$new_description' where `name`='$name'")){
			if(!empty($perms_ids)){
				$this->query->exec("select `id` from `".Tbl::get('TBL_GROUPS')."` where `name`='$new_name'");
				$group_id = $this->query->fetchField("id");
				$this->query->exec("delete from `".Tbl::get('TBL_GROUPS_PERMISSIONS')."` where `group_id`=$group_id");
				foreach($perms_ids as $perm_id){
					$this->query->exec("insert into `".Tbl::get('TBL_GROUPS_PERMISSIONS')."`(`group_id`,`permission_id`) values($group_id,$perm_id)");
				}
			}
			return true;
		}
		else{
			return false;
		}
	}

	/**
	 * Clear all permissions in group
	 *
	 * @param string $name
	 * @return bool
	 */
	public function groupClearPermissions($name){
		if($this->query->exec("select `id` from `".Tbl::get('TBL_GROUPS')."` where `name`='$name'")){
			$group_id = $this->query->fetchField("id");
			$this->query->exec("delete from `".Tbl::get('TBL_GROUPS_PERMISSIONS')."` where `group_id`=$group_id");
			return true;
		}
		else{
			return false;
		}
	}

	/**
	 * Add new permission to group
	 *
	 * @param string $name
	 * @param array $permissions_list
	 * @return bool
	 */
	public function groupAddPermissions($name, $permissions_list){
		$perms_ids = $this->getPermsIds($permissions_list);
		if(empty($perms_ids)){
			return false;
		}
		if($this->query->exec("select `id` from `".Tbl::get('TBL_GROUPS')."` where `name`='$name'")){
			$group_id = $this->query->fetchField("id");
			foreach($perms_ids as $perm_id){
				$this->query->exec("insert into `".Tbl::get('TBL_GROUPS_PERMISSIONS')."`(`group_id`,`permission_id`) values($group_id,$perm_id)");
			}
			return true;
		}
		else{
			return false;
		}
	}

	/**
	 * Remove specified permissions from group
	 *
	 * @param string $name
	 * @param array $permissions_list
	 * @return bool
	 */
	public function groupRemovePermissions($name, $permissions_list){
		$perms_ids = $this->getPermsIds($permissions_list);
		if(empty($perms_ids)){
			return false;
		}
		if($this->query->exec("select `id` from `".Tbl::get('TBL_GROUPS')."` where `name`='$name'")){
			$group_id = $this->query->fetchField("id");
			$sql_statement = "delete from `".Tbl::get('TBL_GROUPS_PERMISSIONS')."` where `group_id`=$group_id and (";
			$count = count($perms_ids) - 1;
			for($i = 0; $i < $count; ++$i){
				$sql_statement .= "`permission_id`=$perms_ids[$i] or ";
			}
			$sql_statement .= "`permission_id`=$perms_ids[$count])";
			$this->query->exec($sql_statement);
			return true;
		}
		else{
			return false;
		}
	}

	/**
	 * Get list of all permissions
	 *
	 * @return array
	 */
	public function getPermissionsList($cacheMinutes = null){
		if($this->query->exec("select `name` from `".Tbl::get('TBL_PERMISSIONS')."`", $cacheMinutes)){
			return $this->query->fetchFields(0, true);
		}
		else{
			return false;
		}
	}

	/**
	 * Get list of groups
	 *
	 * @return array
	 */
	public function getGroupsList($cacheMinutes = null){
		$query = "SELECT * FROM `".Tbl::get('TBL_GROUPS')."`";

		if($this->query->exec($query, $cacheMinutes)){
			return $this->query->fetchRecords();
		}
		return false;
	}
	/**
	 * returns group name of given ID
	 *
	 * @param int $group_id
	 * @return string
	 */
	public function getGroupName($group_id, $cacheMinutes = null){
		if($this->query->exec("SELECT `name` FROM `".Tbl::get('TBL_GROUPS')."` WHERE id = '$group_id'", $cacheMinutes)){
			return $this->query->fetchField("name");
		}
		return false;
	}

	/**
	 * Get permission of specified group
	 *
	 * @param string $name
	 * @return array
	 */
	public function getGroupPermissionsList($name, $cacheMinutes = null){
		if($this->query->exec("SELECT `id` FROM `".Tbl::get('TBL_GROUPS')."` WHERE `name`='$name'", $cacheMinutes)){
			$group_id = $this->query->fetchField("id");
			$this->query->exec("SELECT `permission_id` FROM `".Tbl::get('TBL_GROUPS_PERMISSIONS')."` WHERE `group_id`='$group_id'", $cacheMinutes);
			$perms_ids_count = $this->query->countRecords();
			if($perms_ids_count){
				$perms_ids = $this->query->fetchFields(0, true);
				$sql_statement = "SELECT `name` FROM `".Tbl::get('TBL_PERMISSIONS')."` WHERE `id` IN (" . implode(", ", $perms_ids) . ")";
				$this->query->exec($sql_statement, $cacheMinutes);
				return $this->query->fetchFields(0, true);
			}
			else{
				return array();
			}
		}
		else{
			return false;
		}
	}

	/**
	 * Get description of specified permission
	 *
	 * @param string $name
	 * @return string
	 */
	public function getPermissionDescription($name, $cacheMinutes = null){
		if($this->query->exec("select `description` from `".Tbl::get('TBL_PERMISSIONS')."` where `name`='$name'", $cacheMinutes)){
			return $this->query->fetchField("description");
		}
		else{
			return false;
		}
	}

	/**
	 * Get description of specified group
	 *
	 * @param string $name
	 * @return string
	 */
	public function getGroupDescription($name, $cacheMinutes = null){
		if($this->query->exec("select `description` from `".Tbl::get('TBL_GROUPS')."` where `name`='$name'", $cacheMinutes)){
			return $this->query->fetchField("description");
		}
		else{
			return false;
		}
	}

	/**
	 * Is permission exists
	 *
	 * @param string $permission_name
	 * @return bool
	 */
	public function isPermissionExists($permission_name, $cacheMinutes = null){
		if($this->query->exec("select count(*) as `count` from `".Tbl::get('TBL_PERMISSIONS')."` where `name`='$permission_name'", $cacheMinutes)){
			if($this->query->fetchField("count")){
				return true;
			}
		}
		return false;
	}

	/**
	 * Is group exists
	 *
	 * @param string $group_name
	 * @return bool
	 */
	public function isGroupExists($group_name, $cacheMinutes = null){
		if($this->query->exec("select `id` from `".Tbl::get('TBL_GROUPS')."` where `name`='$group_name'", $cacheMinutes)){
			if($this->query->fetchField("id")){
				return true;
			}
		}
		return false;
	}

	/**
	 * Create a new user
	 *
	 * @param string $login
	 * @param string $password
	 * @param wusr $user
	 * @return int
	 */
	public function createUser($login, $password, User $user = null){
		if(empty($login) or empty($password) or !(strpos($login, "\\") === false) or !(strpos($login, "/") === false)){
			return false;
		}
		$fields = array();
		if(!is_null($user)){
			$creation_date = $user->getCreationDate();
			$enable = ($user->isEnabled()) ? 1 : 0;
			$fields = get_object_vars($user);
			$permissions = $user->getPermissions();
			$groups = $user->getGroups();
			$primary_group = $user->getPrimaryGroup();
			if(count($groups) == 1 and empty($primary_group)){
				$primary_group = $groups[0];
			}
			if(($perms_ids = $this->getPermsIds($permissions)) === false or ($groups_ids = $this->getGroupsIds($groups)) === false or ($primary_group_id = $this->getGroupId($primary_group)) === false){
				return false;
			}
			unset($permissions);
			unset($groups);
			unset($primary_group);
			$fields = array_diff_key($fields, array('id' => 0, 'login' => "", 'enable' => '', 'creation_date' => '', 'permissions' => array(), 'groups' => array(), 'primary_group' => ''));
		}

		$keys = '';
		$values = "";
		if(count($fields)){
			foreach($fields as $key => $value){
				$keys .= "`$key`, ";
				$values .= "'$value', ";
			}

			$keys = ', ' . substr($keys, 0, -2);
			$values = ', ' . substr($values, 0, -2);
		}

		if(empty($creation_date)){
			$creation_date = "now()";
		}
		else{
			$creation_date = "'$creation_date'";
		}

		if(!isset($enable)){
			$enable = 0;
		}

		if($this->query->exec("insert into `".Tbl::get('TBL_USERS')."`(`login`,`password`,`enable`,`creation_date`" . $keys . ") values('$login','" . md5($password) . "','$enable',$creation_date" . $values . ")")){
			if(!is_null($user)){
				$user_id = $this->query->getLastInsertId();
				foreach($perms_ids as $perm_id){
					$this->query->exec("insert into `".Tbl::get('TBL_USERS_PERMISSIONS')."`(`user_id`,`permission_id`) values($user_id,$perm_id)");
				}
				foreach($groups_ids as $group_id){
					if($group_id == $primary_group_id){
						$this->query->exec("insert into `".Tbl::get('TBL_USERS_GROUPS')."` (`user_id`,`group_id`,`is_primary`) values($user_id,$group_id,1)");
					}
					else{
						$this->query->exec("insert into `".Tbl::get('TBL_USERS_GROUPS')."` (`user_id`,`group_id`) values($user_id,$group_id)");
					}
				}
				$hookArgs = array("userId" => $user_id, "login"=>$login, 'password'=>$password, 'userObj'=>$user);
				HookManager::callHook("postUserCreation", $hookArgs);
				return $user_id;
			}
		}
		return false;
	}

	/**
	 * Delete specified user
	 *
	 * @param string $user_id
	 * @return bool
	 */
	public function deleteUser($user_id){
		$user_id = intval($user_id);
		if($this->query->exec("delete from `".Tbl::get('TBL_USERS')."` where `id`='$user_id'")){
			$hookArgs = array("userId" => $user_id);
			HookManager::callHook("postDeleteUser", $hookArgs);
			return true;
		}
		else{
			return false;
		}
	}

	/**
	 * Update specified user
	 *
	 * @param string $user_id
	 * @param wusr $user
	 * @return bool
	 */
	public function updateUser($user_id, User $user){
		$user_id = intval($user_id);

		$fields = get_object_vars($user);

		$permissions = $user->getPermissions();
		$groups = $user->getGroups();
		$primary_group = $user->getPrimaryGroup();
		if(count($groups) == 1 and empty($primary_group)){
			$primary_group = $groups[0];
		}
		if(($perms_ids = $this->getPermsIds($permissions)) === false or ($groups_ids = $this->getGroupsIds($groups)) === false or ($primary_group_id = $this->getGroupId($primary_group)) === false){
			return false;
		}
		unset($permissions);
		unset($groups);
		unset($primary_group);

		$fields = array_diff_key($fields, array('id' => 0, 'login' => "", 'permissions' => array(), 'groups' => array(), 'primary_group' => ''));
		$sql_statement = "update `".Tbl::get('TBL_USERS')."` set ";
		foreach($fields as $key => $value){
			if($value === null){
				$sql_statement .= "`$key` = null, ";
			}
			else{
				$sql_statement .= "`$key` = '" . addslashes($value) . "', ";
			}
		}
		$sql_statement = substr($sql_statement, 0, -2);
		$sql_statement .= " where `id`='$user_id'";
		if(!$this->query->exec($sql_statement)){
			return false;
		}

		$groups_permissions = array();
		foreach($this->getUserGroups($user_id) as $user_group){
			foreach($this->getGroupPermissionsList($user_group) as $one_group_permission){
				array_push($groups_permissions, $one_group_permission);
			}
		}

		$user_groups_perms_ids = $this->getPermsIds($groups_permissions);
		$perms_ids = array_diff($perms_ids, $user_groups_perms_ids);

		$this->query->exec("delete from `".Tbl::get('TBL_USERS_PERMISSIONS')."` where `user_id`='$user_id'");
		$this->query->exec("delete from `".Tbl::get('TBL_USERS_GROUPS')."` where `user_id`='$user_id'");

		foreach($perms_ids as $perm_id){
			$this->query->exec("insert into `".Tbl::get('TBL_USERS_PERMISSIONS')."` (`user_id`,`permission_id`) values('$user_id', '$perm_id')");
		}
		foreach($groups_ids as $group_id){
			if($group_id == $primary_group_id){
				$this->query->exec("insert into `".Tbl::get('TBL_USERS_GROUPS')."`(`user_id`,`group_id`,`is_primary`) values('$user_id', '$group_id',1)");
			}
			else{
				$this->query->exec("insert into `".Tbl::get('TBL_USERS_GROUPS')."`(`user_id`,`group_id`) values('$user_id', '$group_id')");
			}
		}
		$hookArgs = array("userId" => $user_id, 'user'=>$user);
		HookManager::callHook("postUserUpdate", $hookArgs);
		return true;
	}

	/**
	 * Update only specified extra fields
	 *
	 * @param string $user_id
	 * @param array $extra_fields
	 * @return bool
	 */
	public function updateUserExtra($user_id, $extra_fields){
		$user_id = intval($user_id);
		if($this->isUserExists(addslashes($this->getLoginById($user_id))) and count($extra_fields)){
			$sql_statement = "update `".Tbl::get('TBL_USERS')."` set ";
			foreach($extra_fields as $key => $value){
				if($value === null){
					$sql_statement .= "`$key` = null, ";
				}
				else{
					$sql_statement .= "`$key` = '$value', ";
				}
			} 
			$sql_statement = substr($sql_statement, 0, -2);
			$sql_statement .= " where `id`='$user_id'";
			if($this->query->exec($sql_statement)){
				$hookArgs =  array("userId" => $user_id, 'extraFields'=>$extra_fields);
				HookManager::callHook("postUserUpdateExtra", $hookArgs);
				return true;
			}
		}
		return false;
	}

	/**
	 * Indicates is enabled user
	 *
	 * @param string $user_id
	 * @return bool
	 */
	public function isEnabledUser($user_id){
		$user_id = intval($user_id);
		if($this->query->exec("select `enable` from `".Tbl::get('TBL_USERS')."` where `id`='$user_id'")){
			if($this->query->fetchField("enable")){
				return true;
			}
		}
		return false;
	}

	/**
	 * Enable specified user
	 *
	 * @param string $user_id
	 * @return bool
	 */
	public function enableUser($user_id){
		$user_id = intval($user_id);
		if($this->query->exec("update `".Tbl::get('TBL_USERS')."` set `enable`='1' where `id`='$user_id'")){
			$hookArgs = array("userId" => $user_id);
			HookManager::callHook("postEnableUser", $hookArgs);
			return true;
		}
		else{
			return false;
		}
	}

	/**
	 * Disable specified user
	 *
	 * @param string $user_id
	 * @return bool
	 */
	public function disableUser($user_id){
		$user_id = intval($user_id);
		if($this->query->exec("update `".Tbl::get('TBL_USERS')."` set `enable`='0' where `id`='$user_id'")){
			$hookArgs = array("userId" => $user_id);
			HookManager::callHook("postDisableUser", $hookArgs);
			return true;
		}
		else{
			return false;
		}
	}

	/**
	 * Get user creation date
	 *
	 * @param string $user_id
	 * @return string
	 */
	public function getCreationDate($user_id, $cacheMinutes = null){
		$user_id = intval($user_id);
		if($this->query->exec("select `creation_date` from `".Tbl::get('TBL_USERS')."` where `id`='$user_id'", $cacheMinutes)){
			return $this->query->fetchField("creation_date");
		}
		else{
			return false;
		}
	}

	/**
	 * Set user creation date
	 *
	 * @param string $user_id
	 * @param string $date
	 * @return bool
	 */
	public function setCreationDate($user_id, $date){
		$user_id = intval($user_id);
		if(($utime = strtotime($date)) == -1){
			return false;
		}
		else{
			$date = date("Y-m-d", $utime);
			if($this->query->exec("update `".Tbl::get('TBL_USERS')."` set `creation_date`='$date' where `id`='$user_id'")){
				HookManager::callHook("postUserSetCreationDate", array("userId" => $user_id, 'date'=>$date));
				return true;
			}
			else{
				return false;
			}
		}
	}

	/**
	 * Change user login
	 *
	 * @param string $user_id
	 * @param string $new_login
	 * @return bool
	 */
	public function setLogin($user_id, $new_login){
		$user_id = intval($user_id);
		if(empty($new_login)){
			return false;
		}
		if($this->query->exec("update `".Tbl::get('TBL_USERS')."` set `login`='$new_login' where `id`='$user_id'")){
			$hookArgs = array("userId" => $user_id, 'login'=>$new_login);
			HookManager::callHook("postUserLoginChange", $hookArgs);
			return true;
		}
		else{
			return false;
		}
	}

	/**
	 * Get user encrypted password
	 *
	 * @param string $user_id
	 * @return string
	 */
	public function getPassword($user_id, $cacheMinutes = 0){
		$user_id = intval($user_id);
		if($this->query->exec("select `password` from `".Tbl::get('TBL_USERS')."` where `id`='$user_id'", $cacheMinutes)){
			return $this->query->fetchField("password");
		}
		else{
			return false;
		}
	}

	/**
	 * Change user password
	 *
	 * @param string $user_id
	 * @param string $password
	 * @return bool
	 */
	public function setPassword($user_id, $password){
		$user_id = intval($user_id);
		if(empty($password)){
			return false;
		}

		if($this->query->exec("update `".Tbl::get('TBL_USERS')."` set `password`='" . md5($password) . "' where `id`='$user_id'")){
			$hookArgs = array("userId" => $user_id, 'password'=>$password);
			HookManager::callHook("postUserSetPassword", $hookArgs);
			return true;
		}
		else{
			return false;
		}
	}

	/**
	 * eturn User object of given login
	 *
	 * @param string $login
	 * @param int $cacheMinutes
	 * @return User
	 */
	public function getObjectByLogin($login, $cacheMinutes = 0){
		return $this->getObjectById($this->getIdByLogin($login, $cacheMinutes), $cacheMinutes);
	}
	
	/**
	 * Return User object of given user_id
	 *
	 * @param int $user_id
	 * @return User
	 */
	public function getObjectById($user_id, $cacheMinutes = 0){
		$user_id = intval($user_id);
		$this->query->exec("select * from `".Tbl::get('TBL_USERS')."` where `id`='$user_id'", $cacheMinutes);
		if($this->query->countRecords()){

			$res = $this->query->fetchRecord();
			$user = new User();
			$user->setLogin($res["login"]);
			$user->setId($user_id);
			$user->setCreationDate($res["creation_date"]);
			$user->setStatus($res["enable"]);
			foreach($res as $key => $val){
				if($key != 'id' && $key != 'enable' && $key != 'creation_date' && $key != 'login' && $key != 'password'){
					$user->$key = $val;
				}
			}

			$this->query->exec("select `permission_id` from `".Tbl::get('TBL_USERS_PERMISSIONS')."` where `user_id`='$user_id'", $cacheMinutes);
			$perms_ids_count = $this->query->countRecords();
			if($perms_ids_count){
				$perms_ids = $this->query->fetchFields(0, true);

				$sql_statement = "select `name` from `".Tbl::get('TBL_PERMISSIONS')."` where `id` in (";
				$count = $perms_ids_count - 1;
				for($i = 0; $i < $count; ++$i){
					$sql_statement .= $perms_ids[$i] . ", ";
				}
				$sql_statement .= $perms_ids[$count] . ")";

				$this->query->exec($sql_statement, $cacheMinutes);
				$user->setPermissions($this->query->fetchFields(0, true));
			}

			$this->query->exec("select `group_id` from `".Tbl::get('TBL_USERS_GROUPS')."` where `user_id`='$user_id'", $cacheMinutes);
			$groups_ids_count = $this->query->countRecords();
			if($groups_ids_count){
				$groups_ids = $this->query->fetchFields(0, true);

				$sql_statement = "select `name` from `".Tbl::get('TBL_GROUPS')."` where `id` in (";
				$count = $groups_ids_count - 1;
				for($i = 0; $i < $count; ++$i){
					$sql_statement .= $groups_ids[$i] . ", ";
				}
				$sql_statement .= $groups_ids[$count] . ")";

				$this->query->exec($sql_statement, $cacheMinutes);
				$groups_names = $this->query->fetchFields(0, true);
				$user->setGroups($groups_names);

				foreach($groups_names as $group_name){
					$user->addPermissions($this->getGroupPermissionsList($group_name, $cacheMinutes));
				}
				$user->setPrimaryGroup($this->getPrimaryGroup($user_id, $cacheMinutes));
			}
			return $user;
		}
		return new User();
	}

	/**
	 * Check user login password
	 *
	 * @param string $login
	 * @param string $password
	 * @param boolean $isPassMd5
	 * @return boolean
	 */
	public function checkCredentials($login, $password, $isPassMd5 = false){
		if(!empty($login) and !empty($password)){
			$this->query->exec("SELECT `password` FROM `".Tbl::get('TBL_USERS')."` WHERE `login`='$login'");
			if($this->query->countRecords() == 1){
				$validPassword = $this->query->fetchField('password');
				if($validPassword == ($isPassMd5 ? $password : md5($password))){
					return true;
				}
			}
		}
		return false;
	}

	/**
	 * Indicates is available user with specified login and password
	 *
	 * @param string $login
	 * @param password $password
	 * @return bool
	 */
	public function isAvailableUser($login, $password, $cacheMinutes = 0){
		if(empty($login) or empty($password)){
			return false;
		}

		if($this->query->exec("select `enable`, `password` from `".Tbl::get('TBL_USERS')."` where `login`='$login'", $cacheMinutes)){
			$res = $this->query->fetchRecord();
			if($res["password"] == md5($password) and $res["enable"]){
				return true;
			}
		}
		return false;
	}

	/**
	 * Indicates is user exists
	 *
	 * @param string $login
	 * @return bool
	 */
	public function isUserExists($login, $cacheMinutes = null){
		if($this->query->exec("select count(*) as `count` from `".Tbl::get('TBL_USERS')."` where `login`='$login'", $cacheMinutes)){
			if($this->query->fetchField("count") == 1){
				return true;
			}
		}
		return false;
	}

	/**
	 * Get specified extra fields
	 *
	 * @param string $login
	 * @param array $extra_keys
	 * @return array
	 */
	public function getUserExtra($user_id, $extra_keys, $cacheMinutes = 0){
		$extra_fields = array();
		if(count($extra_keys)){
			$user_id = intval($user_id);
			$field_names = '';
			foreach($extra_keys as $field_name){
				$field_names .= "`" . $field_name . "`, ";
			}
			$field_names = substr($field_names, 0, -2);
			if($this->query->exec("select $field_names from `".Tbl::get('TBL_USERS')."` where `id`='$user_id'", $cacheMinutes)){
				return $this->query->fetchRecord();
			}
		}
		return $extra_fields;
	}

	/**
	 * Get groups that specified user belongs to. 
	 *
	 * @param string $user_id
	 * @return array
	 */
	public function getUserGroups($user_id, $cacheMinutes = null){
		$user_id = intval($user_id);
		$this->query->exec("select `group_id` from `".Tbl::get('TBL_USERS_GROUPS')."` where `user_id`='$user_id'", $cacheMinutes);
		$groups_ids_count = $this->query->countRecords();
		if($groups_ids_count){
			$groups_ids = $this->query->fetchFields(0, true);
			$sql_statement = "select `name` from `".Tbl::get('TBL_GROUPS')."` where `id` in (" . implode(', ', $groups_ids) . ")";

			$this->query->exec($sql_statement, $cacheMinutes);
			return $this->query->fetchFields(0, true);
		}
		else{
			return array();
		}
	}

	/**
	 * Get group id whith name $name
	 *
	 * @param string $name
	 * @return Integer or Boolean FALSE if something is wrong
	 */
	public function getGroupId($name, $cacheMinutes = null){
		$name = mysql_real_escape_string($name);
		$this->query->exec("select `id` from `".Tbl::get('TBL_GROUPS')."` where `name`='$name'", $cacheMinutes);
		if(($group_id = $this->query->fetchField("id")) != false){
			return $group_id;
		}
		else{
			return false;
		}
	}

	/**
	 * Is given group primary for this user
	 *
	 * @param string $user_id
	 * @param string $group_name
	 * @return Boolean
	 */
	public function isPrimaryGroup($user_id, $group_name, $cacheMinutes = null){
		$group_name = mysql_real_escape_string($group_name);
		$this->query->exec("select `is_primary` from `".Tbl::get('TBL_USERS_GROUPS')."` where `user_id`='$user_id' and `group_id`='" . $this->getGroupId($group_name) . "'", $cacheMinutes);
		if($this->query->fetchField("is_primary") == '1'){
			return true;
		}
		else{
			return false;
		}
	}

	/**
	 * Get primary group for user
	 *
	 * @param string $user_id
	 * @return String
	 */
	public function getPrimaryGroup($user_id, $cacheMinutes = null){
		$this->query->exec("select g.`name` as `group_name` from `".Tbl::get('TBL_USERS_GROUPS')."` ug left join `".Tbl::get('TBL_GROUPS')."` g on (ug.`group_id`=g.`id`) where ug.`user_id`='$user_id' and ug.`is_primary`='1'", $cacheMinutes);
		if(($group_name = $this->query->fetchField("group_name")) != false){
			return $group_name;
		}
		else{
			return false;
		}
	}

	/**
	 * Is user has group
	 *
	 * @param string $user_id, $group_name
	 * @return bool
	 */
	public function isUserHasGroup($user_id, $group_name, $cacheMinutes = null){
		$this->query->exec("select g.`name` as `group_name` from `".Tbl::get('TBL_USERS_GROUPS')."` ug left join `".Tbl::get('TBL_GROUPS')."` g on (ug.`group_id`=g.`id`) where `ug`.`user_id`='$user_id' and g.`name`='$group_name'", $cacheMinutes);
		if($this->query->countRecords()){
			return true;
		}
		else{
			return false;
		}
	}

	/**
	 * Get permissions that specified user have
	 *
	 * @param string $user_id
	 * @return array
	 */
	public function getUserPermissions($user_id, $cacheMinutes = null){
		$user_id = intval($user_id);
		$this->query->exec("select `permission_id` from `".Tbl::get('TBL_USERS_PERMISSIONS')."` where `user_id`='$user_id'", $cacheMinutes);
		$permissions_ids_count = $this->query->countRecords();
		if($permissions_ids_count){
			$permissions_ids = $this->query->fetchFields(0, true);

			$sql_statement = "select `name` from `".Tbl::get('TBL_PERMISSIONS')."` where `id` in (";
			$count = $permissions_ids_count - 1;
			for($i = 0; $i < $count; ++$i){
				$sql_statement .= $permissions_ids[$i] . ", ";
			}
			$sql_statement .= $permissions_ids[$count] . ")";

			$this->query->exec($sql_statement, $cacheMinutes);
			return $this->query->fetchFields(0, true);
		}
		else{
			return array();
		}
	}

	/**
	 * Set user groups
	 *
	 * @param string $user_id
	 * @param array $groups_list
	 * @param string $primary_group
	 * @return bool
	 */
	public function setUserGroups($user_id, $groups_list, $primary_group = ''){
		$user_id = intval($user_id);
		$groups_ids = $this->getGroupsIds($groups_list);
		$primary_group_id = $this->getGroupId($primary_group);
		if(count($groups_ids) == 1 and empty($primary_group_id)){
			$primary_group_id = $groups_ids[0];
		}

		$this->query->exec("delete from `".Tbl::get('TBL_USERS_GROUPS')."` where `user_id`='$user_id'");

		foreach($groups_ids as $group_id){
			if($group_id == $primary_group_id){
				$this->query->exec("insert into `".Tbl::get('TBL_USERS_GROUPS')."`(`user_id`,`group_id`,`is_primary`) values('$user_id', '$group_id',1)");
			}
			else{
				$this->query->exec("insert into `".Tbl::get('TBL_USERS_GROUPS')."`(`user_id`,`group_id`) values('$user_id', '$group_id')");
			}
		}
		$hookArgs = array("userId" => $user_id, 'groupsList'=>$groups_list, 'primaryGroup'=>$primary_group);
		HookManager::callHook("postUserSetGroups", $hookArgs);
		return true;
	}

	/**
	 * Set user permissions
	 *
	 * @param string $user_id
	 * @param array $permissions_list
	 * @return bool
	 */
	public function setUserPermissions($user_id, $permissions_list){
		$user_id = intval($user_id);
		$perms_ids = $this->getPermsIds($permissions_list);

		$this->query->exec("delete from `".Tbl::get('TBL_USERS_PERMISSIONS')."` where `user_id`='$user_id'");

		foreach($perms_ids as $perm_id){
			$this->query->exec("insert into `".Tbl::get('TBL_USERS_PERMISSIONS')."`(`user_id`,`permission_id`) values('$user_id', '$perm_id')");
		}
		$hookArgs = array("userId" => $user_id, 'permissions'=>$permissions_list);
		HookManager::callHook("postUserSetPermissions", $hookArgs);
		return true;
	}

	/**
	 * Get user list count
	 *
	 * @param UsersFilter $filter
	 * @return integer
	 */
	public function getUsersListCount(UsersFilter $filter = null, $cacheMinutes = 0){
		if($filter === null){
			$filter = new UsersFilter();
		}
		
		$filter->setSelectCount();
		
		$sqlQuery = $filter->getSQL();

		$this->query->exec($sqlQuery, $cacheMinutes);
		return $this->query->fetchField('cnt');
	}

	/**
	 * Get user list
	 *
	 * @param UsersFilter $filter
	 * @param MysqlPager $pager
	 * @return array[User]
	 */
	public function getUsersList(UsersFilter $filter = null, MysqlPager $pager = null, $cacheMinutes = 0){
		$users = array();

		if($filter == null){
			$filter = new UsersFilter();
		}

		$sqlQuery = $filter->getSQL();
		if($pager !== null){
			$this->query = $pager->executePagedSQL($sqlQuery, $cacheMinutes);
		}
		else{
			$this->query->exec($sqlQuery, $cacheMinutes);
		}
		if($this->query->countRecords()){
			foreach($this->query->fetchFields('id') as $user_id){
				array_push($users, $this->getObjectById($user_id, $cacheMinutes));
			}
		}

		return $users;
	}

	/**
	 * Generate email activation code
	 *
	 * @param string $user_id
	 * @return string
	 */
	public function generateRegCode($user_id){
		$user_id = intval($user_id);
		if($this->isUserExists(addslashes($this->getLoginById($user_id)))){
			$reg_code = md5(uniqid(rand(), true));
			if(!$this->query->exec("insert into `".Tbl::get('TBL_REG_CODES')."` (`id`, `user_id`, `code`) values(0, '$user_id', '$reg_code')")){
				return false;
			}
			return $reg_code;
		}
		return false;
	}

	/**
	 * Remove email activation code
	 *
	 * @param string $user_id
	 * @return string
	 */
	public function removeRegCode($user_id){
		$user_id = intval($user_id);
		if($this->isUserExists(addslashes($this->getLoginById($user_id)))){
			if(!$this->query->exec("delete from `".Tbl::get('TBL_REG_CODES')."` where `user_id`=" . $user_id)){
				return false;
			}
			return true;
		}
		return false;
	}

	/**
	 * Validate email activation
	 *
	 * @param string $code
	 * @param boolean $enable_user
	 * @param boolean $delete_code
	 * @return integer
	 */
	public function validateRegistration($code, $enable_user = true, $delete_code = true){
		$this->query->exec("select `id`, `user_id` from `".Tbl::get('TBL_REG_CODES')."` where `code`='$code'");
		if($this->query->countRecords()){
			$code_row = $this->query->fetchRecord();
			if($enable_user){
				if(!$this->enableUser($code_row['user_id'])){
					return false;
				}
			}
			if($delete_code){
				if(!$this->removeRegCode($code_row['user_id'])){
					return false;
				}
			}
			return $code_row['user_id'];
		}
		return false;
	}

	/**
	 * Returns login of given user ID
	 *
	 * @param int $user_id
	 * @return string
	 */
	public function getLoginById($user_id, $cacheMinutes = 0){
		$user_id = intval($user_id);
		$this->query->exec("select `login` from `".Tbl::get('TBL_USERS')."` where `id`='$user_id'", $cacheMinutes);
		if($this->query->countRecords()){
			return $this->query->fetchField('login');
		}
		return false;
	}

	/**
	 * Returns ID of given login
	 *
	 * @param string $login
	 * @return int
	 */
	public function getIdByLogin($login, $cacheMinutes = null){
		$this->query->exec("select `id` from `".Tbl::get('TBL_USERS')."` where `login`='$login'", $cacheMinutes);
		if($this->query->countRecords()){
			return $this->query->fetchField('id');
		}
		return false;
	}
}
?>