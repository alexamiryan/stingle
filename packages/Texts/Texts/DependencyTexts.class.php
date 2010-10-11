<?
class DependencyTexts extends Dependency
{
	public function __construct(){
		$this->addPackage("Db");
		$this->addPackage("Host");
		$this->addPackage("Language", "HostLanguage");
	}
}
?>