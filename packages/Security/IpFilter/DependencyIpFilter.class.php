<?php
class DependencyIpFilter extends Dependency
{
	public function __construct(){
		$this->addPackage("Db");
		$this->addPackage("GeoIP");
	}
}
?>