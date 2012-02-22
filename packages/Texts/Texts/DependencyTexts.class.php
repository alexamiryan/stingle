<?
class DependencyTexts extends Dependency
{
	public function __construct(){
		$this->addPackage("Db");
		$this->addPackage("Filter");
		$this->addPlugin("Host", "Host");
		$this->addPlugin("Language", "HostLanguage");
		$this->addPackage("Filter");
	}
}
?>