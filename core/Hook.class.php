<?php
class Hook{
	private $name;
	private $method;
	private $object = null;
	
	/**
	 * Hook constructor
	 * 
	 * @param string $hookName
	 * @param string $method
	 * @param object $object
	 * @throws InvalidArgumentException
	 */
	public function __construct($hookName, $method, $object = null){
		if(empty($hookName)){
			throw new InvalidArgumentException("Hook name is empty!");
		}
		if(empty($method)){
			throw new InvalidArgumentException("Hook method is empty!");
		}
		
		$this->name = $hookName;
		$this->method = $method;
		$this->object = $object;
	}
	
	/**
	 * Get hook name
	 * 
	 * @return string 
	 */
	public function getName(){
		return $this->name;
	}
	
	/**
	 * Get hook method
	 * 
	 * @return string
	 */
	public function getMethod(){
		return $this->method;
	}
	
	/**
	 * Get hook object
	 * 
	 * @return object
	 */
	public function getObject(){
		return $this->object;
	}
}
