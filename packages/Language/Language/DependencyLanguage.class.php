<?php
class DependencyLanguage extends Dependency
{
	public function __construct(){
		$this->addPlugin("Db");
		$this->addPlugin("Db", "Memcache");
		$this->addPlugin("Pager", "MysqlPager");
	}
}
