<?
class DependencyOneTimeCodes extends Dependency
{
	public function __construct(){
		$this->addPlugin("Security");
		$this->addPlugin("Db");
		$this->addPlugin("Crypto", "AES256");
	}
}
?>