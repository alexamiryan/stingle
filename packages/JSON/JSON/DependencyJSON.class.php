<?php
class DependencyJSON extends Dependency
{
	public function __construct(){
		$this->addPlugin("Output", "Smarty");
	}
}
