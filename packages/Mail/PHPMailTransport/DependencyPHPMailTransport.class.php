<?php
class DependencyPHPMailTransport extends Dependency
{
	public function __construct(){
		$this->addPlugin("Mail", "Mail");
	}
}
