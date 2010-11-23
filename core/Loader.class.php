<?
abstract class Loader {
	protected $packageManager;
	protected $packageName;
	protected $pluginName;
	protected $config;
	
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
	
	final public function load($overrideObjects = false){
		HookManager::callHook("BeforePluginInit", array('pluginConfig' => $this->config));
		$this->includes();
		$this->customInitBeforeObjects();
		$this->loadObjects($overrideObjects);
		$this->customInitAfterObjects();
		$this->registerHooks();
		HookManager::callHook("AfterPluginInit", array('pluginConfig' => $this->config));
	}
	
	protected function includes(){
		
	}
	
	protected function customInitBeforeObjects(){
		
	}
	
	protected function customInitAfterObjects(){
		
	}
	
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
	
	private function getConfig(){
		return $this->packageManager->getPluginConfig($this->packageName, $this->pluginName);
	}
	
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
					}
					else{
						throw new RuntimeException("Object loader of plugin {$this->pluginName} in package {$this->packageName} for object $objectName doesn't exists!");
					}
				}
			}
		}
	}
	
	private function registerHooks(){
		if(isset($this->config->hooks)){
			foreach (get_object_vars($this->config->hooks) as $hookName=>$hookMethod){
				if($this->packageManager->isHookRegistrationIsAllowed($hookMethod, $this->packageName, $this->pluginName)){
					$hookMethodName = "hook$hookMethod";
					if(HookManager::isHookRegistered($hookName, $hookMethodName, $this)){
						throw new RuntimeException("Hook $hookMethod is already registered.");
					}
					if(method_exists($this, $hookMethodName)){
						HookManager::registerHook($hookName, $hookMethodName, $this);
					}
					else{
						throw new RuntimeException("Hook method of plugin {$this->pluginName} in package {$this->packageName} for hook $hookName -> $hookMethodName doesn't exists!");
					}
				}
			}
		}
	}
}
?>