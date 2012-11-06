<?
class LoaderUsers extends Loader{
	protected function includes(){
		require_once ('UserManagement.class.php');
		require_once ('UserAuthorization.class.php');
		require_once ('User.class.php');
		require_once ('UsersFilter.class.php');
		require_once ('RequestLimiterTooManyAuthTriesException.class.php');
		require_once ('Exceptions/UserException.class.php');
		require_once ('Exceptions/UserAuthFailedException.class.php');
		require_once ('Exceptions/UserDisabledException.class.php');
		require_once ('Exceptions/UserNotFoundException.class.php');
	}
	
	protected function customInitBeforeObjects(){
		Tbl::registerTableNames('UserManagement');
		Tbl::registerTableNames('UserAuthorization');
	}
	
	protected function loadUserManagement(){
		$this->userManagement = new UserManagement();
		$this->register($this->userManagement);
	}
	
	protected function loadUserAuthorization(){
		$this->userAuthorization = new UserAuthorization(	$this->userManagement, 
															$this->config->AuxConfig);
		$this->register($this->userAuthorization);
	}
	
	public function hookUserAuthorization(){
		Reg::register($this->config->ObjectsIgnored->User, Reg::get($this->config->Objects->UserAuthorization)->getUserFromRequest());
	}
}
?>