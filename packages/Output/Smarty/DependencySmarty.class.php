<?php
class DependencySmarty extends Dependency
{
	public function __construct(){
		$this->addPlugin("SiteNavigation");
		$this->addPlugin("Db","Memcache");
	}
}
