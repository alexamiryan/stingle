<?
class DependencyAES256 extends Dependency
{
	public function __construct(){
		$this->addPlugin("Crypto", "Crypto");
	}
}
?>