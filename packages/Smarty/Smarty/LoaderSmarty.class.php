<?
class LoaderSmarty extends Loader{
	protected function includes(){
		require_once ('Core/Smarty.class.php');
		require_once ('SmartyWrapper.class.php');
		require_once ('Exceptions/TemplateFileNotFoundException.class.php');
	}
	
	protected function loadSmarty(){
		$this->smarty = new SmartyWrapper();
		$this->register($this->smarty);
	}
	
	public function hookSmartyInit(){
		$siteNavigationConfig = ConfigManager::getConfig("SiteNavigation");
		$this->smarty->initialize($this->config->AuxConfig);
	}
	
	public function hookSmartyDisplay(){
		$this->smarty->output();
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