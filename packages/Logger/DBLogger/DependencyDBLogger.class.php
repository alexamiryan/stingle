<?
class DependencyDBLogger extends Dependency
{
	public function __construct(){
		$this->addPackage("Db");
	}
}
?>