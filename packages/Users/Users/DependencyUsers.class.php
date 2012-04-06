<?
class DependencyUsers extends Dependency
{
	public function __construct(){
		$this->addPackage("Db");
		$this->addPlugin("Db", "QueryBuilder");
		$this->addPackage("Security");
	}
}
?>