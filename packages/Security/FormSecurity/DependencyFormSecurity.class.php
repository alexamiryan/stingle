<?
class DependencyFormSecurity extends Dependency
{
	public function __construct(){
		$this->addPackage("Db");
		$this->addPackage("Security");
	}
}
?>