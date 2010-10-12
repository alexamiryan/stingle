<?
HookManager::callHook("BeforePackagesLoad");

foreach(get_object_vars($config->Packages) as $package){
	$package = get_object_vars($package);
	if(!isset($package[1])){
		$package[1] = array();
	}
	Reg::get('packageMgr')->addPackage($package[0], $package[1]);
}
Reg::get('packageMgr')->load();

HookManager::callHook("AfterPackagesLoad");
?>