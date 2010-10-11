<?
class LoaderRewriteAliasURL extends Loader{
	protected function includes(){
		require_once ('RewriteAliasURL.class.php');
		require_once ('RewriteAliasMap.class.php');
	}
	
	protected function loadaliasMap(){
		$hostConfig = ConfigManager::getConfig("Host","Host");
		
		$this->aliasMap = new RewriteAliasMap(Reg::get($hostConfig->Objects->Host));
		Reg::register($this->config->Objects->aliasMap, $this->aliasMap);
	}
	
	protected function loadrewriteAliasURL(){
		$rewriteURLconfig = $this->packageManager->getPluginConfig("RewriteURL", "RewriteURL");
		$hostConfig = ConfigManager::getConfig("Host","Host");
		
		$this->rewriteAliasURL =  new RewriteAliasURL($rewriteURLconfig, $this->aliasMap->getAliasMap(Reg::get($hostConfig->Objects->Host)));
		Reg::register($this->config->Objects->rewriteAliasURL, $this->rewriteAliasURL);
	}
	
}
?>