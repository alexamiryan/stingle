<?php
abstract class JobQueueChunk{
	
	public static $name = null;
	
	public function __construct(){
		if(static::$name === null or empty(static::$name)){
			throw new RuntimeException("Chunk name can't be empty!");
		}
	}
	
	protected function setName($name){
		static::$name = $name;
	}
	
	public function getName(){
		return static::$name;
	}
	
	abstract public function run(array $params);
}

