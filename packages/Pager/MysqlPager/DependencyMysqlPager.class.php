<?
class DependencyMysqlPager extends Dependency
{
	public function __construct(){
		$this->addPackage("Db");
	}
}
?>