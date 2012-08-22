<?
class LoaderUsers extends Loader{
	protected function includes(){
		require_once ('UserManager.class.php');
		require_once ('UserAuthorization.class.php');
		require_once ('User.class.php');
		require_once ('UsersFilter.class.php');
		require_once ('RequestLimiterTooManyAuthTriesException.class.php');
	}
	
	protected function customInitBeforeObjects(){
		Tbl::registerTableNames('UserManager');
		Tbl::registerTableNames('UserAuthorization');
	}
	
	protected function loadUserManager(){
		$this->UserManager = new UserManager();
		$this->register($this->UserManager);
	}
	
	protected function loadUserAuthorization(){
		$this->userAuthorization = new UserAuthorization(	$this->UserManager, 
															$this->config->AuxConfig);
		$this->register($this->userAuthorization);
	}
	
	public function hookUserAuthorization(){
		Reg::register($this->config->ObjectsIgnored->User, Reg::get($this->config->Objects->UserAuthorization)->getUserFromRequest());
	}
}
?>