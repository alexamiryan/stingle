<?php
class LoaderGeoIP extends Loader{
	
	protected function includes(){
		stingleInclude ('Managers/GeoIP.class.php');
		stingleInclude ('Objects/GeoLocation.class.php');
	}
	
	protected function customInitBeforeObjects(){
		Tbl::registerTableNames('GeoIP');
	}
	
	protected function loadGeoIP(){
		$this->register(new GeoIP());
	}
}
