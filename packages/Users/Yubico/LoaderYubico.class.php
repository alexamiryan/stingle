<?
class LoaderYubico extends Loader{
	protected function includes(){
		require_once ('Yubico.class.php');
		require_once ('YubikeyUserAuthorization.class.php');
	}
	
	protected function customInitBeforeObjects(){
		Tbl::registerTableNames('YubikeyUserAuthorization');
	}
	
	protected function loadYubikeyUserAuthorization(){
		$usersConfig = ConfigManager::getConfig("Users","Users");
		
		$yubikeyUserAuthorization = new YubikeyUserAuthorization(	Reg::get($usersConfig->Objects->UserManagement), 
																	$_SESSION[$usersConfig->sessionVarName], 
																	$this->config);
		
		Reg::register($this->config->Objects->YubikeyUserAuthorization, $yubikeyUserAuthorization);
	}
}
?>