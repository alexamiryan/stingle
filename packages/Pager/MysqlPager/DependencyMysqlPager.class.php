<?php
class DependencyMysqlPager extends Dependency
{
	public function __construct(){
		$this->addPlugin("Db");
	}
}
