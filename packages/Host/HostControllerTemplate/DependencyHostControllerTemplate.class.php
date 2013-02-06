<?php
class DependencyHostControllerTemplate extends Dependency
{
	public function __construct(){
		$this->addPlugin("Output", "Smarty");
		$this->addPlugin("Host", "Host");
		$this->addPlugin("SiteNavigation", "SiteNavigation");
		$this->addPlugin("Db", "QueryBuilder");
	}
}
