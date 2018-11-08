<?php
class DependencyDb extends Dependency
{
	public function __construct(){
		$this->addPlugin("Logger");
		$this->addPlugin("Logger", "SessionLogger");
		$this->addPlugin("SiteNavigation", "SiteNavigation");
	}
}
