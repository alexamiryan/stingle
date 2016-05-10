<?php
class DependencyUserProfile extends Dependency
{
	public function __construct(){
		$this->addPlugin("Profile", "ProfileDeprecated");
	}
}
