<?
class LoaderExternalAuth extends Loader{
	
	protected function includes(){
		require_once ('ExternalAuth.class.php');
		require_once ('ExternalUser.class.php');
	}
}
?>