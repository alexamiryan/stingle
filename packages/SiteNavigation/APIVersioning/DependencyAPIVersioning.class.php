<?php
class DependencyAPIVersioning extends Dependency
{
	public function __construct(){
		$this->addPlugin("SiteNavigation", "SiteNavigation");
	}
}
