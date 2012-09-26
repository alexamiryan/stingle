<?
class LoaderConfigDB extends Loader{
	
	protected function includes(){
		require_once ('Managers/ConfigDBManager.class.php');
		require_once ('Filters/ConfigDBFilter.class.php');
		require_once ('Objects/ConfigDB.class.php');
	}
	
	protected function customInitBeforeObjects(){
		Tbl::registerTableNames("ConfigDBManager");
	}
	
	protected function customInitAfterObjects(){
		$hostLangId = null;
		$configDBFilter = new ConfigDBFilter();
		if(Reg::get('packageMgr')->isPluginLoaded("Language", "HostLanguage")){
			$hostName = ConfigManager::getConfig("Host","Host")->Objects->Host;
			$languageName = ConfigManager::getConfig("Language","Language")->ObjectsIgnored->Language;
			$hostLangId = HostLanguageManager::getHostLanguageId(Reg::get($hostName), Reg::get($languageName));
			$configDBFilter->setHostLang($hostLangId);
		}
		ConfigDBManager::initDBConfig($configDBFilter);
	}
}
?>