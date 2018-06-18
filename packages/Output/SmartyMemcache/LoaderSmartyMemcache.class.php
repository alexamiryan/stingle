<?php

class LoaderSmartyMemcache extends Loader {

	protected function includes() {
		stingleInclude('Managers/SmartyMemcache.class.php');
	}
	
	protected function customInitBeforeObjects(){
		if($this->config->AuxConfig->memcacheSupport){
			if(ConfigManager::getConfig("Db", "Memcache")->AuxConfig->enabled){
				Reg::get(ConfigManager::getConfig("Output", "Smarty")->Objects->Smarty)->caching_type = 'memcache';
			}
		}
	}

	public function hookClearUserSmartyCache($params) {
		if (isset($params["userId"]) && !empty($params["userId"]) && is_numeric($params["userId"])) {
			$memcacheConfig = ConfigManager::getConfig('Db', 'Memcache')->AuxConfig;
			if (!empty($memcacheConfig) and $memcacheConfig->enabled == true) {
				$memcached = new MemcacheWrapper($memcacheConfig->host, $memcacheConfig->port);
				$memcached->invalidateCacheByTag("smrt:u" . $params["userId"]);
			}
		}
	}

}
