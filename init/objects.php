<?
HookManager::callHook("BeforePackagesLoad");

foreach(get_object_vars($config->Packages) as $package){
	$package = get_object_vars($package);
	if(!isset($package[1])){
		$package[1] = array();
	}
	$packageMgr->addPackage($package[0], $package[1]);
}
$packageMgr->load();

HookManager::callHook("AfterPackagesLoad");
?>