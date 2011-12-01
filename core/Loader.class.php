<?
abstract class Loader {
	protected $packageManager;
	protected $packageName;
	protected $pluginName;
	protected $config;
	
	/**
	 * Initialize loader object
	 * 
	 * @param string $pluginName
	 * @param string $packageName
	 * @param PackageManager $packageManager
	 * @throws InvalidArgumentException
	 * @throws RuntimeException
	 */
	public function __construct($pluginName, $packageName, PackageManager $packageManager){
		if(empty($packageName)){
			throw new InvalidArgumentException("\$packageName is empty.");
		}
		if(!is_dir(STINGLE_PATH . "packages/{$packageName}/") and 
			!is_dir(SITE_PACKAGES_PATH . "{$packageName}/")){
			throw new RuntimeException("Package folder does not exist.");
		}
		
		if(empty($pluginName)){
			throw new InvalidArgumentException("\$pluginName is empty.");
		}
		if(!is_dir(STINGLE_PATH . "packages/{$packageName}/{$pluginName}") and 
			!is_dir(SITE_PACKAGES_PATH . "{$packageName}/{$pluginName}")){
			throw new RuntimeException("Plugin folder does not exist.");
		}
		
		$this->packageManager = $packageManager;
		$this->packageName = $packageName;
		$this->pluginName = $pluginName;
		$this->config = $this->getConfig();
	}
	
	/**
	 * Load plugin
	 * 
	 * @param boolean $overrideObjects
	 */
	final public function load($overrideObjects = false){
		$hookArgs = array(	'packageName' => $this->packageName, 
							'pluginName' => $this->pluginName, 
							'pluginConfig' => $this->config
						);
		
		HookManager::callHook("BeforePluginInit", $hookArgs);
		$this->includes();
		$this->customInitBeforeObjects();
		$this->loadObjects($overrideObjects);
		$this->customInitAfterObjects();
		$this->registerHooks();
		HookManager::callHook("AfterPluginInit", $hookArgs);
	}
	
	/**
	 * You can extend this function and 
	 * make necessary includes here for 
	 * the plugin
	 */
	protected function includes(){
		
	}
	
	/**
	 * You can extend this function and 
	 * make some custom initialization
	 * before objects are loaded
	 */
	protected function customInitBeforeObjects(){
		
	}
	
	/**
	 * You can extend this function and 
	 * make some custom post objects load
	 * procedures 
	 */
	protected function customInitAfterObjects(){
		
	}
	
	/**
	 * Get dependencies of plugin
	 * 
	 * @throws RuntimeException
	 * @return Dependency
	 */
	public function getDependencies(){
		$className = "Dependency{$this->pluginName}";
		try{
			if(file_exists(SITE_PACKAGES_PATH . "{$this->packageName}/{$this->pluginName}/{$className}.class.php")){
				require_once (SITE_PACKAGES_PATH . "{$this->packageName}/{$this->pluginName}/{$className}.class.php");
			}
			elseif(file_exists(STINGLE_PATH . "packages/{$this->packageName}/{$this->pluginName}/{$className}.class.php")){
				require_once (STINGLE_PATH . "packages/{$this->packageName}/{$this->pluginName}/{$className}.class.php");
			}
			else{
				throw new RuntimeException();
			}
			
			$deps = new $className();
		}
		catch(RuntimeException $e){
			$deps = new Dependency();
		}
		
		if($this->packageName != $this->pluginName){
			try {
				$this->packageManager->checkPluginExistance($this->packageName, $this->packageName);
				$deps->addPlugin($this->packageName, $this->packageName);
			}
			catch (Exception $e){ }
		}
		
		return $deps;
	}
	
	/**
	 * Get plugin config
	 * 
	 * @return Config
	 */
	private function getConfig(){
		return $this->packageManager->getPluginConfig($this->packageName, $this->pluginName);
	}
	
	/**
	 * Load plugin objects
	 * 
	 * @param boolean $overrideObjects
	 * @throws RuntimeException
	 */
	private function loadObjects($overrideObjects = false){
		if(isset($this->config->Objects)){
			foreach (array_keys(get_object_vars($this->config->Objects)) as $objectName){
				if($this->packageManager->isObjectInitIsAllowed($this->config->Objects->$objectName, $this->packageName, $this->pluginName)){
					if($overrideObjects === false and Reg::isRegistered($this->config->Objects->$objectName)){
						throw new RuntimeException("Object {$this->config->Objects->$objectName} is already defined. If you want to redefine it anyway pass true as 3rd argument to usePlugin function of PackageManager.");
					}
					$loadFuncName = "load$objectName";
					if(method_exists($this, $loadFuncName)){
						$this->$loadFuncName();
						if(!Reg::isRegistered($this->config->Objects->$objectName)){
							throw new RuntimeException("Loader function for object $objectName in plugin {$this->pluginName} of package {$this->packageName} didn't registered it's object in registry!");
						}
					}
					else{
						throw new RuntimeException("Object loader of plugin {$this->pluginName} in package {$this->packageName} for object $objectName doesn't exists!");
					}
				}
			}
		}
	}
	
	/**
	 * Register plugin hooks
	 * 
	 * @throws RuntimeException
	 */
	private function registerHooks(){
		if(isset($this->config->Hooks)){
			foreach (get_object_vars($this->config->Hooks) as $hookName=>$hookMethod){
				if($this->packageManager->isHookRegistrationIsAllowed($hookMethod, $this->packageName, $this->pluginName)){
					$hookMethodName = "hook$hookMethod";
					
					$hook = new Hook($hookName, $hookMethodName, $this);
					
					if(HookManager::isHookRegistered($hook)){
						throw new RuntimeException("Hook $hookMethod is already registered.");
					}
					if(method_exists($this, $hookMethodName)){
						HookManager::registerHook($hook);
					}
					else{
						throw new RuntimeException("Hook method of plugin {$this->pluginName} in package {$this->packageName} for hook $hookName -> $hookMethodName doesn't exists!");
					}
				}
			}
		}
	}
	
	/**
	 * Register initialized object in Reg (Registry)
	 * with name specified in config's 
	 * "Objects" section
	 * 
	 * @param mixed $object
	 * @throws RuntimeException
	 */
	protected function register($object){
		$backtrace = debug_backtrace();
		$loadFunction = $backtrace[1]['function'];
		
		if(substr($loadFunction, 0, 4) != 'load'){
			throw new RuntimeException("Called register function not from one of the loader's load object functions!");
		}
		$objectNameToRegister = substr($loadFunction, 4);
		
		
		$registerName = $this->config->Objects->$objectNameToRegister;
		
		Reg::register($registerName, $object);
	}
}
?>