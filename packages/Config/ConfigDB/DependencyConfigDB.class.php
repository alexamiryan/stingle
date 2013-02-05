<?php
class DependencyConfigDB extends Dependency
{
	public function __construct(){
		$this->addPlugin("Db");
	}
}
