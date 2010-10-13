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
	
	protected function loaddb(){
		MySqlDbManager::createInstance($this->config->host, $this->config->user, $this->config->password, $this->config->name);
		$this->db = MySqlDbManager::getDbObject();
		$this->db->setConnectionEncoding($this->config->encoding);
		Reg::register($this->config->Objects->db, $this->db);
	}
	
	protected function loadquery(){
		$query = MySqlDbManager::getQueryObject();
		Reg::register($this->config->Objects->query, $query);
	}
}
?>