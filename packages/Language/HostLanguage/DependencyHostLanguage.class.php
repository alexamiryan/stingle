<?php
class DependencyHostLanguage extends Dependency
{
	public function __construct(){
		$this->addPlugin('Host');
		$this->addPlugin('Language');
		$this->addPlugin('Db', 'Db');
		$this->addPlugin('Db', 'Memcache');
	}
}
