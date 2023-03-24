<?php
class Dependency
{
	private $deps = array();
	
	/**
	 * Add dependent plugin
	 * 
	 * @param string $packageName
	 * @param string $pluginName
	 * @throws InvalidArgumentException
	 * @throws RuntimeException
	 */
	public function addPlugin($packageName, $pluginName = null){
		if(empty($packageName)){
			throw new InvalidArgumentException("\$packageName is empty");
		}
		
		if($pluginName === null){
			$pluginName = $packageName;
		}
		
		if(empty($pluginName)){
			throw new InvalidArgumentException("\$pluginName is empty.");
		}
		
        $found = false;
        if(is_dir(STINGLE_PATH . "packages/{$packageName}/") && is_dir(STINGLE_PATH . "packages/{$packageName}/{$pluginName}/")){
            $found = true;
        }
		if(is_dir(SITE_PACKAGES_PATH . "{$packageName}/") && is_dir(SITE_PACKAGES_PATH . "{$packageName}/{$pluginName}/")){
            $found = true;
		}
        foreach(AddonManager::get() as $path){
            if(is_dir($path . "packages/{$packageName}/") && is_dir($path . "packages/{$packageName}/{$pluginName}/")){
                $found = true;
            }
        }
        
		if(!$found){
			throw new RuntimeException("There is no plugin $pluginName in $packageName package.");
		}
		if(!in_array($packageName, array_keys($this->deps))){
			$this->deps[$packageName] = array();
		}
		if(!in_array($pluginName, $this->deps[$packageName])){
			array_push($this->deps[$packageName], $pluginName);
		}
	}
	
	/**
	 * Get dependent packages
	 * 
	 * @return array
	 */
	public function getDependentPackages(){
		return array_keys($this->deps);
	}
	
	/**
	 * Get dependent plugins
	 * 
	 * @param string $packageName
	 * @return array
	 */
	public function getDependentPlugins($packageName = null){
		if($packageName === null){
			$returnDepsArray = array();
			foreach($this->deps as $packageName => $plugins){
				foreach($plugins as $pluginName){
					array_push($returnDepsArray, array($packageName, $pluginName));
				}
			}
			return $returnDepsArray;
		}
		else{
			if(isset($this->deps[$packageName])){
				return $this->deps[$packageName];
			}
			else{
				return array();
			}
		}
	}
}
