<?php
class LoaderDb extends Loader{
	protected function includes(){
		require_once ('Managers/MySqlDbManager.class.php');
		require_once ('Managers/Tbl.class.php');
		require_once ('Managers/DbAccessor.class.php');
		require_once ('Managers/MySqlDatabase.class.php');
		require_once ('Exceptions/MySqlException.class.php');
		require_once ('Managers/MySqlQuery.class.php');
	}
	
	protected function loadDb(){
		MySqlDbManager::createInstance(	$this->config->AuxConfig->host, 
										$this->config->AuxConfig->user, 
										$this->config->AuxConfig->password, 
										$this->config->AuxConfig->name);
		$this->db = MySqlDbManager::getDbObject();
		$this->db->setConnectionEncoding($this->config->AuxConfig->encoding);
		$this->register($this->db);
	}
	
	protected function loadQuery(){
		$query = MySqlDbManager::getQueryObject();
		$this->register($query);
	}
}
