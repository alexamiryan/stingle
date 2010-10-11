<?
class LoaderHost extends Loader{
	protected function includes(){
		require_once ('Host.class.php');
		require_once ('HostManager.class.php');
	}
	
	protected function loadPageUrl(){
		$protocol 	= HostManager::protocol();
		$this->pageUrl = $protocol . HostManager::pageURL();
		Reg::register($this->config->Objects->PageUrl, $this->pageUrl);
	}
	
	protected function loadHostName(){
		$this->hostName = HostManager::getHostName();
		Reg::register($this->config->Objects->HostName, $this->hostName);
	}
	
	protected function loadHost(){
		Reg::register($this->config->Objects->Host, HostManager::getHostByName($this->hostName));
	}
	
	protected function loadSiteUrl(){
		Reg::register($this->config->Objects->SiteUrl, HostManager::getSiteUrl());
	}
}
?>