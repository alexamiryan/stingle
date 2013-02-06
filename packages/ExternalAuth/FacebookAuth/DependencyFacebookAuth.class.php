<?php
class DependencyFacebookAuth extends Dependency
{
	public function __construct(){
		$this->addPlugin("Db");
	}
}
