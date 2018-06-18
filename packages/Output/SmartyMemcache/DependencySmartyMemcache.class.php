<?php
class DependencySmartyMemcache extends Dependency
{
	public function __construct(){
		$this->addPlugin("Output","Smarty");
		$this->addPlugin("Db","Memcache");
	}
}
