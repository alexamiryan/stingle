<?
class DependencyIpFilter extends Dependency
{
	public function __construct(){
		$this->addPackage("Db");
		$this->addPlugin("Db", "QueryBuilder");
		$this->addPackage("GeoIP");
	}
}
?>