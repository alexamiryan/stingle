<?php
class LoaderGeoIP extends Loader{
	
	protected function includes(){
		require_once ('Managers/GeoIP.class.php');
		require_once ('Objects/GeoLocation.class.php');
	}
	
	protected function customInitBeforeObjects(){
		Tbl::registerTableNames('GeoIP');
	}
	
	protected function loadGeoIP(){
		$this->register(new GeoIP());
	}
}
