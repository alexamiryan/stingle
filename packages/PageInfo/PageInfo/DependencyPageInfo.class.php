<?
class DependencyPageInfo extends Dependency
{
	public function __construct(){
		$this->addPackage("Db");
		$this->addPackage("Host");
		$this->addPackage("Smarty");
		$this->addPackage("SiteNavigation");
		$this->addPackage("Language", "HostLanguage");
	}
}
?>