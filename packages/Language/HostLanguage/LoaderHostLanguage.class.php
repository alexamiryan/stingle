<?
class LoaderHostLanguage extends Loader{
	protected function includes(){
		require_once ('HostLanguageManager.class.php');
	}
	
	protected function loadHostLanguageManager(){
		$hostConfig = ConfigManager::getConfig("Host","Host");
		Reg::register($this->config->Objects->HostLanguageManager, new HostLanguageManager(Reg::get($hostConfig->Objects->Host)));
	}
}
?>