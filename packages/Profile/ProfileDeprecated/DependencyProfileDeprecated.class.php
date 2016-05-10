<?php
class DependencyProfileDeprecated extends Dependency
{
	public function __construct(){
		$this->addPlugin("Db");
	}
}
