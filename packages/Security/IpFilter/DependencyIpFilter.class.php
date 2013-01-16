<?
class DependencyIpFilter extends Dependency
{
	public function __construct(){
		$this->addPlugin("Db");
		$this->addPlugin("Db", "QueryBuilder");
		$this->addPlugin("GeoIP");
	}
}
?>