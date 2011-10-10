<?
class DependencySmartyHostTpl extends Dependency
{
	public function __construct(){
		$this->addPlugin("Smarty", "Smarty");
		$this->addPlugin("Host", "Host");
	}
}
?>