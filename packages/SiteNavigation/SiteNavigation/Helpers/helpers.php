<?php
/**
 * Call other controller with given URI.
 * Can be used to call different controller using some logic.
 * WARNING! All GET parameters are being lost upon redirection. 
 * 
 * @param string $uri
 */
function redirectController($uri){
	$_SERVER['REQUEST_URI'] = SITE_PATH . $uri;
	$_GET = array();
	
	if(Reg::get('packageMgr')->isPluginLoaded("RewriteURL", "RewriteURL")){
		Reg::get(ConfigManager::getConfig("RewriteURL", "RewriteURL")->Objects->rewriteURL)->parseURL();
	}
	$newNav = Reg::get(ConfigManager::getConfig("SiteNavigation", "SiteNavigation")->Objects->RequestParser)->parse();
	
	Reg::register(ConfigManager::getConfig("SiteNavigation", "SiteNavigation")->ObjectsIgnored->Nav, $newNav, true);
	
	Reg::get(ConfigManager::getConfig("SiteNavigation", "SiteNavigation")->Objects->Controller)->exec();
}