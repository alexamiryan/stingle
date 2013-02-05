<?php
class DependencyHostLanguage extends Dependency
{
	public function __construct(){
		$this->addPlugin("Host");
	}
}
