<?php
class DependencyEmailLog extends Dependency
{
	public function __construct(){
		$this->addPlugin("Mail", "Mail");
		$this->addPlugin("Db", "Db");
	}
}
