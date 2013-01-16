<?
class DependencyExternalkAuth extends Dependency
{
	public function __construct(){
		$this->addPlugin("Db");
		$this->addPlugin("User");
	}
}
?>