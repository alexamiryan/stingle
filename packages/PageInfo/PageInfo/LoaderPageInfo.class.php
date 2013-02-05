<?php
class LoaderPageInfo extends Loader{
	protected function includes(){
		require_once ('PageInfo.class.php');
		require_once ('PageInfoManager.class.php');
	}
	
	protected function customInitBeforeObjects(){
		Tbl::registerTableNames('PageInfo');
	}
	
	protected function loadPageInfo(){
		$hostConfig = ConfigManager::getConfig("Host");
		$languageConfig = ConfigManager::getConfig("Language");
		
		$this->pageInfo = new PageInfo(Reg::get($hostConfig->Objects->Host), Reg::get($languageConfig->Objects->LanguageManager)->getLanguage());
		$this->register($this->pageInfo);
	}
	
	public function hookSetPageInfo(){
		$smartyConfig = ConfigManager::getConfig("Output", "Smarty");
		$siteNavConfig = ConfigManager::getConfig("SiteNavigation");
		
		$module = Reg::get($siteNavConfig->ObjectsIgnored->Nav)->module;
		$page = Reg::get($siteNavConfig->ObjectsIgnored->Nav)->page;
		
		$pageInfo = $this->pageInfo->getInfo($module, $page);
		
		Reg::get($smartyConfig->Objects->Smarty)->setPageTitle($pageInfo['title']);
		Reg::get($smartyConfig->Objects->Smarty)->setPageKeywords($pageInfo['meta_keywords']);
		Reg::get($smartyConfig->Objects->Smarty)->setPageDescription($pageInfo['meta_description']);
	}

}
