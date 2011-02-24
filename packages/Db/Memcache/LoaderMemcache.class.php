<?
class LoaderMemcache extends Loader{
	protected function includes(){
		require_once ('MemcacheWrapper.class.php');
		require_once ('MySqlQueryMemcache.class.php');
	}
	
	protected function loadquery(){
		MySqlDbManager::setQueryClassName("MySqlQueryMemcache");
		$query = new MySqlQueryMemcache(Reg::get(ConfigManager::getConfig("Db","Db")->Objects->db));
		Reg::register($this->config->Objects->query, $query);
	}
	
	public function hookAddMemcacheTimeConfig(Array $params){
		extract($params);
		if(isset($pluginConfig->memcache)){
			foreach($pluginConfig->memcache->toArray() as $className => $cacheTime){
				ConfigManager::addConfig(array('Db','Memcache','time'), $className, $cacheTime);
			}
		}
	}
}
?>