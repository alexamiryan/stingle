<?php
class DependencyMail extends Dependency
{
	public function __construct(){
		$this->addPlugin("Users", "Users");
		$this->addPlugin("Security", "OneTimeCodes");
		$this->addPlugin("Output", "Smarty");
		$this->addPlugin("Crypto", "Crypto");
		$this->addPlugin("JobQueue", "JobQueue");
		$this->addPlugin("Language", "HostLanguage");
	}
}
