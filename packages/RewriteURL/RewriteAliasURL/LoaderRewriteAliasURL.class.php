<?php
class LoaderRewriteAliasURL extends Loader{
	protected function includes(){
		stingleInclude ('Managers/RewriteAliasURL.class.php');
		stingleInclude ('Managers/RewriteAliasMap.class.php');
	}
	
	protected function customInitBeforeObjects(){
		Tbl::registerTableNames('RewriteAliasMap');
	}
	
	protected function loadaliasMap(){
		$hostConfig = ConfigManager::getConfig("Host","Host");
		
		$this->aliasMap = new RewriteAliasMap(Reg::get($hostConfig->Objects->Host));
		$this->register($this->aliasMap);
	}
	
	protected function loadrewriteAliasURL(){
		$rewriteURLconfig = $this->packageManager->getPluginConfig("RewriteURL", "RewriteURL")->AuxConfig;
		$hostConfig = ConfigManager::getConfig("Host","Host");
		
		$this->rewriteAliasURL =  new RewriteAliasURL($rewriteURLconfig, $this->aliasMap->getAliasMap(Reg::get($hostConfig->Objects->Host)));
		$this->register($this->rewriteAliasURL);
	}
	
	public function hookParseAliases(){
		Reg::get($this->config->Objects->rewriteAliasURL)->parseAliases();
		Reg::get($this->config->Objects->rewriteAliasURL)->callParseCustomAliases();
	}
}
