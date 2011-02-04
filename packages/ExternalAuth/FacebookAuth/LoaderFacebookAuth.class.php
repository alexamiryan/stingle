<?
class LoaderFacebookAuth extends Loader{
	
	protected function includes(){
		require_once ('FacebookAuth.class.php');
	}
	
	protected function customInitBeforeObjects(){
		Tbl::registerTableNames('FacebookAuth');
	}
	
	protected function loadFacebookAuth(){
		
		$fbAuth = new FacebookAuth($this->config->auxConfig);
		Reg::register($this->config->Objects->FacebookAuth, $fbAuth);
	}
}
?>