<?php

class LoaderSmartyMemcache extends Loader {

	protected function includes() {
		stingleInclude('Managers/SmartyMemcache.class.php');
	}
	
	protected function customInitBeforeObjects(){
		if(ConfigManager::getConfig("Db", "Memcache")->AuxConfig->enabled){
			Reg::get(ConfigManager::getConfig("Output", "Smarty")->Objects->Smarty)->caching_type = 'memcache';
		}
	}

	public function hookClearUserSmartyCache($params) {
		if (isset($params["userId"]) && !empty($params["userId"]) && is_numeric($params["userId"])) {
			Reg::get('memcache')->invalidateCacheByTag("smrt:u" . $params["userId"]);
		}
	}

}
