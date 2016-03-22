<?php
class DependencyFacebookSDK extends Dependency
{
	public function __construct(){
		$this->addPlugin("Db");
	}
}
