<?php
class LoaderGeoIP extends Loader{
	
	protected function includes(){
		require_once ('GeoIP.class.php');
		require_once ('GeoLocation.class.php');
	}
	
	protected function customInitBeforeObjects(){
		Tbl::registerTableNames('GeoIP');
	}
	
	protected function loadGeoIP(){
		$this->register(new GeoIP());
	}
}
