<?php
class DependencyEmailStats extends Dependency
{
	public function __construct(){
		$this->addPlugin("Db", "Db");
		$this->addPlugin("Mail", "Mail");
	}
}
