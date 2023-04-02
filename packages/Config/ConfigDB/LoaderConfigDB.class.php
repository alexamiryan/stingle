<?php
class LoaderConfigDB extends Loader{
	
	protected function includes(){
		stingleInclude ('Managers/ConfigDBManager.class.php');
		stingleInclude ('Filters/ConfigDBFilter.class.php');
		stingleInclude ('Objects/ConfigDB.class.php');
	}
	
	protected function customInitBeforeObjects(){
		Tbl::registerTableNames("ConfigDBManager");
	}
    
    protected function customInitAfterObjects(){
        $hostLangId = null;
        $configDBFilter = new ConfigDBFilter();
        
        if(Reg::get('packageMgr')->isPluginLoaded("Language", "HostLanguage")){
            try {
                $hostName = ConfigManager::getConfig("Host", "Host")->Objects->Host;
                $languageName = ConfigManager::getConfig("Language", "Language")->ObjectsIgnored->Language;
                $hostLangId = HostLanguageManager::getHostLanguageId(Reg::get($hostName), Reg::get($languageName));
                $configDBFilter->setCommonOrHostLang($hostLangId);
            }
            catch (Exception $e) {}
        }
        
        ConfigDBManager::initDBConfig($configDBFilter);
    }
}
