<?php
class LoaderMigrations extends Loader{
	protected function includes(){
		stingleInclude ('Managers/MigrationsManager.class.php');
	}
	
    protected function customInitBeforeObjects(){
        Tbl::registerTableNames('MigrationsManager');
        MigrationsManager::runMigrationsIfAny('Db', 'Migrations');
    }
    
	public function hookRunMigrations($params){
	    MigrationsManager::runMigrationsIfAny($params['packageName'], $params['pluginName']);
	}
}
