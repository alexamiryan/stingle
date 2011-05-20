<?
class LoaderUserProfile extends Loader{
	protected function includes(){
		require_once ('UserProfile.class.php');
	}
	
	protected function customInitBeforeObjects(){
		Tbl::registerTableNames('UserProfile');
	}
}
?>