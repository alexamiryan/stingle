<?php
class LoaderDb extends Loader{
	protected function includes(){
		stingleInclude ('Managers/MySqlDbManager.class.php');
		stingleInclude ('Managers/Tbl.class.php');
		stingleInclude ('Managers/DbAccessor.class.php');
		stingleInclude ('Managers/MySqlDatabase.class.php');
		stingleInclude ('Exceptions/MySqlException.class.php');
		stingleInclude ('Managers/MySqlQuery.class.php');
		stingleInclude ('Helpers/helpers.inc.php');
	}
	
	protected function loadQuery(){
		Tbl::restoreCachedData();
		
		MySqlDbManager::init($this->config->AuxConfig->hosts);
		
		$this->register(MySqlDbManager::getQueryObject());
	}
	
	
	public function hookStoreTblCache(){
		Tbl::cacheData();
	}
	
}
