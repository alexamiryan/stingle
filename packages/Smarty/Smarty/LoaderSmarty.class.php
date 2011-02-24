<?
class LoaderSmarty extends Loader{
	protected function includes(){
		require_once ('Core/Smarty.class.php');
		require_once ('SmartyWrapper.class.php');
	}
	
	protected function loadSmarty(){
		$this->smarty = new SmartyWrapper();
		Reg::register($this->config->Objects->Smarty, $this->smarty);
	}
	
	public function hookSmartyInit(){
		$siteNavigationConfig = ConfigManager::getConfig("SiteNavigation");
		$this->smarty->initialize(Reg::get($siteNavigationConfig->Nav)->{$siteNavigationConfig->firstLevelName}, Reg::get($siteNavigationConfig->Nav)->{$siteNavigationConfig->secondLevelName}, $this->config);
	}
	
	public function hookSmartyDisplay(){
		$this->smarty->display();
	}
	
	public function hookRegisterSmartyPlugins(Array $params){
		extract($params);
		
		if(	is_dir(STINGLE_PATH . "packages/{$packageName}/") and is_dir(STINGLE_PATH . "packages/{$packageName}/{$pluginName}")){
			if(is_dir(STINGLE_PATH . "packages/{$packageName}/{$pluginName}/SmartyPlugins")){
				$this->smarty->addPluginsDir(STINGLE_PATH . "packages/{$packageName}/{$pluginName}/SmartyPlugins");
			}
		}
		
		if(is_dir(SITE_PACKAGES_PATH . "{$packageName}/") and is_dir(SITE_PACKAGES_PATH . "{$packageName}/{$pluginName}")){
			if(is_dir(SITE_PACKAGES_PATH . "{$packageName}/{$pluginName}/SmartyPlugins")){
				$this->smarty->addPluginsDir(SITE_PACKAGES_PATH . "{$packageName}/{$pluginName}/SmartyPlugins");
			}
		}
	}
}
?>