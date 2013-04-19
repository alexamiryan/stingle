<?php
class LoaderHost extends Loader{
	protected function includes(){
		require_once ('Objects/Host.class.php');
		require_once ('Managers/HostManager.class.php');
	}
	
	protected function customInitBeforeObjects(){
		Tbl::registerTableNames('Host');
	}
	
	protected function loadPageUrl(){
		$protocol = HostManager::protocol();
		$this->pageUrl = $protocol . HostManager::pageURL();
		$this->register($this->pageUrl);
	}
	
	protected function loadHostName(){
		$this->hostName = HostManager::getHostName();
		$this->register($this->hostName);
	}
	
	protected function loadHost(){
		$this->register(HostManager::getHostByName($this->hostName, $this->config->AuxConfig->autoCreateHost));
	}
	
	protected function loadSiteUrl(){
		$this->register(HostManager::getSiteUrl());
	}
}
