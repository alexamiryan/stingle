<?php
class DependencyLinkShortener extends Dependency
{
	public function __construct(){
		$this->addPlugin("Db", "Db");
		$this->addPlugin("RewriteURL", "RewriteURL");
	}
}
