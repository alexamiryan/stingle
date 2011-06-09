<?
class DependencyOneTimeCodes extends Dependency
{
	public function __construct(){
		$this->addPackage("Security");
		$this->addPackage("Db");
		$this->addPlugin("Crypto", "AES256");
	}
}
?>