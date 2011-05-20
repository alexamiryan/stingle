<?
class DependencyRequestLimiter extends Dependency
{
	public function __construct(){
		$this->addPackage("Db");
		$this->addPackage("Security");
	}
}
?>