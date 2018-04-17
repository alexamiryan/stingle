<?php
class Config{
	
	/**
	 * Config constructor
	 * 
	 * @param array $CONFIG
	 */
	public function __construct(array $CONFIG = null){
		if($CONFIG !== null){
			$this->parseConfig($CONFIG);
		}
	}
	
	/**
	 * Magic function overrides default get.
	 * Checks if given config exists
	 * 
	 * @param string $name
	 * @throws RuntimeException
	 */
	public function __get($name){
		if(!isset($this->$name)){
			throw new RuntimeException("There is no such config element with name $name");
		}
		return $this->$name;
	}
	
	/**
	 * Magic function overrides default set
	 * 
	 * @param string $name
	 * @param string $value
	 */
	public function __set($name, $value){
		$this->$name = $value;
	}
	
	/**
	 * Check if given config exists
	 * 
	 * @param string $name
	 */
	public function __isset($name){
		if(isset($this->$name)){
			return true;
		}
		return false;
	}
	
	/**
	 * Convert config to array
	 * 
	 * @param boolean $recursive
	 * @return array
	 */
	public function toArray($recursive = false){
		$returnArray = array();
		foreach(get_object_vars($this) as $key=>$value){
			if($key !== '_configToParse'){
				if($recursive === true and is_a($value,"Config")){
					$returnArray[$key] = $value->toArray(true);
				}
				else{
					$returnArray[$key] = $value;
				}
			}
		}
		
		return $returnArray;
	}
	
	/**
	 * Parse config array into config object
	 * 
	 * @param array $configArray
	 */
	private function parseConfig(array $configArray){
		foreach($configArray as $key=>$value){
			if(is_numeric($key)){
				//$key = 'd' . $key;
			}
			if(is_array($value)){
				$this->{$key} = new Config($value);
			}
			else{
				$this->{$key} = $value;
			}
		}
	}
}
