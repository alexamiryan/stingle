<?php
class DependencySES extends Dependency
{
	public function __construct(){
		$this->addPlugin("Mail", "Mail");
		$this->addPlugin("Mail", "PHPMailTransport");
	}
}
