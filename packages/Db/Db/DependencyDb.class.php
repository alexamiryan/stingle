<?
class DependencyDb extends Dependency
{
	public function __construct(){
		$this->addPackage("Logger");
		$this->addPlugin("Logger", "SessionLogger");
	}
}
?>