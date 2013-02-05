<?php
class DependencyDBLogger extends Dependency
{
	public function __construct(){
		$this->addPlugin("Db");
	}
}
