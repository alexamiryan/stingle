<?
class DependencyTexts extends Dependency
{
	public function __construct(){
		$this->addPackage("Db");
		$this->addPlugin("Host", "Host");
		$this->addPlugin("Language", "HostLanguage");
	}
}
?>