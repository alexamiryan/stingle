<?php
class DependencyMemcache extends Dependency
{
	public function __construct(){
		$this->addPlugin("Db", "Db");
	}
}
