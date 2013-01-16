<?
class DependencyChat extends Dependency
{
	public function __construct(){
		$this->addPlugin("Db");
		$this->addPlugin("Db", "QueryBuilder");
		$this->addPlugin("Comet", "Comet");
	}
}
?>