<?
class LoaderConfig extends Loader{
	
	protected function includes(){
		require_once ('ConfigDBManager.class.php');
	}
	
	protected function customInitBeforeObjects(){
		Tbl::registerTableNames("ConfigDBManager");
	}
	
	protected function customInitAfterObjects(){
		ConfigDBManager::initDBConfig();
	}
}
?>