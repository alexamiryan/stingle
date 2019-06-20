<?php
class DependencyUserSessions extends Dependency
{
	public function __construct(){
		$this->addPlugin("Users", "Users");
		$this->addPlugin("Crypto", "Crypto");
	}
}
