<?php
class DependencyUsers extends Dependency
{
	public function __construct(){
		$this->addPlugin("Db");
		$this->addPlugin("Db", "QueryBuilder");
		$this->addPlugin("Security");
		$this->addPlugin("Crypto");
	}
}
