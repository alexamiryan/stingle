<?php
class DependencyEmailBounce extends Dependency
{
	public function __construct(){
		$this->addPlugin("Mail", "Mail");
		$this->addPlugin("Db", "Db");
		$this->addPlugin("Users", "Users");
	}
}
