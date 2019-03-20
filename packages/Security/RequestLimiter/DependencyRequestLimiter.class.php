<?php
class DependencyRequestLimiter extends Dependency
{
	public function __construct(){
		$this->addPlugin('Db');
		$this->addPlugin('Db', 'Memcache');
		$this->addPlugin('Security');
	}
}
