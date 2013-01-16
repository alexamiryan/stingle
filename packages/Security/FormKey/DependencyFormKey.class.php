<?
class DependencyFormKey extends Dependency
{
	public function __construct(){
		$this->addPlugin("Crypto", "Crypto");
	}
}
?>