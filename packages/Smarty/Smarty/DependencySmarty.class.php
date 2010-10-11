<?php
class DependencySmarty extends Dependency
{
	public function __construct(){
		$this->addPackage("SiteNavigation");
	}
}
?>