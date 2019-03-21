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
	
	protected function loadDb(){
		Tbl::restoreCachedData();
		MySqlDbManager::createInstance(	$this->config->AuxConfig->host, 
										$this->config->AuxConfig->user, 
										$this->config->AuxConfig->password, 
										$this->config->AuxConfig->name,
										$this->config->AuxConfig->isPersistent);
		$this->db = MySqlDbManager::getDbObject();
		$this->db->setConnectionEncoding($this->config->AuxConfig->encoding);
		$this->register($this->db);
	}
	
	protected function loadQuery(){
		$query = MySqlDbManager::getQueryObject();
		$this->register($query);
	}
	
	
	public function hookStoreTblCache(){
		Tbl::cacheData();
	}
	
}
