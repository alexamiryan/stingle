<?php
class DependencyUsersMemcache extends Dependency
{
	public function __construct(){
		$this->addPlugin("Users", "Users");
		$this->addPlugin("Db", "Memcache");
	}
}
