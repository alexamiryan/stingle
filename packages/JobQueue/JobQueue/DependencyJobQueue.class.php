<?php
class DependencyJobQueue extends Dependency
{
	public function __construct(){
		$this->addPlugin("Db");
		$this->addPlugin("Db", "QueryBuilder");
	}
}
