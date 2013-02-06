<?php
class LoaderUsers extends Loader{
	protected function includes(){
		require_once ('Managers/UserAuthorization.class.php');
		require_once ('Managers/UserGroupsManager.class.php');
		require_once ('Managers/UserManager.class.php');
		require_once ('Managers/UserPermissionsManager.class.php');
		require_once ('Objects/User.class.php');
		require_once ('Objects/UserGroup.class.php');
		require_once ('Objects/Permission.class.php');
		require_once ('Objects/UserPermissions.class.php');
		require_once ('Objects/UserProperties.class.php');
		require_once ('Filters/UserGroupsFilter.class.php');
		require_once ('Filters/UserPermissionsFilter.class.php');
		require_once ('Filters/UsersFilter.class.php');
		require_once ('Exceptions/UserException.class.php');
		require_once ('Exceptions/UserAuthFailedException.class.php');
		require_once ('Exceptions/UserDisabledException.class.php');
		require_once ('Exceptions/UserNotFoundException.class.php');
		require_once ('Helpers/helpers.inc.php');
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
		Reg::register($this->config->ObjectsIgnored->User, Reg::get($this->config->Objects->UserAuthorization)->getUserFromRequest());
	}
}
