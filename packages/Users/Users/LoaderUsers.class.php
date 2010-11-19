<?
class LoaderUsers extends Loader{
	protected function includes(){
		require_once ('UserManagement.class.php');
		require_once ('UserAuthorization.class.php');
		require_once ('User.class.php');
		require_once ('UsersFilter.class.php');
	}
	
	protected function customInitBeforeObjects(){
		Tbl::registerTableNames('UserManagement');
	}
	
	protected function loadUserManagement(){
		$this->userManagement = new UserManagement();
		Reg::register($this->config->Objects->UserManagement, $this->userManagement);
	}
	
	protected function loadUserAuthorization(){
		$this->userAuthorization = new UserAuthorization(	$this->userManagement, 
															$this->config->UserAuthConfig);
		Reg::register($this->config->Objects->UserAuthorization, $this->userAuthorization);
	}
	
	public function hookUserAuthorization(){
		Reg::register($this->config->ObjectsIgnored->User, Reg::get($this->config->Objects->UserAuthorization)->getUserFromRequest());
	}
}
?>