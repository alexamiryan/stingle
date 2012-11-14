<?
class DependencyHostControllerTemplate extends Dependency
{
	public function __construct(){
		$this->addPlugin("Smarty", "Smarty");
		$this->addPlugin("Host", "Host");
		$this->addPlugin("SiteNavigation", "SiteNavigation");
		$this->addPlugin("Db", "QueryBuilder");
	}
}
?>