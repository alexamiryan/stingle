<?php
class DependencyConfigDB extends Dependency
{
	public function __construct(){
		$this->addPlugin("Db");
		$this->addPlugin("Db", "QueryBuilder");
		$this->addPlugin("Db", "Memcache");
	}
}
