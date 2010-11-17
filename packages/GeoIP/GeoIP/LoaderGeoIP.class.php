<?
class LoaderGeoIP extends Loader{
	
	protected function includes(){
		require_once ('GeoIP.class.php');
	}
	
	protected function customInitBeforeObjects(){
		Tbl::registerTableNames('GeoIP');
	}
	
	protected function loadGeoIP(){
		Reg::register($this->config->Objects->GeoIP, new GeoIP());
	}
}
?>