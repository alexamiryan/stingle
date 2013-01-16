<?
class LoaderHostControllerTemplate extends Loader{
	protected function includes(){
		require_once ('HostControllerTemplate.class.php');
	}
	
	protected function customInitBeforeObjects(){
		Tbl::registerTableNames('HostControllerTemplate');
	}
	
	public function hookSetTemplateByHost(){
		$controller = Reg::get(ConfigManager::getConfig("SiteNavigation", "SiteNavigation")->Objects->Controller);
		$smarty = Reg::get(ConfigManager::getConfig("Output", "Smarty")->Objects->Smarty);
		$host = Reg::get(ConfigManager::getConfig("Host","Host")->Objects->Host);
		
		$result = HostControllerTemplate::getControllerTemplateByHost($host);
		
		if($result !== false){
			if(isset($result['controller']) and !empty($result['controller'])){
				$controller->setControllersPath($result['controller']);
			}
			
			if(isset($result['template']) and !empty($result['template'])){
				$smarty->setTemplate($result['template']);
			}
		}
	}
}
?>