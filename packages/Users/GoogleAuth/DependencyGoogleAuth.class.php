<?php
class DependencyGoogleAuth extends Dependency
{
	public function __construct(){
		$this->addPlugin("Users", "Users");
	}
}
