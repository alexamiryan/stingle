<?
class DependencyUsers extends Dependency
{
	public function __construct(){
		$this->addPackage("Db");
		$this->addPackage("Filter");
		$this->addPlugin("Crypto", "AES256");
	}
}
?>