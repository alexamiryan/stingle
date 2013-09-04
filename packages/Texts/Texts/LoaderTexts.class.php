<?php
class LoaderTexts extends Loader{
	protected function includes(){
		stingleInclude ('Objects/TextsGroup.class.php');
		stingleInclude ('Objects/Text.class.php');
		stingleInclude ('Objects/TextValue.class.php');
		stingleInclude ('Objects/TextAlias.class.php');
		stingleInclude ('Managers/TextsGroupManager.class.php');
		stingleInclude ('Managers/TextsManager.class.php');
		stingleInclude ('Managers/TextsValuesManager.class.php');
		stingleInclude ('Filters/TextsValuesFilter.class.php');
		stingleInclude ('Managers/TextsAliasManager.class.php');
	}
	
	protected function customInitBeforeObjects(){
		Tbl::registerTableNames('TextsGroupManager');
		Tbl::registerTableNames('TextsManager');
		Tbl::registerTableNames('TextsValuesManager');
		Tbl::registerTableNames('TextsAliasManager');
	}
	
	protected function loadTextsGroupManager(){
		$this->register(new TextsGroupManager());
	}
	
	protected function loadTextsManager(){
		$this->register(new TextsManager());
	}
	
	protected function loadTextsValuesManager(){
		$host = Reg::get(ConfigManager::getConfig("Host")->Objects->Host);
		$language = Reg::get(ConfigManager::getConfig("Language")->ObjectsIgnored->Language);
		
		$this->register(new TextsValuesManager($host, $language));
	}
	
	protected function loadTextsAliasManager(){
		$this->register(new TextsAliasManager());
	}
}
