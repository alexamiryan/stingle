<?php
class DependencyMinify extends Dependency
{
	public function __construct(){
		$this->addPlugin("Output", "Smarty");
	}
}
