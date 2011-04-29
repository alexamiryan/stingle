<?
class DependencyExternalkAuth extends Dependency
{
	public function __construct(){
		$this->addPackage("Db");
		$this->addPackage("User");
	}
}
?>