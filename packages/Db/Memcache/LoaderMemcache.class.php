<?
class LoaderMemcache extends Loader{
	protected function includes(){
		require_once ('MemcacheWrapper.class.php');
		require_once ('MySqlQueryMemcache.class.php');
	}
	
	protected function loadQuery(){
		MySqlDbManager::setQueryClassName("MySqlQueryMemcache");
		$query = new MySqlQueryMemcache(Reg::get(ConfigManager::getConfig("Db","Db")->Objects->Db));
		Reg::register($this->config->Objects->Query, $query);
	}
	
	public function hookAddMemcacheTimeConfig(Array $params){
		extract($params);
		if(isset($pluginConfig->Memcache)){
			foreach($pluginConfig->Memcache->toArray() as $className => $cacheTime){
				ConfigManager::addConfig(array('Db','Memcache','AuxConfig','Time'), $className, $cacheTime);
			}
		}
	}
}
?>