<?
class LoaderTexts extends Loader{
	protected function includes(){
		require_once ('Text.class.php');
		require_once ('TextsManager.class.php');
	}
	
	protected function loadTextsManager(){
		$hostConfig = ConfigManager::getConfig("Host");
		$languageConfig = ConfigManager::getConfig("Language");
		
		$this->textsManager = new TextsManager(Reg::get($hostConfig->Objects->Host), Reg::get($languageConfig->Objects->LanguageManager)->getLanguage());
		Reg::register($this->config->Objects->TextsManager, $this->textsManager);
	}
}
?>