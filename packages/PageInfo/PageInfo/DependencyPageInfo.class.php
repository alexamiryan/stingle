<?php
class DependencyPageInfo extends Dependency
{
	public function __construct(){
		$this->addPlugin("Db");
		$this->addPlugin("Host");
		$this->addPlugin("Output", "Smarty");
		$this->addPlugin("SiteNavigation");
		$this->addPlugin("Language", "HostLanguage");
	}
}
