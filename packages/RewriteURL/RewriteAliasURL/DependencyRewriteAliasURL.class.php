<?
class DependencyRewriteAliasURL extends Dependency
{
	public function __construct(){
		$this->addPackage("Db");
		$this->addPackage("Host");
	}
}
?>