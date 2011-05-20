<?
class LoaderSecurity extends Loader{
	protected function includes(){
		require_once ('SecurityException.class.php');
		require_once ('InputSecurity.class.php');
	}
	
	public function hookSecureInputData(){
		InputSecurity::secureInputData();
	}
}
?>