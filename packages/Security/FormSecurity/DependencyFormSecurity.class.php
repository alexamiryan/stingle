<?php
class DependencySecurity extends Dependency
{
	public function __construct(){
		$this->addPackage("Db");
	}
}
?>