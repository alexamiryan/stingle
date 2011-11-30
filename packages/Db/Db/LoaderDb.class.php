<?
class LoaderDb extends Loader{
	protected function includes(){
		require_once ('MySqlDbManager.class.php');
		require_once ('Tbl.class.php');
		require_once ('DbAccessor.class.php');
		require_once ('MySqlDatabase.class.php');
		require_once ('MySqlException.class.php');
		require_once ('MySqlQuery.class.php');
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
?>