<?
class PackageManager {
	
	private $customConfigs;
	private $loadedPackages = array();
	private $objectAllowanceTable = array();
	private $hookAllowanceTable = array();
	private $pluginsToLoad = array();
	
	public function __construct(){
		$this->customConfigs = new Config();
	}
	
	public function addPackage($packageName, $plugins = array(), Config $customConfig = null, $autoload = true){
		$this->checkPackageExistance($packageName);
		
		if(in_array($packageName, array_keys($this->loadedPackages))){
			return;
		}
		if(!is_array($plugins)){
			$plugins = explode(";", $plugins);
		}
		
		if(empty($plugins)){
			array_push($plugins, $packageName);
		}
		
		if($customConfig !== null){
			$this->customConfigs->$packageName = $customConfig;
		}

		if(!isset($this->pluginsToLoad[$packageName]) or !is_array($this->pluginsToLoad[$packageName])){
			$this->pluginsToLoad[$packageName] = array();
		}
		foreach ($plugins as $pluginName){
			array_push($this->pluginsToLoad[$packageName], $pluginName);
		}
	}
	
	public function load(){
		$this->buildAllowanceTables($this->pluginsToLoad);
		
		foreach ($this->pluginsToLoad as $packageName => $plugins){
			foreach ($plugins as $pluginName){
				$this->usePlugin($packageName, $pluginName);
			}
			HookManager::callHook("AfterThisPluginTreeInit");
			HookManager::unRegisterHook("AfterThisPluginTreeInit");
		}

		$this->customConfigs = new Config();
	}
	
	public function usePlugin($packageName, $pluginName, Config $customConfig = null, $overrideObjects = false){
		$this->checkPluginExistance($packageName, $pluginName);
		
		if(!isset($this->loadedPackages[$packageName])){
			$this->loadedPackages[$packageName] = array();
		}
		
		if($this->isPluginLoaded($packageName, $pluginName)){
			return;
		}
		
		$backtrace = debug_backtrace();
		$callingClassName = $backtrace[1]['class'];
		$callingFunctionName = $backtrace[1]['function'];
		$myClassName = get_class($this);
		
		if($myClassName != $callingClassName or !in_array($callingFunctionName, array("load", "addPackage", "resolveDependencies"))){
			$this->buildAllowanceTables(array($packageName => array($pluginName)));
		}
		
		$loader = $this->getPluginLoader($packageName, $pluginName);
		
		$deps = $loader->getDependencies();
		$this->resolveDependencies($deps);
		
		if($customConfig !== null){
			if(!isset($this->customConfigs->$packageName)){
				$this->customConfigs->$packageName = new Config();
			}
			$this->customConfigs->$packageName->$pluginName = $customConfig;
		}

		$loader->load($overrideObjects);
		array_push($this->loadedPackages[$packageName], $pluginName);
	}
	
	public function isPluginLoaded($packageName, $pluginName){
		if(isset($this->loadedPackages[$packageName]) and in_array($pluginName, $this->loadedPackages[$packageName])){
			return true;
		}
		return false;
	}
	
	private function buildAllowanceTables($pluginsToLoad){
		if(empty($pluginsToLoad)){
			return;
		}
		
		$arrayForBuild = array();
		foreach($pluginsToLoad as $packageName=>$plugins){
			foreach($plugins as $pluginName){
				array_push($arrayForBuild, array($packageName, $pluginName));
			}
		}
		
		$dependencyArray = $this->getDependencyArray($arrayForBuild);
		
		$this->checkForDependencyLoop($dependencyArray);
		
		$dependencyArray = $this->simplifyDependencyTree($dependencyArray);
		
		$masterPlugins = array();
		foreach($dependencyArray["masters"] as $master){
			if(!in_array($master, $dependencyArray["slaves"])){
				if(!in_array($master, $masterPlugins)){
					array_push($masterPlugins, $master);
				}
			}
		}
		
		$cleanedDependencyArray = array("masters"=>array(), "slaves"=>array());
		foreach($dependencyArray["slaves"] as $key=>$slave){
			if($slave !== array(null, null)){
				array_push($cleanedDependencyArray["masters"], $dependencyArray["masters"][$key]);
				array_push($cleanedDependencyArray["slaves"], $dependencyArray["slaves"][$key]);
			}
		}
		
		$pluginsByPriorityTmp = $this->getPluginsByPriorityTable($cleanedDependencyArray, $masterPlugins);
		
		$pluginsByPriority = array();
		foreach($pluginsByPriorityTmp as $plugin => $info){
			if(!is_array($pluginsByPriority[$info[1]])){
				$pluginsByPriority[$info[1]] = array();
			}
			array_push($pluginsByPriority[$info[1]], array($info[0], $plugin));
		}
		
		ksort($pluginsByPriority, SORT_NUMERIC);

		foreach($pluginsByPriority as $priority=>$plugins){
			$thisPriorityObjects = array();
			foreach($plugins as $plugin){
				$pluginConfig = $this->getPluginConfig($plugin[0], $plugin[1]);
				if(!isset($pluginConfig->Objects)){
					continue;
				}
				
				$pluginObjects = get_object_vars($pluginConfig->Objects);
				foreach($pluginObjects as $Object){
					if(in_array($Object, array_keys($thisPriorityObjects))){
						$conflictingPlugin = $thisPriorityObjects[$Object];
						throw new RuntimeException("Object conflict between {$plugin[1]} of package {$plugin[0]} and {$conflictingPlugin[1]} of package {$conflictingPlugin[0]} with Object $Object");
					}
					
					$this->objectAllowanceTable[$Object] = $plugin;
					$thisPriorityObjects[$Object] = $plugin;
				}
			}
		}
		
		foreach($pluginsByPriority as $priority=>$plugins){
			$thisPriorityHooks = array();
			foreach($plugins as $plugin){
				$pluginConfig = $this->getPluginConfig($plugin[0], $plugin[1]);
				if(!isset($pluginConfig->hooks)){
					continue;
				}
				
				$pluginHooks = get_object_vars($pluginConfig->hooks);
				foreach($pluginHooks as $hook){
					if(in_array($hook, array_keys($thisPriorityHooks))){
						$conflictingPlugin = $thisPriorityHooks[$hook];
						throw new RuntimeException("Hook registration conflict between {$plugin[1]} of package {$plugin[0]} and {$conflictingPlugin[1]} of package {$conflictingPlugin[0]} with hook $hook");
					}
					
					$this->hookAllowanceTable[$hook] = $plugin;
					$thisPriorityHooks[$hook] = $plugin;
				}
			}
		}
	}
	
	private function checkForDependencyLoop($dependencyArray){
		foreach($dependencyArray["slaves"] as $slaveKey=>$slavePlugin){
			$exists = $this->checkIfPluginExistsInDependencyChain($dependencyArray, $slaveKey);
			if($exists === true){
				throw new RuntimeException("Dependency loop detected!");
			}
		}
		return false;
	}
	
	private function checkIfPluginExistsInDependencyChain($dependencyArray, $slaveKeyToFind, $currentSlaveKey = null, $passedKeys = array()){
		if($currentSlaveKey === null){
			$currentSlaveKey = $slaveKeyToFind;
		}
		array_push($passedKeys, $currentSlaveKey);
		if($dependencyArray["masters"][$currentSlaveKey] == $dependencyArray["slaves"][$slaveKeyToFind]){
			return true;
		}
		else{
			foreach(array_keys($dependencyArray["slaves"], $dependencyArray["masters"][$currentSlaveKey]) as $masterKey){
				if(!in_array($masterKey, $passedKeys)){
					$exists = $this->checkIfPluginExistsInDependencyChain(&$dependencyArray, &$slaveKeyToFind, $masterKey, &$passedKeys);
					if($exists === true){
						return true;
					}
				}
			}
		}
		return false;
	}
	
	private function simplifyDependencyTree($dependencyArray){
		$simplifiedDependencyArray = array("masters"=>array(), "slaves"=>array());
		for ($i=0;$i<count($dependencyArray["masters"]);$i++){
			$masterPlugin = $dependencyArray["masters"][$i];
			$slavePlugin = $dependencyArray["slaves"][$i];
			if(!$this->searchForDependencyTreeConnection($dependencyArray, $masterPlugin, $slavePlugin, array($i))){
				array_push($simplifiedDependencyArray["masters"], $masterPlugin);
				array_push($simplifiedDependencyArray["slaves"], $slavePlugin);
			}
		}
		
		return $simplifiedDependencyArray;
	}
	
	private function searchForDependencyTreeConnection($dependencyArray, $master, $slave, $notThisKey = array()){
		$slaveKeys = array_keys($dependencyArray["slaves"], $slave);
		
		foreach ($slaveKeys as $slaveKey){
			if(!in_array($slaveKey,$notThisKey)){
				if($dependencyArray["masters"][$slaveKey] == $master){
					return true;
				}
				else{
					array_push($notThisKey, $slaveKey);
					$found = $this->searchForDependencyTreeConnection($dependencyArray, $master, $dependencyArray["masters"][$slaveKey], &$notThisKey);
					if($found === true){
						return true;
					}
				}
			}
		}
		return false;
	}
	
	private function getPluginsByPriorityTable($dependencyArray, $masterPlugins, $pluginsByPriority = array(), $currentPriority = 0){
		foreach ($masterPlugins as $masterPlugin){
			if(!in_array($masterPlugin[1], array_keys($pluginsByPriority)) or $pluginsByPriority[$masterPlugin[1]][1] < $currentPriority){
				$pluginsByPriority[$masterPlugin[1]] = array($masterPlugin[0], $currentPriority);
			}
			
			// Look if there is more than one master in masters and loop on them
			$masterKeys = array_keys($dependencyArray["masters"], $masterPlugin);
			foreach ($masterKeys as $masterKey){
				// Get slave value
				$slavePlugin = $dependencyArray["slaves"][$masterKey];
				
				$this->getPluginsByPriorityTable($dependencyArray, array($slavePlugin), &$pluginsByPriority, $currentPriority + 1);
			}
		}
		return $pluginsByPriority;
	}
	
	private function getDependencyArray($plugins, $depList = array(), $depListSeparate = null){
		// Define arrays of masters and slaves if not defined
		if(empty($depList)){
			$depListSeparate = array("masters" => array(), "slaves" => array());
		}
		foreach ($plugins as $packagePluginPair){
			list($packageName, $pluginName) = $packagePluginPair;
			// Get plugin loader
			$pluginLoader = $this->getPluginLoader($packageName, $pluginName);
			// Get plugin dependencies
			$dependencies = $pluginLoader->getDependencies();
			
			// Loop on dependencies
			$dependentPlugins = $dependencies->getDependentPlugins();
			if(count($dependentPlugins)){
				foreach ($dependentPlugins as $depPackagePluginPair){
					list($depPackage, $depPlugin) = $depPackagePluginPair;
					// If not already parsed this dependency parse it
					if(!in_array(array($packageName => $depPackage ,$pluginName => $depPlugin), $depList)){
						array_push($depList, array($packageName => $depPackage ,$pluginName => $depPlugin));
						array_push($depListSeparate["masters"], array($depPackage, $depPlugin));
						array_push($depListSeparate["slaves"], array($packageName, $pluginName));
						
						// Try to go deeper in case this dependency has dependencies too
						$this->getDependencyArray(array(array($depPackage, $depPlugin)), &$depList, &$depListSeparate);
					}
				}
			}
			else{
				array_push($depListSeparate["masters"], array($packageName, $pluginName));
				array_push($depListSeparate["slaves"], array(null, null));
			}
		}
		return $depListSeparate;
	}
	
	public function isObjectInitIsAllowed($objectName, $packageName, $pluginName){
		if(empty($pluginName)){
			throw new InvalidArgumentException("\$pluginName is empty");
		}
		if(empty($objectName)){
			throw new InvalidArgumentException("\$objectName is empty.");
		}
		
		if	(	!isset($this->objectAllowanceTable[$objectName]) or 
					(	isset($this->objectAllowanceTable[$objectName]) and 
						$this->objectAllowanceTable[$objectName] == array($packageName, $pluginName)
					)
			){
				return true;
		}
		return false;
	}
	
	public function isHookRegistrationIsAllowed($hookName, $packageName, $pluginName){
		if(empty($pluginName)){
			throw new InvalidArgumentException("\$pluginName is empty");
		}
		if(empty($hookName)){
			throw new InvalidArgumentException("\$hookName is empty.");
		}
		
		if	(	!isset($this->hookAllowanceTable[$hookName]) or 
					(	isset($this->hookAllowanceTable[$hookName]) and 
						$this->hookAllowanceTable[$hookName] == array($packageName, $pluginName)
					)
			){
				return true;
		}
		return false;
	}
	
	private function resolveDependencies(Dependency $deps){
		foreach ($deps->getDependentPackages() as $depPackage){
			foreach ($deps->getDependentPlugins($depPackage) as $depPlugin){
				if(!isset($this->loadedPackages[$depPackage]) or !in_array($depPlugin, $this->loadedPackages[$depPackage])){
					$this->usePlugin($depPackage, $depPlugin);
				}
			}
		}
	}
	
	private function checkPackageExistance($packageName){
		if(empty($packageName)){
			throw new InvalidArgumentException("\$packageName is empty");
		}
		if(!is_dir(STINGLE_PATH . "packages/{$packageName}/") and 
			!is_dir(SITE_PACKAGES_PATH . "{$packageName}/")){
			throw new RuntimeException("There is no package $packageName.");
		}
	}
	
	private function checkPluginExistance($packageName, $pluginName){
		$this->checkPackageExistance($packageName);
		if(empty($pluginName)){
			throw new InvalidArgumentException("\$pluginName is empty.");
		}
		if(!is_dir(STINGLE_PATH . "packages/{$packageName}/{$pluginName}/") and 
			!is_dir(SITE_PACKAGES_PATH . "{$packageName}/{$pluginName}/")){
			throw new RuntimeException("There is no plugin $pluginName in $packageName package.");
		}
	}
	
	private function getPluginLoader($packageName, $pluginName){
		if(empty($packageName)){
			throw new InvalidArgumentException("\$packageName is empty");
		}
		if(empty($pluginName)){
			throw new InvalidArgumentException("\$pluginName is empty.");
		}
		
		$className = "Loader$pluginName";
		
		if(file_exists(SITE_PACKAGES_PATH . "{$packageName}/{$pluginName}/{$className}.class.php")){
			require_once (SITE_PACKAGES_PATH . "{$packageName}/{$pluginName}/{$className}.class.php");
		}
		elseif(file_exists(STINGLE_PATH . "packages/{$packageName}/{$pluginName}/{$className}.class.php")){
			require_once (STINGLE_PATH . "packages/{$packageName}/{$pluginName}/{$className}.class.php");
		}
		else{
			throw new RuntimeException("Loader file of plugin $pluginName in package $packageName does not exists");
		}

		try{
			$loader = new $className( $pluginName, $packageName, $this );
		}
		catch(RuntimeException $e){
			throw new RuntimeException("Loader class of plugin $pluginName in package $packageName does not exists");
		}
		
		return $loader;
	}
	
	public function getPluginConfig($packageName, $pluginName){
		$pluginConfig = ConfigManager::getConfig($packageName, $pluginName);
		if(isset($this->customConfigs->$packageName) and isset($this->customConfigs->$packageName->$pluginName)){
			$pluginConfig = ConfigManager::mergeConfigs($this->customConfigs->$packageName->$pluginName, $pluginConfig);
		}
		
		return $pluginConfig;
	}
}
?>