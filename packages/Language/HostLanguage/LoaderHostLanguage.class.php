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
	}
}