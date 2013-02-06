<?php
class LoaderTexts extends Loader{
	protected function includes(){
		require_once ('Objects/TextsGroup.class.php');
		require_once ('Objects/Text.class.php');
		require_once ('Objects/TextValue.class.php');
		require_once ('Objects/TextAlias.class.php');
		require_once ('Managers/TextsGroupManager.class.php');
		require_once ('Managers/TextsManager.class.php');
		require_once ('Managers/TextsValuesManager.class.php');
		require_once ('Filters/TextsValuesFilter.class.php');
		require_once ('Managers/TextsAliasManager.class.php');
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
