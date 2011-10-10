<?
class LoaderSmartyHostTpl extends Loader{
	protected function includes(){
		require_once ('SmartyHostTpl.class.php');
	}
	
	protected function customInitBeforeObjects(){
		Tbl::registerTableNames('SmartyHostTpl');
	}
	
	public function hookSetTemplateByHost(){
		$smarty = Reg::get(ConfigManager::getConfig("Smarty", "Smarty")->Objects->Smarty);
		$host = Reg::get(ConfigManager::getConfig("Host","Host")->Objects->Host);
		
		$templateByHost = SmartyHostTpl::getTemplateByHost($host);
		
		if($templateByHost !== false){
			$smarty->setTemplate($templateByHost);
		}
	}
}
?>