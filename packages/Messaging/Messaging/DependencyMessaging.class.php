<?
class DependencyMessaging extends Dependency
{
	public function __construct(){
		$this->addPackage("Db");
		$this->addPackage("Filter");
	}
}
?>