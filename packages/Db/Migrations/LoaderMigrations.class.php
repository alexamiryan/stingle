<?php
class LoaderMigrations extends Loader{
	protected function includes(){
		stingleInclude ('Managers/MigrationsManager.class.php');
	}
	
	protected function loadQuery(){
		Tbl::restoreCachedData();
		
		MySqlDbManager::init($this->config->AuxConfig->instances);
		
		$this->register(MySqlDbManager::getQueryObject());
	}
    
    protected function customInitBeforeObjects(){
        Tbl::registerTableNames('MigrationsManager');
    }
    
	public function hookRunMigrations($params){
	    MigrationsManager::runMigrationsIfAny($params['packageName'], $params['pluginName']);
	}
	
}
