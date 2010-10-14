<?
class DependencyLanguage extends Dependency
{
	public function __construct(){
		$this->addPackage("Db");
		$this->addPlugin("Pager", "MysqlPager");
	}
}
?>