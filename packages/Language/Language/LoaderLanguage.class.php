<?php
class LoaderLanguage extends Loader{
	protected function includes(){
		require_once ('Objects/Language.class.php');
		require_once ('Objects/Constant.class.php');
		require_once ('Managers/LanguageManager.class.php');
	}
	
	protected function customInitBeforeObjects(){
		Tbl::registerTableNames('Language');
		Tbl::registerTableNames('Constant');
		Tbl::registerTableNames('LanguageManager');
	}
	
	protected function loadLanguageManager(){
		$this->register(new LanguageManager());
	}
	
	public function hookGetLanguageObj(){
		Reg::register($this->config->ObjectsIgnored->Language, Reg::get($this->config->Objects->LanguageManager)->getLanguage());
	}
	
	public function hookDefineAllConstants(){
		Reg::get($this->config->Objects->LanguageManager)->defineAllConsts();
	}
}
