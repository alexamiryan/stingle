<?
class LoaderUsers extends Loader{
	protected function includes(){
		require_once ('UserManagement.class.php');
		require_once ('UserAuthorization.class.php');
		require_once ('User.class.php');
		require_once ('UsersFilter.class.php');
	}
	
	protected function loadUserManagement(){
		$this->userManagement = new UserManagement();
		Reg::register($this->config->Objects->UserManagement, $this->userManagement);
	}
	
	protected function loadUserAuthorization(){
		$this->userAuthorization = new UserAuthorization(	$this->userManagement, 
															$_SESSION[$this->config->sessionVarName]);
		Reg::register($this->config->Objects->UserAuthorization, $this->userAuthorization);
	}
	
	public function hookUserAuthorization(){
		Reg::register($this->config->ObjectsIgnored->User, Reg::get($this->config->Objects->UserAuthorization)->authorize());
	}
}
?>