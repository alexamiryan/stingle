<?
class UserPermissions{
	private $permissionsList;
	
	public function __construct(array $permissionsList){
		if(is_array($permissionsList)){
			foreach($permissionsList as  $permission){
				if($permission instanceof Permission){
					$this->permissionsList[$permission->name] = $permission;
				}
			}
		}
	}
	
	public function hasPermission($permissionName){
		if(isset($this->permissionsList[$permissionName])){
			return true;
		}
		return false;
	}
	
	public function getPermission($permissionName){
		if(isset($this->permissionsList[$permissionName])){
			return $this->permissionsList[$permissionName];
		}
		return false;
	}
	
	public function getPermissionsList(){
		return $this->permissionsList;
	}
}
?>