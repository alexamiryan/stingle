<?
class LoaderSmarty extends Loader{
	protected function includes(){
		require_once ('Smarty/Smarty.class.php');
		require_once ('SmartyWrapper.class.php');
	}
	
	protected function loadSmarty(){
		$this->smarty = new SmartyWrapper();
		Reg::register($this->config->Objects->Smarty, $this->smarty);
	}
	
	public function hookSmartyInit(){
		if(isset($this->config->pluginDirs)){
			foreach(get_object_vars($this->config->pluginDirs) as $pluginDir){
				$this->smarty->addPluginsDir($pluginDir);
			}
		}
		$siteNavigationConfig = ConfigManager::getConfig("SiteNavigation");
		$this->smarty->initialize(Reg::get($siteNavigationConfig->Nav)->{$siteNavigationConfig->firstLevelName}, Reg::get($siteNavigationConfig->Nav)->{$siteNavigationConfig->secondLevelName}, $this->config);
	}
	
	public function hookSmartyDisplay(){
		$this->smarty->display();
	}
}
?>