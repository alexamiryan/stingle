<?
class DependencyRequestLimiter extends Dependency
{
	public function __construct(){
		$this->addPlugin("Db");
		$this->addPlugin("Security");
	}
}
?>