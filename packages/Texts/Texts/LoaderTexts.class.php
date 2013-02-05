<?php
class LoaderTexts extends Loader{
	protected function includes(){
		require_once ('TextsGroup.class.php');
		require_once ('Text.class.php');
		require_once ('TextValue.class.php');
		require_once ('TextAlias.class.php');
		require_once ('TextsGroupManager.class.php');
		require_once ('TextsManager.class.php');
		require_once ('TextsValuesManager.class.php');
		require_once ('TextsValuesFilter.class.php');
		require_once ('TextsAliasManager.class.php');
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
