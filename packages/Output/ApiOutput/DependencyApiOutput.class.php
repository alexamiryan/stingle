<?php
class DependencyApiOutput extends Dependency
{
	public function __construct(){
		$this->addPlugin("Info", "Info");
	}
}
