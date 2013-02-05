<?php
class DependencyGps extends Dependency
{
	public function __construct(){
		$this->addPlugin("Db");
	}
}
