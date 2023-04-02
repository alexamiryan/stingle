<?php
class LoaderHostLanguage extends Loader{
	protected function includes(){
		stingleInclude ('Managers/HostLanguageManager.class.php');
	}
	
	protected function customInitBeforeObjects(){
		Tbl::registerTableNames('HostLanguageManager');
	}
	
	protected function loadHostLanguageManager(){
		$hostConfig = ConfigManager::getConfig("Host","Host");
		$this->register(new HostLanguageManager(Reg::get($hostConfig->Objects->Host)));
        Reg::register(
            ConfigManager::getConfig('Language', 'Language')->ObjectsIgnored->Language,
            Reg::get(ConfigManager::getConfig('Language', 'Language')->Objects->LanguageManager)->getLanguage()
        );
	}
}