<?php
class DependencyWebPushNotifications extends Dependency
{
	public function __construct(){
		$this->addPlugin("Users", "Users");
		$this->addPlugin("JobQueue", "JobQueue");
	}
}
