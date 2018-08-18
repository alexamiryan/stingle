<?php
class DependencyUniversalOutput extends Dependency
{
	public function __construct(){
		$this->addPlugin("Output", "Smarty");
		$this->addPlugin("Info", "Info");
	}
}
