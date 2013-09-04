<?php
class LoaderUsers extends Loader{
	protected function includes(){
		stingleInclude ('Managers/UserAuthorization.class.php');
		stingleInclude ('Managers/UserGroupsManager.class.php');
		stingleInclude ('Managers/UserManager.class.php');
		stingleInclude ('Managers/UserPermissionsManager.class.php');
		stingleInclude ('Objects/User.class.php');
		stingleInclude ('Objects/UserGroup.class.php');
		stingleInclude ('Objects/Permission.class.php');
		stingleInclude ('Objects/UserPermissions.class.php');
		stingleInclude ('Objects/UserProperties.class.php');
		stingleInclude ('Filters/UserGroupsFilter.class.php');
		stingleInclude ('Filters/UserPermissionsFilter.class.php');
		stingleInclude ('Filters/UsersFilter.class.php');
		stingleInclude ('Exceptions/UserException.class.php');
		stingleInclude ('Exceptions/UserAuthFailedException.class.php');
		stingleInclude ('Exceptions/UserDisabledException.class.php');
		stingleInclude ('Exceptions/UserNotFoundException.class.php');
		stingleInclude ('Exceptions/UserPermissionException.class.php');
		stingleInclude ('Helpers/helpers.inc.php');
	}
	
	protected function customInitBeforeObjects(){
		Tbl::registerTableNames('UserManager');
		Tbl::registerTableNames('UserAuthorization');
	}
	
	protected function loadUserManager(){
		$this->register(new UserManager($this->config->AuxConfig));
	}
	protected function loadUserGroupsManager(){
		$this->register(new UserGroupsManager());
	}
	protected function loadUserPermissionsManager(){
		$this->register(new UserPermissionsManager());
	}
	
	protected function loadUserAuthorization(){
		$this->register(new UserAuthorization($this->config->AuxConfig));
	}
	
	public function hookUserAuthorization(){
		$user = Reg::get($this->config->Objects->UserAuthorization)->getUserFromRequest();
		if(is_a($user, "User")){
			Reg::register($this->config->ObjectsIgnored->User, $user);
		}
	}
}
