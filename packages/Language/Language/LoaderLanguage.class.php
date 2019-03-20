<?php
class LoaderLanguage extends Loader{
	protected function includes(){
		stingleInclude ('Objects/Language.class.php');
		stingleInclude ('Objects/Constant.class.php');
		stingleInclude ('Managers/LanguageManager.class.php');
		stingleInclude ('Helpers/helpers.inc.php');
	}
	
	protected function customInitBeforeObjects(){
		Tbl::registerTableNames('Language');
		Tbl::registerTableNames('Constant');
		Tbl::registerTableNames('LanguageManager');
	}
	
	protected function loadLanguageManager(){
		$this->register(new LanguageManager($this->config));
	}
	
	public function hookGetLanguageObj(){
		Reg::register($this->config->ObjectsIgnored->Language, Reg::get($this->config->Objects->LanguageManager)->getLanguage());
	}
	
	public function hookDefineAllConstants(){
		if($this->config->AuxConfig->defineAllConstsOn === true){
			Reg::get($this->config->Objects->LanguageManager)->defineAllConsts();
		}
	}
}
