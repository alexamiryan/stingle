<?php
class DependencyTexts extends Dependency
{
	public function __construct(){
		$this->addPlugin("Db");
		$this->addPlugin("Db", "QueryBuilder");
		$this->addPlugin("Host");
		$this->addPlugin("Language", "HostLanguage");
	}
}
