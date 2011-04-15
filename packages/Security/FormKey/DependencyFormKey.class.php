<?php
class DependencyFormKey extends Dependency
{
	public function __construct(){
		$this->addPackage("Security");
	}
}
?>