<?php
class DependencyGeoIPGps extends Dependency
{
	public function __construct(){
		$this->addPlugin("Db");
		$this->addPlugin("GeoIP");
	}
}
