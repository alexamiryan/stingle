<?php
class DependencyQueryBuilder extends Dependency
{
	public function __construct(){
		$this->addPlugin("Db");
	}
}
