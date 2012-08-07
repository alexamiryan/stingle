<?
class UserPermissions{
	private $permissionsList;
	
	public function __construct(array $permissionsList){
		$this->permissionsList = $permissionsList;
	}
	
	public function hasPermission($permission){
		return (in_array($permission, $this->permissionsList) ? true : false);
	}
	
	public function getPermissionsList(){
		return $this->permissionsList;
	}
}
?>